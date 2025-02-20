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
	 * Display setup
	 */
	public function display_setup_page() {
		global $racketmanager;
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
									$updates            = $competition->update_seasons( $seasons );
									if ( $updates ) {
										$this->set_message( __( 'Match dates updated', 'racketmanager' ) );
										$events = $competition->get_events();
										foreach ( $events as $competition_event ) {
											$seasons = $competition_event->seasons;
											if ( empty( $competition_event->offset ) ) {
												$match_dates = $current_season['match_dates'];
											} else {
												$i = 0;
												foreach( $current_season['match_dates'] as $match_date ) {
													$match_dates[ $i ] = RacketManager_Util::amend_date( $match_date, $competition_event->offset, '+', 'week' );
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
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$this->printMessage();
				} else {
					$valid          = true;
					$competition_id = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
					$season         = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
					$competition    = get_competition( $competition_id );
					if ( $competition && $season ) {
						$racketmanager->calculate_team_ratings( $competition->id, $season );
					}
					$this->set_message( __( 'League ratings set', 'racketmanager' ) );
					$this->printMessage();
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
	 * Display event setup
	 */
	public function display_setup_event_page() {
		global $racketmanager;
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
					require RACKETMANAGER_PATH . 'admin/includes/setup.php';
				}
			}
		}
	}
	/**
	 * Display league page
	 */
	public function display_league_page() {
		global $league, $championship, $competition;

		if ( ! current_user_can( 'view_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$league_id = isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : null;
			if ( $league_id ) {
				$league = get_league( $league_id );
				if ( $league ) {
					
				}
			}
			$league    = get_league();
			$league_id = $league->id;
			$league->set_season();
			$season      = $league->get_season();
			$league_mode = ( isset( $league->event->competition->mode ) ? ( $league->event->competition->mode ) : '' );
			$tab         = 'standings';
			$match_day   = false;
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST['doaction'] ) ) {
				$this->handle_league_teams_action( $league );
			} elseif ( isset( $_POST['delmatches'] ) ) {
				$this->delete_matches_from_league();
				$tab = 'matches';
			} elseif ( isset( $_POST['updateLeague'] ) && 'team' === $_POST['updateLeague'] ) {
				$this->league_manage_team( $league );
				if ( $league->is_championship ) {
					$tab = 'preliminary';
				}
			} elseif ( isset( $_POST['updateLeague'] ) && 'teamPlayer' === $_POST['updateLeague'] ) {
				$this->add_player_team_to_league( $league );
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
				$tab = 'standings';
			} elseif ( isset( $_POST['saveRanking'] ) ) {
				$this->league_manual_rank_teams( $league );
				$tab = 'standings';
			} elseif ( isset( $_POST['randomRanking'] ) ) {
				$this->league_random_rank_teams( $league );
				$tab = 'standings';
			} elseif ( isset( $_POST['ratingPointsRanking'] ) ) {
				$this->league_rating_points_rank_teams( $league );
				$tab = 'standings';
			}
			$this->printMessage();
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			// check if league is a cup championship.
			$cup = ( 'championship' === $league_mode ) ? true : false;
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$group     = isset( $_GET['group'] ) ? sanitize_text_field( wp_unslash( $_GET['group'] ) ) : '';
			$team_id   = isset( $_GET['team_id'] ) ? intval( $_GET['team_id'] ) : false;
			$match_day = false;
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
			$options    = $this->options;
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
			if ( intval( $league->num_matches_per_page ) > 0 ) {
				$match_args['limit'] = intval( $league->num_matches_per_page );
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
			include_once RACKETMANAGER_PATH . '/admin/show-league.php';
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
