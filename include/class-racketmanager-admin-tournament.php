<?php
/**
 * RacketManager-Admin API: RacketManager-admin-tournament class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Tournament
 */

namespace Racketmanager;

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
	 * League_id the id of the current league.
	 *
	 * @var $league_id
	 */
	private $league_id;
	/**
	 * Constructor
	 */
	public function __construct() {
		global $racketmanager_ajax_admin;
		parent::__construct();
	}
	/**
	 * Display tournaments page
	 */
	public function display_tournaments_page() {
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$season_select      = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : '';
			$competition_select = isset( $_GET['competition'] ) ? intval( $_GET['competition'] ) : '';
			if ( isset( $_POST['addTournament'] ) ) {
				if ( ! current_user_can( 'edit_teams' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} else {
					check_admin_referer( 'racketmanager_add-tournament' );
					$tournament                   = new \stdClass();
					$tournament->name             = isset( $_POST['tournament'] ) ? sanitize_text_field( wp_unslash( $_POST['tournament'] ) ) : null;
					$tournament->competition_id   = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
					$tournament->season           = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
					$tournament->venue            = isset( $_POST['venue'] ) ? intval( $_POST['venue'] ) : null;
					$tournament->date_open        = isset( $_POST['date_open'] ) ? sanitize_text_field( wp_unslash( $_POST['date_open'] ) ) : null;
					$tournament->closing_date     = isset( $_POST['closingdate'] ) ? sanitize_text_field( wp_unslash( $_POST['closingdate'] ) ) : null;
					$tournament->date_start       = isset( $_POST['date_start'] ) ? sanitize_text_field( wp_unslash( $_POST['date_start'] ) ) : null;
					$tournament->date             = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : null;
					$tournament->starttime        = isset( $_POST['starttime'] ) ? sanitize_text_field( wp_unslash( $_POST['starttime'] ) ) : null;
					$tournament->competition_code = isset( $_POST['competition_code'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_code'] ) ) : null;
					$tournament                   = new Racketmanager_Tournament( $tournament );
					if ( $tournament ) {
						$this->set_competition_dates( $tournament );
						$this->schedule_tournament_ratings( $tournament );
					}
					$this->printMessage();
				}
			} elseif ( isset( $_POST['doTournamentDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
				if ( ! current_user_can( 'del_teams' ) ) {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				} else {
					check_admin_referer( 'tournaments-bulk' );
					foreach ( $_POST['tournament'] as $tournament_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$tournament = get_tournament( $tournament_id );
						$tournament->delete();
					}
				}
				$this->printMessage();
			}
			$club_id = 0;
			$this->printMessage();
			$clubs           = $this->get_clubs();
				$tournaments = $this->get_tournaments(
					array(
						'season'         => $season_select,
						'competition_id' => $competition_select,
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
	public function display_tournament_overview_page() {
		if ( isset( $_GET['tournament'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tournament_id = intval( $_GET['tournament'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tournament    = get_tournament( $tournament_id );
			if ( $tournament ) {
				$tournament->events  = $tournament->get_events();
				$tournament->entries = $tournament->get_entries( array( 'count' => true ) );
				$tab                 = 'overview';
				$entries_confirmed   = $tournament->get_entries(
					array(
						'status' => 'confirmed',
					)
				);
				$confirmed_entries   = RacketManager_Util::get_players_list( $entries_confirmed );
				$entries_pending     = $tournament->get_entries(
					array(
						'status' => 'pending',
					)
				);
				$pending_entries     = RacketManager_Util::get_players_list( $entries_pending );
				require RACKETMANAGER_PATH . 'admin/show-tournament.php';
			}
		}
	}
	/**
	 * Display tournament draw
	 */
	public function display_tournament_draw_page() {
		global $tab;
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
							$this->printMessage();
							$tab = 'matches'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
						} else {
							$league->championship->handle_admin_page( $league, $season );
							if ( isset( $_POST['saveRanking'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
								$this->league_manual_rank_teams( $league );
								$this->printMessage();
								$tab = 'preliminary'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							} elseif ( isset( $_POST['randomRanking'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
								$this->league_random_rank_teams( $league );
								$this->printMessage();
								$tab = 'preliminary'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							} elseif ( isset( $_POST['ratingPointsRanking'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
								$this->league_rating_points_rank_teams( $league );
								$tab = 'preliminary'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							} elseif ( empty( $tab ) ) {
								$tab = 'finalresults'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
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
	public function display_tournament_setup_page() {
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
								$tournament_season['matchDates'] = array();
								foreach ( array_reverse( $rounds ) as $match_date ) {
									$tournament_season['matchDates'][] = $match_date;
								}
								$tournament_season['num_match_days'] = count( $tournament_season['matchDates'] );
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
					$this->printMessage();
				} else {
					$valid         = true;
					$tournament_id = isset( $_POST['tournament_id'] ) ? intval( $_POST['tournament_id'] ) : null;
					$this->calculate_team_ratings( $tournament_id );
					$this->set_message( __( 'Tournament ratings set', 'racketmanager' ) );
					$this->printMessage();
				}
			}
			$season        = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
			$tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
			//phpcs:enable WordPress.Security.NonceVerification.Recommended
			if ( $tournament_id ) {
				$tournament = get_tournament( $tournament_id );
				if ( $tournament ) {
					$match_dates = $tournament->competition->seasons[ $season ]['matchDates'];
					require RACKETMANAGER_PATH . 'admin/tournament/setup.php';
				}
			}
		}
	}
	/**
	 * Display tournament setup
	 */
	public function display_tournament_setup_event_page() {
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
					$action    = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : null;
					$league_id = isset( $_POST['league_id'] ) ? intval( $_POST['league_id'] ) : null;
					$season    = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
					$league    = get_league( $league_id );
					if ( $league ) {
						$event_season    = $league->event->seasons[ $season ];
						$num_first_round = $league->championship->num_teams_first_round;
						if ( isset( $_POST['rounds'] ) ) {
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
									$round_date = $round['match_date'];
									$teams      = $league->championship->get_final_teams( $round['key'] );
									if ( 1 !== intval( $round['round'] ) ) {
										$prev_round      = $round['round'] - 1;
										$prev_round_name = $league->championship->get_final_keys( $prev_round );
										$first_round     = false;
										$home_team       = 1;
										$away_team       = 2;
									} else {
										$first_round = true;
										switch ( $round['num_matches'] ) {
											case 1:
												$team_array = array( 1 );
												break;
											case 2:
												$team_array = array( 1, 3 );
												break;
											case 4:
												$team_array = array( 1, 5, 3, 7 );
												break;
											case 8:
												$team_array = array( 1, 9, 4, 12, 11, 14, 7, 15 );
												break;
											case 16:
												$team_array = array( 1, 17, 9, 25, 4, 21, 13, 28, 6, 22, 14, 30, 7, 23, 15, 31 );
												break;
											case 32:
												$team_array = array( 1, 33, 17, 49, 9, 41, 25, 57, 4, 36, 20, 52, 12, 44, 28, 60, 6, 38, 22, 54, 14, 46, 30, 62, 7, 39, 23, 55, 15, 47, 31, 63 );
												break;
											default:
												$team_array = array();
												break;
										}
									}
									$matches[ $round_date ] = array();
									for ( $i = 0; $i < $round['num_matches']; ++$i ) {
										$match            = new \stdClass();
										$match->date      = $round_date . ' 00:00:00';
										$match->match_day = '';
										if ( 'final' !== $round['key'] ) {
											if ( $round['round'] & 1 ) {
												$match->host = 'home';
											} else {
												$match->host = 'away';
											}
										}
										if ( $first_round ) {
											$home_team      = $team_array[ $i ];
											$home_team_name = $home_team . '_';
											$away_team      = $num_first_round + 1 - $home_team;
											$away_team_name = $away_team . '_';
										} else {
											$home_team_name = '1_' . $prev_round_name . '_' . $home_team;
											$away_team_name = '1_' . $prev_round_name . '_' . $away_team;
										}
										$match->home_team = $teams[ $home_team_name ]->id;
										$match->away_team = $teams[ $away_team_name ]->id;
										if ( $first_round ) {
											++$home_team;
											$away_team = $num_first_round + 1 - $home_team;
										} else {
											$home_team += 2;
											$away_team += 2;
										}
										$match->location          = null;
										$match->league_id         = $league->id;
										$match->season            = $season;
										$match->final_round       = $round['key'];
										$match->num_rubbers       = $league->num_rubbers;
										$matches[ $round_date ][] = $match;
									}
									$next_round_date = $round['match_date'];
								}
							}
							if ( $valid ) {
								if ( 'replace' === $action ) {
									$league->delete_season_matches( $season );
									$message = __( 'Matches replaced', 'racketmanager' );
								} else {
									$message = __( 'Matches added', 'racketmanager' );
								}
								$event_season['matchDates'] = array();
								foreach ( array_reverse( $matches ) as $match_date => $round_matches ) {
									$event_season['matchDates'][] = $match_date;
									foreach ( $round_matches as $match ) {
										$league->add_match( $match );
									}
								}
								if ( ! $league->championship->is_consolation ) {
									$event_season['num_match_days'] = count( $event_season['matchDates'] );
									$event                          = get_event( $league->event_id );
									if ( $event ) {
										$event_seasons            = $event->seasons;
										$event_seasons[ $season ] = $event_season;
										$event->update_seasons( $event_seasons );
									}
								}
								$this->set_message( $message );
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
					$this->printMessage();
				} else {
					$valid         = true;
					$tournament_id = isset( $_POST['tournament_id'] ) ? intval( $_POST['tournament_id'] ) : null;
					$league_id     = isset( $_POST['league_id'] ) ? intval( $_POST['league_id'] ) : null;
					$season        = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
					$this->calculate_team_ratings( $tournament_id, $season, $league_id );
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
							$match_dates = empty( $league->event->seasons[ $season ]['matchDates'] ) ? $league->event->competition->seasons[ $season ]['matchDates'] : $league->event->seasons[ $season ]['matchDates'];
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
	public function displayTournamentPage() {
		global $racketmanager;
		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} elseif ( isset( $_POST['editTournament'] ) ) {
			if ( ! current_user_can( 'edit_teams' ) ) {
				$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
			} else {
				check_admin_referer( 'racketmanager_manage-tournament' );
				if ( isset( $_POST['tournament_id'] ) ) {
					$tournament_id = intval( $_POST['tournament_id'] );
					$tournament    = get_tournament( $tournament_id );
					if ( $tournament ) {
						$tournament->name             = isset( $_POST['tournament'] ) ? sanitize_text_field( wp_unslash( $_POST['tournament'] ) ) : null;
						$tournament->season           = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
						$tournament->venue            = isset( $_POST['venue'] ) ? intval( $_POST['venue'] ) : null;
						$tournament->date             = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : null;
						$tournament->date_open        = isset( $_POST['date_open'] ) ? sanitize_text_field( wp_unslash( $_POST['date_open'] ) ) : null;
						$tournament->closing_date     = isset( $_POST['closingdate'] ) ? sanitize_text_field( wp_unslash( $_POST['closingdate'] ) ) : null;
						$tournament->date_start       = isset( $_POST['date_start'] ) ? sanitize_text_field( wp_unslash( $_POST['date_start'] ) ) : null;
						$tournament->competition_code = isset( $_POST['competition_code'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_code'] ) ) : null;
						$success                      = $tournament->update( $tournament );
						if ( $success ) {
							$this->set_competition_dates( $tournament );
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
		} else {
			$tournament_id = null;
		}
		$edit = false;
		if ( $tournament_id ) {
			$edit        = true;
			$form_title  = __( 'Edit Tournament', 'racketmanager' );
			$form_action = __( 'Update', 'racketmanager' );
		} else {
			$form_title  = __( 'Add Tournament', 'racketmanager' );
			$form_action = __( 'Add', 'racketmanager' );
			$tournament  = (object) array(
				'name'             => '',
				'competition_id'   => '',
				'id'               => '',
				'venue'            => '',
				'date'             => '',
				'closingdate'      => '',
				'numcourts'        => '',
				'date_open'        => '',
				'closing_date'     => '',
				'date_start'       => '',
				'competition_code' => '',
			);
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
	public function displayTournamentPlanPage() {
		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['saveTournamentPlan'] ) ) {
				check_admin_referer( 'racketmanager_tournament-planner' );
				if ( isset( $_POST['tournamentId'] ) ) {
					// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$tournament = get_tournament( intval( $_POST['tournamentId'] ) );
					$courts     = isset( $_POST['court'] ) ? $_POST['court'] : null;
					$start_time = isset( $_POST['starttime'] ) ? $_POST['starttime'] : null;
					$matches    = isset( $_POST['match'] ) ? $_POST['match'] : null;
					$match_time = isset( $_POST['matchtime'] ) ? $_POST['matchtime'] : null;
					// phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$tournament->save_plan( $courts, $start_time, $matches, $match_time );
					$this->printMessage();
				}
				$tab = 'matches';
			} elseif ( isset( $_POST['resetTournamentPlan'] ) ) {
				check_admin_referer( 'racketmanager_tournament-planner' );
				if ( isset( $_POST['tournamentId'] ) ) {
					$tournament = get_tournament( intval( $_POST['tournamentId'] ) );
					$tournament->reset_plan();
					$this->printMessage();
				}
				$tab = 'matches';
			} elseif ( isset( $_POST['saveTournament'] ) ) {
				check_admin_referer( 'racketmanager_tournament' );
				if ( isset( $_POST['tournamentId'] ) ) {
					$tournament     = get_tournament( intval( $_POST['tournamentId'] ) );
					$start_time     = isset( $_POST['starttime'] ) ? sanitize_text_field( wp_unslash( $_POST['starttime'] ) ) : null;
					$num_courts     = isset( $_POST['numcourts'] ) ? intval( $_POST['numcourts'] ) : null;
					$time_increment = isset( $_POST['timeincrement'] ) ? sanitize_text_field( wp_unslash( $_POST['timeincrement'] ) ) : null;
					$tournament->update_plan( $start_time, $num_courts, $time_increment );
					$this->printMessage();
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
	public function display_tournament_matches_page() {
		global $competition;
		if ( ! current_user_can( 'edit_matches' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			//phpcs:disable WordPress.Security.NonceVerification.Recommended
			$finalkey      = isset( $_GET['final'] ) ? intval( $_GET['final'] ) : null;
			$season        = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
			$tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
			$league_id     = isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : null;
			$finalkey      = isset( $_GET['final'] ) ? sanitize_text_field( wp_unslash( $_GET['final'] ) ) : null;
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
							if ( $finalkey ) {
								$is_finals = true;
								$mode      = 'edit';
								$edit      = true;

								$final           = $league->championship->get_finals( $finalkey );
								$num_first_round = $league->championship->num_teams_first_round;

								$max_matches = $final['num_matches'];

								/* translators: %s: round name */
								$form_title = sprintf( __( 'Edit Matches - %s', 'racketmanager' ), $league->championship->get_final_name( $finalkey ) );
								$match_args = array(
									'final'   => $finalkey,
									'orderby' => array(
										'id' => 'ASC',
									),
								);
								if ( 'final' !== $finalkey && ! empty( $league->current_season['homeAway'] ) && 'true' === $league->current_season['homeAway'] ) {
									$match_args['leg'] = 1;
								}
								$matches      = $league->get_matches( $match_args );
								$teams        = $league->championship->get_final_teams( $finalkey );
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
	public function display_tournament_match_page() {
		if ( ! current_user_can( 'edit_matches' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			//phpcs:disable WordPress.Security.NonceVerification.Recommended
			$finalkey      = isset( $_GET['final'] ) ? intval( $_GET['final'] ) : null;
			$season        = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
			$tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
			$league_id     = isset( $_GET['league'] ) ? intval( $_GET['league'] ) : null;
			$finalkey      = isset( $_GET['final'] ) ? sanitize_text_field( wp_unslash( $_GET['final'] ) ) : null;
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
									$final           = $league->championship->get_finals( $finalkey );
									$final_teams     = $league->championship->get_final_teams( $final['key'], 'ARRAY' );
									if ( is_numeric( $match->home_team ) ) {
										$home_team = get_team( $match->home_team );
										if ( $home_team ) {
											$home_title = $home_team->title;
										} else {
											$home_title = null;
										}
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
										if ( $away_team ) {
											$away_title = $away_team->title;
										} else {
											$away_title = null;
										}
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
	 * @return void
	 */
	private function set_competition_dates( $tournament ) {
		$competition = get_competition( $tournament->competition_id );
		if ( $competition ) {
			$season = isset( $competition->seasons[ $tournament->season ] ) ? $competition->seasons[ $tournament->season ] : null;
			if ( $season ) {
				$updates = false;
				if ( empty( $season['dateOpen'] ) || $season['dateOpen'] !== $tournament->date ) {
					$updates            = true;
					$season['dateOpen'] = $tournament->date;
				}
				if ( empty( $season['dateEnd'] ) || $season['dateEnd'] !== $tournament->date ) {
					$updates           = true;
					$season['dateEnd'] = $tournament->date;
				}
				if ( empty( $season['dateStart'] ) || $season['dateStart'] !== $tournament->date_start ) {
					$updates             = true;
					$season['dateStart'] = $tournament->date_start;
				}
				if ( empty( $season['closing_date'] ) || $season['closing_date'] !== $tournament->closing_date ) {
					$updates                = true;
					$season['closing_date'] = $tournament->closing_date;
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
					$season_data                   = new \stdclass();
					$season_data->season           = $season['name'];
					$season_data->num_match_days   = $season['num_match_days'];
					$season_data->object_id        = $competition->id;
					$season_data->match_dates      = isset( $season['matchDates'] ) ? $season['matchDates'] : false;
					$season_data->fixed_dates      = isset( $season['fixedMatchDates'] ) ? $season['fixedMatchDates'] : false;
					$season_data->home_away        = isset( $season['homeAway'] ) ? $season['homeAway'] : false;
					$season_data->status           = 'live';
					$season_data->date_open        = $season['dateOpen'];
					$season_data->closing_date     = $season['closing_date'];
					$season_data->date_start       = $season['dateStart'];
					$season_data->date_end         = $season['dateEnd'];
					$season_data->competition_code = $season['competition_code'];
					$season_data->type             = 'competition';
					$season_data->is_box           = false;
					$this->edit_season( $season_data );
				}
			} else {
				$competition_season = $this->add_season_to_competition( $tournament->season, $tournament->competition_id );
				if ( $competition_season ) {
					$season_data                   = new \stdclass();
					$season_data->season           = $competition_season['name'];
					$season_data->num_match_days   = $competition_season['num_match_days'];
					$season_data->object_id        = $competition->id;
					$season_data->match_dates      = false;
					$season_data->fixed_dates      = false;
					$season_data->home_away        = false;
					$season_data->status           = 'live';
					$season_data->date_open        = $tournament->date_open;
					$season_data->closing_date     = $tournament->closing_date;
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
	 * @return void
	 */
	private function calculate_team_ratings( $tournament_id ) {
		$tournament = get_tournament( $tournament_id );
		if ( ! $tournament ) {
			return;
		}
		$players = $tournament->get_entries();
		foreach ( $players as $player ) {
			$player = get_player( $player );
			if ( $player ) {
				$player->set_tournament_rating();
			}
		}
	}
	/**
	 * Schedule tournament ratings setting function
	 *
	 * @param object $tournament tournament object.
	 * @return void
	 */
	private function schedule_tournament_ratings( $tournament ) {
		if ( $tournament ) {
			$day            = intval( gmdate( 'd' ) );
			$month          = intval( gmdate( 'm' ) );
			$year           = intval( gmdate( 'Y' ) );
			$hour           = intval( gmdate( 'H' ) );
			$schedule_start = mktime( $hour, 0, 0, $month, $day, $year );
			$schedule_name  = 'rm_calculate_tournament_ratings';
			$schedule_args  = array( $tournament->id );
			if ( ! wp_next_scheduled( $schedule_name, $schedule_args ) ) {
				$success = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
				if ( ! $success ) {
					$this->set_message( __( 'Error scheduling tournament ratings calculation', 'racketmanager' ), true );
				}
			}
		}
	}
}
