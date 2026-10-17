<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Ajax;

use Racketmanager\Domain\DTO\League\Save_Add_Points_Command;
use Racketmanager\Exceptions\Clubs_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Util\Util;
use stdClass;
use function Racketmanager\event_dropdown;
use function Racketmanager\get_event;
use function Racketmanager\get_league_team;
use function Racketmanager\get_team;
use function Racketmanager\league_dropdown;
use function Racketmanager\match_dropdown;
use function Racketmanager\season_dropdown;

/**
 * Adapter for Admin AJAX requests.
 */
class Admin_Ajax_Adapter {
    private const DEFAULT_NONCE_FIELD = 'security';
    private const DEFAULT_NONCE_ACTION = 'ajax-nonce';

    public function __construct(
        private Security_Service_Interface $security_service,
        private Json_Response_Factory_Interface $response_factory,
        private RacketManager $racketmanager,
        private Competition_Service $competition_service,
        private Competition_Entry_Service $competition_entry_service,
        private Notification_Service $notification_service,
        private Fixture_Repository_Interface $fixture_repository,
        private Settings_Service $settings_service
    ) {}

    public function save_add_points( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $cap_error = $this->check_capability( 'edit_leagues' );
        if ( $cap_error ) {
            return $cap_error;
        }

        $command = Save_Add_Points_Command::from_array( $request );
        if ( ! $command->table_id ) {
            return $this->response_factory->create_success_response();
        }

        $league_entry = get_league_team( $command->table_id );
        if ( $league_entry ) {
            $league_entry->amend_points( $command->points );

            return $this->response_factory->create_success_response();
        }

        return $this->response_factory->create_error_response( __( 'League entry not found', 'racketmanager' ), 500 );
    }

    public function insert_home_stadium( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $stadium = null;
        $team_id = isset( $request['team_id'] ) ? (int) $request['team_id'] : null;
        if ( $team_id ) {
            $team = get_team( $team_id );
            if ( $team ) {
                $stadium = trim( (string) $team->stadium );
            }
        }

        return $this->response_factory->create_success_response( $stadium );
    }

    public function get_event_dropdown( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $competition_id = isset( $request['competition_id'] ) ? (int) $request['competition_id'] : null;
        $output         = event_dropdown( $competition_id );

        return $this->response_factory->create_success_response( $output );
    }

    public function get_league_dropdown( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $event_id = isset( $request['event_id'] ) ? (int) $request['event_id'] : null;
        $output   = league_dropdown( $event_id );

        return $this->response_factory->create_success_response( $output );
    }

    public function set_season_dropdown( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $league_id = isset( $request['league_id'] ) ? (int) $request['league_id'] : null;
        $output    = season_dropdown( $league_id );

        return $this->response_factory->create_success_response( $output );
    }

    public function set_match_dropdown( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $league_id = isset( $request['league_id'] ) ? (int) $request['league_id'] : null;
        $season    = isset( $request['season'] ) ? sanitize_text_field( wp_unslash( (string) $request['season'] ) ) : null;
        $output    = match_dropdown( $league_id, [ 'season' => $season ] );

        return $this->response_factory->create_success_response( $output );
    }

    public function check_team_exists( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $found = false;
        if ( isset( $request['name'] ) ) {
            $name = stripslashes( sanitize_text_field( wp_unslash( (string) $request['name'] ) ) );
            $team = $this->racketmanager->get_team_id( $name );
            if ( $team ) {
                $found = true;
            }
        }

        return $this->response_factory->create_success_response( $found );
    }

    public function email_constitution( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $cap_error = $this->check_capability( 'edit_leagues' );
        if ( $cap_error ) {
            return $cap_error;
        }

        $event_id = isset( $request['eventId'] ) ? (int) $request['eventId'] : null;
        if ( ! $event_id ) {
            return $this->response_factory->create_error_response( __( 'Event not specified', 'racketmanager' ), 500 );
        }

        $event = get_event( $event_id );
        if ( ! $event ) {
            return $this->response_factory->create_error_response( __( 'Event not found', 'racketmanager' ), 500 );
        }

        $season = $event->current_season;
        $event->send_constitution( $season );

        return $this->response_factory->create_success_response( __( 'Constitution emailed', 'racketmanager' ) );
    }

    public function notify_competition_entries_open( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $cap_error = $this->check_capability( 'edit_leagues' );
        if ( $cap_error ) {
            return $cap_error;
        }

        $competition_id = isset( $request['competitionId'] ) ? (int) $request['competitionId'] : null;
        $season         = isset( $request['season'] ) ? (int) $request['season'] : null;
        try {
            $competition        = $this->competition_service->get_by_id( (int) $competition_id );
            $competition_season = $competition->get_season_by_name( (string) $season );
            if ( empty( $competition_season ) ) {
                return $this->response_factory->create_error_response( __( 'Season not found for competition', 'racketmanager' ), 500 );
            }

            if ( 'team' === $competition->settings['entry_type'] ) {
                try {
                    $entry_found = $this->competition_service->get_clubs_for_competition( (int) $competition_id, $season );
                    if ( $entry_found ) {
                        try {
                            $validator = $this->competition_entry_service->notify_team_entry_reminder( (int) $competition_id, $season );
                        } catch ( Competition_Not_Found_Exception|Season_Not_Found_Exception|Clubs_Not_Found_Exception $e ) {
                            return $this->response_factory->create_error_response( $e->getMessage(), 500 );
                        }
                    } else {
                        $validator = $this->competition_entry_service->notify_team_entry_open( (int) $competition_id, $season );
                    }

                    return $this->response_factory->create_success_response( $validator->msg );
                } catch ( Competition_Not_Found_Exception $e ) {
                    return $this->response_factory->create_error_response( $e->getMessage(), 500 );
                }
            }

            return $this->response_factory->create_error_response( __( 'Invalid competition entry type', 'racketmanager' ), 500 );
        } catch ( Competition_Not_Found_Exception $e ) {
            return $this->response_factory->create_error_response( $e->getMessage(), 404 );
        }
    }

