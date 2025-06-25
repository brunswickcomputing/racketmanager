<?php
/**
 * RacketManager-Admin API: RacketManager-admin-league class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-League
 */

namespace Racketmanager;

use stdClass;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration League panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class Admin_League extends RacketManager_Admin {
    private ?string $invalid_permissions;
    private ?string $invalid_security_token;

    /**
     * Constructor
     */
    public function __construct() {
        $this->invalid_permissions    = __( 'You do not have sufficient permissions to access this page', 'racketmanager' );
        $this->invalid_security_token = __( 'Security token invalid', 'racketmanager' );
    }
    /**
     * Display leagues page
     */
    public function display_leagues_page(): void {
        if ( ! current_user_can( 'edit_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->printMessage();
        } else {
            $competition_type  = 'league';
            $type              = '';
            $season            = '';
            $standalone        = true;
            $competition_query = array( 'type' => $competition_type );
            $page_title        = ucfirst( $competition_type ) . ' ' . __( 'Competitions', 'racketmanager' );
            require_once RACKETMANAGER_PATH . '/admin/show-competitions.php';
        }
    }
    /**
     * Display season list
     */
    public function display_seasons_page(): void {
        global $racketmanager;
        $competition = null;
        if ( isset( $_POST['doActionSeason'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $competition_id = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if ( $competition_id ) {
                $competition = get_competition( $competition_id );
                if ( $competition ) {
                    $this->delete_seasons_from_competition( $competition );
                } else {
                    $racketmanager->set_message( __( 'Competition not found', 'racketmanager' ), true );
                }
            } else {
                $racketmanager->set_message( __( 'Competition id not found', 'racketmanager' ), true );
            }
            $racketmanager->printMessage();
        } elseif ( isset( $_GET['competition_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $competition_id = intval( $_GET['competition_id'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $competition    = get_competition( $competition_id );
        }
        $racketmanager->printMessage();
        if ( $competition ) {
            require_once RACKETMANAGER_PATH . 'admin/includes/show-seasons.php';
        }
    }
    /**
     * Display season overview
     */
    public function display_overview_page(): void {
        if ( isset( $_GET['competition_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $competition_id = intval( $_GET['competition_id'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $competition    = get_competition( $competition_id );
            if ( $competition ) {
                $season = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                if ( $season ) {
                    if ( isset( $competition->seasons[ $season ] ) ) {
                        $competition->events          = $competition->get_events();
                        $tab                          = 'overview';
                        $current_season               = (object) $competition->seasons[ $season ];
                        if ( isset( $current_season->date_closing ) && $current_season->date_closing <= gmdate( 'Y-m-d' ) ) {
                            $current_season->is_active = true;
                        } else {
                            $current_season->is_active = false;
                        }
                        $current_season->is_open = false;
                        $current_season->entries = $competition->get_clubs( array( 'status' => 1 ) );
                        require_once RACKETMANAGER_PATH . 'admin/league/show-season.php';

                    }
                }
            }
        }
    }
    /**
     * Display setup
     */
    public function display_setup_page(): void {
        global $racketmanager;
        if ( ! current_user_can( 'edit_matches' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->printMessage();
        } else {
            if ( isset( $_POST['action'] ) ) {
                if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add_championship-matches' ) ) {
                    $this->set_message( $this->invalid_security_token, true );
                    $this->printMessage();
                } else {
                    $msg            = array();
                    $valid          = true;
                    $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $season         = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
                    if ( $competition_id ) {
                        $competition = get_competition( $competition_id );
                        if ( $competition ) {
                            $current_season = $competition->seasons[ $season ];
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
                                    $seasons            = $competition->seasons;
                                    $seasons[ $season ] = $current_season;
                                    $updates            = $competition->update_seasons( $seasons );
                                    if ( $updates ) {
                                        $match_dates = array();
                                        $this->set_message( __( 'Match dates updated', 'racketmanager' ) );
                                        $events = $competition->get_events();
                                        foreach ( $events as $competition_event ) {
                                            $seasons = $competition_event->seasons;
                                            if ( empty( $competition_event->offset ) ) {
                                                $match_dates = $current_season['match_dates'];
                                            } else {
                                                $i = 0;
                                                foreach( $current_season['match_dates'] as $match_date ) {
                                                    $match_dates[ $i ] = Racketmanager_Util::amend_date( $match_date, $competition_event->offset, '+', 'week' );
                                                    ++$i;
                                                }
                                            }
                                            $seasons[ $season ]['match_dates'] = $match_dates;
                                            $updates                           = $competition_event->update_seasons( $seasons );
                                        }
                                    } else {
                                        $this->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
                                    }
                                } else {
                                    $message = implode( '<br>', $msg );
                                    $this->set_message( $message, true );
                                }
                                $this->printMessage();
                            }
                        }
                    }
                }
            } elseif ( isset( $_POST['rank'] ) ) {
                if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_calculate_ratings' ) ) {
                    $this->set_message( $this->invalid_security_token, true );
                } else {
                    $valid          = true;
                    $competition_id = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
                    $season         = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
                    $competition    = get_competition( $competition_id );
                    if ( $competition && $season ) {
                        $racketmanager->calculate_team_ratings( $competition->id, $season );
                    }
                    $this->set_message( __( 'League ratings set', 'racketmanager' ) );
                }
                $this->printMessage();
            }
            $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
            $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( $competition_id ) {
                $competition = get_competition( $competition_id );
                if ( $competition ) {
                    $current_season = $competition->seasons[ $season ];
                    require_once RACKETMANAGER_PATH . 'admin/includes/setup.php';
                }
            }
        }
    }
    /**
     * Display event setup
     */
    public function display_setup_event_page(): void {
        if ( ! current_user_can( 'edit_matches' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->printMessage();
        } else {
            if ( isset( $_POST['action'] ) ) {
                if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add_championship-matches' ) ) {
                    $this->set_message( $this->invalid_security_token, true );
                    $this->printMessage();
                } else {
                    $msg      = array();
                    $valid    = true;
                    $event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $season   = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
                    if ( $event_id ) {
                        $event = get_event( $event_id );
                        if ( $event ) {
                            $current_season = $event->seasons[ $season ];
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
                                    $seasons            = $event->seasons;
                                    $seasons[ $season ] = $current_season;
                                    $updates            = $event->update_seasons( $seasons );
                                    if ( $updates ) {
                                        $this->set_message( __( 'Match dates updated', 'racketmanager' ) );
                                    } else {
                                        $this->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
                                    }
                                } else {
                                    $message = implode( '<br>', $msg );
                                    $this->set_message( $message, true );
                                }
                                $this->printMessage();
                            }
                        }
                    }
                }
            }
            $season   = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
            $event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( $event_id ) {
                $event = get_event( $event_id );
                if ( $event ) {
                    $current_season = $event->seasons[ $season ];
                    require_once RACKETMANAGER_PATH . 'admin/includes/setup.php';
                }
            }
        }
    }
    /**
     * Display league page
     */
    public function display_league_page(): void {
        global $league, $racketmanager;

        if ( ! current_user_can( 'view_leagues' ) ) {
            $racketmanager->set_message( $this->invalid_permissions, true );
            $racketmanager->printMessage();
        } else {
            $league_id = isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : null;
            if ( $league_id ) {
                $league = get_league( $league_id );
                if ( $league ) {
                    $league_id = $league->id;
                    $league->set_season();
                    $season      = $league->get_season();
                    $league_mode = ( isset( $league->event->competition->mode ) ? ( $league->event->competition->mode ) : '' );
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
                        $this->league_contact_teams();
                    } elseif ( isset( $_POST['saveRanking'] ) ) {
                        $this->league_manual_rank_teams( $league );
                    } elseif ( isset( $_POST['randomRanking'] ) ) {
                        $this->league_random_rank_teams( $league );
                    } elseif ( isset( $_POST['ratingPointsRanking'] ) ) {
                        $this->league_rating_points_rank_teams( $league );
                    }
                    $racketmanager->printMessage();
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
                    if ( empty( $league->event->seasons ) ) {
                        $this->set_message( __( 'You need to add at least one season for the competition', 'racketmanager' ), true );
                        $this->printMessage();
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
                    require_once RACKETMANAGER_PATH . '/admin/show-league.php';
                } else {
                    $racketmanager->set_message( __( 'League not found', 'racketmanager' ), true );
                    $racketmanager->printMessage();
                }
            } else {
                $racketmanager->set_message( __( 'League id not found', 'racketmanager' ), true );
                $racketmanager->printMessage();
            }
        }
    }
    /**
     * Display event page
     */
    public function display_event_page(): void {
        if ( ! current_user_can( 'edit_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->printMessage();
        } else {
            $tab = 'leagues';
            if ( isset( $_GET['event_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
                if ( isset( $_POST['addLeague'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                    $this->add_league_to_event();
                    $this->printMessage();
                } elseif ( isset( $_GET['edit_league'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $league_id    = intval( $_GET['edit_league'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $league_edit  = get_league( $league_id );
                    $league_title = $league_edit->title;
                } elseif ( isset( $_POST['doActionLeague'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                    $this->delete_leagues_from_event();
                    $this->printMessage();
                } elseif ( isset( $_POST['updateSettings'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                    $tab = 'settings';
                    $this->update_event_settings( $event );
                    $this->printMessage();
                }
                if ( ! isset( $season ) ) {
                    $event_season = $event->current_season['name'] ?? '';
                    $season       = ( isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : $event_season );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                }
                require_once RACKETMANAGER_PATH . 'admin/league/show-event.php';

            }
        }
    }
    /**
     * Display constitution page
     */
    public function display_constitution_page(): void {
        global $racketmanager;
        if ( ! current_user_can( 'edit_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->printMessage();
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
                $this->printMessage();
            } elseif ( isset( $_POST['saveConstitution'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $tab = 'constitution';
                $this->save_constitution();
                $this->printMessage();
            } elseif ( isset( $_POST['promoteRelegate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $tab = 'constitution';
                $this->action_promotion_relegation();
                $racketmanager->printMessage();
            } elseif ( isset( $_POST['action'] ) && 'addTeamsToLeague' === $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $tab = 'constitution';
                $this->add_teams_to_constitution();
                $this->printMessage();
            } elseif ( isset( $_POST['generate_matches'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $tab = 'constitution';
                $this->generate_box_league_matches();
                $this->printMessage();
            }
            if ( ! isset( $season ) ) {
                $event_season = $event->current_season['name'] ?? '';
                $season       = ( isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : $event_season );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }
            require_once RACKETMANAGER_PATH . 'admin/league/show-constitution.php';
        }
    }
    /**
     * Display schedule page
     */
    public function display_schedule_page(): void {
        global $racketmanager;
        if ( ! current_user_can( 'edit_leagues' ) ) {
            $racketmanager->set_message( $this->invalid_permissions, true );
            $racketmanager->printMessage();
        } elseif ( isset( $_POST['scheduleAction'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $tab = 'schedule';
            if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_schedule-matches' ) ) {
                $racketmanager->set_message( $this->invalid_security_token, true );
                $racketmanager->printMessage();
                return;
            }
            if ( isset( $_POST['actionSchedule'] ) ) {
                if ( 'schedule' === $_POST['actionSchedule'] ) {
                    $events = sanitize_text_field( $_POST['event'] ) ?? array();
                    if ( $events ) {
                        $this->schedule_league_matches( $events );
                    }
                } elseif ( 'delete' === $_POST['actionSchedule'] ) {
                    $events = sanitize_text_field( $_POST['event'] ) ?? array();
                    if ( $events ) {
                        foreach ( $events as $event_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                            $this->delete_event_matches( $event_id );
                        }
                    }
                }
                $racketmanager->printMessage();
            }
        }
        if ( isset( $_GET['competition_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
            require_once RACKETMANAGER_PATH . 'admin/league/show-schedule.php';
        }
    }
    /**
     * Action promotion and relegation function
     *
     * @return void
     */
    private function action_promotion_relegation(): void {
        global $racketmanager;
        if ( ! current_user_can( 'edit_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
        } elseif ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'constitution-bulk' ) ) {
            $this->set_message( $this->invalid_security_token, true );
        } else {
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
                                $racketmanager->set_message( __( 'Promotion and relegation actioned', 'racketmanager' ) );
                            } else {
                                $racketmanager->set_message( __( 'Error with promotion and relegation', 'racketmanager' ), true );
                            }
                        }
                    } else {
                        $racketmanager->set_message( __( 'Promotion and relegation has already occurred', 'racketmanager' ), true );
                    }
                }
                // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            }
        }
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
        global $wpdb;

        $validation          = new stdClass();
        $validation->success = true;
        $messages            = array();
        $c                   = 0;
        $num_match_days      = 0;
        $home_away           = '';
        foreach ( $events as $event_id ) {
            $event       = get_event( $event_id );
            $season      = $event->get_season();
            $match_count = $this->get_matches(
                array(
                    'count'    => true,
                    'event_id' => $event->id,
                    'season'   => $season,
                )
            );
            if ( 0 !== $match_count ) {
                $validation->success = false;
                /* translators: %1$s: event name %2$d season */
                $messages[] = sprintf( __( '%1$s already has matches scheduled for %2$d', 'racketmanager' ), $event->name, $season );
                break;
            } elseif ( 0 === $c ) {
                $num_match_days = $event->current_season['num_match_days'];
                if ( ! isset( $event->current_season['match_dates'] ) ) {
                    $validation->success = false;
                    /* translators: %s: event name */
                    $messages[] = sprintf( __( 'Events match dates not set for %s', 'racketmanager' ), $event->name );
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
            $season                = $this->getLatestSeason();
            $event_ids             = implode( ',', $events );
            $teams_missing_details = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    "SELECT `t`.`title`FROM $wpdb->racketmanager_teams t , $wpdb->racketmanager_team_events tc , $wpdb->racketmanager_table t1 , $wpdb->racketmanager l WHERE t.`id` = `tc`.`team_id` AND `tc`.`match_day` = '' AND `tc`.`event_id` in (" . $event_ids . ') AND l.`id` = `t1`.`league_id` AND `l`.`event_id` = tc.`event_id` AND `t1`.`season` = %s AND `t1`.`team_id` = `tc`.`team_id`',
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
        $season               = $this->getLatestSeason();
        $event_ids            = implode( ',', $events );
        /* clear out schedule keys for this run */
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "UPDATE $wpdb->racketmanager_table SET `group` = '' WHERE `season` = %s AND `league_id` IN (SELECT `id` FROM $wpdb->racketmanager WHERE `event_id` IN ($event_ids))",
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
            "SELECT `t`.`club_id`, tbl.`league_id` FROM $wpdb->racketmanager_team_events tc, $wpdb->racketmanager_teams t, $wpdb->racketmanager l, $wpdb->racketmanager_table tbl WHERE tc.`team_id` = t.`id` AND tc.`event_id` = l.`event_id` AND l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND tc.`event_id` in (" . $event_ids . ') AND tbl.`season` = %s GROUP BY t.`club_id`, tbl.`league_id` HAVING COUNT(*) > 1',
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
                "SELECT tbl.`id`, tbl.`team_id`, tbl.`league_id` FROM $wpdb->racketmanager_team_events tc, $wpdb->racketmanager_teams t, $wpdb->racketmanager l, $wpdb->racketmanager_table tbl WHERE tc.`team_id` = t.`id` AND tc.`event_id` = l.`event_id` AND l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND tc.`event_id` in (" . $event_ids . ') AND tbl.`season` = %s AND t.`club_id` = %d AND tbl.`league_id` = %d ORDER BY tbl.`team_id`',
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
                            $this->set_table_group( $ref, $table1 );
                            $this->set_table_group( $alt_ref, $table2 );
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
                "SELECT `t`.`club_id`, `tc`.`match_day`, `tc`.`match_time`, count(*) FROM $wpdb->racketmanager_team_events tc, $wpdb->racketmanager_teams t, $wpdb->racketmanager l, $wpdb->racketmanager_table tbl WHERE tc.`team_id` = t.`id` AND tc.`event_id` = l.`event_id` AND l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND tc.`event_id` in (" . $event_ids . ') AND tbl.`season` = %s AND tbl.`profile` != 3 GROUP BY t.`club_id`, tc.`match_day`, tc.`match_time` HAVING COUNT(*) > 1 ORDER BY count(*) DESC, RAND()',
                $season
            )
        );
        /* for each club / match time combination balance schedule so one team is home while the other is away */
        foreach ( $event_teams as $event_team ) {
            $teams = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    "SELECT tbl.`id`, tbl.`team_id`, tbl.`league_id`, tbl.`group` FROM $wpdb->racketmanager_team_events tc, $wpdb->racketmanager_teams t, $wpdb->racketmanager l, $wpdb->racketmanager_table tbl WHERE tc.`team_id` = t.`id` AND tc.`event_id` = l.`event_id` AND l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND tc.`event_id` in (" . $event_ids . ') AND tbl.`season` = %s AND t.`club_id` = %d AND tc.`match_day` = %s AND tc.`match_time` = %s AND tbl.`profile` != 3 ORDER BY tbl.`group`, tbl.`team_id`',
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
                                $this->set_table_group( $ref, $table1 );
                                $this->set_table_group( $alt_ref, $table2 );
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
                                    $this->set_table_group( $ref, $table1 );
                                    $this->set_table_group( $alt_ref, $table2 );
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
                                        $this->set_table_group( $ref, $table1 );
                                        $this->set_table_group( $alt_ref, $table2 );
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
     * Set table group
     *
     * @param string $group group.
     * @param integer $id id.
     */
    public function set_table_group( string $group, int $id ): void {
        global $wpdb;

        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_table SET `group` = %s WHERE `id` = %d",
                $group,
                $id
            )
        );
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
            "SELECT `group` as `value` FROM $wpdb->racketmanager_table WHERE `league_id` = $league AND `season` = $season AND `group` != ''"
        );
    }
}
