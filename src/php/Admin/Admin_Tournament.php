<?php
/**
 * RacketManager-Admin API: RacketManager-admin-tournament class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Tournament
 */

namespace Racketmanager\Admin;

use Racketmanager\Admin\Controllers\Tournament_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Modify_Page_View_Model;
use Racketmanager\Admin\Controllers\Tournament_Plan_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Plan_Page_View_Model;
use Racketmanager\Domain\DTO\Tournament\Championship_Rounds_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Finals_Config_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Finals_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Information_Request_DTO;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Services\Validator\Validator_Tournament;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use Racketmanager\Util\Util_Messages;
use stdClass;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_match;
use function Racketmanager\get_team;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class Admin_Tournament extends Admin_Championship {
    /**
     * Function to handle administration tournament displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        $view_map = [
                'modify'       => [ $this, 'display_tournament_page' ],
                'plan'         => [ $this, 'display_plan_page' ],
                'tournament'   => [ $this, 'display_tournament_overview_page' ],
                'draw'         => [ $this, 'display_draw_page' ],
                'setup'        => [ $this, 'display_setup_page' ],
                'setup-event'  => [ $this, 'display_setup_event_page' ],
                'matches'      => [ $this, 'display_matches_page' ],
                'match'        => [ $this, 'display_match_page' ],
                'teams'        => [ $this, 'display_teams_list' ],
                'contact'      => [ $this, 'display_contact_page' ],
                'information'  => [ $this, 'display_information_page' ],
            // Views handled by external sub-controllers
                'config'       => [ $this->get_admin_competition(), 'display_config_page' ],
                'event-config' => [ $this->get_admin_event(), 'display_config_page' ],
                'team'         => [ $this->get_admin_club(), 'display_team_page' ],
        ];

        try {
            // Resolve the callback or fall back to default
            $callback = $view_map[ $view ] ?? [ $this, 'display_tournaments_page' ];

            call_user_func( $callback );

        } catch ( Tournament_Not_Found_Exception | Invalid_Status_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
        }
    }

    private function get_admin_competition(): Admin_Competition {
        return $this->admin_competition ??= new Admin_Competition( $this->racketmanager );
    }

    private function get_admin_event(): Admin_Event {
        return $this->admin_event ??= new Admin_Event( $this->racketmanager );
    }

    private function get_admin_club(): Admin_Club {
        return $this->admin_club ??= new Admin_Club( $this->racketmanager );
    }

    /**
     * Display tournaments page
     */
    public function display_tournaments_page(): void {
        $validator = new Validator_Tournament();
        $validator = $validator->capability( 'edit_leagues' );
        if ( $validator->error ) {
            throw new Invalid_Status_Exception( $validator->msg );
        }
        if ( isset( $_POST['doTournamentDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
            $validator = $validator->capability( 'del_teams' );
            if ( empty( $validator->error ) ) {
                $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_tournaments-bulk' );
            }
            if ( $validator->error ) {
                throw new Invalid_Status_Exception( $validator->msg );
            }
            $tournaments = isset( $_POST['tournament'] ) ? array_map( 'absint', $_POST['tournament'] ) : array();
            if ( $tournaments ) {
                $messages      = array();
                $message_error = false;
                foreach ( $tournaments as $tournament_id ) {
                    try {
                        $deleted = $this->tournament_service->remove_tournament( $tournament_id );
                        if ( $deleted ) {
                            $messages[] = Util_Messages::tournament_deleted( $tournament_id );
                        } else {
                            $messages[] = Util_Messages::tournament_not_deleted( $tournament_id );
                        }
                    } catch ( Tournament_Not_Found_Exception $e ) {
                        $messages[]    = $e->getMessage();
                        $message_error = true;
                    }
                }
                $message = implode( '<br>', $messages );
                $this->set_message( $message, $message_error );
            }
        }
        $age_group_select   = isset( $_GET['age_group'] ) ? sanitize_text_field( wp_unslash( $_GET['age_group'] ) ) : '';
        $season_select      = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : '';
        $competition_select = isset( $_GET['competition'] ) ? intval( $_GET['competition'] ) : '';
        $this->show_message();
        $clubs       = $this->club_service->get_clubs();
        $tournaments = $this->tournament_service->get_tournaments_with_details(
            array(
                'season'         => $season_select,
                'competition_id' => $competition_select,
                'age_group'      => $age_group_select,
                'orderby'        => array(
                    'date' => 'desc',
                    'name' => 'asc',
                ),
            )
        );
        $seasons      = $this->season_service->get_all_seasons();
        $competitions = $this->competition_service->get_tournament_competitions();
        $age_groups   = Util_Lookup::get_age_groups();
        require_once RACKETMANAGER_PATH . 'templates/admin/show-tournaments.php';
    }

    /**
     * Display tournament overview
     */
    public function display_tournament_overview_page(): void {
        $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
            $overview   = $this->tournament_service->get_tournament_overview( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
        if ( isset( $_POST['contactTeam'] ) || isset( $_POST['contactTeamActive'] ) ) {
            $this->contact_tournament_teams();
            $this->show_message();
        }
        $events = $this->tournament_service->get_leagues_by_event_for_tournament( $tournament_id );
        $tab               = 'overview';
        $confirmed_entries = $this->tournament_service->get_players_for_tournament( $tournament_id, 'confirmed' );
        $unpaid_entries    = $this->tournament_service->get_players_for_tournament( $tournament_id, 'unpaid' );
        $pending_entries   = $this->tournament_service->get_players_for_tournament( $tournament_id, 'pending' );
        $withdrawn_entries = $this->tournament_service->get_players_for_tournament( $tournament_id, 'withdrawn' );
        require_once RACKETMANAGER_PATH . 'templates/admin/show-tournament.php';
    }

    /**
     * Display tournament draw
     */
    public function display_draw_page(): void {
        global $tab;
        //phpcs:disable WordPress.Security.NonceVerification.Recommended
        $season        = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
        $league_id     = isset( $_GET['league'] ) ? intval( $_GET['league'] ) : null;
        $tab           = isset( $_GET['league-tab'] ) ? sanitize_text_field( wp_unslash( $_GET['league-tab'] ) ) : null;
        //phpcs:enable WordPress.Security.NonceVerification.Recommended
        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
        $league = get_league( $league_id );
        if ( $league ) {
            $updates = $this->handle_league_teams_action( $league );
            if ( $updates ) {
                $tab = 'preliminary';
            }
            if ( isset( $_POST['updateLeague'] ) && 'match' === $_POST['updateLeague'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $this->manage_matches_in_league( $league );
                $tab = 'matches'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
            } elseif ( isset( $_POST['action'] ) && 'addTeamsToLeague' === $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $this->league_add_teams( $league );
                $this->set_message( __( 'Teams added', 'racketmanager' ) );
                $tab = 'preliminary'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
            } elseif ( isset( $_POST['updateLeague'] ) && 'teamPlayer' === $_POST['updateLeague'] ) {
                $this->edit_player_team( $league );
                $tab = 'preliminary';
            } elseif ( empty( $tab ) ) {
                $tab = $this->handle_championship_admin_page( $league ); //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                if ( isset( $_POST['saveRanking'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                    $this->rank_teams( $league, 'manual' );
                    $tab = 'preliminary'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                } elseif ( isset( $_POST['randomRanking'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                    $this->rank_teams( $league, 'random' );
                    $tab = 'preliminary'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                } elseif ( isset( $_POST['ratingPointsRanking'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                    $this->rank_teams( $league, 'ratings' );
                    $tab = 'preliminary'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                } elseif ( empty( $tab ) ) {
                    $tab = 'finalResults'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                }
            }
            $this->show_message();
            require_once RACKETMANAGER_PATH . 'templates/admin/tournament/draw.php';
        }
    }

    /**
     * Display tournament setup
     */
    public function display_setup_page(): void {
        $validator = new Validator_Tournament();
        $validator = $validator->capability( 'edit_matches' );
        if ( ! empty( $validator->error ) ) {
            throw new Invalid_Status_Exception( $validator->msg );
        }
        if ( isset( $_POST['action'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_add_championship-matches' );
            if ( ! empty( $validator->error ) ) {
                throw new Invalid_Status_Exception( $validator->msg );
            }
            $tournament_id = isset( $_POST['tournament_id'] ) ? intval( $_POST['tournament_id'] ) : null;
            $request = new Championship_Rounds_Request_DTO( $_POST );
            try {
                $response = $this->tournament_service->set_round_dates_for_tournament( $tournament_id, $request );
                if ( is_wp_error( $response ) ) {
                    $validator->error    = true;
                    $validator->err_flds = $response->get_error_codes();
                    $validator->err_msgs = $response->get_error_messages();
                    $this->set_message( __( 'Error setting tournament round dates', 'racketmanager' ), true );
                } else {
                    $this->set_message( __( 'Tournament round dates updated', 'racketmanager' ) );
                }
            } catch ( Tournament_Not_Found_Exception|Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
                throw new Tournament_Not_Found_Exception( $e->getMessage() );
            }
        } elseif ( isset( $_POST['rank'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_calculate_ratings' );
            if ( ! empty( $validator->error ) ) {
                throw new Invalid_Status_Exception( $validator->msg );
            }
            $tournament_id = isset( $_POST['tournament_id'] ) ? intval( $_POST['tournament_id'] ) : null;
            try {
                $updates = $this->tournament_service->calculate_player_team_rating_for_tournament( $tournament_id );
                if ( $updates ) {
                    $this->set_message( __( 'Tournament ratings set', 'racketmanager' ) );
                } else {
                    $this->set_message( __( 'No ratings to set', 'racketmanager' ), 'warning' );
                }
            } catch ( Tournament_Not_Found_Exception $e ) {
                throw new Tournament_Not_Found_Exception( $e->getMessage() );
            }
        }
        $this->show_message();
        $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
        try {
            $tournament_details = $this->tournament_service->get_tournament_with_details( $tournament_id );
        } catch ( Tournament_Not_Found_Exception|Competition_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
        $tournament = $tournament_details->tournament;
        $competition = $tournament_details->competition;
        $season        = $tournament->get_season();
        $tournament_season = $competition->get_season_by_name( $season );
        $match_dates       = $tournament_season['match_dates'] ?? null;
        if ( empty( $match_dates ) ) {
            $match_dates  = array();
            $match_date   = null;
            $round_length = $competition->settings['round_length'] ?? 7;
            $i            = 0;
            foreach ( $tournament->finals as $final ) {
                $r = $final['round'] - 1;
                if ( 0 === $i ) {
                    $match_date = $tournament->date_end;
                } elseif ( 1 === $i ) {
                    $match_date = Util::amend_date( $tournament->date_end, 7, '-' );
                } else {
                    $match_date = Util::amend_date( $match_date, $round_length, '-' );
                }
                $match_dates[ $r ] = $match_date;
                ++$i;
            }
        }
        require_once RACKETMANAGER_PATH . 'templates/admin/tournament/setup.php';
    }

    /**
     * Display event setup
     */
    public function display_setup_event_page(): void {
        $validator = new Validator_Tournament();
        $validator = $validator->capability( 'edit_matches' );
        if ( ! empty( $validator->error ) ) {
            throw new Invalid_Status_Exception( $validator->msg );
        }
        if ( isset( $_POST['action'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_add_championship-matches' );
            if ( ! empty( $validator->error ) ) {
                throw new Invalid_Status_Exception( $validator->msg );
            }
            $valid     = true;
            $action    = sanitize_text_field( wp_unslash( $_POST['action'] ) );
            $league_id = isset( $_POST['league_id'] ) ? intval( $_POST['league_id'] ) : null;
            $season    = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
            $rounds    = $_POST['rounds'] ?? null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $league    = get_league( $league_id );
            if ( $league ) {
                $this->set_championship_matches( $league, $season, $rounds, $action );
            }
        }
        $season        = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
        $league_id     = isset( $_GET['league'] ) ? intval( $_GET['league'] ) : null;
        //phpcs:enable WordPress.Security.NonceVerification.Recommended
        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
        $league = get_league( $league_id );
        if ( $league ) {
            $match_count = $league->get_matches(
                    array(
                            'count' => true,
                            'final' => 'all',
                    )
            );
            $tab              = 'matches';
            $event_dtls       = $league->event->get_season_by_name( $season );
            $competition_dtls = $league->event->competition->get_season_by_name( $season );
            $match_dates      = empty( $event_dtls['match_dates'] ) ? $competition_dtls['match_dates'] : $event_dtls['match_dates'];
            require_once RACKETMANAGER_PATH . 'templates/admin/tournament/setup.php';
        }
    }

    /**
     * Display tournament page
     */
    public function display_tournament_page(): void {
        $controller = $this->racketmanager->container->get( 'tournament_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( __( 'Controller not available', 'racketmanager' ) );
        }

        $result = $controller->modify_page( $_GET, $_POST );

        if ( ! empty( $result['redirect'] ) ) {
            $redirect_url = strval( $result['redirect'] );

            if ( headers_sent() ) {
                $js_url   = esc_url_raw( $redirect_url );
                $html_url = esc_url( $redirect_url );

                echo '<script>window.location.replace(' . wp_json_encode( $js_url ) . ');</script>';
                echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_attr( $html_url ) . '"></noscript>';
                exit;
            }

            wp_safe_redirect( $redirect_url );
            exit;
        }

        if ( ! empty( $result['message'] ) ) {
            $this->set_message(
                    strval( $result['message'] ),
                    $result['message_type'] ?? false
            );
        }

        $this->show_message();

        $vm = $result['view_model'] ?? null;
        if ( ! ( $vm instanceof Tournament_Modify_Page_View_Model ) ) {
            throw new Invalid_Status_Exception( __( 'Invalid view model', 'racketmanager' ) );
        }

        $vars = $vm->to_template_vars();
        foreach ( $vars as $key => $value ) {
            ${$key} = $value;
        }

        require_once RACKETMANAGER_PATH . 'templates/admin/tournament-edit.php';
    }

    /**
     * Display tournament plan page
     */
    public function display_plan_page(): void {
        $controller = $this->racketmanager->container->get( 'tournament_plan_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Plan_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( __( 'Controller not available', 'racketmanager' ) );
        }

        $result = $controller->plan_page( $_GET, $_POST );

        if ( ! empty( $result['redirect'] ) ) {
            $redirect_url = strval( $result['redirect'] );

            if ( headers_sent() ) {
                $js_url   = esc_url_raw( $redirect_url );
                $html_url = esc_url( $redirect_url );

                echo '<script>window.location.replace(' . wp_json_encode( $js_url ) . ');</script>';
                echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_attr( $html_url ) . '"></noscript>';
                exit;
            }

            wp_safe_redirect( $redirect_url );
            exit;
        }

        if ( ! empty( $result['message'] ) ) {
            $this->set_message(
                    strval( $result['message'] ),
                    $result['message_type'] ?? false
            );
        }

        $this->show_message();

        $vm = $result['view_model'] ?? null;
        if ( ! ( $vm instanceof Tournament_Plan_Page_View_Model ) ) {
            throw new Invalid_Status_Exception( __( 'Invalid view model', 'racketmanager' ) );
        }

        $vars = $vm->to_template_vars();
        foreach ( $vars as $key => $value ) {
            ${$key} = $value;
        }

        require_once RACKETMANAGER_PATH . 'templates/admin/tournament/plan.php';
    }

    /**
     * Display tournament matches page
     */
    public function display_matches_page(): void {
        $validator = new Validator_Tournament();
        $validator = $validator->capability( 'edit_matches' );
        if ( ! empty( $validator->error ) ) {
            throw new Invalid_Status_Exception( $validator->msg );
        }
        //phpcs:disable WordPress.Security.NonceVerification.Recommended
        $final_key     = isset( $_GET['final'] ) ? intval( $_GET['final'] ) : null;
        $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
        $league_id     = isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : null;
        $final_key     = isset( $_GET['final'] ) ? sanitize_text_field( wp_unslash( $_GET['final'] ) ) : null;
        //phpcs:enable WordPress.Security.NonceVerification.Recommended
        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
            $season     = $tournament->get_season();
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
        $league = get_league( $league_id );
        if ( $league ) {
            $is_finals       = false;
            $single_cup_game = false;
            $bulk            = false;
            $matches         = array();
            if ( $final_key ) {
                $is_finals = true;
                $mode      = 'edit';
                $edit      = true;

                $final           = $league->championship->get_finals( $final_key );
                $num_first_round = $league->championship->num_teams_first_round;

                $max_matches = $final['num_matches'];

                /* translators: %s: round name */
                $form_title = sprintf( __( 'Edit Matches - %s', 'racketmanager' ), Util::get_final_name( $final_key ) );
                $match_args = array(
                        'final'   => $final_key,
                        'orderby' => array(
                                'id' => 'ASC',
                        ),
                );
                if ( 'final' !== $final_key && ! empty( $league->current_season['home_away'] ) && 'true' === $league->current_season['home_away'] ) {
                    $match_args['leg'] = 1;
                }
                $matches      = $league->get_matches( $match_args );
                $teams        = $league->championship->get_final_teams( $final_key );
                $submit_title = $form_title;
            }
            //phpcs:enable WordPress.Security.NonceVerification.Recommended
            require_once RACKETMANAGER_PATH . 'templates/admin/includes/match.php';
        }
    }

    /**
     * Display tournament match page
     */
    public function display_match_page(): void {
        $validator = new Validator_Tournament();
        $validator = $validator->capability( 'edit_matches' );
        if ( ! empty( $validator->error ) ) {
            throw new Invalid_Status_Exception( $validator->msg );
        }
        //phpcs:disable WordPress.Security.NonceVerification.Recommended
        $final_key     = isset( $_GET['final'] ) ? intval( $_GET['final'] ) : null;
        $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
        $league_id     = isset( $_GET['league'] ) ? intval( $_GET['league'] ) : null;
        $final_key     = isset( $_GET['final'] ) ? sanitize_text_field( wp_unslash( $_GET['final'] ) ) : null;
        $match_id      = isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : null;
        //phpcs:enable WordPress.Security.NonceVerification.Recommended
        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
            $season     = $tournament->get_season();
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
        $league = get_league( $league_id );
        if ( $league && $match_id ) {
            $match = get_match( $match_id );
            if ( $match ) {
                $single_cup_game = true;
                $bulk            = false;
                $mode            = 'edit';
                $edit            = true;
                $form_title      = __( 'Edit Match', 'racketmanager' );
                $submit_title    = $form_title;
                $matches[0]      = $match;
                $match_day       = $match->match_day;
                $max_matches     = 1;
                $final           = $league->championship->get_finals( $final_key );
                $final_teams     = $league->championship->get_final_teams( $final['key'] );
                if ( is_numeric( $match->home_team ) ) {
                    $home_team  = get_team( $match->home_team );
                    $home_title = $home_team?->title;
                } else {
                    $home_team = $final_teams[ $match->home_team ];
                    if ( $home_team ) {
                        $home_title = $home_team->title;
                    } else {
                        $home_title = null;
                    }
                }
                if ( is_numeric( $match->away_team ) ) {
                    $away_team  = get_team( $match->away_team );
                    $away_title = $away_team?->title;
                } else {
                    $away_team = $final_teams[ $match->away_team ];
                    if ( $away_team ) {
                        $away_title = $away_team->title;
                    } else {
                        $away_title = null;
                    }
                }
                require_once RACKETMANAGER_PATH . 'templates/admin/includes/match.php';
            }
        }
    }

    /**
     * Contact teams in tournament in admin screen
     */
    private function contact_tournament_teams(): void {
        $validator = new Validator();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_contact-teams-preview' );
        $validator = $validator->capability( 'edit_teams' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->error, true );
            return;
        }
        $tournament_id = isset( $_POST['tournament_id'] ) ? intval( $_POST['tournament_id'] ) : null;
        $message       = isset( $_POST['emailMessage'] ) ? htmlspecialchars_decode( $_POST['emailMessage'] ) : null;
        $active        = isset( $_POST['contactTeamActive'] );
        try {
            $sent = $this->tournament_service->contact_teams( $tournament_id, $message, $active );
            if ( $sent ) {
                $this->set_message( __( 'Email sent to players', 'racketmanager' ) );
            } else {
                $this->set_message( __( 'Unable to send email', 'racketmanager' ), true );
            }
        } catch ( Tournament_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
        }
    }
    /**
     * Display tournament information page
     */
    public function display_information_page(): void {
        $validator = new Validator_Tournament();
        $validator = $validator->capability( 'edit_teams' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        $tournament_id = isset( $_GET['tournament_id'] ) ? intval( $_GET['tournament_id'] ) : null;
        if ( isset( $_POST['setInformation'] ) ) {
            $tournament_information = new Tournament_Information_Request_DTO( $_POST );
            try {
                $response = $this->tournament_service->set_tournament_information( $tournament_id, $tournament_information );
                if ( is_WP_Error( $response ) ) {
                    $this->set_message( $response->get_error_message(), true );
                } elseif ( $response ) {
                    $this->set_message( __( 'Information updated', 'racketmanager' ) );
                } else {
                    $this->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
                }
            } catch ( Tournament_Not_Found_Exception $e ) {
                $this->set_message( $e->getMessage(), true );
            }
        } else {
            if ( isset( $_POST['notifyFinalists'] ) ) {
                try {
                    $response = $this->tournament_service->notify_finalists_for_tournament( $tournament_id );
                    if ( $response ) {
                        $this->set_message( __( 'Finalists notified', 'racketmanager' ) );
                    } else {
                        $this->set_message( __( 'No notification', 'racketmanager' ), true );
                    }
                } catch ( Tournament_Not_Found_Exception|Invalid_Argument_Exception $e ) {
                    $this->set_message( $e->getMessage(), true );
                }
            }
        }
        $this->show_message();
        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
            return;
        }
        require_once RACKETMANAGER_PATH . 'templates/admin/tournament/information.php';
    }
    /**
     * Calculate team ratings function
     *
     * @param object $league league object.
     *
     * @return void
     */
    private function edit_player_team( object $league ): void {

    }
    /**
     * Add new season to competition
     *
     * @param string $season season.
     * @param int $competition_id competition id.
     * @param int|null $num_match_days number of match days.
     *
     * @return array|boolean
     */
    public function add_season_to_competition( string $season, int $competition_id, int $num_match_days = null ): bool|array {
        try {
            $competition = $this->competition_service->get_competition( $competition_id );
        } catch ( Competition_Not_Found_Exception ) {
            return false;
        }
        if ( ! $num_match_days ) {
            $num_match_days = Util::get_default_match_days( $competition->type );
        }
        if ( ! $num_match_days ) {
            $this->set_message( __( 'Number of match days not specified', 'racketmanager' ), 'error' );
            return false;
        }
        $seasons            = empty( $competition->get_seasons() ) ? array() : $competition->get_seasons();
        $seasons[ $season ] = array(
            'name'           => $season,
            'num_match_days' => $num_match_days,
            'status'         => 'draft',
        );
        ksort( $seasons );
        $competition->update_seasons( $seasons );
        $events = $this->competition_service->get_events_for_competition( $competition_id );
        foreach ( $events as $event ) {
            if ( empty( $event->get_season_by_name( $season ) ) ) {
                $this->add_season_to_event( $season, $event->id, $num_match_days );
            }
        }
        /* translators: %s: season name */
        $this->set_message( sprintf( __( 'Season %s added', 'racketmanager' ), $season ) );

        return $competition->get_season_by_name( $season );
    }
    /**
     * Edit season in object - competition or event
     *
     * @param object $season_data season data.
     */
    private function edit_season( object $season_data ): void {
        $competition = null;
        $event       = null;
        if ( 'competition' === $season_data->type ) {
            try {
                $competition = $this->competition_service->get_by_id( $season_data->object_id );
                $object      = $competition;
            } catch ( Competition_Not_Found_Exception ) {
                $object = null;
            }
        } elseif ( 'event' === $season_data->type ) {
            $event  = get_event( $season_data->object_id );
            $object = $event;
        } else {
            $object      = null;
        }
        $seasons                         = $object->seasons;
        $seasons[ $season_data->season ] = array(
            'name'              => $season_data->season,
            'num_match_days'    => $season_data->num_match_days,
            'match_dates'       => $season_data->match_dates,
            'home_away'         => $season_data->home_away,
            'fixed_match_dates' => $season_data->fixed_dates,
            'status'            => $season_data->status,
            'date_closing'      => $season_data->date_closing,
        );
        if ( 'competition' === $season_data->type ) {
            $seasons[ $season_data->season ]['date_open']        = $season_data->date_open;
            $seasons[ $season_data->season ]['date_start']       = $season_data->date_start;
            $seasons[ $season_data->season ]['date_end']         = $season_data->date_end;
            $seasons[ $season_data->season ]['competition_code'] = $season_data->competition_code;
            $seasons[ $season_data->season ]['venue']            = $season_data->venue ?? null;
            $seasons[ $season_data->season ]['grade']            = $season_data->grade ?? null;
        }
        ksort( $seasons );
        if ( 'competition' === $season_data->type ) {
            $competition->update_seasons( $seasons );
        } elseif ( 'event' === $season_data->type ) {
            $event->update_seasons(  $seasons );
        }
        if ( 'competition' === $season_data->type ) {
            $events = $this->competition_service->get_events_for_competition( $competition->id );
            foreach ( $events as $event ) {
                $event_season                 = new stdClass();
                $event_season->object_id      = $event->id;
                $event_season->type           = 'event';
                $event_season->season         = $season_data->season;
                $event_season->num_match_days = $season_data->num_match_days;
                $event_season->match_dates    = $season_data->match_dates;
                $event_season->home_away      = $season_data->home_away;
                $event_season->fixed_dates    = $season_data->fixed_dates;
                $event_season->status         = $season_data->status;
                $event_season->date_closing   = $season_data->date_closing;
                $this->edit_season( $event_season );
            }
        }
    }
    /**
     * Add new season to event
     *
     * @param string $season season.
     * @param int $event_id event_id.
     * @param int|null $num_match_days number of match days.
     *
     * @return void
     */
    private function add_season_to_event( string $season, int $event_id, ?int $num_match_days ): void {
        global $event;

        $event = get_event( $event_id );
        if ( empty( $event->get_seasons() ) ) {
            $event_seasons = array();
        } else {
            $event_seasons = $event->get_seasons();
        }
        if ( $event->is_box ) {
            $event_seasons[ $season ] = array(
                'name'           => $season,
                'num_match_days' => 0,
                'status'         => 'draft',
            );
        } else {
            if ( ! $num_match_days ) {
                $num_match_days = Util::get_default_match_days( $event->competition->type );
            }
            if ( ! $num_match_days ) {
                $this->set_message( __( 'Number of match days not specified', 'racketmanager' ), 'error' );
                return;
            }
            $event_seasons[ $season ] = array(
                'name'           => $season,
                'num_match_days' => $num_match_days,
                'status'         => 'draft',
            );
        }
        $seasons = $event->get_seasons();
        ksort( $seasons );
        $event->update_seasons( $seasons );
    }
}