    public function notify_tournament_entries_open( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $cap_error = $this->check_capability( 'edit_leagues' );
        if ( $cap_error ) {
            return $cap_error;
        }

        $tournament_id = isset( $request['tournamentId'] ) ? (int) $request['tournamentId'] : null;
        try {
            $result = $this->competition_entry_service->notify_tournament_entry_open( (int) $tournament_id );
            if ( $result ) {
                return $this->response_factory->create_success_response( sprintf( __( '%d notifications sent', 'racketmanager' ), $result ) );
            }

            return $this->response_factory->create_error_response( __( 'Notifications not sent', 'racketmanager' ), 500 );
        } catch ( Tournament_Not_Found_Exception|Invalid_Argument_Exception $e ) {
            return $this->response_factory->create_error_response( $e->getMessage(), 500 );
        }
    }

    public function notify_teams_fixture( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $cap_error = $this->check_capability( 'edit_matches', 'edit_leagues' );
        if ( $cap_error ) {
            return $cap_error;
        }

        $message_sent = false;
        if ( isset( $request['matchId'] ) ) {
            $match        = $this->fixture_repository->find_by_id( (int) sanitize_text_field( wp_unslash( (string) $request['matchId'] ) ) );
            $message_sent = $match ? $this->notification_service->send_next_fixture_notification( $match ) : false;
        }
        if ( $message_sent ) {
            $msg = __( 'Teams notified', 'racketmanager' );
        } else {
            $msg = __( 'No notification', 'racketmanager' );
        }

        return $this->response_factory->create_success_response( [ 'message' => $msg ] );
    }

    public function send_fixtures( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $cap_error = $this->check_capability( 'edit_matches', 'edit_leagues' );
        if ( $cap_error ) {
            return $cap_error;
        }

        $event_id = isset( $request['eventId'] ) ? (int) $request['eventId'] : null;
        $event    = get_event( $event_id );
        if ( ! $event ) {
            return $this->response_factory->create_error_response( __( 'Event not found', 'racketmanager' ), 404 );
        }

        $message_sent = $this->notification_service->send_event_fixtures( $event );
        if ( $message_sent ) {
            return $this->response_factory->create_success_response( [ 'msg' => __( 'Captains emailed', 'racketmanager' ) ] );
        }

        return $this->response_factory->create_error_response( __( 'No notification', 'racketmanager' ), 500 );
    }

    public function set_tournament_dates( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( $request );
        if ( $security_error ) {
            return $security_error;
        }

        $cap_error = $this->check_capability( 'edit_leagues', 'racketmanager_settings' );
        if ( $cap_error ) {
            return $cap_error;
        }

        $grade      = isset( $request['grade'] ) ? sanitize_text_field( wp_unslash( (string) $request['grade'] ) ) : '';
        $date_start = isset( $request['dateStart'] ) ? sanitize_text_field( wp_unslash( (string) $request['dateStart'] ) ) : null;
        if ( ! $date_start ) {
            return $this->response_factory->create_error_response( __( 'No start date specified', 'racketmanager' ), 500 );
        }

        $parameters = $this->settings_service->get_category( 'championship' );
        if ( ! $parameters ) {
            return $this->response_factory->create_error_response( __( 'No lead time parameters set', 'racketmanager' ), 500 );
        }

        $date_open     = Util::amend_date( $date_start, (int) $parameters['open_lead_time'], '-' );
        $date_closing  = Util::amend_date( $date_start, (int) ( $parameters['date_closing'][ $grade ] ?? 0 ), '-' );
        $date_withdraw = Util::amend_date( $date_start, (int) ( $parameters['date_withdrawal'][ $grade ] ?? 0 ), '-' );

        $res                = new stdClass();
        $res->msg           = __( 'Dates set', 'racketmanager' );
        $res->date_open     = $date_open;
        $res->date_closing  = $date_closing;
        $res->date_withdraw = $date_withdraw;

        return $this->response_factory->create_success_response( $res );
    }

    private function check_capability( string ...$capabilities ): ?Response {
        foreach ( $capabilities as $capability ) {
            if ( $this->security_service->current_user_can( $capability ) ) {
                return null;
            }
        }
        if ( $this->security_service->current_user_can( 'manage_racketmanager' ) ) {
            return null;
        }

        return $this->response_factory->create_error_response(
            __( 'You do not have permission to perform this action.', 'racketmanager' ),
            403
        );
    }

    private function nonce_failure_response( array $request = [], string $field = self::DEFAULT_NONCE_FIELD, string $action = self::DEFAULT_NONCE_ACTION, ?string $override_msg = null ): ?Response {
        $details = $this->verify_nonce_details( $request, $field, $action );
        if ( $details === null ) {
            return null;
        }
        if ( $override_msg !== null ) {
            $details->msg = $override_msg;
        }

        return $this->response_factory->create_error_response( $details->msg ?? $details, (int) $details->status );
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
