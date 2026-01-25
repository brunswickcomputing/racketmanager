<?php
/**
 * RacketManager-Admin API: RacketManager-admin-tournament class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Tournament
 */

namespace Racketmanager\Admin;

use Racketmanager\Domain\Tournament;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Services\Validator\Validator_Plan;
use Racketmanager\Services\Validator\Validator_Tournament;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use stdClass;
use function Racketmanager\get_competition;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_match;
use function Racketmanager\get_team;
use function Racketmanager\get_tournament;

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
        $this->admin_competition = new Admin_Competition( $this->racketmanager );
        $this->admin_club        = new Admin_Club( $this->racketmanager );
        $this->admin_event       = new Admin_Event( $this->racketmanager );
        if ( 'modify' === $view ) {
            $this->display_tournament_page();
        } elseif ( 'plan' === $view ) {
            $this->display_plan_page();
        } elseif ( 'tournament' === $view ) {
            $this->display_tournament_overview_page();
        } elseif ( 'draw' === $view ) {
            $this->display_draw_page();
        } elseif ( 'setup' === $view ) {
            $this->display_setup_page();
        } elseif ( 'setup-event' === $view ) {
            $this->display_setup_event_page();
        } elseif ( 'matches' === $view ) {
            $this->display_matches_page();
        } elseif ( 'match' === $view ) {
            $this->display_match_page();
        } elseif ( 'teams' === $view ) {
            $this->display_teams_list();
        } elseif ( 'config' === $view ) {
            $this->admin_competition->display_config_page();
        } elseif ( 'event-config' === $view ) {
            $this->admin_event->display_config_page();
        } elseif ( 'team' === $view ) {
            $this->admin_club->display_team_page();
        } elseif ( 'contact' === $view ) {
            $this->display_contact_page();
        } elseif ( 'information' === $view ) {
            $this->display_information_page();
        } else {
            $this->display_tournaments_page();
        }
    }
    /**
     * Display tournaments page
     */
    public function display_tournaments_page(): void {
        global $racketmanager;
        if ( ! current_user_can( 'edit_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
            return;
        }
        $age_group_select   = isset( $_GET['age_group'] ) ? sanitize_text_field( wp_unslash( $_GET['age_group'] ) ) : '';
        $season_select      = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : '';
        $competition_select = isset( $_GET['competition'] ) ? intval( $_GET['competition'] ) : '';
        if ( isset( $_POST['doTournamentDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
            if ( ! current_user_can( 'del_teams' ) ) {
                $this->set_message( $this->no_permission, true );
            } else {
                check_admin_referer( 'tournaments-bulk' );
                $tournaments = $_POST['tournament'] ?? array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $messages = array();
                foreach ( $tournaments as $tournament_id ) {
                    $tournament = get_tournament( $tournament_id );
                    $tournament->delete();
                    $messages[] = $tournament->name . ' ' . __( 'deleted', 'racketmanager' );
                }
                $message = implode( '<br>', $messages );
                $this->set_message( $message );
            }
            $this->show_message();
        }
        $club_id = 0;
        $this->show_message();
        $clubs       = $this->club_service->get_clubs();
        $tournaments = $racketmanager->get_tournaments(
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
        $seasons      = $racketmanager->get_seasons( 'DESC' );
        $competitions = $this->competition_service->get_tournaments();
        $age_groups   = Util_Lookup::get_age_groups();
        require_once RACKETMANAGER_PATH . 'templates/admin/show-tournaments.php';
    }

    /**
     * Display tournament overview
     */
    public function display_tournament_overview_page(): void {
        if ( isset( $_GET['tournament'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( isset( $_POST['contactTeam'] ) || isset( $_POST['contactTeamActive'] ) ) {
                $this->contact_teams();
                $this->show_message();
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
                $confirmed_entries       = Util::get_players_list( $entries_confirmed );
                $entries_pay_due         = $tournament->get_entries(
                    array(
                        'status' => 'unpaid',
                    )
                );
                $pay_due_entries         = Util::get_players_list( $entries_pay_due );
                $entries_pending         = $tournament->get_entries(
                    array(
                        'status' => 'pending',
                    )
                );
                $pending_entries         = Util::get_players_list( $entries_pending );
                $entries_withdrawn       = $tournament->get_entries(
                    array(
                        'status' => 'withdrawn',
                    )
                );
                $withdrawn_entries       = Util::get_players_list( $entries_withdrawn );
                require_once RACKETMANAGER_PATH . 'templates/admin/show-tournament.php';
            }
        }
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
        if ( $tournament_id ) {
            $tournament = get_tournament( $tournament_id );
            if ( $tournament && $league_id ) {
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
        }
    }

    /**
     * Display tournament setup
     */
    public function display_setup_page(): void {
        if ( ! current_user_can( 'edit_matches' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
        } else {
            if ( isset( $_POST['action'] ) ) {
                if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add_championship-matches' ) ) {
                    $this->set_message( $this->invalid_security_token, true );
                    $this->show_message();
                } else {
                    $valid         = true;
                    $tournament_id = isset( $_POST['tournament_id'] ) ? intval( $_POST['tournament_id'] ) : null;
                    $season        = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
                    $tournament    = get_tournament( $tournament_id );
                    if ( $tournament ) {
                        $tournament_season = $tournament->competition->get_season_by_name( $season );
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
                                    $tournament_seasons            = $competition->get_seasons();
                                    $tournament_seasons[ $season ] = $tournament_season;
                                    $competition->update_seasons( $tournament_seasons );
                                }
                                $this->set_message( __( 'Tournament match dates updated', 'racketmanager' ) );
                            } else {
                                $message = implode( '<br>', $msg );
                                $this->set_message( $message, true );
                            }
                            $this->show_message();
                        }
                    }
                }
            } elseif ( isset( $_POST['rank'] ) ) {
                if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_calculate_ratings' ) ) {
                    $this->set_message( $this->invalid_security_token, true );
                } else {
                    $valid         = true;
                    $tournament_id = isset( $_POST['tournament_id'] ) ? intval( $_POST['tournament_id'] ) : null;
                    $this->calculate_player_team_ratings( $tournament_id );
                    $this->set_message( __( 'Tournament ratings set', 'racketmanager' ) );
                }
                $this->show_message();
            }
            $season        = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
            $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
            //phpcs:enable WordPress.Security.NonceVerification.Recommended
            if ( $tournament_id ) {
                $tournament = get_tournament( $tournament_id );
                if ( $tournament ) {
                    $tournament_season = $tournament->competition->get_season_by_name( $season );
                    $match_dates       = $tournament_season['match_dates'] ?? null;
                    if ( empty( $match_dates ) ) {
                        $match_dates  = array();
                        $match_date   = null;
                        $round_length = $tournament->competition->round_length ?? 7;
                        $i            = 0;
                        foreach ( $tournament->finals as $final ) {
                            $r = $final['round'] - 1;
                            if ( 0 === $i ) {
                                $match_date = $tournament->date;
                            } elseif ( 1 === $i ) {
                                $match_date = Util::amend_date( $tournament->date, 7, '-' );
                            } else {
                                $match_date = Util::amend_date( $match_date, $round_length, '-' );
                            }
                            $match_dates[ $r ] = $match_date;
                            ++ $i;
                        }
                    }
                    require_once RACKETMANAGER_PATH . 'templates/admin/tournament/setup.php';
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
            $this->show_message();
        } else {
            if ( isset( $_POST['action'] ) ) {
                if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add_championship-matches' ) ) {
                    $this->set_message( $this->invalid_security_token, true );
                    $this->show_message();
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
                if ( $tournament && $league_id ) {
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
            }
        }
    }

    /**
     * Display tournament page
     */
    public function display_tournament_page(): void {
        global $racketmanager;
        $validator = new Validator_Tournament();
        $validator = $validator->capability( 'edit_teams' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
        if ( $tournament_id ) {
            $edit      = true;
            $validator = $validator->tournament( $tournament_id );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->err_msgs[0], true );
                $this->show_message();
                return;
            }
            $tournament       = get_tournament( $tournament_id );
            $tournament->fees = $tournament->get_fees();
        } else {
            $edit       = false;
            $tournament = null;
        }
        if ( isset( $_POST['addTournament'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_add-tournament' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
            } else {
                $tournament = $this->get_input();
                $validator  = $this->validate_tournament( $tournament );
                if ( empty( $validator->error ) ) {
                    $tournament = new Tournament( $tournament );
                    $this->set_competition_dates( $tournament );
                    $tournament->schedule_activities();
                    $edit = true;
                    ?>
                    <script>
                        let url = new URL(window.location.href);
                        url.searchParams.append('tournament', <?php echo esc_attr( $tournament->id ); ?>);
                        history.pushState('', '', url.toString());
                    </script>
                    <?php
                    $this->set_message( __( 'Tournament added', 'racketmanager' ) );
                } else {
                    $this->set_message( __( 'Error adding tournament', 'racketmanager' ), true );
                }
            }
        } elseif ( isset( $_POST['editTournament'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_manage-tournament' );
            if ( empty( $validator->error ) ) {
                $tournament_id_passed = isset( $_POST['tournament_id'] ) ? intval( $_POST['tournament_id'] ) : null;
                $validator            = $validator->compare( $tournament_id_passed, $tournament_id );
            }
            if ( ! empty( $validator->error ) ) {
                if ( empty( $validator->msg ) ) {
                    $msg = $validator->err_msgs[0];
                } else {
                    $msg = $validator->msg;
                }
                $this->set_message( $msg, true );
            } else {
                $tournament_input = $this->get_input( $tournament );
                $validator  = $this->validate_tournament( $tournament_input );
                if ( empty( $validator->error ) ) {
                    $updates = $tournament->update( $tournament_input );
                    if ( $updates ) {
                        $this->set_message( __( 'Tournament updated', 'racketmanager' ) );
                        $this->set_competition_dates( $tournament );
                        $tournament->schedule_activities();
                    } else {
                        $this->set_message( $this->no_updates, 'warning' );
                    }
                } else {
                    $tournament = $tournament_input;
                    $this->set_message( __( 'Error updating tournament', 'racketmanager' ), true );
                }
            }
        }
        $this->show_message();
        if ( empty( $edit ) ) {
            $form_title  = __( 'Add Tournament', 'racketmanager' );
            $form_action = __( 'Add', 'racketmanager' );
        } else {
            $form_title  = __( 'Edit Tournament', 'racketmanager' );
            $form_action = __( 'Update', 'racketmanager' );
        }
        $clubs             = $this->club_service->get_clubs(
            array(
                'type' => 'affiliated',
            )
        );
        $competition_query = array( 'type' => 'tournament' );
        $competitions      = $this->competition_service->get_tournaments();
        $seasons           = $racketmanager->get_seasons( 'DESC' );
        require_once RACKETMANAGER_PATH . 'templates/admin/tournament-edit.php';
    }
    private function get_input( ?object $tournament = null): object {
        if ( empty( $tournament ) ) {
            $tournament = new stdClass();
        } else {
            $tournament = clone $tournament;
        }
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
        return $tournament;
    }
    private function validate_tournament( ?object $tournament = null ): stdClass {
        $validator = new Validator_Tournament();
        $validator = $validator->name( $tournament->name );
        $validator = $validator->competition( $tournament->competition_id );
        $validator = $validator->season( $tournament->season );
        $validator = $validator->venue( $tournament->venue );
        $validator = $validator->grade( $tournament->grade );
        $validator = $validator->num_entries( $tournament->num_entries );
        $validator = $validator->date( $tournament->date_open, 'open' );
        $validator = $validator->date( $tournament->date_closing, 'closing', $tournament->date_open, 'open' );
        $validator = $validator->date( $tournament->date_withdrawal, 'withdrawal', $tournament->date_closing, 'closing' );
        $validator = $validator->date( $tournament->date_start, 'start', $tournament->date_withdrawal, 'withdrawal' );
        $validator = $validator->date( $tournament->date, 'end', $tournament->date_start, 'start' );
        return $validator->get_details();
    }
    /**
     * Display tournament plan page
     */
    public function display_plan_page(): void {
        global $racketmanager;
        if ( ! current_user_can( 'edit_teams' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
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
                    $updates = $tournament->save_plan( $courts, $start_time, $matches, $match_time );
                    if ( $updates ) {
                        $this->set_message( __( 'Plan updated', 'racketmanager' ) );
                    } else {
                        $this->set_message( $this->no_updates, 'warning' );
                    }
                    $this->show_message();
                }
                $tab = 'matches';
            } elseif ( isset( $_POST['resetTournamentPlan'] ) ) {
                check_admin_referer( 'racketmanager_tournament-planner' );
                if ( isset( $_POST['tournamentId'] ) ) {
                    $tournament = get_tournament( intval( $_POST['tournamentId'] ) );
                    $updates = $tournament->reset_plan();
                    if ( $updates ) {
                        $this->set_message( __( 'Plan reset', 'racketmanager' ) );
                    } else {
                        $this->set_message( $this->no_updates, 'warning' );
                    }
                    $this->show_message();
                }
                $tab = 'matches';
            } elseif ( isset( $_POST['saveTournament'] ) ) {
                check_admin_referer( 'racketmanager_tournament' );
                if ( isset( $_POST['tournamentId'] ) ) {
                    $tournament     = get_tournament( intval( $_POST['tournamentId'] ) );
                    $start_time     = isset( $_POST['startTime'] ) ? sanitize_text_field( wp_unslash( $_POST['startTime'] ) ) : null;
                    $num_courts     = isset( $_POST['numCourtsAvailable'] ) ? intval( $_POST['numCourtsAvailable'] ) : null;
                    $time_increment = isset( $_POST['timeIncrement'] ) ? sanitize_text_field( wp_unslash( $_POST['timeIncrement'] ) ) : null;
                    $validator      = new Validator_Plan();
                    $validator      = $validator->start_time( $start_time );
                    $validator      = $validator->num_courts_available( $num_courts );
                    $validator      = $validator->time_increment( $time_increment );
                    if ( empty( $validator->error ) ) {
                        $updates = $tournament->update_plan( $start_time, $num_courts, $time_increment );
                        if ( $updates ) {
                            $this->set_message( __( 'Plan updated', 'racketmanager' ) );
                        } else {
                            $this->set_message( $this->no_updates, 'warning' );
                        }
                    } else {
                        $this->set_message( __( 'Unable to update plan', 'racketmanager' ), true );
                    }
                    $this->show_message();
                }
                $tab = 'config';
            }

            if ( isset( $_GET['tournament'] ) ) {
                $tournament_id = intval( $_GET['tournament'] );
                $tournament    = get_tournament( $tournament_id );
                $final_matches = $racketmanager->get_matches(
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
            require_once RACKETMANAGER_PATH . 'templates/admin/tournament/plan.php';
        }
    }

    /**
     * Display tournament matches page
     */
    public function display_matches_page(): void {
        if ( ! current_user_can( 'edit_matches' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
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
                if ( $tournament && $league_id ) {
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
     * Display tournament match page
     */
    public function display_match_page(): void {
        if ( ! current_user_can( 'edit_matches' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
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
            $season = $competition->get_season_by_name( $tournament->season ) ?? null;
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
    public function display_teams_page(): void {
        $this->display_teams_list();
    }

    /**
     * Contact teams in tournament in admin screen
     */
    protected function contact_teams(): void {
        if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_contact-teams-preview' ) ) {
            $this->set_message( $this->invalid_security_token, true );
            return;
        }
        if ( ! current_user_can( 'edit_teams' ) ) {
            $this->set_message( $this->no_permission, true );
            return;
        }
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
                    $this->set_message( __( 'Email sent to players', 'racketmanager' ) );
                }
            } else {
                $this->set_message( __( 'Tournament not found', 'racketmanager' ), true );
            }
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
        if ( $tournament_id ) {
            $validator = $validator->tournament( $tournament_id );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->err_msgs[0], true );
                $this->show_message();
                return;
            }
            $tournament = get_tournament( $tournament_id );
            if ( isset( $_POST['setInformation'] ) ) {
                $information               = new stdClass();
                $information->parking      = isset( $_POST['parking'] ) ? sanitize_text_field( wp_unslash( $_POST['parking'] ) ) : null;
                $information->catering     = isset( $_POST['catering'] ) ? sanitize_text_field( wp_unslash( $_POST['catering'] ) ) : null;
                $information->photography  = isset( $_POST['photography'] ) ? sanitize_text_field( wp_unslash( $_POST['photography'] ) ) : null;
                $information->spectators   = isset( $_POST['spectators'] ) ? sanitize_text_field( wp_unslash( $_POST['spectators'] ) ) : null;
                $information->referee      = isset( $_POST['referee'] ) ? sanitize_text_field( wp_unslash( $_POST['referee'] ) ) : null;
                $information->match_format = isset( $_POST['matchFormat'] ) ? sanitize_text_field( wp_unslash( $_POST['matchFormat'] ) ) : null;
                $validator                 = $validator->information( $information );
                if ( empty( $validator->error ) ) {
                    $updates = $tournament->set_information( $information );
                    if ( $updates ) {
                        $this->set_message( __( 'Information updated', 'racketmanager' ) );
                    } else {
                        $this->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
                    }
                } else {
                    $this->set_message( __( 'Information not updated', 'racketmanager' ), true );
                }
            } else {
                if ( isset( $_POST['notifyFinalists'] ) ) {
                    $return = $tournament->notify_finalists();
                    $this->set_message( $return->msg, $return->error );
                }
                $information = $tournament->information;
            }
        }
        $this->show_message();
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
            $event = get_event( $event );
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
        if ( '' === $event->get_seasons() ) {
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
