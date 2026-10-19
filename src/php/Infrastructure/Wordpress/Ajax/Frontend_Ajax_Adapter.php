<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Ajax;

use Exception;
use Racketmanager\Domain\DTO\Competition\Cup_Entry_Request_DTO;
use Racketmanager\Domain\DTO\Competition\League_Entry_Request_DTO;
use Racketmanager\Domain\DTO\Player\Search_Players_Command;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Duplicate_BTM_Exception;
use Racketmanager\Exceptions\Duplicate_Email_Exception;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Updated_Exception;
use Racketmanager\Exceptions\Registration_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\Infrastructure\Security\Target_Season_Validator;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Services\Validator\Validator_Entry_Form;
use stdClass;
use function is_user_logged_in;
use function Racketmanager\event_team_match_dropdown;
use function Racketmanager\get_league;
use function Racketmanager\player_search;
use function Racketmanager\show_alert;
use function Racketmanager\show_team_edit_modal;
use function Racketmanager\team_order_players;

/**
 * Adapter for Frontend AJAX requests.
 */
class Frontend_Ajax_Adapter {
    private const NONCE_FIELD = 'racketmanager_nonce';
    private const DEFAULT_NONCE_FIELD = 'security';
    private const DEFAULT_NONCE_ACTION = 'ajax-nonce';

    public function __construct(
        private Security_Service_Interface $security_service,
        private Json_Response_Factory_Interface $response_factory,
        private Competition_Service $competition_service,
        private Competition_Entry_Service $competition_entry_service,
        private Team_Service $team_service,
        private Player_Service $player_service,
        private Registration_Service $registration_service,
        private Club_Service $club_service,
        private Tournament_Service $tournament_service,
        private Fixture_Repository_Interface $fixture_repository,
        private Rubber_Repository_Interface $rubber_repository
    ) {}

    public function logged_out(): Response {
        $msg = __( 'Must be logged in to access this feature', 'racketmanager' );

        return $this->response_factory->create_error_response(
            [
                $msg,
                [],
                [],
            ],
            401
        );
    }

    public function logged_out_modal(): Response {
        $msg    = __( 'Must be logged in to access this feature', 'racketmanager' );
        $output = show_alert( $msg, 'danger', 'modal' );

        return $this->response_factory->create_error_response(
            [
                $msg,
                $output,
            ],
            401
        );
    }

    public function update_team( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request, self::NONCE_FIELD, 'team-update' );
        if ( $security_error ) {
            return $security_error;
        }

        $validator = new Validator();
        $event_id  = empty( $request['event_id'] ) ? null : (int) $request['event_id'];
        $team_id   = empty( $request['team_id'] ) ? null : (int) $request['team_id'];
        $validator = $validator->event( $event_id );
        $validator = $validator->team( $team_id );

        if ( empty( $validator->error ) ) {
            $field_ref    = $event_id . '-' . $team_id;
            $team_details = $this->get_team_input( (string) $field_ref, $request );
            $validator    = $validator->captain( ! empty( $team_details->captain_id ) ? (string) $team_details->captain_id : null, $team_details->contactno, $team_details->contactemail, (string) $field_ref );
            $validator    = $validator->telephone( $team_details->contactno, (string) $field_ref );
            $validator    = $validator->email( $team_details->contactemail, ! empty( $team_details->captain_id ) ? (int) $team_details->captain_id : null, true, (string) $field_ref );
            $validator    = $validator->match_day( $team_details->match_day, (string) $field_ref );
            $validator    = $validator->match_time( $team_details->match_time, (string) $field_ref );
        }
        if ( empty( $validator->error ) ) {
            try {
                $msg = $this->team_service->update_team_event(
                    (int) $team_id,
                    $event_id,
                    (int) $team_details->captain_id,
                    (string) $team_details->contactno,
                    (string) $team_details->contactemail,
                    $team_details->match_day,
                    $team_details->match_time
                );

                return $this->response_factory->create_success_response( $msg );
            } catch ( Event_Not_Found_Exception|Team_Not_Found_Exception ) {
                $validator->error = true;
            }
        }

        $return = $validator->get_details();
        if ( empty( $return->msg ) ) {
            $return->msg = __( 'Unable to update team', 'racketmanager' );
        }

