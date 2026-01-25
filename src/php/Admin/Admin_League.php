<?php
/**
 * RacketManager-Admin API: RacketManager-admin-league class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-League
 */

namespace Racketmanager\Admin;

use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Util\Util;
use stdClass;
use function Racketmanager\get_competition;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_league_team;
use function Racketmanager\get_match;
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
        $competition_id = null;
        $competition    = null;
        $validator      = new Validator();
        $validator      = $validator->capability( 'edit_leagues' );
        if  ( empty( $validator->error ) ) {
            $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
            $validator      = $validator->competition( $competition_id );
            if ( empty( $validator->error ) ) {
                $competition = get_competition( $competition_id );
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
        if ( isset( $_POST['contactTeam'] ) ) {
            $this->contact_teams();
        }
        $this->show_message();
        //contactTeam
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
        $validator = new Validator();
        $validator = $validator->capability( 'edit_matches' );
        if (  ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['action'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_add_championship-matches' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
                $this->show_message();
                return;
            }
            $msg            = array();
            $valid          = true;
            $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $season         = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
            if ( $competition_id ) {
                $competition = get_competition( $competition_id );
                if ( $competition ) {
                    $current_season = $competition->get_season_by_name( $season );
                    if ( isset( $_POST['rounds'] ) ) {
                        $rounds = array();
                        foreach ( $_POST['rounds'] as $round ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                            if ( empty( $round['match_date'] ) ) {
                                /* translators: $s: $round number */
                                $msg[] = sprintf( __( 'Match date missing for round %s', 'racketmanager' ), $round['round'] );
                                $valid = false;
                            } elseif ( ! empty( $next_round_date ) && $round['match_date'] <= $next_round_date ) {
                                /* translators: $s: $round number */
                                $msg[] = sprintf( __( 'Match date for round %s after next round date', 'racketmanager' ), $round['round'] );
                                $valid = false;
                            } else {
                                $round_date      = $round['match_date'];
                                $rounds[]        = $round_date;
                                $next_round_date = $round_date;
                            }
                        }
                        if ( $valid ) {
                            $current_season['match_dates'] = array();
                            foreach ( $rounds as $match_date ) {
                                $current_season['match_dates'][] = $match_date;
                            }
                            $seasons            = $competition->get_seasons();
                            $seasons[ $season ] = $current_season;
                            $updates            = $competition->update_seasons( $seasons );
                            if ( $updates ) {
                                $match_dates = array();
                                $this->set_message( __( 'Match dates updated', 'racketmanager' ) );
                                $events = $this->competition_service->get_events_for_competition( $competition_id, $season );
                                foreach ( $events as $competition_event ) {
                                    $seasons = $competition_event->get_seasons();
                                    if ( empty( $competition_event->offset ) ) {
                                        $match_dates = $current_season['match_dates'];
                                    } else {
                                        $i = 0;
                                        foreach( $current_season['match_dates'] as $match_date ) {
                                            $match_dates[ $i ] = Util::amend_date( $match_date, $competition_event->offset, '+', 'week' );
                                            ++$i;
                                        }
                                    }
                                    $seasons[ $season ]['match_dates'] = $match_dates;
                                    $updates                           = $competition_event->update_seasons( $seasons );
                                }
                            } else {
                                $this->set_message( $this->no_updates, 'warning' );
                            }
                        } else {
                            $message = implode( '<br>', $msg );
                            $this->set_message( $message, true );
                        }
                    }
                }
            }
        } elseif ( isset( $_POST['rank'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_calculate_ratings' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
                $this->show_message();
                return;
            }
            $valid          = true;
            $competition_id = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
            $season         = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
            try {
                $this->competition_service->calculate_team_ratings( $competition_id, $season );
                $this->set_message( __( 'League ratings set', 'racketmanager' ) );
            } catch ( Competition_Not_Found_Exception| Season_Not_Found_Exception $e ) {
                $this->set_message( $e->getMessage(), false );
            }
        }
        $this->show_message();
        $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $validator      = $validator->competition( $competition_id );
        if ( empty( $validator->error ) ) {
            $competition = get_competition( $competition_id );
            $current_season = $competition->get_season_by_name( $season );
            require_once RACKETMANAGER_PATH . 'templates/admin/includes/setup.php';
        } else {
            $this->set_message( $validator->err_msg[0], true );
            $this->show_message();
        }
    }
    /**
     * Display event setup
     */
    public function display_setup_event_page(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'edit_matches' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['action'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_add_championship-matches' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
                $this->show_message();
                return;
            }
            $msg      = array();
            $valid    = true;
            $event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $season   = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
            if ( $event_id ) {
                $event = get_event( $event_id );
                if ( $event ) {
                    $current_season = $event->get_season_by_name( $season );
                    if ( isset( $_POST['rounds'] ) ) {
                        $rounds = array();
                        foreach ( $_POST['rounds'] as $round ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                            if ( empty( $round['match_date'] ) ) {
                                /* translators: $s: $round number */
                                $msg[] = sprintf( __( 'Match date missing for round %s', 'racketmanager' ), $round['round'] );
                                $valid = false;
                            } elseif ( ! empty( $next_round_date ) && $round['match_date'] <= $next_round_date ) {
                                /* translators: $s: $round number */
                                $msg[] = sprintf( __( 'Match date for round %s after next round date', 'racketmanager' ), $round['round'] );
                                $valid = false;
                            } else {
                                $round_date      = $round['match_date'];
                                $rounds[]        = $round_date;
                                $next_round_date = $round_date;
                            }
                        }
                        if ( $valid ) {
                            $current_season['match_dates'] = array();
                            foreach ( $rounds as $match_date ) {
                                $current_season['match_dates'][] = $match_date;
                            }
                            $seasons            = $event->get_seasons();
                            $seasons[ $season ] = $current_season;
                            $updates            = $event->update_seasons( $seasons );
                            if ( $updates ) {
                                $this->set_message( __( 'Match dates updated', 'racketmanager' ) );
                            } else {
                                $this->set_message( $this->no_updates, 'warning' );
                            }
                        } else {
                            $message = implode( '<br>', $msg );
                            $this->set_message( $message, true );
                        }
                        $this->show_message();
                    }
                }
            }
        }
        $season   = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( $event_id ) {
            $event = get_event( $event_id );
            if ( $event ) {
                $current_season = $event->get_season_by_name( $season );
                require_once RACKETMANAGER_PATH . 'templates/admin/includes/setup.php';
            }
        }
    }
    /**
     * Display league page
     */
    public function display_league_page(): void {
        global $league, $racketmanager;

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
                    $tab         = 'standings';
                    $match_day   = false;
                    // phpcs:disable WordPress.Security.NonceVerification.Missing
                    if ( isset( $_POST['doAction'] ) ) {
                        $this->handle_league_teams_action( $league );
                    } elseif ( isset( $_POST['delMatches'] ) ) {
                        $this->delete_matches_from_league();
                        $tab = 'matches';
                    } elseif ( isset( $_POST['updateLeague'] ) && 'team' === $_POST['updateLeague'] ) {
                        $this->league_manage_team( $league );
                        if ( $league->is_championship ) {
                            $tab = 'preliminary';
                        }
                    } elseif ( isset( $_POST['updateLeague'] ) && 'match' === $_POST['updateLeague'] ) {
                        $this->manage_matches_in_league( $league );
                    } elseif ( isset( $_POST['updateLeague'] ) && 'results' === $_POST['updateLeague'] ) {
                        $this->update_results_in_league();
                        $tab = 'matches';
                    } elseif ( isset( $_POST['updateLeague'] ) && 'teams_manual' === $_POST['updateLeague'] ) {
                        $this->league_manual_rank( $league );
                    } elseif ( isset( $_POST['action'] ) && 'addTeamsToLeague' === $_POST['action'] ) {
                        $this->league_add_teams( $league );
                        if ( $league->is_championship ) {
                            $tab = 'preliminary';
                        }
                    } elseif ( isset( $_POST['contactTeam'] ) ) {
                        $this->contact_teams();
                    } elseif ( isset( $_POST['saveRanking'] ) ) {
                        $this->rank_teams( $league, 'manual' );
                    } elseif ( isset( $_POST['randomRanking'] ) ) {
                        $this->rank_teams( $league, 'random' );
                    } elseif ( isset( $_POST['ratingPointsRanking'] ) ) {
                        $this->rank_teams( $league, 'ratings' );
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
                    if ( 'championship' !== $league_mode ) {
                        $match_args['reset_query_args'] = true;
                        $matches                        = $league->get_matches( $match_args );
                        $league->set_num_matches();
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
        if ( isset( $_POST['addLeague'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $this->add_league_to_event();
        } elseif ( isset( $_GET['edit_league'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $league_id    = intval( $_GET['edit_league'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $league_edit  = get_league( $league_id );
            $league_title = $league_edit->title;
        } elseif ( isset( $_POST['doActionLeague'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $this->delete_leagues_from_event();
        }
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
            if ( isset( $_POST['doActionConstitution'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $tab = 'constitution';
                $this->delete_constitution_teams();
                $this->show_message();
            } elseif ( isset( $_POST['saveConstitution'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $tab = 'constitution';
                $this->save_constitution();
                $this->show_message();
            } elseif ( isset( $_POST['promoteRelegate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $tab = 'constitution';
                $this->action_promotion_relegation();
                $this->show_message();
            } elseif ( isset( $_POST['action'] ) && 'addTeamsToLeague' === $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $tab = 'constitution';
                $this->add_teams_to_constitution();
                $this->show_message();
            } elseif ( isset( $_POST['generate_matches'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $tab = 'constitution';
                $this->generate_box_league_matches();
                $this->show_message();
            }
            if ( ! isset( $season ) ) {
                $event_season = $event->current_season['name'] ?? '';
                $season       = ( isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : $event_season );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }
            $seasons = $racketmanager->get_seasons( 'DESC' );
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
        if ( isset( $_POST['scheduleAction'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $tab = 'schedule';
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_schedule-matches' );
            if (  ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
                $this->show_message();
                return;
            }
            $action = isset( $_POST['actionSchedule'] ) ? sanitize_text_field( wp_unslash( $_POST['actionSchedule'] ) ) : null;
            switch ( $action ) {
                case 'schedule':
                    $events = wp_unslash( $_POST['event'] ) ?? array();
                    if ( $events ) {
                        $this->schedule_league_matches( $events );
                    }
                    break;
                case 'delete':
                    $events = wp_unslash( $_POST['event'] ) ?? array();
                    if ( $events ) {
                        foreach ( $events as $event_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                            $this->delete_event_matches( $event_id );
                        }
                    }
                    break;
                default:
                    $this->set_message( __( 'No action specified', 'raketmanager' ), 'warning' );
                    break;
            }
            $this->show_message();
        }
        $competition_id = intval( $_GET['competition_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $competition    = get_competition( $competition_id );
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
    /**
     * Delete matches for event
     *
     * @param int $event event to be deleted.
     *
     * @return boolean $success
     */
    private function delete_event_matches( int $event ): bool {
        global $racketmanager;
        $success     = true;
        $event       = get_event( $event );
        $season      = $event->get_season();
        $match_count = $racketmanager->get_matches(
            array(
                'count'    => true,
                'event_id' => $event->id,
                'season'   => $season,
                'time'     => 'latest',
            )
        );

        if ( $match_count ) {
            $this->set_message( __( 'Event has completed matches', 'racketmanager' ), true );
            $success = false;
        } else {
            $leagues = $event->get_leagues();
            foreach ( $leagues as $league ) {
                $matches = $league->get_matches( array( 'season' => $season ) );
                foreach ( $matches as $match ) {
                    $match = get_match( $match->id );
                    $match->delete();
                }
            }
            $this->set_message( __( 'Matches deleted', 'racketmanager' ) );
        }
        return $success;
    }
    /**
     * Schedule league matches
     *
     * @param array $events array of events to schedule matches for.
     *
     * @return void
     */
    protected function schedule_league_matches( array $events ): void {
        $validation = $this->validate_schedule( $events );
        if ( $validation->success ) {
            $max_teams    = $validation->num_rounds + 1;
            $default_refs = array();
            for ( $i = 1; $i <= $max_teams; $i++ ) {
                $default_refs[] = $i;
            }

            $i = 1;
            do {
                $result = $this->setup_teams_in_schedule( $events, $max_teams, $default_refs );
                ++$i;
            } while ( ! $result && $i < 20 );

            if ( $result ) {
                foreach ( $events as $event_id ) {
                    $event = get_event( $event_id );
                    foreach ( $event->get_leagues() as $league ) {
                        $league = get_league( $league );
                        $league->schedule_matches();
                    }
                }
                $this->set_message( __( 'Matches scheduled', 'racketmanager' ) );
            }
        }
    }

    /**
     * Validate schedule by team
     *
     * @param array $events array of events to validate schedule.
     *
     * @return object $validation
     */
    private function validate_schedule( array $events ): object {
        global $racketmanager, $wpdb;

        $validation          = new stdClass();
        $validation->success = true;
        $messages            = array();
        $c                   = 0;
        $num_match_days      = 0;
        $home_away           = '';
        $match_dates         = array();
        foreach ( $events as $event_id ) {
            $event       = get_event( $event_id );
            $season      = $event->get_season();
            $match_count = $racketmanager->get_matches(
                array(
                    'count'    => true,
                    'event_id' => $event->id,
                    'season'   => $season,
                )
            );
            if ( $match_count ) {
                $validation->success = false;
                /* translators: %1$s: event name %2$d season */
                $messages[] = sprintf( __( '%1$s already has matches scheduled for %2$d', 'racketmanager' ), $event->name, $season );
                break;
            } elseif ( empty( $c ) ) {
                $num_match_days = $event->current_season['num_match_days'];
                if ( ! isset( $event->current_season['match_dates'] ) ) {
                    $validation->success = false;
                    /* translators: %s: event name */
                    $messages[] = sprintf( __( 'Events match dates not set for %s', 'racketmanager' ), $event->name );
                } else {
                    $match_dates = $event->current_season['match_dates'];
                }
                $home_away = empty( $event->current_season['home_away'] ) ? false : $event->current_season['home_away'];
                if ( $home_away ) {
                    $validation->num_rounds = $num_match_days / 2;
                } else {
                    $validation->num_rounds = $num_match_days;
                }
            } else {
                if ( $event->current_season['num_match_days'] !== $num_match_days ) {
                    $validation->success = false;
                    $messages[]          = __( 'Events have different number of match days', 'racketmanager' );
                }
                if ( ! isset( $event->current_season['match_dates'] ) ) {
                    $validation->success = false;
                    /* translators: %s: event name */
                    $messages[] = sprintf( __( 'Events match dates not set for %s', 'racketmanager' ), $event->name );
                } elseif ( $event->current_season['match_dates'] !== $match_dates ) {
                    $validation->success = false;
                    $messages[]          = __( 'Events have different match dates', 'racketmanager' );
                }
                $home_away_new = empty( $event->current_season['home_away'] ) ? false : $event->current_season['home_away'];
                if ( $home_away_new !== $home_away ) {
                    $validation->success = false;
                    $messages[]          = __( 'Events have different home / away setting', 'racketmanager' );
                }
            }
            ++$c;
        }

        if ( $validation->success ) {
            $season                = $this->get_latest_season();
            $event_ids             = implode( ',', $events );
            $teams_missing_details = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    "SELECT `t`.`title` FROM $wpdb->racketmanager_teams t, $wpdb->racketmanager_league_teams t1, $wpdb->racketmanager l WHERE t.`id` = t1.`team_id` AND t1.`match_day` IS NULL AND l.`id` = t1.`league_id` AND l.`event_id` in (" . $event_ids . ') AND t1.`season` = %s',
                    $season
                )
            );
            if ( $teams_missing_details ) {
                $missing_teams = array();
                foreach ( $teams_missing_details as $team ) {
                    $missing_teams[] = $team->title;
                }
                $teams               = implode( ' and ', $missing_teams );
                $validation->success = false;
                /* translators: %s: teams with missing match days */
                $messages[] = sprintf( __( 'Missing match days for %s', 'racketmanager' ), $teams );
            }
        }
        $message = implode( '<br>', $messages );
        $this->set_message( $message, true );
        return $validation;
    }
    /**
     * Get latest season
     *
     * @return int
     */
    private function get_latest_season(): int {
        global $wpdb;

        return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            "SELECT MAX(name) FROM $wpdb->racketmanager_seasons"
        );
    }

    /**
     * Setup teams in schedule where necessary
     *
     * @param array $events array of events to schedule.
     * @param int $max_teams maximum numbers of teams in division.
     * @param array $default_refs default keys to use for scheduling.
     * @return boolean $validation->success
     */
    private function setup_teams_in_schedule(array $events, int $max_teams, array $default_refs ): bool {
        global $wpdb;
        $validation           = new stdClass();
        $validation->success  = true;
        $validation->messages = array();
        $season               = $this->get_latest_season();
        $event_ids            = implode( ',', $events );
        /* clear out schedule keys for this run */
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "UPDATE $wpdb->racketmanager_league_teams SET `group` = '' WHERE `season` = %s AND `league_id` IN (SELECT `id` FROM $wpdb->racketmanager WHERE `event_id` IN ($event_ids))",
                $season
            )
        );
        $validation = $this->handle_teams_in_same_division( $events, $season, $validation, $default_refs );
        if ( $validation->success ) {
            $validation = $this->handle_teams_with_same_match_time( $events, $season, $max_teams, $validation, $default_refs );
        }
        $message = implode( '<br>', $validation->messages );
        $this->set_message( $message, true );
        return $validation->success;
    }

    /**
     * Setup teams from the same club in a division.
     * These teams will play each other in the first round
     * Options are:
     *  1 - 6, 2 - 5, 3 - 4
     *  1 - 8, 2 - 7, 3 - 6, 4 - 5
     *  1 - 10, 2 - 9, 3 - 8, 4 - 7, 3 - 6, 4 - 5
     *
     * @param array $events array of events to schedule.
     * @param string $season season.
     * @param object $validation details of validation.
     * @param array $default_refs default keys to use for scheduling.
     *
     * @return object $validation
     */
    private function handle_teams_in_same_division( array $events, string $season, object $validation, array $default_refs ): object {
        global $wpdb;
        $alt_ref   = null;
        $event_ids = implode( ',', $events );
        /* set refs for those teams in the same division so they play first */
        $sql = $wpdb->prepare(
            "SELECT `t`.`club_id`, tbl.`league_id` FROM $wpdb->racketmanager_teams t, $wpdb->racketmanager l, $wpdb->racketmanager_league_teams tbl WHERE l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND l.`event_id` in (" . $event_ids . ') AND tbl.`season` = %s GROUP BY t.`club_id`, tbl.`league_id` HAVING COUNT(*) > 1',
            $season
        );
        $club_leagues = wp_cache_get( md5( $sql ), 'club_leagues' );
        if ( ! $club_leagues ) {
            $club_leagues = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
            wp_cache_set( md5( $sql ), $club_leagues, 'club_leagues' );
        }
        foreach ( $club_leagues as $club_league ) {
            $sql = $wpdb->prepare(
                "SELECT tbl.`id`, tbl.`team_id`, tbl.`league_id` FROM $wpdb->racketmanager_teams t, $wpdb->racketmanager l, $wpdb->racketmanager_league_teams tbl WHERE l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND l.`event_id` in (" . $event_ids . ') AND tbl.`season` = %s AND t.`club_id` = %d AND tbl.`league_id` = %d ORDER BY tbl.`team_id`',
                $season,
                $club_league->club_id,
                $club_league->league_id
            );
            $teams = wp_cache_get( md5( $sql ), 'club_leagues' );
            if ( ! $teams ) {
                $teams = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
                );
                wp_cache_set( md5( $sql ), $teams, 'club_leagues' );
            }
            $counter  = 1;
            $alt_refs = array();
            $refs     = array();
            $table1   = '';
            $league1  = '';
            $team1    = '';
            foreach ( $teams as $team ) {
                if ( $counter & 1 ) {
                    $team1    = $team->team_id;
                    $table1   = $team->id;
                    $league1  = $team->league_id;
                    $refs     = $default_refs;
                    $alt_refs = $refs;
                    $groups   = $this->get_table_groups( $league1, $season );
                    if ( $groups ) {
                        foreach ( $groups as $group ) {
                            $ref = array_search( intval( $group->value ), $refs, true );
                            array_splice( $refs, $ref, 1 );
                        }
                    }
                } else {
                    $team2   = $team->team_id;
                    $table2  = $team->id;
                    $league2 = $team->league_id;
                    $groups  = $this->get_table_groups( $league2, $season );
                    if ( $groups ) {
                        foreach ( $groups as $group ) {
                            $ref = array_search( intval( $group->value ), $alt_refs, true );
                            array_splice( $alt_refs, $ref, 1 );
                        }
                    }
                    if ( $refs ) {
                        $alt_found      = false;
                        $ref_option     = array( 2, 1, 3 );
                        $alt_ref_option = array( 5, 6, 4 );
                        for ( $i = 0; $i < 3; $i++ ) {
                            $ref_free = array_search( $ref_option[ $i ], $refs, true );
                            $ref      = $ref_option[ $i ];
                            if ( $ref_free ) {
                                $alt_ref   = $alt_ref_option[ $i ];
                                $alt_found = in_array($alt_ref, $alt_refs, true);
                                if ( false !== $alt_found ) {
                                    break;
                                }
                            }
                        }
                        if ( false !== $alt_found ) {
                            Util::set_table_group( $ref, $table1 );
                            Util::set_table_group( $alt_ref, $table2 );
                        } else {
                            $validation->success = false;
                            /* translators: %1$d: league %2$d team 1 %2$d team 2 */
                            $validation->messages[] = sprintf( __( 'Unable to schedule first round for league %1$d for team %2$d and team %3$d', 'racketmanager' ), $league1, $team1, $team2 );
                        }
                    } else {
                        $validation->success = false;
                        /* translators: %1$d: league %2$d team 1 %2$d team 2 */
                        $validation->messages[] = sprintf( __( 'Error in scheduling first round for league %1$d for team %2$d and team %3$d', 'racketmanager' ), $league1, $team1, $team2 );
                    }
                }
                ++$counter;
            }
        }
        return $validation;
    }

    /**
     * Setup teams from same club with same match time.
     * These teams will always play alternate home matches.
     * Options are:
     *  1 - 3, 2 - 4
     *  1 - 4, 2 - 5, 3 - 6
     *  1 - 5, 2 - 6, 3 - 7, 4 - 8
     *  1 - 6, 2 - 7, 3 - 8, 4 - 9, 5 - 10
     *
     * @param array $events array of events to schedule.
     * @param string $season season.
     * @param int $max_teams maximum number of teams in division.
     * @param object $validation details of validation.
     * @param array $default_refs default keys to use for scheduling.
     *
     * @return object $validation
     */
    private function handle_teams_with_same_match_time( array $events, string $season, int $max_teams, object $validation, array $default_refs ): object {
        global $wpdb;
        $event_ids = implode( ',', $events );
        /* find all clubs with multiple matches at the same time */
        $event_teams = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                "SELECT `t`.`club_id`, tbl.`match_day`, tbl.`match_time`, count(*) FROM $wpdb->racketmanager_teams t, $wpdb->racketmanager l, $wpdb->racketmanager_league_teams tbl WHERE l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND l.`event_id` in (" . $event_ids . ') AND tbl.`season` = %s AND tbl.`profile` != 3 GROUP BY t.`club_id`, tbl.`match_day`, tbl.`match_time` HAVING COUNT(*) > 1 ORDER BY count(*) DESC, RAND()',
                $season
            )
        );
        /* for each club / match time combination balance schedule so one team is home while the other is away */
        foreach ( $event_teams as $event_team ) {
            $teams = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    "SELECT tbl.`id`, tbl.`team_id`, tbl.`league_id`, tbl.`group` FROM $wpdb->racketmanager_teams t, $wpdb->racketmanager l, $wpdb->racketmanager_league_teams tbl WHERE l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND l.`event_id` in (" . $event_ids . ') AND tbl.`season` = %s AND t.`club_id` = %d AND tbl.`match_day` = %s AND tbl.`match_time` = %s AND tbl.`profile` != 3 ORDER BY tbl.`group`, tbl.`team_id`',
                    $season,
                    $event_team->club_id,
                    $event_team->match_day,
                    $event_team->match_time
                )
            );
            $counter  = 1;
            $refs     = array();
            $alt_refs = array();
            $table1   = 0;
            $team1    = 0;
            $league1  = 0;
            foreach ( $teams as $team ) {

                /* for first of pair */
                if ( $counter & 1 ) {
                    $team1    = $team->team_id;
                    $table1   = $team->id;
                    $league1  = $team->league_id;
                    $group1   = $team->group;
                    $refs     = $default_refs;
                    $alt_refs = $refs;
                    $groups   = $this->get_table_groups( $league1, $season );
                    if ( $groups ) {
                        foreach ( $groups as $group ) {
                            $ref = array_search( intval( $group->value ), $refs, true );
                            array_splice( $refs, $ref, 1 );
                        }
                    }
                } else {
                    /* for second of pair */
                    $table2  = $team->id;
                    $league2 = $team->league_id;
                    $group2  = $team->group;
                    $groups  = $this->get_table_groups( $league2, $season );
                    if ( $groups ) {
                        foreach ( $groups as $group ) {
                            $ref = array_search( intval( $group->value ), $alt_refs, true );
                            array_splice( $alt_refs, $ref, 1 );
                        }
                    }
                    if ( $refs ) {
                        if ( ! empty( $group1 ) ) {
                            $ref = $group1;
                            if ( ! empty( $group2 ) ) {
                                $alt_ref   = $group2;
                                $alt_found = true;
                            } else {
                                $alt_ref = $ref + $max_teams / 2;
                                if ( $alt_ref > $max_teams ) {
                                    $alt_ref = $alt_ref - $max_teams;
                                }
                                $alt_found = in_array(intval($ref), $refs, true);
                            }
                            if ( false !== $alt_found ) {
                                Util::set_table_group( $ref, $table1 );
                                Util::set_table_group( $alt_ref, $table2 );
                            } else {
                                $validation->success = false;
                                $league              = get_league( $league1 );
                                $team                = get_team( $team1 );
                                /* translators: %1$s: team name %2$s league name */
                                $validation->messages[] = sprintf( __( '1 - Error in scheduling %1$s in %2$s', 'racketmanager' ), $team->title, $league->title );
                            }
                        } else {
                            $ref_set = false;
                            if ( ! empty( $group2 ) ) {
                                $alt_ref = $group2;
                                $ref     = $alt_ref - $max_teams / 2;
                                if ( $ref < 1 ) {
                                    $ref = $ref + $max_teams;
                                }
                                $alt_found = in_array(intval($ref), $refs, true);
                                if ( false !== $alt_found ) {
                                    Util::set_table_group( $ref, $table1 );
                                    Util::set_table_group( $alt_ref, $table2 );
                                } else {
                                    $validation->success = false;
                                    $league              = get_league( $league1 );
                                    $team                = get_team( $team1 );
                                    /* translators: %1$s: team name %2$s league name */
                                    $validation->messages[] = sprintf( __( '4 - Error in scheduling %1$s in %2$s', 'racketmanager' ), $team->title, $league->title );
                                }
                            } else {
                                $count_refs = count( $refs );
                                for ( $i = 0; $i < $count_refs; $i++ ) {
                                    $ref     = $refs[ $i ];
                                    $alt_ref = $ref + $max_teams / 2;
                                    if ( $alt_ref > $max_teams ) {
                                        $alt_ref = $alt_ref - $max_teams;
                                    }
                                    $alt_found = in_array(intval($alt_ref), $alt_refs, true);
                                    if ( false !== $alt_found ) {
                                        $ref_set = true;
                                        Util::set_table_group( $ref, $table1 );
                                        Util::set_table_group( $alt_ref, $table2 );
                                        break;
                                    }
                                }
                                if ( ! $ref_set ) {
                                    $validation->success = false;
                                    $league              = get_league( $league1 );
                                    $team                = get_team( $team1 );
                                    /* translators: %1$s: team name %2$s league name */
                                    $validation->messages[] = sprintf( __( '2 - Error in scheduling %1$s in %2$s', 'racketmanager' ), $team->title, $league->title );
                                }
                            }
                        }
                    } else {
                        $validation->success = false;
                        $league              = get_league( $league1 );
                        $team                = get_team( $team1 );
                        /* translators: %1$s: team name %2$s league name */
                        $validation->messages[] = sprintf( __( '3 - Error in scheduling %1$s in %2$s', 'racketmanager' ), $team->title, $league->title );
                    }
                }
                ++$counter;
            }
        }
        return $validation;
    }


    /**
     * Set get table groups
     *
     * @param integer $league league.
     * @param integer $season season.
     *
     * @return array $groups table groups.
     */
    private function get_table_groups( int $league, int $season ): array {
        global $wpdb;

        return $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
        //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT `group` as `value` FROM $wpdb->racketmanager_league_teams WHERE `league_id` = $league AND `season` = $season AND `group` != ''"
        );
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
                $reset        = isset( $_GET['reset'] );
                $match_id     = intval( $_GET['edit'] );
                $match        = get_match( $match_id );
                $mode         = 'edit';
                $edit         = true;
                $form_title   = __( 'Edit Match', 'racketmanager' );
                $submit_title = $form_title;
                if ( $reset ) {
                    $match->reset_result();
                }
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
     * Delete matches from league in admin screen
     */
    private function delete_matches_from_league(): void {
        $validator = new Validator();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_matches-bulk' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->capability( 'del_matches' );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            return;
        }
        $messages = array();
        if ( isset( $_POST['match'] ) ) {
            foreach ( $_POST['match'] as $match_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $match = get_match( $match_id );
                $match->delete();
                /* translators: %d: Match id */
                $messages[] = ( sprintf( __( 'Match id %d deleted', 'racketmanager' ), $match_id ) );
                $message    = implode( '<br>', $messages );
                $this->set_message( $message );
            }
        }
    }
    /**
     * Add team to league in admin screen
     *
     * @param object $league league object.
     */
    private function league_manage_team( object $league ): void {
        $validator = new Validator();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_manage-teams' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->capability( 'edit_teams' );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            return;
        }
        if ( isset( $_POST['action'] ) && 'Add' === $_POST['action'] ) {
            $this->set_message( __( 'New team cannot be added to a league', 'racketmanager' ), true );
            return;
        }
        $team_id = isset( $_POST['team_id'] ) ? intval( $_POST['team_id'] ) : null;
        if ( $team_id ) {
            $team = get_team( intval( $_POST['team_id'] ) );
            if ( isset( $_POST['team'] ) && isset( $_POST['clubId'] ) && isset( $_POST['team_type'] ) ) {
                $team->update( sanitize_text_field( wp_unslash( $_POST['team'] ) ), intval( $_POST['clubId'] ), sanitize_text_field( wp_unslash( $_POST['team_type'] ) ) );
            }
        }
    }
    /**
     * Update results in league in admin screen
     */
    private function update_results_in_league(): void {
        global $league;
        $validator = new Validator();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_matches-bulk' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->capability( 'update_results' );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            return;
        }
        $custom      = $_POST['custom'] ?? array();
        $matches     = $_POST['matches'] ?? array();
        $home_points = $_POST['home_points'] ?? array();
        $away_points = $_POST['away_points'] ?? array();
        //phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $season = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
        if ( ! empty( $matches ) && ! empty( $home_points ) && ! empty( $away_points ) && ! empty( $season ) ) {
            $league->set_finals( false );
            $num_matches = $league->update_match_results( $matches, $home_points, $away_points, $custom, $season, false );
            /* translators: %d: number of matches updated */
            $this->set_message( sprintf( __( 'Updated Results of %d matches', 'racketmanager' ), $num_matches ) );
        }
    }
    /**
     * Rank teams in league after manually adjusting points in admin screen
     *
     * @param object $league league object.
     */
    private function league_manual_rank( object $league ): void {
        $validator = new Validator();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_teams-bulk' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->capability( 'update_results' );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            return;
        }
        $points = array();
        if ( isset( $_POST['points_plus'] ) && isset( $_POST['points_minus'] ) && isset( $_POST['add_points'] ) && isset( $_POST['num_done_matches'] ) && isset( $_POST['num_won_matches'] ) && isset( $_POST['num_draw_matches'] ) && isset( $_POST['num_lost_matches'] ) ) {
            $league = get_league( $league );
            //phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $points['points_plus']       = $_POST['points_plus'];
            $points['points_minus']      = $_POST['points_minus'];
            $points['add_points']        = $_POST['add_points'];
            $matches                     = array();
            $matches['num_done_matches'] = $_POST['num_done_matches'];
            $matches['num_won_matches']  = $_POST['num_won_matches'];
            $matches['num_draw_matches'] = $_POST['num_draw_matches'];
            $matches['num_lost_matches'] = $_POST['num_lost_matches'];
            if ( isset( $_POST['team_id'] ) && isset( $_POST['custom'] ) ) {
                $league->save_standings_manually( $_POST['team_id'], $points, $matches, $_POST['custom'] );
                $this->set_message( __( 'Standings Table updated', 'racketmanager' ) );
            }
            //phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
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
                    if ( $league_id !== $league_team->league_id || $rank !== intval( $league_team->rank ) || $status !== $league_team->status || $profile !== $league_team->profile ) {
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
}
