<?php
/**
 * RacketManager-Admin API: RacketManager-admin-league class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-League
 */

namespace Racketmanager;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration League panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class RacketManager_Admin_League extends RacketManager_Admin {

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
	 * Display leagues page
	 */
	public function display_leagues_page() {
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$competition_type  = 'league';
			$type              = '';
			$season            = '';
			$standalone        = true;
			$competition_query = array( 'type' => $competition_type );
			$page_title        = ucfirst( $competition_type ) . ' ' . __( 'Competitions', 'racketmanager' );
			include_once RACKETMANAGER_PATH . '/admin/show-competitions.php';
		}
	}
	/**
	 * Display season list
	 */
	public function display_seasons_page() {
		global $racketmanager;
		if ( isset( $_POST['doactionseason'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
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
			require RACKETMANAGER_PATH . 'admin/includes/show-seasons.php';
		}
	}
	/**
	 * Display season overview
	 */
	public function display_overview_page() {
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
						$current_season->date_closing = isset( $current_season->date_closing ) ? $current_season->date_closing : $current_season->date_close;
						if ( isset( $current_season->date_closing ) && $current_season->date_closing <= gmdate( 'Y-m-d' ) ) {
							$current_season->is_active = true;
						} else {
							$current_season->is_active = false;
						}
						$current_season->is_open = false;
						$current_season->entries = $competition->get_clubs( array( 'status' => 1 ) );
						require RACKETMANAGER_PATH . 'admin/league/show-season.php';

					}
				}
			}
		}
	}
	/**
	 * Display cup draw
	 */
	public function display_cup_draw_page() {
		global $tab, $racketmanager;
		//phpcs:disable WordPress.Security.NonceVerification.Recommended
		$season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
		$competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$league_id      = isset( $_GET['league'] ) ? intval( $_GET['league'] ) : null;
		//phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( $competition_id ) {
			$competition = get_competition( $competition_id );
			if ( $competition ) {
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
							if ( $league->is_championship ) {
								$tab = 'preliminary'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							}
							$racketmanager->printMessage();
						} else {
							$tab = $league->championship->handle_admin_page( $league, $season ); //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
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
								$tab = 'finalresults'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							}
						}
						require RACKETMANAGER_PATH . 'admin/cup/draw.php';
					}
				}
			}
		}
	}
	/**
	 * Display setup
	 */
	public function display_setup_page() {
		if ( ! current_user_can( 'edit_matches' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['action'] ) ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add_championship-matches' ) ) {
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$this->printMessage();
				} else {
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
									$competition->update_seasons( $seasons );
									$this->set_message( __( 'Match dates updated', 'racketmanager' ) );
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
			$season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
			$competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $competition_id ) {
				$competition = get_competition( $competition_id );
				if ( $competition ) {
					$current_season = $competition->seasons[ $season ];
					require RACKETMANAGER_PATH . 'admin/includes/setup.php';
				}
			}
		}
	}
	/**
	 * Display cup event setup
	 */
	public function display_cup_setup_event_page() {
		global $racketmanager;
		if ( ! current_user_can( 'edit_matches' ) ) {
			$racketmanager->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$racketmanager->printMessage();
		} else {
			if ( isset( $_POST['action'] ) ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add_championship-matches' ) ) {
					$racketmanager->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$racketmanager->printMessage();
				} else {
					$valid     = true;
					$action    = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : null;
					$league_id = isset( $_POST['league_id'] ) ? intval( $_POST['league_id'] ) : null;
					$season    = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
					$rounds    = isset( $_POST['rounds'] ) ? $_POST['rounds'] : null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$league    = get_league( $league_id );
					if ( $league ) {
						$valid = $this->set_championship_matches( $league, $season, $rounds, $action );
					}
				}
			}
			$season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
			$competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
			$league_id      = isset( $_GET['league'] ) ? intval( $_GET['league'] ) : null;
			//phpcs:enable WordPress.Security.NonceVerification.Recommended
			if ( $competition_id ) {
				$competition = get_competition( $competition_id );
				if ( $competition ) {
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
							if ( empty( $league->seasons[ $season ]['rounds'] ) ) {
								$match_dates = empty( $league->event->seasons[ $season ]['match_dates'] ) ? $league->event->competition->seasons[ $season ]['match_dates'] : $league->event->seasons[ $season ]['match_dates'];
							} else {
								foreach ( array_reverse( $league->seasons[ $season ]['rounds'] ) as $round ) {
									$match_dates[] = $round->date;
								}
							}
							require RACKETMANAGER_PATH . 'admin/cup/setup.php';
						}
					}
				}
			}
		}
	}
	/**
	 * Display event page
	 */
	public function display_event_page() {
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
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
				} elseif ( isset( $_GET['editleague'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$league_id    = intval( $_GET['editleague'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$league_edit  = get_league( $league_id );
					$league_title = $league_edit->title;
				} elseif ( isset( $_POST['doactionleague'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$this->delete_leagues_from_event();
					$this->printMessage();
				} elseif ( isset( $_POST['updateSettings'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$tab = 'settings';
					$this->update_event_settings( $event );
					$this->printMessage();
				}
				if ( ! isset( $season ) ) {
					$event_season = isset( $event->current_season['name'] ) ? $event->current_season['name'] : '';
					$season       = ( isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : $event_season );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}
				include_once RACKETMANAGER_PATH . 'admin/league/show-event.php';

			}
		}
	}
	/**
	 * Display constitution page
	 */
	public function display_constitution_page() {
		global $racketmanager;
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
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
			if ( isset( $_POST['doactionconstitution'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$tab = 'constitution';
				$this->delete_constitution_teams();
				$this->printMessage();
			} elseif ( isset( $_POST['saveconstitution'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
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
				$event_season = isset( $event->current_season['name'] ) ? $event->current_season['name'] : '';
				$season       = ( isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : $event_season );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
			include_once RACKETMANAGER_PATH . 'admin/league/show-constitution.php';
		}
	}
	/**
	 * Display schedule page
	 */
	public function display_schedule_page() {
		global $racketmanager;
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$racketmanager->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$racketmanager->printMessage();
		} elseif ( isset( $_POST['scheduleAction'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$tab = 'schedule';
			if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_schedule-matches' ) ) {
				$racketmanager->set_message( __( 'Security token invalid', 'racketmanager' ), true );
				$racketmanager->printMessage();
				return;
			}
			if ( isset( $_POST['actionSchedule'] ) ) {
				if ( 'schedule' === $_POST['actionSchedule'] ) {
					if ( isset( $_POST['event'] ) ) {
						$this->scheduleLeagueMatches( $_POST['event'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					}
				} elseif ( 'delete' === $_POST['actionSchedule'] ) {
					if ( isset( $_POST['event'] ) ) {
						foreach ( $_POST['event'] as $event_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
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
				$event_season = isset( $event->current_season['name'] ) ? $event->current_season['name'] : '';
				$season       = ( isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : $event_season );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
			include_once RACKETMANAGER_PATH . 'admin/league/show-schedule.php';
		}
	}
	/**
	 * Display cup matches page
	 */
	public function display_cup_matches_page() {
		global $competition;
		if ( ! current_user_can( 'edit_matches' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			//phpcs:disable WordPress.Security.NonceVerification.Recommended
			$finalkey       = isset( $_GET['final'] ) ? intval( $_GET['final'] ) : null;
			$season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
			$competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
			$league_id      = isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : null;
			$finalkey       = isset( $_GET['final'] ) ? sanitize_text_field( wp_unslash( $_GET['final'] ) ) : null;
			//phpcs:enable WordPress.Security.NonceVerification.Recommended
			if ( $competition_id ) {
				$competition = get_competition( $competition_id );
				if ( $competition ) {
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
								if ( 'final' !== $finalkey && ! empty( $league->current_season['home_away'] ) && 'true' === $league->current_season['home_away'] ) {
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
	 * Display cup match page
	 */
	public function display_cup_match_page() {
		if ( ! current_user_can( 'edit_matches' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			//phpcs:disable WordPress.Security.NonceVerification.Recommended
			$finalkey       = isset( $_GET['final'] ) ? intval( $_GET['final'] ) : null;
			$season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
			$competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
			$league_id      = isset( $_GET['league'] ) ? intval( $_GET['league'] ) : null;
			$finalkey       = isset( $_GET['final'] ) ? sanitize_text_field( wp_unslash( $_GET['final'] ) ) : null;
			$match_id       = isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : null;
			//phpcs:enable WordPress.Security.NonceVerification.Recommended
			if ( $competition_id ) {
				$competition = get_competition( $competition_id );
				if ( $competition ) {
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
	 * Action promotion and relegation function
	 *
	 * @return void
	 */
	private function action_promotion_relegation() {
		global $racketmanager;
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
		} elseif ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'constitution-bulk' ) ) {
			$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
		} else {
			$valid = true;
			$js    = false;
			if ( isset( $_POST['js-active'] ) ) {
				$js = ( 1 === intval( $_POST['js-active'] ) ) ? true : false;
			}
			$rank = 0;
			if ( isset( $_POST['table_id'] ) ) {
				$latest_season = isset( $_POST['latest_season'] ) ? sanitize_text_field( wp_unslash( $_POST['latest_season'] ) ) : null;
				// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				$event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : null;
				if ( $event_id ) {
					$event = get_event( $event_id );
					if ( $event ) {
						$teams = array();
						foreach ( $_POST['table_id'] as $table_id ) {
							$status = isset( $_POST['status'][ $table_id ] ) ? $_POST['status'][ $table_id ] : null;
							if ( empty( $rank ) && $status ) {
								$valid = false;
							}
							$team_id   = isset( $_POST['team_id'][ $table_id ] ) ? $_POST['team_id'][ $table_id ] : null;
							$league_id = isset( $_POST['league_id'][ $table_id ] ) ? $_POST['league_id'][ $table_id ] : null;
							$old_rank  = isset( $_POST['old_rank'][ $table_id ] ) ? $_POST['old_rank'][ $table_id ] : null;
							$status    = isset( $_POST['status'][ $table_id ] ) ? $_POST['status'][ $table_id ] : null;
							if ( $js ) {
								++$rank;
							} else {
								$rank = isset( $_POST['rank'][ $table_id ] ) ? $_POST['rank'][ $table_id ] : '';
							}
							$team            = new \stdClass();
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
								$racketmanager->set_message( __( 'Error with promotion and relagation', 'racketmanager' ), true );
							}
						}
					} else {
						$racketmanager->set_message( __( 'Promotion and relagation has already occurred', 'racketmanager' ), true );
					}
				}
				// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			}
		}
	}
}
