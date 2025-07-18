<?php
/**
 * RacketManager-Admin API: RacketManager-admin-tournament class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Tournament
 */

namespace Racketmanager;

use stdClass;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class RacketManager_Admin_Tournament extends RacketManager_Admin {

    /**
     * Constructor
     */
    public function __construct() {
    }

    /**
     * Display tournaments page
     */
    public function display_tournaments_page(): void {
        global $racketmanager;
        if ( ! current_user_can( 'edit_leagues' ) ) {
            $this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
            $this->printMessage();
        } else {
            $age_group_select   = isset( $_GET['age_group'] ) ? sanitize_text_field( wp_unslash( $_GET['age_group'] ) ) : '';
            $season_select      = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : '';
            $competition_select = isset( $_GET['competition'] ) ? intval( $_GET['competition'] ) : '';
            if ( isset( $_POST['doTournamentDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
                if ( ! current_user_can( 'del_teams' ) ) {
                    $this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
                } else {
                    check_admin_referer( 'tournaments-bulk' );
                    $tournaments = $_POST['tournament'] ?? array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    foreach ( $tournaments as $tournament_id ) {
                        $tournament = get_tournament( $tournament_id );
                        $tournament->delete();
                    }
                }
                $racketmanager->printMessage();
            }
            $club_id = 0;
            $racketmanager->printMessage();
            $clubs       = $this->get_clubs();
            $tournaments = $this->get_tournaments(
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
            include_once RACKETMANAGER_PATH . '/admin/show-tournaments.php';
        }
    }

    /**
     * Display tournament overview
     */
    public function display_tournament_overview_page(): void {
        global $racketmanager;
        if ( isset( $_GET['tournament'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( isset( $_POST['contactTeam'] ) || isset( $_POST['contactTeamActive'] ) ) {
                $this->contact_teams();
                $racketmanager->printMessage();
            }
            $tournament_id = intval( $_GET['tournament'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $tournament    = get_tournament( $tournament_id );
            if ( $tournament ) {
                $tournament->events = $tournament->get_events();
                $i                  = 0;
                foreach ( $tournament->events as $event ) {
                    $leagues = $event->get_leagues();
                    if ( $leagues ) {
                        $tournament->events[ $i ]->leagues = $leagues;
                    }
                    ++ $i;
                }
                $tournament->num_entries = $tournament->get_entries( array( 'count' => true ) );
                $tab                     = 'overview';
                $entries_confirmed       = $tournament->get_entries(
                    array(
                        'status' => 'confirmed',
                    )
                );
                $confirmed_entries       = Racketmanager_Util::get_players_list( $entries_confirmed );
                $entries_pay_due         = $tournament->get_entries(
                    array(
                        'status' => 'unpaid',
                    )
                );
                $pay_due_entries         = Racketmanager_Util::get_players_list( $entries_pay_due );
                $entries_pending         = $tournament->get_entries(
                    array(
                        'status' => 'pending',
                    )
                );
                $pending_entries         = Racketmanager_Util::get_players_list( $entries_pending );
                $entries_withdrawn       = $tournament->get_entries(
                    array(
                        'status' => 'withdrawn',
                    )
                );
                $withdrawn_entries       = Racketmanager_Util::get_players_list( $entries_withdrawn );
                require RACKETMANAGER_PATH . 'admin/show-tournament.php';
            }
        }
    }

    /**
     * Display tournament draw
     */
    public function display_tournament_draw_page(): void {
        global $tab, $racketmanager;
        //phpcs:disable WordPress.Security.NonceVerification.Recommended
        $season        = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
        $league_id     = isset( $_GET['league'] ) ? intval( $_GET['league'] ) : null;
        //phpcs:enable WordPress.Security.NonceVerification.Recommended
        if ( $tournament_id ) {
            $tournament = get_tournament( $tournament_id );
            if ( $tournament ) {
                if ( $league_id ) {
                    $league = get_league( $league_id );
                    if ( $league ) {
                        $this->handle_league_teams_action( $league );
                        if ( isset( $_POST['updateLeague'] ) && 'match' === $_POST['updateLeague'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                            $this->manage_matches_in_league( $league );
                            $racketmanager->printMessage();
                            $tab = 'matches'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                        } elseif ( isset( $_POST['action'] ) && 'addTeamsToLeague' === $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                            $this->league_add_teams( $league );
                            $tab = 'preliminary'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                            $racketmanager->printMessage();
                        } elseif ( isset( $_POST['updateLeague'] ) && 'teamPlayer' === $_POST['updateLeague'] ) {
                            $this->edit_player_team( $league );
                            $tab = 'preliminary';
                        } else {
                            $tab = $league->championship->handle_admin_page( $league ); //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                            if ( isset( $_POST['saveRanking'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                                $this->league_manual_rank_teams( $league );
                                $racketmanager->printMessage();
                                $tab = 'preliminary'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                            } elseif ( isset( $_POST['randomRanking'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                                $this->league_random_rank_teams( $league );
                                $racketmanager->printMessage();
                                $tab = 'preliminary'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                            } elseif ( isset( $_POST['ratingPointsRanking'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                                $this->league_rating_points_rank_teams( $league );
                                $tab = 'preliminary'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                            } elseif ( empty( $tab ) ) {
                                $tab = 'finalResults'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                            }
                        }
                        require RACKETMANAGER_PATH . 'admin/tournament/draw.php';
                    }
                }
            }
        }
    }

    /**
     * Display tournament setup
     */
    public function display_tournament_setup_page(): void {
        if ( ! current_user_can( 'edit_matches' ) ) {
            $this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
            $this->printMessage();
        } else {
            if ( isset( $_POST['action'] ) ) {
                if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add_championship-matches' ) ) {
                    $this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
                    $this->printMessage();
                } else {
                    $valid         = true;
                    $tournament_id = isset( $_POST['tournament_id'] ) ? intval( $_POST['tournament_id'] ) : null;
                    $season        = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
                    $tournament    = get_tournament( $tournament_id );
                    if ( $tournament ) {
                        $tournament_season = $tournament->competition->seasons[ $season ];
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
                                $tournament_season['match_dates'] = array();
                                foreach ( array_reverse( $rounds ) as $match_date ) {
                                    $tournament_season['match_dates'][] = $match_date;
                                }
                                $tournament_season['num_match_days'] = count( $tournament_season['match_dates'] );
                                $competition                         = get_competition( $tournament->competition->id );
                                if ( $competition ) {
                                    $tournament_seasons            = $competition->seasons;
                                    $tournament_seasons[ $season ] = $tournament_season;
                                    $competition->update_seasons( $tournament_seasons );
                                }
                                $this->set_message( __( 'Tournament match dates updated', 'racketmanager' ) );
                            } else {
                                $message = implode( '<br>', $msg );
                                $this->set_message( $message, true );
                            }
                            $this->printMessage();
                        }
                    }
                }
            } elseif ( isset( $_POST['rank'] ) ) {
                if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_calculate_ratings' ) ) {
                    $this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
                } else {
                    $valid         = true;
                    $tournament_id = isset( $_POST['tournament_id'] ) ? intval( $_POST['tournament_id'] ) : null;
                    $this->calculate_player_team_ratings( $tournament_id );
                    $this->set_message( __( 'Tournament ratings set', 'racketmanager' ) );
                }
                $this->printMessage();
            }
            $season        = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
            $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
            //phpcs:enable WordPress.Security.NonceVerification.Recommended
            if ( $tournament_id ) {
                $tournament = get_tournament( $tournament_id );
                if ( $tournament ) {
                    $match_dates = $tournament->competition->seasons[ $season ]['match_dates'] ?? array();
                    if ( empty( $match_dates ) ) {
                        $match_date   = null;
                        $round_length = $tournament->competition->round_length ?? 7;
                        $i            = 0;
                        foreach ( $tournament->finals as $final ) {
                            $r = $final['round'] - 1;
                            if ( 0 === $i ) {
                                $match_date = $tournament->date;
                            } elseif ( 1 === $i ) {
                                $match_date = Racketmanager_Util::amend_date( $tournament->date, 7, '-' );
                            } else {
                                $match_date = Racketmanager_Util::amend_date( $match_date, $round_length, '-' );
                            }
                            $match_dates[ $r ] = $match_date;
                            ++ $i;
                        }
                    }
                    require RACKETMANAGER_PATH . 'admin/tournament/setup.php';
                }
            }
        }
    }

    /**
     * Display event setup
     */
    public function display_setup_event_page(): void {
        if ( ! current_user_can( 'edit_matches' ) ) {
            $this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
            $this->printMessage();
        } else {
            if ( isset( $_POST['action'] ) ) {
                if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add_championship-matches' ) ) {
                    $this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
                    $this->printMessage();
                } else {
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
            }
            $season        = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
            $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
            $league_id     = isset( $_GET['league'] ) ? intval( $_GET['league'] ) : null;
            //phpcs:enable WordPress.Security.NonceVerification.Recommended
            if ( $tournament_id ) {
                $tournament = get_tournament( $tournament_id );
                if ( $tournament ) {
                    if ( $league_id ) {
                        $league = get_league( $league_id );
                        if ( $league ) {
                            $match_count = $league->get_matches(
                                array(
                                    'count' => true,
                                    'final' => 'all',
                                )
                            );
                            $tab         = 'matches';
                            $match_dates = empty( $league->event->seasons[ $season ]['match_dates'] ) ? $league->event->competition->seasons[ $season ]['match_dates'] : $league->event->seasons[ $season ]['match_dates'];
                            require RACKETMANAGER_PATH . 'admin/tournament/setup.php';
                        }
                    }
                }
            }
        }
    }

    /**
     * Display tournament page
     */
    public function displayTournamentPage(): void {
        global $racketmanager;
        if ( ! current_user_can( 'edit_teams' ) ) {
            $this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
            $this->printMessage();
        } elseif ( isset( $_POST['addTournament'] ) ) {
            if ( ! current_user_can( 'edit_teams' ) ) {
                $racketmanager->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
            } else {
                check_admin_referer( 'racketmanager_add-tournament' );
                $tournament                   = new stdClass();
                $tournament->name             = isset( $_POST['tournamentName'] ) ? sanitize_text_field( wp_unslash( $_POST['tournamentName'] ) ) : null;
                $tournament->competition_id   = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
                $tournament->season           = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
                $tournament->venue            = isset( $_POST['venue'] ) ? intval( $_POST['venue'] ) : null;
                $tournament->date_open        = isset( $_POST['dateOpen'] ) ? sanitize_text_field( wp_unslash( $_POST['dateOpen'] ) ) : null;
                $tournament->date_closing     = isset( $_POST['dateClose'] ) ? sanitize_text_field( wp_unslash( $_POST['dateClose'] ) ) : null;
                $tournament->date_withdrawal  = isset( $_POST['dateWithdraw'] ) ? sanitize_text_field( wp_unslash( $_POST['dateWithdraw'] ) ) : null;
                $tournament->date_start       = isset( $_POST['dateStart'] ) ? sanitize_text_field( wp_unslash( $_POST['dateStart'] ) ) : null;
                $tournament->date             = isset( $_POST['dateEnd'] ) ? sanitize_text_field( wp_unslash( $_POST['dateEnd'] ) ) : null;
                $tournament->start_time       = isset( $_POST['startTime'] ) ? sanitize_text_field( wp_unslash( $_POST['startTime'] ) ) : null;
                $tournament->competition_code = isset( $_POST['competition_code'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_code'] ) ) : null;
                $tournament->grade            = isset( $_POST['grade'] ) ? sanitize_text_field( wp_unslash( $_POST['grade'] ) ) : null;
                $fees                         = new stdClass();
                $fees->competition            = isset( $_POST['feeCompetition'] ) ? floatval( $_POST['feeCompetition'] ) : null;
                $fees->event                  = isset( $_POST['feeEvent'] ) ? floatval( $_POST['feeEvent'] ) : null;
                $fees->id                     = isset( $_POST['feeId'] ) ? intval( $_POST['feeId'] ) : null;
                $tournament->fees             = $fees;
                $tournament->num_entries      = isset( $_POST['num_entries'] ) ? intval( $_POST['num_entries'] ) : null;
                $tournament                   = new Racketmanager_Tournament( $tournament );
                if ( $racketmanager->error ) {
                    $racketmanager->printMessage();
                } else {
                    $this->set_competition_dates( $tournament );
                    $tournament->schedule_activities();
                    $this->display_tournaments_page();

                    return;
                }
            }
        } elseif ( isset( $_POST['editTournament'] ) ) {
            if ( ! current_user_can( 'edit_teams' ) ) {
                $this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
            } else {
                check_admin_referer( 'racketmanager_manage-tournament' );
                if ( isset( $_POST['tournament_id'] ) ) {
                    $tournament_id = intval( $_POST['tournament_id'] );
                    $tournament    = get_tournament( $tournament_id );
                    if ( $tournament ) {
                        $tournament->name             = isset( $_POST['tournamentName'] ) ? sanitize_text_field( wp_unslash( $_POST['tournamentName'] ) ) : null;
                        $tournament->season           = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
                        $tournament->venue            = isset( $_POST['venue'] ) ? intval( $_POST['venue'] ) : null;
                        $tournament->date             = isset( $_POST['dateEnd'] ) ? sanitize_text_field( wp_unslash( $_POST['dateEnd'] ) ) : null;
                        $tournament->date_open        = isset( $_POST['dateOpen'] ) ? sanitize_text_field( wp_unslash( $_POST['dateOpen'] ) ) : null;
                        $tournament->date_closing     = isset( $_POST['dateClose'] ) ? sanitize_text_field( wp_unslash( $_POST['dateClose'] ) ) : null;
                        $tournament->date_withdrawal  = isset( $_POST['dateWithdraw'] ) ? sanitize_text_field( wp_unslash( $_POST['dateWithdraw'] ) ) : null;
                        $tournament->date_start       = isset( $_POST['dateStart'] ) ? sanitize_text_field( wp_unslash( $_POST['dateStart'] ) ) : null;
                        $tournament->competition_code = isset( $_POST['competition_code'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_code'] ) ) : null;
                        $tournament->grade            = isset( $_POST['grade'] ) ? sanitize_text_field( wp_unslash( $_POST['grade'] ) ) : null;
                        $fees                         = new stdClass();
                        $fees->competition            = isset( $_POST['feeCompetition'] ) ? floatval( $_POST['feeCompetition'] ) : null;
                        $fees->event                  = isset( $_POST['feeEvent'] ) ? floatval( $_POST['feeEvent'] ) : null;
                        $fees->id                     = isset( $_POST['feeId'] ) ? intval( $_POST['feeId'] ) : null;
                        $tournament->fees             = $fees;
                        $tournament->num_entries      = isset( $_POST['num_entries'] ) ? intval( $_POST['num_entries'] ) : null;
                        $success                      = $tournament->update( $tournament );
                        if ( $success ) {
                            $this->set_competition_dates( $tournament );
                            $tournament->schedule_activities();
                        }
                    } else {
                        $racketmanager->set_message( __( 'Tournament not found', 'racketmanager' ), true );
                    }
                }
            }
            $racketmanager->printMessage();
        } elseif ( isset( $_GET['tournament'] ) ) {
            $tournament_id = intval( $_GET['tournament'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $tournament    = get_tournament( $tournament_id );
            if ( $tournament ) {
                $tournament->fees = $tournament->get_fees();
            }
        } else {
            $tournament_id = null;
            $tournament    = (object) array(
                'name'             => '',
                'competition_id'   => '',
                'id'               => '',
                'venue'            => '',
                'date'             => '',
                'date_closing'     => '',
                'date_open'        => '',
                'date_start'       => '',
                'competition_code' => '',
            );
        }
        $edit = false;
        if ( empty( $tournament_id ) ) {
            $form_title  = __( 'Add Tournament', 'racketmanager' );
            $form_action = __( 'Add', 'racketmanager' );
        } else {
            $edit        = true;
            $form_title  = __( 'Edit Tournament', 'racketmanager' );
            $form_action = __( 'Update', 'racketmanager' );
        }
        $clubs             = $this->get_clubs(
            array(
                'type' => 'affiliated',
            )
        );
        $competition_query = array( 'type' => 'tournament' );
        $competitions      = $this->get_competitions( $competition_query );
        include_once RACKETMANAGER_PATH . '/admin/tournament-edit.php';
    }

    /**
     * Display tournament plan page
     */
    public function displayTournamentPlanPage(): void {
        global $racketmanager;
        if ( ! current_user_can( 'edit_teams' ) ) {
            $racketmanager->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
            $racketmanager->printMessage();
        } else {
            if ( isset( $_POST['saveTournamentPlan'] ) ) {
                check_admin_referer( 'racketmanager_tournament-planner' );
                if ( isset( $_POST['tournamentId'] ) ) {
                    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $tournament = get_tournament( intval( $_POST['tournamentId'] ) );
                    $courts     = $_POST['court'] ?? null;
                    $start_time = $_POST['startTime'] ?? null;
                    $matches    = $_POST['match'] ?? null;
                    $match_time = $_POST['matchtime'] ?? null;
                    // phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $tournament->save_plan( $courts, $start_time, $matches, $match_time );
                    $racketmanager->printMessage();
                }
                $tab = 'matches';
            } elseif ( isset( $_POST['resetTournamentPlan'] ) ) {
                check_admin_referer( 'racketmanager_tournament-planner' );
                if ( isset( $_POST['tournamentId'] ) ) {
                    $tournament = get_tournament( intval( $_POST['tournamentId'] ) );
                    $tournament->reset_plan();
                    $racketmanager->printMessage();
                }
                $tab = 'matches';
            } elseif ( isset( $_POST['saveTournament'] ) ) {
                check_admin_referer( 'racketmanager_tournament' );
                if ( isset( $_POST['tournamentId'] ) ) {
                    $tournament     = get_tournament( intval( $_POST['tournamentId'] ) );
                    $start_time     = isset( $_POST['startTime'] ) ? sanitize_text_field( wp_unslash( $_POST['startTime'] ) ) : null;
                    $num_courts     = isset( $_POST['numCourts'] ) ? intval( $_POST['numCourts'] ) : null;
                    $time_increment = isset( $_POST['timeIncrement'] ) ? sanitize_text_field( wp_unslash( $_POST['timeIncrement'] ) ) : null;
                    $tournament->update_plan( $start_time, $num_courts, $time_increment );
                    $racketmanager->printMessage();
                }
                $tab = 'config';
            }

            if ( isset( $_GET['tournament'] ) ) {
                $tournament_id = intval( $_GET['tournament'] );
                $tournament    = get_tournament( $tournament_id );
                $final_matches = $this->get_matches(
                    array(
                        'season'         => $tournament->season,
                        'final'          => 'final',
                        'competition_id' => $tournament->competition_id,
                    )
                );
            }
            if ( empty( $tab ) ) {
                $tab = 'matches';
            }
            include_once RACKETMANAGER_PATH . '/admin/tournament/plan.php';
        }
    }

    /**
     * Display tournament matches page
     */
    public function display_tournament_matches_page(): void {
        if ( ! current_user_can( 'edit_matches' ) ) {
            $this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
            $this->printMessage();
        } else {
            //phpcs:disable WordPress.Security.NonceVerification.Recommended
            $final_key     = isset( $_GET['final'] ) ? intval( $_GET['final'] ) : null;
            $season        = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
            $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
            $league_id     = isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : null;
            $final_key     = isset( $_GET['final'] ) ? sanitize_text_field( wp_unslash( $_GET['final'] ) ) : null;
            //phpcs:enable WordPress.Security.NonceVerification.Recommended
            if ( $tournament_id ) {
                $tournament = get_tournament( $tournament_id );
                if ( $tournament ) {
                    if ( $league_id ) {
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
                                $form_title = sprintf( __( 'Edit Matches - %s', 'racketmanager' ), Racketmanager_Util::get_final_name( $final_key ) );
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
                            include_once RACKETMANAGER_PATH . '/admin/includes/match.php';
                        }
                    }
                }
            }
        }
    }

    /**
     * Display tournament match page
     */
    public function display_tournament_match_page(): void {
        if ( ! current_user_can( 'edit_matches' ) ) {
            $this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
            $this->printMessage();
        } else {
            //phpcs:disable WordPress.Security.NonceVerification.Recommended
            $final_key     = isset( $_GET['final'] ) ? intval( $_GET['final'] ) : null;
            $season        = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
            $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
            $league_id     = isset( $_GET['league'] ) ? intval( $_GET['league'] ) : null;
            $final_key     = isset( $_GET['final'] ) ? sanitize_text_field( wp_unslash( $_GET['final'] ) ) : null;
            $match_id      = isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : null;
            //phpcs:enable WordPress.Security.NonceVerification.Recommended
            if ( $tournament_id ) {
                $tournament = get_tournament( $tournament_id );
                if ( $tournament ) {
                    $is_finals = true;
                    if ( $league_id ) {
                        $league = get_league( $league_id );
                        if ( $league ) {
                            if ( $match_id ) {
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
                                    include_once RACKETMANAGER_PATH . '/admin/includes/match.php';
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Set competition dates for tournament function
     *
     * @param object $tournament tournament.
     *
     * @return void
     */
    private function set_competition_dates( object $tournament ): void {
        $competition = get_competition( $tournament->competition_id );
        if ( $competition ) {
            $season = $competition->seasons[ $tournament->season ] ?? null;
            if ( $season ) {
                $updates = false;
                if ( empty( $season['date_open'] ) || $season['date_open'] !== $tournament->date_open ) {
                    $updates             = true;
                    $season['date_open'] = $tournament->date_open;
                }
                if ( empty( $season['date_end'] ) || $season['date_end'] !== $tournament->date ) {
                    $updates            = true;
                    $season['date_end'] = $tournament->date;
                }
                if ( empty( $season['date_start'] ) || $season['date_start'] !== $tournament->date_start ) {
                    $updates              = true;
                    $season['date_start'] = $tournament->date_start;
                }
                if ( empty( $season['date_closing'] ) || $season['date_closing'] !== $tournament->date_closing ) {
                    $updates                = true;
                    $season['date_closing'] = $tournament->date_closing;
                }
                if ( empty( $season['competition_code'] ) ) {
                    if ( ! empty( $tournament->competition_code ) ) {
                        $updates                    = true;
                        $season['competition_code'] = $tournament->competition_code;
                    }
                } elseif ( $season['competition_code'] !== $tournament->competition_code ) {
                    $updates                    = true;
                    $season['competition_code'] = $tournament->competition_code;
                }
                if ( $updates ) {
                    $season_data                   = new stdClass();
                    $season_data->season           = $season['name'];
                    $season_data->num_match_days   = $season['num_match_days'];
                    $season_data->object_id        = $competition->id;
                    $season_data->match_dates      = $season['match_dates'] ?? false;
                    $season_data->fixed_dates      = $season['fixed_match_dates'] ?? false;
                    $season_data->home_away        = $season['home_away'] ?? false;
                    $season_data->status           = 'live';
                    $season_data->date_open        = $season['date_open'];
                    $season_data->date_closing     = $season['date_closing'];
                    $season_data->date_start       = $season['date_start'];
                    $season_data->date_end         = $season['date_end'];
                    $season_data->competition_code = $season['competition_code'] ?? null;
                    $season_data->type             = 'competition';
                    $season_data->is_box           = false;
                    $this->edit_season( $season_data );
                }
            } else {
                $competition_season = $this->add_season_to_competition( $tournament->season, $tournament->competition_id );
                if ( $competition_season ) {
                    $season_data                   = new stdClass();
                    $season_data->season           = $competition_season['name'];
                    $season_data->num_match_days   = $competition_season['num_match_days'];
                    $season_data->object_id        = $competition->id;
                    $season_data->match_dates      = false;
                    $season_data->fixed_dates      = false;
                    $season_data->home_away        = false;
                    $season_data->status           = 'live';
                    $season_data->date_open        = $tournament->date_open;
                    $season_data->date_closing     = $tournament->date_closing;
                    $season_data->date_start       = $tournament->date_start;
                    $season_data->date_end         = $tournament->date;
                    $season_data->type             = 'competition';
                    $season_data->is_box           = false;
                    $season_data->competition_code = $tournament->competition_code;
                    $this->edit_season( $season_data );
                }
            }
        }
    }

    /**
     * Calculate team ratings function
     *
     * @param int $tournament_id tournament id.
     *
     * @return void
     */
    private function calculate_player_team_ratings( int $tournament_id ): void {
        $tournament = get_tournament( $tournament_id );
        $tournament?->calculate_player_team_ratings();
    }

    /**
     * Display tournament teams page
     */
    public function display_tournament_teams_page(): void {
        $this->display_teams_list();
    }

    /**
     * Contact teams in tournament in admin screen
     */
    protected function contact_teams(): void {
        global $racketmanager;
        if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_contact-teams-preview' ) ) {
            $racketmanager->set_message( __( 'Security token invalid', 'racketmanager' ), true );
        } elseif ( current_user_can( 'edit_teams' ) ) {
            if ( isset( $_POST['tournament_id'] ) && isset( $_POST['emailMessage'] ) ) {
                $tournament = get_tournament( $_POST['tournament_id'] );
                if ( $tournament ) {
                    if ( isset( $_POST['contactTeamActive'] ) ) {
                        $active = true;
                    } else {
                        $active = false;
                    }
                    $message = htmlspecialchars_decode( $_POST['emailMessage'] ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $sent    = $tournament->contact_teams( $message, $active );
                    if ( $sent ) {
                        $racketmanager->set_message( __( 'Email sent to players', 'racketmanager' ) );
                    }
                } else {
                    $racketmanager->set_message( __( 'Tournament not found', 'racketmanager' ), true );
                }
            }
        } else {
            $racketmanager->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
        }
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
}
