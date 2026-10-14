<?php
/**
 * RacketManager-Admin API: RacketManager-admin-league class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-League
 */

namespace Racketmanager\Admin;

use Racketmanager\Admin\Presenters\Admin_Date_Presenter;
use Racketmanager\Admin\Presenters\Admin_League_Matches_Presenter;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Util\Util;
use stdClass;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_team;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration League panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class Admin_League extends Admin_Display {
    /**
     * Function to handle administration finances displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        switch ( $view ) {
            case 'seasons':
                $this->display_seasons_page();
                break;
            case 'overview':
                $this->display_overview_page();
                break;
            case 'setup':
                $this->display_setup_page();
                break;
            case 'setup-event':
                $this->display_setup_event_page();
                break;
            case 'modify':
                $this->admin_competition = new Admin_Competition( $this->racketmanager );
                $this->admin_competition->display_season_modify_page();
                break;
            case 'config':
                $this->admin_competition = new Admin_Competition( $this->racketmanager );
                $this->admin_competition->display_config_page();
                break;
            case 'event-config':
                $this->admin_event = new Admin_Event( $this->racketmanager );
                $this->admin_event->display_config_page();
                break;
            case 'event':
                $this->display_event_page();
                break;
            case 'constitution':
                $this->display_constitution_page();
                break;
            case 'league':
                $this->display_league_page();
                break;
            case 'matches':
                $this->display_matches_page();
                break;
            case 'match':
                $this->display_match_page();
                break;
            case 'plan':
                $this->display_schedule_page();
                break;
            case 'teams':
                $this->display_teams_list();
                break;
            case 'team':
                $this->admin_club = new Admin_Club( $this->racketmanager );
                $this->admin_club->display_team_page();
                break;
            case 'contact':
                $this->display_contact_page();
                break;
            default:
                $this->display_leagues_page();
                break;
        }
    }
    /**
     * Display leagues page
     */
    public function display_leagues_page(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'edit_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        $competition_type  = 'league';
        $type              = '';
        $season            = '';
        $standalone        = true;
        $competition_query = array( 'type' => $competition_type );
        $page_title        = ucfirst( $competition_type ) . ' ' . __( 'Competitions', 'racketmanager' );
        require_once RACKETMANAGER_PATH . 'templates/admin/show-competitions.php';
    }

    /**
     * Display season overview
     */
    public function display_overview_page(): void {
        $this->apply_flash_message();
        $competition_id = null;
        $competition    = null;
        $validator      = new Validator();
        $validator      = $validator->capability( 'edit_leagues' );
        if  ( empty( $validator->error ) ) {
            $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
            $validator      = $validator->competition( $competition_id );
            if ( empty( $validator->error ) ) {
                $competition = $this->competition_service->get_competition( $competition_id );
                $season      = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
                $seasons     = $competition->get_seasons();
                $validator   = $validator->season_set( $season, $seasons );
            }
        }
        if ( ! empty( $validator->error ) ) {
            if ( empty( $validator->msg ) ) {
                $this->set_message( $validator->err_msg[0] , true );
            } else {
                $this->set_message( $validator->msg, true );
            }
            $this->show_message();
            return;
        }
        if ( isset( $_POST['contactTeam'] ) && $this->contact_teams() ) {
            return;
        }
        $this->show_message();
        $competition_overview = $this->competition_service->get_competition_overview( $competition_id, $season );
        $competition_events   = $this->competition_service->get_events_with_details_for_competition( $competition_id, $season );
        $tab                  = 'overview';
        $current_season       = (object) $competition->get_season_by_name( $season ) ?? array();
        if ( isset( $current_season->date_closing ) && $current_season->date_closing <= gmdate( 'Y-m-d' ) ) {
            $current_season->is_active = true;
        } else {
            $current_season->is_active = false;
        }
        $current_season->is_open = false;
        $current_season->entries = $this->competition_service->get_clubs_for_competition( $competition_id, $season );
        require_once RACKETMANAGER_PATH . 'templates/admin/league/show-season.php';
    }
    /**
     * Display setup
     */
    public function display_setup_page(): void {
        global $racketmanager;
        $this->apply_flash_message();
        $validator = new Validator();
        $validator = $validator->capability( 'edit_matches' );
        if (  ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['action'] ) || isset( $_POST['rank'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $result = $this->league_setup_dispatcher->handle( $_GET, $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by the action guard.
            if ( $this->deliver_league_action_result( $result, $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                return;
            }
        }
        $this->show_message();
        $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $validator      = $validator->competition( $competition_id );
        if ( empty( $validator->error ) ) {
            $competition = $this->competition_service->get_competition( $competition_id );
            $current_season = $competition->get_season_by_name( (string) $season );
            require_once RACKETMANAGER_PATH . 'templates/admin/includes/setup.php';
        } else {
            $this->set_message( $validator->err_msgs[0] ?? $validator->msg, true );
            $this->show_message();
        }
    }
    /**
     * Display event setup
     */
    public function display_setup_event_page(): void {
        $this->apply_flash_message();
        $validator = new Validator();
        $validator = $validator->capability( 'edit_matches' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $result = $this->event_setup_dispatcher->handle( $_GET, $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by the action guard.
            if ( $this->deliver_league_action_result( $result, $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                return;
            }
        }
        $this->show_message();
        $season   = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( $event_id ) {
            $event = get_event( $event_id );
            if ( $event ) {
                $current_season = $event->get_season_by_name( (string) $season );
                require_once RACKETMANAGER_PATH . 'templates/admin/includes/setup.php';
            }
        }
    }
    /**
     * Display league page
     */
    public function display_league_page(): void {
        global $league, $racketmanager;

        $this->apply_flash_message();
        if ( ! current_user_can( 'view_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
        } else {
            $league_id = isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : null;
            if ( $league_id ) {
                $league = get_league( $league_id );
                if ( $league ) {
                    $seasons   = $league->event->get_seasons();
                    $league_id = $league->id;
                    $league->set_season();
                    $season      = $league->get_season();
                    $league_mode = ( isset( $league->event->competition->settings['mode'] ) ? ( $league->event->competition->settings['mode'] ) : '' );
                    $requested_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( strval( $_GET['tab'] ) ) ) : '';
                    $allowed_tabs  = array( 'standings', 'crosstable', 'matches', 'preliminary' );
                    $tab           = in_array( $requested_tab, $allowed_tabs, true ) ? $requested_tab : 'standings';
                    $match_day   = false;
                    // phpcs:disable WordPress.Security.NonceVerification.Missing
                    if ( isset( $_POST['contactTeam'] ) ) {
                        if ( $this->contact_teams() ) {
                            return;
                        }
                    } elseif ( isset( $_POST['doAction'] ) || ( isset( $_POST['action'] ) && 'addTeamsToLeague' === $_POST['action'] ) || isset( $_POST['saveRanking'] ) || isset( $_POST['randomRanking'] ) || isset( $_POST['ratingPointsRanking'] ) || ( isset( $_POST['updateLeague'] ) && 'teams_manual' === $_POST['updateLeague'] ) ) {
                        $result = $this->league_team_dispatcher->handle( $_GET, $_POST, $league );
                        if ( $this->deliver_league_action_result( $result, $_GET ) ) {
                            return;
                        }
                    } elseif ( isset( $_POST['delMatches'] ) || ( isset( $_POST['updateLeague'] ) && in_array( $_POST['updateLeague'], array( 'match', 'results' ), true ) ) ) {
                        $result = $this->league_match_dispatcher->handle( $_GET, $_POST, $league );
                        if ( $this->deliver_league_action_result( $result, $_GET ) ) {
                            return;
                        }
                    } elseif ( isset( $_POST['updateLeague'] ) && 'team' === $_POST['updateLeague'] ) {
                        // TODO: handle update of team captain/contact/match day/match time
                        if ( $league->is_championship ) {
                            $tab = 'preliminary';
                        }
                    }
                    $this->show_message();
                    // phpcs:enable WordPress.Security.NonceVerification.Missing

                    // check if league is a cup championship.
                    $cup = 'championship' === $league_mode;
                    // phpcs:disable WordPress.Security.NonceVerification.Recommended
                    $group     = isset( $_GET['group'] ) ? sanitize_text_field( wp_unslash( $_GET['group'] ) ) : '';
                    $team_id   = isset( $_GET['team_id'] ) ? intval( $_GET['team_id'] ) : false;
                    if ( isset( $_GET['match_day'] ) ) {
                        if ( -1 !== $_GET['match_day'] ) {
                            $match_day = intval( $_GET['match_day'] );
                            $league->set_match_day( $match_day );
                        }
                        $tab = 'matches';
                    } elseif ( 'current_match_day' === $league->match_display ) {
                        $league->set_match_day( 'current' );
                    } elseif ( 'all' === $league->match_display ) {
                        $league->set_match_day( -1 );
                    }
                    // phpcs:enable WordPress.Security.NonceVerification.Recommended
                    $options    = $racketmanager->options;
                    $match_args = array(
                        'final' => '',
                        'cache' => false,
                    );
                    if ( $season ) {
                        $match_args['season'] = $season;
                    }
                    if ( $group ) {
                        $match_args['group'] = $group;
                    }
                    if ( $team_id ) {
                        $match_args['team_id'] = $team_id;
                    }
                    if ( $league->num_matches_per_page > 0 ) {
                        $match_args['limit'] = $league->num_matches_per_page;
                    }
                    if ( empty( $league->event->get_seasons() ) ) {
                        $this->set_message( __( 'You need to add at least one season for the competition', 'racketmanager' ), true );
                        $this->show_message();
                    }
                    $teams = $league->get_league_teams(
                        array(
                            'season' => $season,
                            'cache'  => false,
                        )
                    );
                    $finals = array();
                    if ( 'championship' === $league_mode ) {
                        $finals = $this->present_league_finals( $league, (string) $season );
                    } else {
                        $matches = $this->prepare_league_matches( $league, $match_args, (string) $season, $group );
                    }
                    if ( isset( $_GET['match_paged'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        $tab = 'matches';
                    }
                    if ( isset( $_GET['standingstable'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        $get       = sanitize_text_field( wp_unslash( $_GET['standingstable'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        $match_day = false;
                        $mode      = 'all';
                        if ( preg_match( '/match_day-\d/', $get, $hits ) ) {
                            $res       = explode( '-', $hits[0] );
                            $match_day = $res[1];
                        } elseif ( in_array( $get, array( 'home', 'away' ), true ) ) {
                            $mode = htmlspecialchars( $get );
                        }
                        $teams = $league->get_standings( $teams, $match_day, $mode );
                    }
                    if ( isset( $_GET['match_day'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        $tab = 'matches';
                    }
                    require_once RACKETMANAGER_PATH . 'templates/admin/show-league.php';
                } else {
                    $this->set_message( __( 'League not found', 'racketmanager' ), true );
                    $this->show_message();
                }
            } else {
                $this->set_message( __( 'League id not found', 'racketmanager' ), true );
                $this->show_message();
            }
        }
    }
    /**
     * Display event page
     */
    public function display_event_page(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'edit_leagues' );
        if ( empty( $validator->error ) ) {
            $event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : null;
            $validator = $validator->event( $event_id );
        }
        if ( ! empty( $validator->error ) ) {
            if ( empty( $validator->msg ) ) {
                $this->set_message( $validator->err_msgs(), true );
            } else {
                $this->set_message( $validator->msg, true );
            }
        }
        if ( ! current_user_can( 'edit_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
            return;
        }
        $tab       = 'leagues';
        $event     = get_event( $event_id );
        $league_id = false;
        if ( isset( $_POST['addLeague'] ) || ( isset( $_POST['doActionLeague'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) ) {
            $result = $this->league_event_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_league_action_result( $result, $_GET ) ) {
                return;
            }
        } elseif ( isset( $_GET['edit_league'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $league_id    = intval( $_GET['edit_league'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $league_edit  = get_league( $league_id );
            $league_title = $league_edit->title;
        }
        $this->apply_flash_message();
        $this->show_message();
        if ( ! isset( $season ) ) {
            $event_season = $event->current_season['name'] ?? '';
            $season       = ( isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : $event_season );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }
        require_once RACKETMANAGER_PATH . 'templates/admin/event/show-leagues.php';
    }
    /**
     * Display constitution page
     */
    public function display_constitution_page(): void {
        global $racketmanager;
        if ( ! current_user_can( 'edit_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
        } elseif ( isset( $_GET['event_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $event_id     = intval( $_GET['event_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $event        = get_event( $event_id );
            $league_id    = false;
            $league_title = '';
            $season_id    = false;
            $season_data  = array(
                'name'           => '',
                'num_match_days' => '',
                'homeAndAway'    => '',
            );
            $club_id      = 0;
            if ( ( isset( $_POST['doActionConstitution'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) || isset( $_POST['saveConstitution'] ) || isset( $_POST['promoteRelegate'] ) || ( isset( $_POST['action'] ) && 'addTeamsToLeague' === $_POST['action'] ) || isset( $_POST['generate_matches'] ) ) {
                $result = $this->league_constitution_dispatcher->handle( $_GET, $_POST, $event );
                if ( $this->deliver_league_action_result( $result, $_GET ) ) {
                    return;
                }
            }
            $this->apply_flash_message();
            $this->show_message();
            if ( ! isset( $season ) ) {
                $event_season = $event->current_season['name'] ?? '';
                $season       = ( isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : $event_season );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }
            $seasons = $this->season_service->get_all_seasons();
            require_once RACKETMANAGER_PATH . 'templates/admin/league/show-constitution.php';
        }
    }
    /**
     * Display schedule page
     */
    public function display_schedule_page(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'edit_leagues' );
        if ( empty( $validator->error ) ) {
            $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
            $validator      = $validator->competition( $competition_id );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->err_msgs[0], true );
            }
        } else {
            $this->set_message( $validator->msg, true );
        }
        if ( ! empty( $validator->error ) ) {
            $this->show_message();
            return;
        }
        if ( isset( $_POST['scheduleAction'] ) ) {
            $result = $this->league_schedule_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_league_action_result( $result, $_GET ) ) {
                return;
            }
        }
        $this->apply_flash_message();
        $this->show_message();
        $competition_id = intval( $_GET['competition_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $competition    = $this->competition_service->get_competition( $competition_id );
        $league_id      = false;
        $league_title   = '';
        $season_id      = false;
        $season_data    = array(
            'name'           => '',
            'num_match_days' => '',
            'homeAndAway'    => '',
        );
        $club_id        = 0;
        if ( ! isset( $season ) ) {
            $event_season = $competition->current_season['name'] ?? '';
            $season       = ( isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : $event_season );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }
        require_once RACKETMANAGER_PATH . 'templates/admin/league/show-schedule.php';
    }
    /**
     * Action promotion and relegation function
     *
     * @return void
     */
    private function action_promotion_relegation(): void {
        $validator = new Validator();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'constitution-bulk' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->capability( 'edit_leagues' );
        }
        if (  ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        $teams = array();
        $valid = true;
        $js    = false;
        if ( isset( $_POST['js-active'] ) ) {
            $js = 1 === intval( $_POST['js-active'] );
        }
        $rank = 0;
        if ( isset( $_POST['table_id'] ) ) {
            $latest_season = isset( $_POST['latest_season'] ) ? sanitize_text_field( wp_unslash( $_POST['latest_season'] ) ) : null;
            // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : null;
            if ( $event_id ) {
                $event = get_event( $event_id );
                if ( $event ) {
                    foreach ( $_POST['table_id'] as $table_id ) {
                        $status = $_POST['status'][$table_id] ?? null;
                        if ( empty( $rank ) && $status ) {
                            $valid = false;
                        }
                        $team_id   = $_POST['team_id'][$table_id] ?? null;
                        $league_id = $_POST['league_id'][$table_id] ?? null;
                        $old_rank  = $_POST['old_rank'][$table_id] ?? null;
                        $status    = $_POST['status'][$table_id] ?? null;
                        if ( $js ) {
                            ++$rank;
                        } else {
                            $rank = $_POST['rank'][$table_id] ?? '';
                        }
                        $team            = new stdClass();
                        $team->team_id   = $team_id;
                        $team->league_id = $league_id;
                        $team->table_id  = $table_id;
                        $team->rank      = $rank;
                        $team->old_rank  = $old_rank;
                        $team->status    = $status;
                        $teams[]         = $team;
                    }
                }
                if ( $valid ) {
                    if ( $teams ) {
                        $result = $event->promote_and_relegate( $teams, $latest_season );
                        if ( $result ) {
                            $this->set_message( __( 'Promotion and relegation actioned', 'racketmanager' ) );
                        } else {
                            $this->set_message( __( 'Error with promotion and relegation', 'racketmanager' ), true );
                        }
                    }
                } else {
                    $this->set_message( __( 'Promotion and relegation has already occurred', 'racketmanager' ), true );
                }
            }
            // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
        }
    }
    public function display_matches_page(): void {
        $event_id  = null;
        $event     = null;
        $season    = null;
        $validator = new Validator();
        $validator = $validator->capability( 'edit_leagues' );
        if ( empty( $validator->error ) ) {
            $event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : null;
            $validator = $validator->event( $event_id );
            if ( empty( $validator->error ) ) {
                $event     = get_event( $event_id );
                $season    = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
                $validator = $validator->season_set( $season, $event->get_seasons() );
            }
        }
        if ( ! empty( $validator->error ) ) {
            if ( empty( $validator->msg ) ) {
                $this->set_message( $validator->err_msgs[0], true );
            } else {
                $this->set_message( $validator->msg, true );
            }
            $this->show_message();
            return;
        }
        $matches = $event->get_matches(
            array(
                'event_id' => $event->id,
                'season'   => $season,
                'orderby'  => array(
                    'match_day' => 'ASC',
                    'date'      => 'ASC',
                    'league_id' => 'ASC',
                    'home_team' => 'ASC',
                ),
            )
        );
        require_once RACKETMANAGER_PATH . 'templates/admin/event/show-matches.php';
    }
    /**
     * Display match editing page
     */
    public function display_match_page(): void {
        $league      = null;
        $max_matches = null;
        $match       = null;
        $final       = null;
        $team_array  = array();
        $num_first_round = null;
        $prev_round_name = null;
        $home_team       = null;
        $away_team       = null;
        if ( ! current_user_can( 'edit_matches' ) ) {
            $this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
        } else {
            //phpcs:disable WordPress.Security.NonceVerification.Recommended
            $is_finals       = false;
            $final_key        = false;
            $cup             = false;
            $single_cup_game = false;
            $group           = isset( $_GET['group'] ) ? sanitize_text_field( wp_unslash( $_GET['group'] ) ) : null;
            $class           = 'alternate';
            $bulk            = false;
            if ( isset( $_GET['league_id'] ) ) {
                $league_id = intval( $_GET['league_id'] );
                $league    = get_league( $league_id );
                // check if league is a cup championship.
                $cup = $league->event->competition->is_championship;
            }
            $season = $league->current_season['name'];

            // select first group if none is selected and league is cup championship.
            if ( $cup && empty( $group ) ) {
                $groups = ($league->groups ?? '');
                if ( ! is_array( $groups ) ) {
                    $groups = explode( ';', $groups );
                }
                $group = $groups[0] ?? '';
            }

            $matches = array();
            if ( isset( $_GET['edit'] ) ) {
                $match_id     = intval( $_GET['edit'] );
                $match        = $this->fixture_repository->find_by_id( $match_id );
                $mode         = 'edit';
                $edit         = true;
                $form_title   = __( 'Edit Match', 'racketmanager' );
                $submit_title = $form_title;
                if ( isset( $match->final_round ) && '' !== $match->final_round ) {
                    $cup             = true;
                    $single_cup_game = true;
                }
                $league_id  = $match->league_id;
                $matches[0] = $match;
                $match_day  = $match->match_day;
                $final_key   = $match->final_round ?? '';

                $max_matches = 1;
            } elseif ( isset( $_GET['match_day'] ) ) {
                $mode  = 'edit';
                $edit  = true;
                $bulk  = true;
                $order = false;

                $match_day = intval( $_GET['match_day'] );
                $season    = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : null;

                $match_args = array(
                    'match_day' => $match_day,
                    'season'    => $season,
                );
                if ( $cup ) {
                    $match_args['group'] = $group;
                }
                /* translators: $d: Match day */
                $form_title   = sprintf( __( 'Edit Matches - Match Day %d', 'racketmanager' ), $match_day );
                $submit_title = __( 'Edit Matches', 'racketmanager' );

                $matches     = $league->get_matches( $match_args );
                $max_matches = count( $matches );
            } elseif ( isset( $_GET['final'] ) ) {
                $is_finals = true;
                $final_key  = $league->championship->get_current_final_key();
                $mode      = isset( $_GET['mode'] ) ? sanitize_text_field( wp_unslash( $_GET['mode'] ) ) : null;
                $edit      = 'edit' === $mode;

                $final           = $league->championship->get_finals( $final_key );
                $num_first_round = $league->championship->num_teams_first_round;

                $max_matches = $final['num_matches'];

                if ( 'add' === $mode ) {
                    /* translators: %s: round name */
                    $form_title = sprintf( __( 'Add Matches - %s', 'racketmanager' ), Util::get_final_name( $final_key ) );
                    for ( $h = 0; $h < $max_matches; $h++ ) {
                        $matches[ $h ] = new stdClass();
                        if ( 'final' !== $final_key ) {
                            $round = $final['round'];
                            if ( $round & 1 ) {
                                $matches[ $h ]->host = 'home';
                            } else {
                                $matches[ $h ]->host = 'away';
                            }
                        }
                        $matches[ $h ]->hour    = $league->event->competition->settings['default_match_start_time']['hour'];
                        $matches[ $h ]->minutes = $league->event->competition->settings['default_match_start_time']['minutes'];
                    }
                } else {
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
                    $matches = $league->get_matches( $match_args );
                }
                $submit_title = $form_title;
            } else {
                $mode = 'add';
                $edit = false;
                $bulk = $cup;
                global $wpdb;

                // Get max match day.
                $search = $wpdb->prepare(
                    '`league_id` = %d AND `season`  = %s',
                    $league->id,
                    $season
                );
                if ( $cup ) {
                    $search .= $wpdb->prepare(
                        ' AND `group` = %s',
                        $group
                    );
                }
                $submit_title = __( 'Add Matches', 'racketmanager' );
                if ( $cup ) {
                    /* translators: %s: group name */
                    $form_title  = sprintf( __( 'Add Matches - Group %s', 'racketmanager' ), $group );
                    $max_matches = ceil( ( $league->num_teams / 2 ) * $season['num_match_days'] ); // set number of matches to add to half the number of teams per match day.
                } else {
                    $form_title  = $submit_title;
                    $max_matches = ceil( $league->num_teams_total ); // set number of matches to add to half the number of teams per match day.
                }
                $match_day        = 1;
                $matches[]        = new stdClass();
                $matches[0]->year = ( isset( $_GET['season'] ) && is_numeric( $_GET['season'] ) ) ? intval( $_GET['season'] ) : gmdate( 'Y' );
                for ( $i = 0; $i < $max_matches; $i++ ) {
                    $matches[]              = new stdClass();
                    $matches[ $i ]->hour    = $league->event->competition->settings['default_match_start_time']['hour'];
                    $matches[ $i ]->minutes = $league->event->competition->settings['default_match_start_time']['minutes'];
                }
            }

            if ( $single_cup_game ) {
                $final       = $league->championship->get_finals( $final_key );
                $final_teams = $league->championship->get_final_teams( $final['key'] );
                if ( is_numeric( $match->home_team ) ) {
                    $home_team = get_team( $match->home_team );
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
                    $away_team = get_team( $match->away_team );
                    $away_title = $away_team?->title;
                } else {
                    $away_team = $final_teams[ $match->away_team ];
                    if ( $away_team ) {
                        $away_title = $away_team->title;
                    } else {
                        $away_title = null;
                    }
                }
            } elseif ( $is_finals ) {
                $teams = $league->championship->get_final_teams( $final_key );
                if ( 'add' === $mode ) {
                    $round = $final['round'];
                    if ( 1 !== intval( $round ) ) {
                        $prev_round      = $final['round'] - 1;
                        $prev_round_name = $league->championship->get_final_keys( $prev_round );
                        $first_round     = false;
                        $home_team       = 1;
                        $away_team       = 2;
                    } else {
                        $first_round = true;
                        $team_array = match ($max_matches) {
                            1 => array(1),
                            2 => array(1, 3),
                            4 => array(1, 5, 3, 7),
                            8 => array(1, 9, 4, 12, 11, 14, 7, 15),
                            16 => array(1, 17, 9, 25, 4, 21, 13, 28, 6, 22, 14, 30, 7, 23, 15, 31),
                            32 => array(1, 33, 17, 49, 9, 41, 25, 57, 4, 36, 20, 52, 12, 44, 28, 60, 6, 38, 22, 54, 14, 46, 30, 62, 7, 39, 23, 55, 15, 47, 31, 63),
                            default => array(),
                        };
                    }
                    for ( $i = 0; $i < $max_matches; $i++ ) {
                        if ( $first_round ) {
                            $home_team      = $team_array[ $i ];
                            $home_team_name = $home_team . '_';
                            $away_team      = $num_first_round + 1 - $home_team;
                            $away_team_name = $away_team . '_';
                        } else {
                            $home_team_name = '1_' . $prev_round_name . '_' . $home_team;
                            $away_team_name = '1_' . $prev_round_name . '_' . $away_team;
                        }
                        $matches[ $i ]->home_team = $teams[ $home_team_name ]->id;
                        $matches[ $i ]->away_team = $teams[ $away_team_name ]->id;
                        if ( $first_round ) {
                            ++$home_team;
                            $away_team = $num_first_round + 1 - $home_team;
                        } else {
                            $home_team += 2;
                            $away_team += 2;
                        }
                    }
                }
            } else {
                $teams = $league->get_league_teams(
                    array(
                        'season'  => $season,
                        'orderby' => array( 'title' => 'ASC' ),
                    )
                );
            }
            //phpcs:enable WordPress.Security.NonceVerification.Recommended
            include_once RACKETMANAGER_PATH . 'templates/admin/includes/match.php';
        }
    }
    /**
     * Build league menu
     *
     * @return array
     */
    private function get_menu(): array {
        $league = get_league();
        $season = ( isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : $league->current_season ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $sport  = ( isset( $league->sport ) ? ( $league->sport ) : '' );

        $menu          = array();
        $menu['teams'] = array(
            'title'    => __( 'Add Teams', 'racketmanager' ),
            'callback' => array( &$this, 'display_teams_list' ),
            'cap'      => 'edit_teams',
            'show'     => true,
        );
        $menu['team']  = array(
            'title'    => __( 'Add Team', 'racketmanager' ),
            'callback' => array( &$this, 'display_team_page' ),
            'cap'      => 'edit_teams',
            'show'     => false,
        );
        $menu['match'] = array(
            'title'    => __( 'Add Matches', 'racketmanager' ),
            'callback' => array( &$this, 'display_match_page' ),
            'cap'      => 'edit_matches',
        );
        if ( $league->is_championship ) {
            $menu['match']['show'] = false;
            if ( $league->event->competition->is_player_entry && empty( $league->championship->is_consolation ) ) {
                $menu['team']['show'] = true;
            }
        } else {
            $menu['match']['show'] = true;
        }
        $menu['contact'] = array(
            'title'    => __( 'Contact', 'racketmanager' ),
            'callback' => array( $this, 'display_contact_page' ),
            'cap'      => 'edit_teams',
            'show'     => true,
        );
        $menu            = apply_filters( 'racketmanager_league_menu_' . $sport, $menu, $league->id, $season );
        return apply_filters( 'racketmanager_league_menu_' . $league->mode, $menu, $league->id, $season );
    }
    /**
     * Add league to event via admin
     */
    private function add_league_to_event(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'edit_leagues' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_add-league' );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            return;
        }
        $league_id   = isset( $_POST['league_id'] ) ? (int) $_POST['league_id'] : null;
        $event_id    = isset( $_POST['event_id'] ) ? (int) $_POST['event_id'] : null;
        $league_name = isset( $_POST['league_title'] ) ? sanitize_text_field( wp_unslash( $_POST['league_title'] ) ) : null;
        if ( empty( $league_id ) ) {
            try {
                $league = $this->league_service->add_league_to_event( $event_id, $league_name );
                $this->set_message( __( 'League added', 'racketmanager' ) );
            } catch ( League_Not_Found_Exception $e ) {
                $this->set_message( $e->getMessage(), true );
            }
        } else {
            $league = get_league( $league_id );
            if ( sanitize_text_field( wp_unslash( $_POST['league_title'] ) ) === $league->title ) {
                $this->set_message( $this->no_updates, 'warning' );
            } else {
                $league_title = isset( $_POST['league_title'] ) ? sanitize_text_field( wp_unslash( $_POST['league_title'] ) ) : null;
                $sequence     = isset( $_POST['sequence'] ) ? sanitize_text_field( wp_unslash( $_POST['sequence'] ) ) : null;
                $league->update( $league_title, $sequence );
                $this->set_message( __( 'League Updated', 'racketmanager' ) );
            }
        }
    }
    /**
     * Delete league(s) from event via admin
     */
    private function delete_leagues_from_event(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'del_leagues' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'leagues-bulk' );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            return;
        }
        $messages = array();
        if ( isset( $_POST['league'] ) ) {
            foreach ( $_POST['league'] as $league_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $league = get_league( $league_id );
                $league->delete();
                $messages[] = $league->title . ' ' . __( 'deleted', 'racketmanager' );
            }
            $message = implode( '<br>', $messages );
            $this->set_message( $message );
        }
    }
    /**
     * Save constitution for event via admin
     */
    protected function save_constitution(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'edit_leagues' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'constitution-bulk' );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            return;
        }
        $js = false;
        if ( isset( $_POST['js-active'] ) ) {
            $js = 1 === intval( $_POST['js-active'] );
        }
        $rank = 0;
        if ( isset( $_POST['table_id'] ) ) {
            $updates       = 0;
            $latest_season = isset( $_POST['latest_season'] ) ? sanitize_text_field( wp_unslash( $_POST['latest_season'] ) ) : null;
            // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            foreach ( $_POST['table_id'] as $table_id ) {
                $team      = $_POST['team_id'][ $table_id ] ?? null;
                $league_id = intval( $_POST['league_id'][ $table_id ] ) ?? null;
                if ( $js ) {
                    ++$rank;
                } else {
                    $rank = intval( $_POST['rank'][ $table_id ] ) ?? null;
                }
                $status  = $_POST['status'][ $table_id ] ?? null;
                $profile = $_POST['profile'][ $table_id ] ?? null;
                if ( isset( $_POST['constitutionAction'] ) && 'insert' === $_POST['constitutionAction'] ) {
                    $profile = '0';
                    $league  = get_league( $league_id );
                    $league?->add_team( $team, $latest_season, $rank, $status, $profile );
                } elseif ( isset( $_POST['constitutionAction'] ) && 'update' === $_POST['constitutionAction'] ) {
                    $league_team = get_league_team( $table_id );
                    if ( $league_id !== $league_team->league_id || $rank !== intval( $league_team->rank ) || $status !== $league_team->status || $profile !== $league_team->get_profile() ) {
                        $league_team?->set_constitution_rank( $league_id, $rank, $status, $profile );
                        ++$updates;
                    }
                    if ( $updates ) {
                        $this->set_message( __( 'Updated', 'racketmanager' ) );
                    } else {
                        $this->set_message( $this->no_updates, 'warning' );
                    }
                }
            }
            // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
        }
    }
    /**
     * Add teams(s) to constitution via admin
     */
    private function add_teams_to_constitution(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'edit_leagues' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_add-teams-bulk' );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            return;
        }
        $teams = isset( $_POST['team'] ) ? array_values( $_POST['team'] ) : array();
        if ( $teams ) {
            $messages = array();
            foreach ( $_POST['team'] as $team_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $rank    = '99';
                $status  = 'NT';
                $profile = '1';
                $league  = get_league( intval( $_POST['league_id'] ) );
                $league->add_team( $team_id, sanitize_text_field( wp_unslash( $_POST['season'] ) ), $rank, $status, $profile );
                $team       = get_team( $team_id );
                $messages[] = $team->title . ' ' . __( 'added', 'racketmanager' );
            }
            $message = implode( '<br>', $messages );
            $this->set_message( $message );
        } else {
            $this->set_message( __( 'No teams to add', 'racketmanager' ), 'warning' );
        }
    }

    /**
     * Delete teams(s) from constitution via admin
     */
    private function delete_constitution_teams(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'del_leagues' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'constitution-bulk' );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            return;
        }
        // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
        $messages = array();
        foreach ( $_POST['table'] as $table_id ) {
            $teams   = $_POST['team_id'] ?? array();
            $leagues = $_POST['league_id'] ?? array();
            $team    = $teams[$table_id] ?? 0;
            $league  = $leagues[$table_id] ?? 0;
            if ( isset( $team ) && isset( $league ) ) {
                $league = get_league( $league );
                $league->delete_team( $team, sanitize_text_field( wp_unslash( $_POST['latest_season'] ) ) );
                $messages[] = $team . ' ' . __( 'deleted', 'racketmanager' );
            }
        }
        $message = implode( '<br>', $messages );
        $this->set_message( $message );
        // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    }
    /**
     * Generate matches
     */
    private function generate_box_league_matches(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'edit_leagues' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'constitution-bulk' );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            return;
        }
        $event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : null;
        $season   = isset( $_POST['latest_season'] ) ? intval( $_POST['latest_season'] ) : null;
        if ( $event_id ) {
            if ( $season ) {
                $event = get_event( $event_id );
                $event->generate_box_league_matches();
                $this->set_message( __( 'Matches generated', 'racketmanager' ) );
            } else {
                $this->set_message( __( 'No season set', 'racketmanager' ), true );
            }
        } else {
            $this->set_message( __( 'No event set', 'racketmanager' ), true );
        }
    }

    /**
     * Fetch league matches and present them as preformatted view models for the admin league template.
     *
     * @param object $league     League entity.
     * @param array  $match_args Match query arguments.
     * @param string $season     Season name.
     * @param string $group      Group name.
     *
     * @return array
     */
    public function prepare_league_matches( object $league, array $match_args, string $season, string $group ): array {
        $match_args['reset_query_args'] = true;
        $matches                        = $league->get_matches( $match_args );
        $league->set_num_matches();

        return $this->resolve_league_matches_presenter()->present_collection( $matches, $league, $season, $group );
    }

    private function resolve_league_matches_presenter(): Admin_League_Matches_Presenter {
        $container = $this->racketmanager->container ?? null;
        if ( $container instanceof Simple_Container && $container->has( 'admin_league_matches_presenter' ) ) {
            return $container->get( 'admin_league_matches_presenter' );
        }

        return new Admin_League_Matches_Presenter( new Admin_Date_Presenter() );
    }
}