        return $this->response_factory->create_error_response( $return, (int) $return->status );
    }

    private function get_team_input( string $field_ref, array $request ): object {
        $team_details               = new stdClass();
        $suffix                     = '-' . $field_ref;
        $team_details->captain_id   = empty( $request[ 'captainId' . $suffix ] ) ? null : (int) $request[ 'captainId' . $suffix ];
        $team_details->contactno    = empty( $request[ 'contactno' . $suffix ] ) ? null : sanitize_text_field( wp_unslash( (string) $request[ 'contactno' . $suffix ] ) );
        $team_details->contactemail = empty( $request[ 'contactemail' . $suffix ] ) ? null : sanitize_text_field( wp_unslash( (string) $request[ 'contactemail' . $suffix ] ) );
        $team_details->match_day    = isset( $request[ 'matchday' . $suffix ] ) ? (int) $request[ 'matchday' . $suffix ] : null;
        $team_details->match_time   = empty( $request[ 'matchtime' . $suffix ] ) ? null : sanitize_text_field( wp_unslash( (string) $request[ 'matchtime' . $suffix ] ) );

        return $team_details;
    }

    public function update_player( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request, self::NONCE_FIELD, 'racketmanager_update-player' );
        if ( $security_error ) {
            return $security_error;
        }

        $return    = new stdClass();
        $validator = new Validator();
        $player_id = empty( $request['playerId'] ) ? null : (int) $request['playerId'];
        try {
            $player = $this->player_service->amend_player_details( $player_id, $request );
            if ( is_wp_error( $player ) ) {
                $validator->error    = true;
                $validator->err_flds = $player->get_error_codes();
                $validator->err_msgs = $player->get_error_messages();
                $validator->msg      = __( 'Error with player details', 'racketmanager' );
            } else {
                $return->msg = __( 'Player updated', 'racketmanager' );

                return $this->response_factory->create_success_response( $return );
            }
        } catch ( Player_Not_Updated_Exception $e ) {
            $return->state = 'warning';
            $return->msg   = $e->getMessage();

            return $this->response_factory->create_success_response( $return );
        } catch ( Player_Not_Found_Exception|Duplicate_Email_Exception|Duplicate_BTM_Exception|Exception $e ) {
            $validator->error = true;
            $validator->msg   = $e->getMessage();
        }

        $return = $validator->get_details();

        return $this->response_factory->create_error_response( $return, (int) $return->status );
    }

    public function get_team_event_info( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $event_id = empty( $request['eventId'] ) ? null : (int) $request['eventId'];
        $team_id  = empty( $request['teamId'] ) ? null : (int) $request['teamId'];

        try {
            $team_details    = $this->team_service->get_latest_team_details_for_event( (int) $team_id, (int) $event_id );
            $return          = new stdClass();
            $return->message = __( 'Team information', 'racketmanager' );
            $return->info    = $team_details;

            return $this->response_factory->create_success_response( $return );
        } catch ( Event_Not_Found_Exception|Team_Not_Found_Exception $e ) {
            $validator      = new Validator();
            $validator->msg = $e->getMessage();
            $return         = $validator->get_details();

            return $this->response_factory->create_error_response( $return, (int) $return->status );
        }
    }

    public function cup_entry( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request, self::NONCE_FIELD, 'racketmanager_cup-entry' );
        if ( $security_error ) {
            return $security_error;
        }

        $validator = new Validator_Entry_Form();
        if ( ! is_user_logged_in() ) {
            $validator = $validator->logged_in_entry();
        } else {
            $request_dto = new Cup_Entry_Request_DTO( $request );
            try {
                $response = $this->competition_entry_service->request_cup_entry( $request_dto );
                if ( is_wp_error( $response ) ) {
                    $validator->error    = true;
                    $validator->err_flds = $response->get_error_codes();
                    $validator->err_msgs = $response->get_error_messages();
                    $validator->msg      = __( 'Errors in cup entry form', 'racketmanager' );
                } else {
                    $msg = __( 'Cup entry complete', 'racketmanager' );

                    return $this->response_factory->create_success_response( $msg );
                }
            } catch ( Competition_Not_Found_Exception ) {
                $validator->error = true;
            }
            $return = $validator->get_details();

            return $this->response_factory->create_error_response( $return, (int) $return->status );
        }

        $return = $validator->get_details();
        if ( empty( $return->msg ) ) {
            $return->msg = __( 'Errors in entry form', 'racketmanager' );
        }

        return $this->response_factory->create_error_response( $return, (int) $return->status );
    }

    public function league_entry( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request, self::NONCE_FIELD, 'racketmanager_league-entry' );
        if ( $security_error ) {
            return $security_error;
        }

        $validator = new Validator_Entry_Form();
        if ( ! is_user_logged_in() ) {
            $validator = $validator->logged_in_entry();
        } else {
            $request_dto = new League_Entry_Request_DTO( $request );
            try {
                $response = $this->competition_entry_service->request_league_entry( $request_dto );
                if ( is_wp_error( $response ) ) {
                    $validator->error    = true;
                    $validator->err_flds = $response->get_error_codes();
                    $validator->err_msgs = $response->get_error_messages();
                    $validator->msg      = __( 'Errors in league entry form', 'racketmanager' );
                } else {
                    $msg = __( 'League entry complete', 'racketmanager' );

                    return $this->response_factory->create_success_response( $msg );
                }
            } catch ( Competition_Not_Found_Exception $e ) {
                $validator->msg   = $e->getMessage();
                $validator->error = true;
            }
            $return = $validator->get_details();

            return $this->response_factory->create_error_response( $return, (int) $return->status );
        }

        $return = $validator->get_details();
        if ( empty( $return->msg ) ) {
            $return->msg = __( 'Errors in entry form', 'racketmanager' );
        }

        return $this->response_factory->create_error_response( $return, (int) $return->status );
    }

    public function search_players( array $request = [] ): Response {
        $security_error = $this->nonce_failure_non_modal_html_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $command = Search_Players_Command::from_request( $request );
        if ( $command->search_string ) {
            $output = player_search( $command->search_string );
        } else {
            $output = show_alert( __( 'Search string not supplied', 'racketmanager' ), 'danger' );
        }

        return $this->response_factory->create_raw_response( (string) $output );
    }

    public function tab_data( array $request = [] ): Response {
        $security_error = $this->nonce_failure_plain_text_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $target     = null;
        $output     = null;
        $target_ref = isset( $request['target'] ) ? sanitize_text_field( wp_unslash( (string) $request['target'] ) ) : null;
        if ( $target_ref ) {
            $target_id = isset( $request['targetId'] ) ? (int) $request['targetId'] : ( isset( $request['id'] ) ? (int) $request['id'] : 0 );
            switch ( $target_ref ) {
                case 'competition':
                    try {
                        $target = $this->competition_service->get_by_id( $target_id );
                    } catch ( Competition_Not_Found_Exception $e ) {
                        $output = $e->getMessage();
                    }
                    break;
                case 'event':
                    try {
                        $target = $this->competition_service->get_event_by_id( $target_id );
                    } catch ( Event_Not_Found_Exception $e ) {
                        $output = $e->getMessage();
                    }
                    break;
                case 'league':
                    $target = get_league( $target_id );
                    break;
                case 'tournament':
                    try {
                        $target = $this->tournament_service->get_tournament( $target_id );
                    } catch ( Tournament_Not_Found_Exception $e ) {
                        $output = $e->getMessage();
                    }
                    break;
                default:
                    $output = __( 'Invalid target', 'racketmanager' );
            }
            if ( $target ) {
                $tab = isset( $request['tab'] ) ? sanitize_text_field( wp_unslash( (string) $request['tab'] ) ) : null;
                if ( $tab ) {
                    $valid_tabs = [ 'clubs', 'draws', 'events', 'matches', 'players', 'teams', 'standings', 'crosstable', 'winners', 'overview', 'order_of_play', 'draw' ];
                    $tab_pos    = array_search( $tab, $valid_tabs, true );
                    $tab_name   = $tab_pos !== false ? $valid_tabs[ $tab_pos ] : null;

                    $args    = [];
                    $link_id = isset( $request['link_id'] ) ? sanitize_text_field( wp_unslash( (string) $request['link_id'] ) ) : null;
                    if ( $link_id !== null && '' !== $link_id ) {
                        if ( preg_match( '/^[A-Za-z0-9 _-]+$/', $link_id ) ) {
                            $args[ $tab ] = $link_id;
                        } else {
                            $output = __( 'Invalid link ID', 'racketmanager' );
                        }
                    }
                    if ( empty( $output ) ) {
                        $season = isset( $request['season'] ) ? sanitize_text_field( wp_unslash( (string) $request['season'] ) ) : null;
                        if ( ! empty( $season ) ) {
                            $valid_season = Target_Season_Validator::validate( $target, $target_ref, $season );
                            if ( ! empty( $valid_season ) ) {
                                $args['season'] = $valid_season;
                            }
                        }
                        $target_id_for_function = $target->id ?? ( method_exists( $target, 'get_id' ) ? $target->get_id() : $target_id );
                        $function_name = 'Racketmanager\\' . $target_ref . '_' . $tab_name;
                        if ( function_exists( $function_name ) ) {
                            ob_start();
                            $func_output     = $function_name( $target_id_for_function, $args );
                            $buffered_output = ob_get_clean();
                            $output          = ! empty( $func_output ) ? $func_output : $buffered_output;
                        } else {
                            $output = __( 'Tab not valid', 'racketmanager' );
                        }
                    }
                } else {
                    $output = __( 'Tab not found', 'racketmanager' );
                }
            } elseif ( empty( $output ) ) {
                $output = __( 'Target not found', 'racketmanager' );
            }
        } else {
            $output = __( 'Target ref not found', 'racketmanager' );
        }

        return $this->response_factory->create_raw_response( (string) $output );
    }

    public function show_team_order_players( array $request = [] ): Response {
        $security_error = $this->nonce_failure_non_modal_html_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $club_id  = isset( $request['clubId'] ) ? sanitize_text_field( wp_unslash( (string) $request['clubId'] ) ) : null;
        $event_id = isset( $request['eventId'] ) ? (int) $request['eventId'] : 0;
        $output   = team_order_players( $event_id, [ 'club_id' => $club_id ] );

        return $this->response_factory->create_raw_response( (string) $output );
    }

    public function validate_team_order( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $validator = new Validator();
        $event_id  = isset( $request['eventId'] ) ? (int) $request['eventId'] : null;
        $team_id   = isset( $request['teamId'] ) ? (int) $request['teamId'] : null;
        $match_id  = isset( $request['matchId'] ) ? (int) $request['matchId'] : null;
        $set_team  = ! empty( $request['setTeam'] ) && sanitize_text_field( wp_unslash( (string) $request['setTeam'] ) );
        $event     = null;

        if ( $event_id ) {
            try {
                $event = $this->competition_service->get_event_by_id( $event_id );
            } catch ( Event_Not_Found_Exception $e ) {
                $validator->error      = true;
                $validator->err_msgs[] = $e->getMessage();
                $validator->err_flds[] = 'event_id';
                $validator->status     = 404;
            }
        } else {
            $validator->error      = true;
            $validator->err_msgs[] = __( 'Event id not supplied', 'racketmanager' );
            $validator->err_flds[] = 'event_id';
            $validator->status     = 404;
        }

        if ( empty( $validator->error ) ) {
            $rubber_nums = isset( $request['rubber_num'] ) ? (array) wp_unslash( $request['rubber_num'] ) : [];
            $players     = isset( $request['players'] ) ? (array) wp_unslash( $request['players'] ) : [];
            $wtns        = isset( $request['wtn'] ) ? (array) wp_unslash( $request['wtn'] ) : [];
            $rubbers     = [];
            foreach ( $rubber_nums as $rubber_num ) {
                $new_rubber             = new stdClass();
                $new_rubber->num        = $rubber_num;
                $new_rubber->players    = $players[ $rubber_num ] ?? [];
                $new_rubber->wtn        = $wtns[ $rubber_num ] ?? 0;
                $new_rubber->status     = null;
                $rubbers[ $rubber_num ] = $new_rubber;
            }
            $match_type    = substr( $event->type ?? '', 1, 1 );
            $match_players = [];
            foreach ( $rubbers as $rubber ) {
                $team_wtn = 0;
                foreach ( (array) $rubber->players as $player_ref => $player_id ) {
                    if ( $player_id ) {
                        try {
                            $player       = $this->registration_service->get_registration( (int) $player_id );
                            $player_found = in_array( $player_id, $match_players, true );
                            if ( $player_found ) {
                                $validator->error      = true;
                                $validator->err_msgs[] = __( 'Player already selected', 'racketmanager' );
                                $validator->err_flds[] = 'players_' . $rubber->num . '_' . $player_ref;
                                $validator->status     = 400;
                            } else {
                                $team_wtn       += empty( $player->wtn[ $match_type ] ) ? 40.9 : $player->wtn[ $match_type ];
                                $match_players[] = $player_id;
                            }
                        } catch ( Registration_Not_Found_Exception $e ) {
                            $validator->error      = true;
                            $validator->err_msgs[] = $e->getMessage();
                            $validator->err_flds[] = 'players_' . $rubber->num . '_' . $player_ref;
                            $validator->status     = 400;
                        }
                    } else {
                        $validator->error      = true;
                        $validator->err_msgs[] = __( 'Player not selected', 'racketmanager' );
                        $validator->err_flds[] = 'players_' . $rubber->num . '_' . $player_ref;
                        $validator->status     = 400;
                    }
                }
                $rubber->wtn = round( $team_wtn, 1 );
            }
        }
        if ( empty( $validator->error ) ) {
            $valid_order = $this->check_player_order( $rubbers );
            if ( $valid_order ) {
                if ( ! empty( $set_team ) ) {
                    $updates = false;
                    if ( $match_id ) {
                        $match = $this->fixture_repository->find_by_id( (int) $match_id );
                        if ( $match && $team_id ) {
                            if ( $team_id === (int) $match->home_team ) {
                                $opponent = 'home';
                            } elseif ( $team_id === (int) $match->away_team ) {
                                $opponent = 'away';
                            } else {
                                $opponent = null;
                            }
                            if ( $opponent ) {
                                $match_rubbers = $this->rubber_repository->find_by_fixture_id( (int) $match->get_id() );
                                foreach ( $match_rubbers as $match_rubber ) {
                                    $rubber = $rubbers[ $match_rubber->rubber_number ] ?? null;
                                    if ( $rubber ) {
                                        $rubber_players[ $opponent ] = $rubber->players;
                                        $match_rubber->set_players( $rubber_players );
                                        $updates = true;
                                    }
                                }
                            }
                        }
                    }
                    $msg = $updates ? __( 'Team players set', 'racketmanager' ) : __( 'Valid playing order but unable to set team', 'racketmanager' );
                } else {
                    $msg = __( 'Valid playing order', 'racketmanager' );
                }
            } else {
                $msg = __( 'Invalid playing order', 'racketmanager' );
            }
            $return          = new stdClass();
            $return->rubbers = $rubbers;
            $return->msg     = $msg;
            $return->valid   = $valid_order;

            return $this->response_factory->create_success_response( $return );
        }

        if ( empty( $validator->msg ) ) {
            $validator->msg = __( 'Unable to validate match', 'racketmanager' );
        }

        return $this->response_factory->create_error_response( $validator, (int) $validator->status );
    }

    private function check_player_order( array $rubbers ): bool {
        $valid_order = true;
        foreach ( $rubbers as $rubber_num => $rubber ) {
            if ( isset( $rubbers[ $rubber_num + 1 ] ) ) {
                if ( $rubber->wtn <= $rubbers[ $rubber_num + 1 ]->wtn ) {
                    $rubber->status       = 'W';
                    $rubber->status_class = 'winner';
                } else {
                    $valid_order          = false;
                    $rubber->status       = 'L';
                    $rubber->status_class = 'loser';
                }
            }
            if ( empty( $rubbers[ $rubber_num - 1 ] ) ) {
                continue;
            }
            if ( $rubber->wtn >= $rubbers[ $rubber_num - 1 ]->wtn ) {
                if ( 'L' !== $rubber->status ) {
                    $rubber->status       = 'W';
                    $rubber->status_class = 'winner';
                }
            } else {
                $valid_order          = false;
                $rubber->status       = 'L';
                $rubber->status_class = 'loser';
            }
        }

        return $valid_order;
    }

    public function show_team_edit_modal( array $request = [] ): Response {
        $security_error = $this->nonce_failure_html_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $team_id  = isset( $request['teamId'] ) ? (int) $request['teamId'] : 0;
        $event_id = isset( $request['eventId'] ) ? (int) $request['eventId'] : null;
        $modal    = isset( $request['modal'] ) ? sanitize_text_field( wp_unslash( (string) $request['modal'] ) ) : null;
        $output   = show_team_edit_modal( $team_id, [ 'event_id' => $event_id, 'modal' => $modal ] );

        return $this->response_factory->create_raw_response( (string) $output );
    }

    public function get_event_team_match_dropdown( array $request = [] ): Response {
        $security_error = $this->nonce_failure_non_modal_html_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $team_id  = isset( $request['teamId'] ) ? (int) $request['teamId'] : null;
        $event_id = isset( $request['eventId'] ) ? (int) $request['eventId'] : 0;
        $output   = event_team_match_dropdown( $event_id, [ 'team_id' => $team_id ] );

        return $this->response_factory->create_raw_response( (string) $output );
    }

    public function get_player_details( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $type    = isset( $request['type'] ) ? sanitize_text_field( wp_unslash( (string) $request['type'] ) ) : null;
        $name    = isset( $request['name'] ) ? sanitize_text_field( wp_unslash( (string) $request['name'] ) ) : null;
        $gender  = ! empty( $request['partnerGender'] ) ? sanitize_text_field( wp_unslash( (string) $request['partnerGender'] ) ) : ( ! empty( $request['gender'] ) ? sanitize_text_field( wp_unslash( (string) $request['gender'] ) ) : null );
        $club_id = empty( $request['club'] ) ? null : (int) $request['club'];
        $players = $this->get_players_lookup( $type, $name, $gender, $club_id );

        return $this->response_factory->create_success_response( $players );
    }

    private function get_players_lookup( ?string $type = null, ?string $name = null, ?string $gender = null, ?int $club_id = null ): ?array {
        $players = null;
        if ( 'non_club' === $type ) {
            $players = $this->registration_service->get_non_club_players( [ 'gender' => $gender ] );
        } elseif ( 'club' === $type && $club_id ) {
            $players = $this->club_service->get_players( $club_id, [ 'gender' => $gender ] );
        } elseif ( 'registered' === $type ) {
            $players = $this->registration_service->get_players( [ 'gender' => $gender ] );
        } elseif ( 'championship' === $type ) {
            $players = $this->player_service->get_all_players( [ 'name' => $name, 'gender' => $gender ] );
        } elseif ( 'user' === $type ) {
            $players = $this->player_service->get_all_players( [ 'name' => $name, 'gender' => $gender, 'is_user' => true ] );
        } elseif ( 'all' === $type ) {
            $players = $this->player_service->get_all_players( [ 'name' => $name, 'gender' => $gender, 'all' => true ] );
        }

        return $players;
    }

    private function nonce_failure_response( array $request = [], string $field = self::DEFAULT_NONCE_FIELD, string $action = self::DEFAULT_NONCE_ACTION, ?string $override_msg = null ): ?Response {
        $details = $this->verify_nonce_details( $request, $field, $action );
        if ( $details === null ) {
            return null;
        }
        if ( $override_msg !== null ) {
            $details->msg = $override_msg;
        }

        return $this->response_factory->create_error_response( $details, (int) $details->status );
    }

    private function nonce_failure_non_modal_html_response( array $request = [], string $field = self::DEFAULT_NONCE_FIELD, string $action = self::DEFAULT_NONCE_ACTION ): ?Response {
        $details = $this->verify_nonce_details( $request, $field, $action );
        if ( $details === null ) {
            return null;
        }

        $output = show_alert( (string) $details->msg, 'danger' );

        return $this->response_factory->create_raw_response( $output, 200 );
    }

    private function nonce_failure_plain_text_response( array $request = [], string $field = self::DEFAULT_NONCE_FIELD, string $action = self::DEFAULT_NONCE_ACTION ): ?Response {
        $details = $this->verify_nonce_details( $request, $field, $action );
        if ( $details === null ) {
            return null;
        }

        $output = (string) $details->msg;

        return $this->response_factory->create_raw_response( $output, 200 );
    }

    private function nonce_failure_html_response( array $request = [], string $field = self::DEFAULT_NONCE_FIELD, string $action = self::DEFAULT_NONCE_ACTION ): ?Response {
        $details = $this->verify_nonce_details( $request, $field, $action );
        if ( $details === null ) {
            return null;
        }

        $output = show_alert( (string) $details->msg, 'danger', 'modal' );

        return $this->response_factory->create_raw_response( $output, (int) $details->status );
    }

    private function verify_nonce_details( array $request = [], string $field = self::DEFAULT_NONCE_FIELD, string $action = self::DEFAULT_NONCE_ACTION ): ?stdClass {
        $nonce_set = isset( $request[ $field ] );
        $nonce     = $request[ $field ] ?? '';
        $nonce     = is_string( $nonce ) ? sanitize_text_field( wp_unslash( $nonce ) ) : '';
        if ( $nonce !== '' && $this->security_service->verify_nonce( $nonce, $action ) ) {
            return null;
        }

        $details           = new stdClass();
        $details->error    = true;
        $details->err_flds = [];
        $details->err_msgs = [];
        $details->status   = 403;
        $details->msg      = ! $nonce_set
            ? __( 'There was a problem with your request. Please try again or refresh the page', 'racketmanager' )
            : __( 'Sorry, the action could not be completed. The link or form you are using has expired or is invalid. Please try again, or go back to the previous page and try again', 'racketmanager' );

        return $details;
    }
}
