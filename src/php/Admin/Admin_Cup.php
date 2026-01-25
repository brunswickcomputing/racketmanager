<?php
/**
 * RacketManager-Admin API: RacketManager-admin-cup class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Cup
 */

namespace Racketmanager\Admin;

use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Updated_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Services\Validator\Validator_Plan;
use Racketmanager\Util\Util;
use function Racketmanager\get_club;
use function Racketmanager\get_competition;
use function Racketmanager\get_league;
use function Racketmanager\get_match;
use function Racketmanager\get_team;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration Cup panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class Admin_Cup extends Admin_Championship {
    /**
     * Function to handle administration cup displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        $this->admin_competition = new Admin_Competition( $this->racketmanager );
        $this->admin_club        = new Admin_Club( $this->racketmanager );
        $this->admin_event       = new Admin_Event( $this->racketmanager );
        if ( 'seasons' === $view ) {
            $this->display_seasons_page();
        } elseif ( 'modify' === $view ) {
            $this->admin_competition->display_season_modify_page();
        } elseif ( 'overview' === $view ) {
            $this->display_cup_overview_page();
        } elseif ( 'setup' === $view ) {
            $this->display_cup_setup_page();
        } elseif ( 'setup-event' === $view ) {
            $this->display_setup_event_page();
        } elseif ( 'draw' === $view ) {
            $this->display_cup_draw_page();
        } elseif ( 'matches' === $view ) {
            $this->display_cup_matches_page();
        } elseif ( 'match' === $view ) {
            $this->display_cup_match_page();
        } elseif ( 'plan' === $view ) {
            $this->display_cup_plan_page();
        } elseif ( 'teams' === $view ) {
            $this->display_teams_list();
        } elseif ( 'team' === $view ) {
            $this->admin_club->display_team_page();
        } elseif ( 'config' === $view ) {
            $this->admin_competition->display_config_page();
        } elseif ( 'event' === $view || 'event-config' === $view ) {
            $this->admin_event->display_config_page();
        } else {
            $this->display_cups_page();
        }
    }
    /**
     * Display cups page
     */
    public function display_cups_page(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'edit_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        $competition_type  = 'cup';
        $type              = '';
        $season            = '';
        $standalone        = true;
        $competition_query = array( 'type' => $competition_type );
        $page_title        = ucfirst( $competition_type ) . ' ' . __( 'Competitions', 'racketmanager' );
        include_once RACKETMANAGER_PATH . 'templates/admin/show-competitions.php';
    }

    /**
     * Display cup overview page
     *
     * @return void
     */
    public function display_cup_overview_page(): void {
        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
        $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        try {
            $competition = $this->competition_service->get_competition_by_season( $competition_id, $season );
        } catch ( Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
            return;
        }
        $competition_events = $this->competition_service->get_events_for_competition( $competition_id, $season );
        $i                   = 0;
        foreach ( $competition_events as $event ) {
            $leagues = $event->get_leagues();
            if ( $leagues ) {
                $competition_events[ $i ]->leagues = $leagues;
            }
            ++$i;
            $leagues = $event->get_leagues();
        }
        $tab        = 'overview';
        $cup_season = (object) $competition->get_season_by_name( $season );
        if ( isset( $cup_season->date_closing ) && $cup_season->date_closing <= gmdate( 'Y-m-d' ) ) {
            $cup_season->is_active = true;
        } else {
            $cup_season->is_active = false;
        }
        $cup_season->is_open    = false;
        $cup_season->venue_name = null;
        if ( isset( $cup_season->venue ) ) {
            try {
                $venue_club             = $this->club_service->get_club( $cup_season->venue );
                $cup_season->venue_name = $venue_club->shortcode;
            } catch ( Club_Not_Found_Exception ) {
                $cup_season->venue_name = null;
            }
        }
        $cup_season->entries = $this->competition_service->get_clubs_for_competition( $competition_id, $season );
        $competition_overview = $this->competition_service->get_competition_overview( $competition_id, $season );

        require_once RACKETMANAGER_PATH . 'templates/admin/cup/show-season.php';
    }
    /**
     * Display cup draw
     */
    public function display_cup_draw_page(): void {
        global $tab;
        //phpcs:disable WordPress.Security.NonceVerification.Recommended
        $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $league_id      = isset( $_GET['league'] ) ? intval( $_GET['league'] ) : null;
        $tab            = isset( $_GET['league-tab'] ) ? sanitize_text_field( wp_unslash( $_GET['league-tab'] ) ) : null;
        //phpcs:enable WordPress.Security.NonceVerification.Recommended
        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
            return;
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
                if ( $league->is_championship ) {
                    $tab = 'preliminary'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                }
            } elseif( empty( $tab) ) {
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
            require_once RACKETMANAGER_PATH . 'templates/admin/cup/draw.php';
        }
    }
    /**
     * Display cup setup
     */
    public function display_cup_setup_page(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'edit_matches' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->error, true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['action'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce','racketmanager_add_championship-matches');
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->error, true );
            } else {
                $valid          = true;
                $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $season         = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
                if ( $competition_id ) {
                    $competition = get_competition( $competition_id );
                    if ( $competition ) {
                        $cup_season = $competition->get_season_by_name( $season );
                        if ( isset( $_POST['rounds'] ) ) {
                            $msg    = array();
                            $rounds = array();
                            foreach ( $_POST['rounds'] as $round ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                                if ( empty( $round['match_date'] ) ) {
                                    /* translators: $s: $round number */
                                    $msg[] = sprintf( __( 'Match date missing for round %s', 'racketmanager' ), $round['round'] );
                                    $valid = false;
                                } elseif ( ! empty( $next_round_date ) && $round['match_date'] >= $next_round_date ) {
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
                                $cup_season['match_dates'] = array();
                                foreach ( array_reverse( $rounds ) as $match_date ) {
                                    $cup_season['match_dates'][] = $match_date;
                                }
                                $cup_seasons                  = $competition->get_seasons();
                                $cup_season['num_match_days'] = count( $cup_season['match_dates'] );
                                $cup_seasons[ $season ]       = $cup_season;
                                $competition->update_seasons( $cup_seasons );
                                $this->set_message( __( 'Cup match dates updated', 'racketmanager' ) );
                            } else {
                                $message = implode( '<br>', $msg );
                                $this->set_message( $message, true );
                            }
                            $this->show_message();
                        }
                    }
                }
            }
        } elseif ( isset( $_POST['rank'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce','racketmanager_calculate_ratings');
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->error, true );
            } else {
                $valid          = true;
                $competition_id = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
                $season         = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
                try {
                    $this->competition_service->calculate_team_ratings( $competition_id, $season);
                    $this->set_message( __( 'Cup ratings set', 'racketmanager' ) );
                } catch ( Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
                    $this->set_message( $e->getMessage(), true );
                }
            }
            $this->show_message();
        }
        $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( $competition_id ) {
            try {
                $competition = $this->competition_service->get_by_id( $competition_id );
            } catch ( Competition_Not_Found_Exception $e ) {
                $this->set_message( $e->getMessage(), true );
                $this->show_message();
                return;
            }
            $season_data = $competition->get_season_by_name( $season );
            $match_dates = $season_data['match_dates'];
            if ( empty( $match_dates ) ) {
                $date_end     = date_create( $season_data['date_end'] );
                $day_end      = date_format( $date_end, 'N' );
                $day_adjust   = $day_end - 1;
                $end_date     = Util::amend_date( $season_data['date_end'], $day_adjust, '-' );
                $round_length = $season_data['round_length'] ?? 7;
                $match_date   = null;
                $i            = 0;
                foreach( $competition->finals as $final ) {
                    $r = $final['round'] - 1;
                    if ( 0 === $i ) {
                        $match_date = $season_data['date_end'];
                    } elseif ( 1 === $i ) {
                        if ( $competition->settings['fixed_match_dates'] ) {
                            $match_date = Util::amend_date( $end_date, $round_length, '-' );
                        } else {
                            $match_date = Util::amend_date( $season_data['date_end'], 7 );
                        }
                    } elseif ( 0 === $r && $competition->settings['fixed_match_dates'] ) {
                        $match_date = $season_data['date_start'];
                    } else {
                        $match_date = Util::amend_date( $match_date, $round_length, '-' );
                    }
                    $match_dates[ $r ] = $match_date;
                    ++$i;
                }
            }
            require_once RACKETMANAGER_PATH . 'templates/admin/cup/setup.php';
        }
    }
    /**
     * Display event setup
     */
    public function display_setup_event_page(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'edit_matches' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->error, true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['action'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_add_championship-matches' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->error, true );
            } else {
                $valid     = true;
                $action    = sanitize_text_field( wp_unslash( $_POST['action'] ) );
                $league_id = isset( $_POST['league_id'] ) ? intval( $_POST['league_id'] ) : null;
                $season    = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
                $rounds    = $_POST['rounds'] ?? null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $league    = get_league( $league_id );
                if ( $league ) {
                    if ( $rounds ) {
                        $this->set_championship_matches( $league, $season, $rounds, $action );
                    } else {
                        $this->set_message( __( 'No rounds specified', 'racketmanager' ), true );
                    }
                }
            }
        } elseif ( isset( $_POST['rank'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce','racketmanager_calculate_ratings');
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->error, true );
            } else {
                $competition_id = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
                $season = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
                try {
                    $this->competition_service->calculate_team_ratings( $competition_id, $season);
                    $this->set_message( __( 'Cup ratings set', 'racketmanager' ) );
                } catch ( Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
                    $this->set_message( $e->getMessage(), true );
                }
            }
        }
        $this->show_message();
        $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
            return;
        }
        $league_id      = isset( $_GET['league'] ) ? intval( $_GET['league'] ) : null;
        //phpcs:enable WordPress.Security.NonceVerification.Recommended
        $league = get_league( $league_id );
        if ( $league ) {
            $match_count = $league->get_matches(
                array(
                    'count' => true,
                    'final' => 'all',
                )
            );
            $tab                = 'matches';
            $league_season      = $league->seasons[ $season ] ?? array();
            $event_season       = $league->event->get_season_by_name( $season );
            $competition_season = $competition->get_season_by_name( $season );
            if ( empty( $league_season['rounds'] ) ) {
                if ( empty( $event_season['match_dates'] ) ) {
                    $match_dates = array();
                    if ( empty( $league->event->offset ) ) {
                        $match_dates = $competition_season['match_dates'] ?? array();
                    } elseif( isset( $competition_season['match_dates'] ) && is_array( $competition_season['match_dates'] ) ) {
                        $i = 0;
                        $num_match_dates = count( $competition_season['match_dates'] );
                        foreach( $competition_season['match_dates'] as $match_date ) {
                            if ( $i === $num_match_dates - 1 ) {
                                $match_dates[ $i ] = $match_date;
                            } else {
                                $match_dates[ $i ] = Util::amend_date( $match_date, $league->event->offset, '+', 'week' );
                            }
                            ++$i;
                        }
                    }
                } else {
                    $match_dates = $event_season['match_dates'];
                }
            } else {
                foreach ( array_reverse( $league_season['rounds'] ) as $round ) {
                    $match_dates[] = $round->date;
                }
            }
            require_once RACKETMANAGER_PATH . 'templates/admin/cup/setup.php';
        }
    }
    /**
     * Display cup plan page
     */
    public function display_cup_plan_page(): void {
        $validator = new Validator_Plan();
        $validator = $validator->capability( 'edit_teams' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->error, true );
            $this->show_message();
            return;
        }
        $competition = null;
        $season      = null;
        if ( isset( $_POST['savePlan'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_cup-planner-nonce', 'racketmanager_cup-planner' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->error, true );
                $this->show_message();
                return;
            }
            $competition_id = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
            $season         = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
            $courts         = $_POST['court'] ?? null;
            $start_time     = $_POST['startTime'] ?? null;
            $matches        = $_POST['match'] ?? null;
            $match_time     = $_POST['matchtime'] ?? null;
            try {
                $updates = $this->competition_service->save_plan( $competition_id, $season, $courts, $start_time, $matches, $match_time );
                if ( $updates ) {
                    $this->set_message( __( 'Plan saved', 'racketmanager' ) );
                } else {
                    $this->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
                }
            } catch ( Competition_Not_Updated_Exception $e ) {
                $this->set_message( $e->getMessage(), 'warning' );
            } catch ( Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
                $this->set_message( $e->getMessage(), true );
            }
            $tab = 'matches';
        } elseif ( isset( $_POST['resetPlan'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_cup-planner-nonce', 'racketmanager_cup-planner' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->error, true );
                $this->show_message();
                return;
            }
            $competition_id = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
            $season         = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
            $matches        = $_POST['match'] ?? null;
            try {
                $updates = $this->competition_service->reset_plan( $competition_id, $season, $matches );
                if ( $updates ) {
                    $this->set_message( __( 'Plan reset', 'racketmanager' ) );
                } else {
                    $this->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
                }
            } catch ( Competition_Not_Updated_Exception $e ) {
                $this->set_message( $e->getMessage(), 'warning' );
            } catch ( Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
                $this->set_message( $e->getMessage(), true );
            }
            $tab = 'matches';
        } elseif ( isset( $_POST['saveCup'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_cup-nonce', 'racketmanager_cup' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->error, true );
                $this->show_message();
                return;
            }
            $competition_id = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
            $season         = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
            $start_time     = isset( $_POST['startTime'] ) ? sanitize_text_field( wp_unslash( $_POST['startTime'] ) ) : null;
            $num_courts     = isset( $_POST['numCourtsAvailable'] ) ? intval( $_POST['numCourtsAvailable'] ) : null;
            $time_increment = isset( $_POST['timeIncrement'] ) ? sanitize_text_field( wp_unslash( $_POST['timeIncrement'] ) ) : null;
            try {
                $updates = $this->competition_service->set_plan_config( $competition_id, $season, $start_time, $num_courts, $time_increment );
                if ( is_wp_error( $updates ) ) {
                    $validator->error    = true;
                    $validator->err_flds = $updates->get_error_codes();
                    $validator->err_msgs = $updates->get_error_messages();
                    $this->set_message( __( 'Error with plan details', 'racketmanager' ), true );
                } elseif( $updates) {
                    $this->set_message( __( 'Plan updated', 'racketmanager' ) );
                } else {
                    $this->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
                }
            } catch ( Competition_Not_Updated_Exception $e ) {
                $this->set_message( $e->getMessage(), 'warning' );
            } catch ( Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
                $this->set_message( $e->getMessage(), true );
            }
            $tab = 'config';
        }
        $this->show_message();

        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
        $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        try {
            $competition = $this->competition_service->get_competition_by_season( $competition_id, $season );
        } catch ( Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
            return;
        }
        $competition_events     = $this->competition_service->get_events_for_competition( $competition_id, $season );
        $num_events             = count( $competition_events );
        $cup_season             = (object) $competition->get_season_by_name( $season );
        $cup_season->venue_name = null;
        if ( isset( $cup_season->venue ) ) {
            $venue_club = get_club( $cup_season->venue );
            if ( $venue_club ) {
                $cup_season->venue_name = $venue_club->shortcode;
            }
        }
        if ( ! isset( $cup_season->orderofplay ) ) {
            $cup_season->orderofplay = array();
        }
        if ( ! isset( $cup_season->time_increment ) ) {
            $cup_season->time_increment = null;
        }
        if ( ! isset( $cup_season->num_courts ) ) {
            $cup_season->num_courts = null;
        }
        if ( ! isset( $cup_season->starttime ) ) {
            $cup_season->starttime = null;
        }
        $final_matches = $competition->get_matches(
            array(
                'season'         => $season,
                'final'          => 'final',
            )
        );
        if ( empty( $tab ) ) {
            $tab = 'matches';
        }
        require_once RACKETMANAGER_PATH . 'templates/admin/cup/plan.php';
    }
    /**
     * Display cup matches page
     */
    public function display_cup_matches_page(): void {
        global $competition;
        if ( ! current_user_can( 'edit_matches' ) ) {
            $this->set_message( $this->no_permission, true );
            $this->show_message();
        } else {
            //phpcs:disable WordPress.Security.NonceVerification.Recommended
            $final_key       = isset( $_GET['final'] ) ? intval( $_GET['final'] ) : null;
            $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
            $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
            $league_id      = isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : null;
            $final_key       = isset( $_GET['final'] ) ? sanitize_text_field( wp_unslash( $_GET['final'] ) ) : null;
            //phpcs:enable WordPress.Security.NonceVerification.Recommended
            if ( $competition_id ) {
                $competition = get_competition( $competition_id );
                if ( $competition && $league_id ) {
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
            }
        }
    }
    /**
     * Display cup match page
     */
    public function display_cup_match_page(): void {
        if ( ! current_user_can( 'edit_matches' ) ) {
            $this->set_message( $this->no_permission, true );
            $this->show_message();
        } else {
            //phpcs:disable WordPress.Security.NonceVerification.Recommended
            $final_key       = isset( $_GET['final'] ) ? intval( $_GET['final'] ) : null;
            $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
            $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
            $league_id      = isset( $_GET['league'] ) ? intval( $_GET['league'] ) : null;
            $final_key       = isset( $_GET['final'] ) ? sanitize_text_field( wp_unslash( $_GET['final'] ) ) : null;
            $match_id       = isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : null;
            //phpcs:enable WordPress.Security.NonceVerification.Recommended
            if ( $competition_id ) {
                $competition = get_competition( $competition_id );
                if ( $competition ) {
                    $is_finals = true;
                    if ( $league_id ) {
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
                                require_once RACKETMANAGER_PATH . 'templates/admin/includes/match.php';
                            }
                        }
                    }
                }
            }
        }
    }
}
