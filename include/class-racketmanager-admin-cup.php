<?php
/**
 * RacketManager-Admin API: RacketManager-admin-cup class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Cup
 */

namespace Racketmanager;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration Cup panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class RacketManager_Admin_Cup extends RacketManager_Admin {

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
	 * Display cups page
	 */
	public function display_cups_page() {
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$competition_type  = 'cup';
			$type              = '';
			$season            = '';
			$standalone        = true;
			$competition_query = array( 'type' => $competition_type );
			$page_title        = ucfirst( $competition_type ) . ' ' . __( 'Competitions', 'racketmanager' );
			include_once RACKETMANAGER_PATH . '/admin/show-competitions.php';
		}
	}
	/**
	 * Display cup season list
	 */
	public function display_cup_seasons_page() {
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
	 * Display cup season overview
	 */
	public function display_cup_overview_page() {
		if ( isset( $_GET['competition_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$competition_id = intval( $_GET['competition_id'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$competition    = get_competition( $competition_id );
			if ( $competition ) {
				$season = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( $season ) {
					if ( isset( $competition->seasons[ $season ] ) ) {
						$competition->events = $competition->get_events();
						$tab                 = 'overview';
						$cup_season          = (object) $competition->seasons[ $season ];
						if ( isset( $cup_season->date_closing ) && $cup_season->date_closing <= gmdate( 'Y-m-d' ) ) {
							$cup_season->is_active = true;
						} else {
							$cup_season->is_active = false;
						}
						$cup_season->is_open    = false;
						$cup_season->venue_name = null;
						if ( isset( $cup_season->venue ) ) {
							$venue_club = get_club( $cup_season->venue );
							if ( $venue_club ) {
								$cup_season->venue_name = $venue_club->shortcode;
							}
						}
						$cup_season->entries = $competition->get_clubs( array( 'status' => 1 ) );
						require RACKETMANAGER_PATH . 'admin/cup/show-season.php';

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
	 * Display cup setup
	 */
	public function display_cup_setup_page() {
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
							$cup_season = $competition->seasons[ $season ];
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
									$cup_season['match_dates'] = array();
									foreach ( array_reverse( $rounds ) as $match_date ) {
										$cup_season['match_dates'][] = $match_date;
									}
									$cup_seasons                  = $competition->seasons;
									$cup_season['num_match_days'] = count( $cup_season['match_dates'] );
									$cup_seasons[ $season ]       = $cup_season;
									$competition->update_seasons( $cup_seasons );
									$this->set_message( __( 'Cup match dates updated', 'racketmanager' ) );
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
					$this->calculate_team_ratings( $competition_id, $season );
					$this->set_message( __( 'Cup ratings set', 'racketmanager' ) );
					$this->printMessage();
				}
			}
			$season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
			$competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $competition_id ) {
				$competition = get_competition( $competition_id );
				if ( $competition ) {
					$match_dates = $competition->seasons[ $season ]['match_dates'];
					require RACKETMANAGER_PATH . 'admin/cup/setup.php';
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
			} elseif ( isset( $_POST['rank'] ) ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_calculate_ratings' ) ) {
					$racketmanager->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$racketmanager->printMessage();
				} else {
					$valid          = true;
					$competition_id = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
					$league_id      = isset( $_POST['league_id'] ) ? intval( $_POST['league_id'] ) : null;
					$season         = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
					$this->calculate_team_ratings( $competition_id, $season, $league_id );
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
	 *
	 * Display cup create/edit page
	 */
	public function display_cup_page() {
		global $racketmanager;
		$racketmanager->error_fields   = array();
		$racketmanager->error_messages = array();
		if ( ! current_user_can( 'edit_seasons' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} elseif ( isset( $_POST['addSeason'] ) ) {
			if ( ! current_user_can( 'edit_seasons' ) ) {
				$racketmanager->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
			} elseif ( isset( $_POST['competition_id'] ) ) {
				check_admin_referer( 'racketmanager_add-season' );
				$competition_id = intval( $_POST['competition_id'] );
				$competition    = get_competition( $competition_id );
				if ( $competition ) {
					$season                        = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
					$cup_season                    = new \stdClass();
					$cup_season->name              = $season;
					$cup_season->venue             = isset( $_POST['venue'] ) ? intval( $_POST['venue'] ) : null;
					$cup_season->date_end          = isset( $_POST['dateEnd'] ) ? sanitize_text_field( wp_unslash( $_POST['dateEnd'] ) ) : null;
					$cup_season->date_end          = $cup_season->date_end;
					$cup_season->date_open         = isset( $_POST['dateOpen'] ) ? sanitize_text_field( wp_unslash( $_POST['dateOpen'] ) ) : null;
					$cup_season->date_open         = $cup_season->date_open;
					$cup_season->date_closing      = isset( $_POST['dateClose'] ) ? sanitize_text_field( wp_unslash( $_POST['dateClose'] ) ) : null;
					$cup_season->date_start        = isset( $_POST['dateStart'] ) ? sanitize_text_field( wp_unslash( $_POST['dateStart'] ) ) : null;
					$cup_season->date_start        = $cup_season->date_start;
					$cup_season->competition_code  = isset( $_POST['competition_code'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_code'] ) ) : null;
					$cup_season->fixed_match_dates = isset( $_POST['fixedMatchDates'] ) ? ( 'true' === $_POST['fixedMatchDates'] ? true : false ) : false;
					$cup_season->home_away         = isset( $_POST['homeAway'] ) ? ( 'true' === $_POST['homeAway'] ? true : false ) : false;
					$cup_season->grade             = isset( $_POST['grade'] ) ? sanitize_text_field( wp_unslash( $_POST['grade'] ) ) : null;
					$this->set_competition_dates( $cup_season, $competition );
					if ( $racketmanager->error ) {
						$racketmanager->printMessage();
					} else {
						$racketmanager->set_message( __( 'Season added to cup', 'racketmanager' ) );
						$this->schedule_open_activities( $competition->id, $cup_season );
					}
				} else {
					$racketmanager->set_message( __( 'Competition not found', 'racketmanager' ), true );
				}
			}
			$racketmanager->printMessage();
		} elseif ( isset( $_POST['editSeason'] ) ) {
			if ( ! current_user_can( 'edit_seasons' ) ) {
				$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
			} else {
				check_admin_referer( 'racketmanager_manage-season' );
				if ( isset( $_POST['competition_id'] ) ) {
					$competition_id = intval( $_POST['competition_id'] );
					$competition    = get_competition( $competition_id );
					if ( $competition ) {
						$season = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
						if ( $season ) {
							$cup_season                    = new \stdClass();
							$cup_season->name              = $season;
							$cup_season->venue             = isset( $_POST['venue'] ) ? intval( $_POST['venue'] ) : null;
							$cup_season->date_end          = isset( $_POST['dateEnd'] ) ? sanitize_text_field( wp_unslash( $_POST['dateEnd'] ) ) : null;
							$cup_season->date_end          = $cup_season->date_end;
							$cup_season->date_open         = isset( $_POST['dateOpen'] ) ? sanitize_text_field( wp_unslash( $_POST['dateOpen'] ) ) : null;
							$cup_season->date_open         = $cup_season->date_open;
							$cup_season->date_closing      = isset( $_POST['dateClose'] ) ? sanitize_text_field( wp_unslash( $_POST['dateClose'] ) ) : null;
							$cup_season->date_start        = isset( $_POST['dateStart'] ) ? sanitize_text_field( wp_unslash( $_POST['dateStart'] ) ) : null;
							$cup_season->date_start        = $cup_season->date_start;
							$cup_season->competition_code  = isset( $_POST['competition_code'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_code'] ) ) : null;
							$cup_season->fixed_match_dates = isset( $_POST['fixedMatchDates'] ) ? ( 'true' === $_POST['fixedMatchDates'] ? true : false ) : false;
							$cup_season->home_away         = isset( $_POST['homeAway'] ) ? ( 'true' === $_POST['homeAway'] ? true : false ) : false;
							$cup_season->grade             = isset( $_POST['grade'] ) ? sanitize_text_field( wp_unslash( $_POST['grade'] ) ) : null;
							$this->set_competition_dates( $cup_season, $competition );
							$this->schedule_open_activities( $competition->id, $cup_season );
						} else {
							$racketmanager->set_message( __( 'Season not found', 'racketmanager' ), true );
						}
					} else {
						$racketmanager->set_message( __( 'Competition not found', 'racketmanager' ), true );
					}
				}
			}
			$racketmanager->printMessage();
		} elseif ( isset( $_GET['competition_id'] ) ) {
			$competition_id = intval( $_GET['competition_id'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$competition    = get_competition( $competition_id );
			$season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
			$cup_season     = isset( $competition->seasons[ $season ] ) ? (object) $competition->seasons[ $season ] : null;
		}
		$edit = false;
		if ( empty( $season ) ) {
			$form_title  = __( 'Add Cup Season', 'racketmanager' );
			$form_action = __( 'Add', 'racketmanager' );
		} else {
			$edit        = true;
			$form_title  = __( 'Edit Cup Season', 'racketmanager' );
			$form_action = __( 'Update', 'racketmanager' );
		}
		$clubs = $this->get_clubs(
			array(
				'type' => 'affiliated',
			)
		);
		include_once RACKETMANAGER_PATH . 'admin/cup/season-edit.php';
	}
	/**
	 * Display cup plan page
	 */
	public function display_cup_plan_page() {
		global $racketmanager;
		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			if ( isset( $_POST['savePlan'] ) ) {
				check_admin_referer( 'racketmanager_cup-planner' );
				if ( isset( $_POST['competition_id'] ) ) {
					// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$competition = get_competition( intval( $_POST['competition_id'] ) );
					if ( $competition ) {
						$season = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
						if ( $season ) {
							$courts     = isset( $_POST['court'] ) ? $_POST['court'] : null;
							$start_time = isset( $_POST['starttime'] ) ? $_POST['starttime'] : null;
							$matches    = isset( $_POST['match'] ) ? $_POST['match'] : null;
							$match_time = isset( $_POST['matchtime'] ) ? $_POST['matchtime'] : null;
							// phpcs:enable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							$competition->save_plan( $season, $courts, $start_time, $matches, $match_time );
						} else {
							$racketmanager->set_message( __( 'Season not specified', 'racketmanager' ), true );
						}
					} else {
						$racketmanager->set_message( __( 'Competition not specified', 'racketmanager' ), true );
					}
					$racketmanager->printMessage();
				}
				$tab = 'matches';
			} elseif ( isset( $_POST['resetPlan'] ) ) {
				check_admin_referer( 'racketmanager_cup-planner' );
				if ( isset( $_POST['competition_id'] ) ) {
					$competition = get_competition( intval( $_POST['competition_id'] ) );
					if ( $competition ) {
						$season = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
						if ( $season ) {
							$matches = isset( $_POST['match'] ) ? $_POST['match'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							$competition->reset_plan( $season, $matches );
						}
					}
					$racketmanager->printMessage();
				}
				$tab = 'matches';
			} elseif ( isset( $_POST['saveCup'] ) ) {
				check_admin_referer( 'racketmanager_cup' );
				if ( isset( $_POST['competition_id'] ) ) {
					$competition = get_competition( intval( $_POST['competition_id'] ) );
					// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					if ( $competition ) {
						$season = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
						if ( $season ) {
							$start_time     = isset( $_POST['starttime'] ) ? sanitize_text_field( wp_unslash( $_POST['starttime'] ) ) : null;
							$num_courts     = isset( $_POST['numcourts'] ) ? intval( $_POST['numcourts'] ) : null;
							$time_increment = isset( $_POST['timeincrement'] ) ? sanitize_text_field( wp_unslash( $_POST['timeincrement'] ) ) : null;
							$competition->update_plan( $season, $start_time, $num_courts, $time_increment );
						} else {
							$racketmanager->set_message( __( 'Season not specified', 'racketmanager' ), true );
						}
					} else {
						$racketmanager->set_message( __( 'Competition not specified', 'racketmanager' ), true );
					}
					$racketmanager->printMessage();
				}
				$tab = 'config';
			}

			if ( isset( $_GET['competition_id'] ) ) {
				$competition_id = intval( $_GET['competition_id'] );
				$competition    = get_competition( $competition_id );
				if ( $competition ) {
					$season = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
					if ( $season ) {
						$final_matches = $this->get_matches(
							array(
								'season'         => $season,
								'final'          => 'final',
								'competition_id' => $competition_id,
							)
						);
					}
				}
			}
			if ( $competition ) {
				$competition->events = $competition->get_events();
				if ( $season ) {
					$cup_season             = (object) $competition->seasons[ $season ];
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
				}
			}
			if ( empty( $tab ) ) {
				$tab = 'matches';
			}
			include_once RACKETMANAGER_PATH . '/admin/cup/plan.php';
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
	 * Set season dates for cup function
	 *
	 * @param object $cup_season season details.
	 * @param object $competition competition details.
	 * @return void
	 */
	private function set_competition_dates( $cup_season, $competition ) {
		global $racketmanager;
		$updates = false;
		if ( empty( $cup_season->name ) ) {
			$racketmanager->error_fields[]   = 'season';
			$racketmanager->error_messages[] = __( 'Season not specified', 'racketmanager' );
		}
		if ( empty( $cup_season->date_open ) ) {
			$racketmanager->error_messages[] = __( 'Opening date must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'date_open';
		}
		if ( empty( $cup_season->date_end ) ) {
			$racketmanager->error_messages[] = __( 'End date must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'date_end';
		}
		if ( empty( $cup_season->date_start ) ) {
			$racketmanager->error_messages[] = __( 'Start date must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'date_start';
		}
		if ( empty( $cup_season->date_closing ) ) {
			$racketmanager->error_messages[] = __( 'Closing date must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'date_closing';
		}
		if ( empty( $cup_season->venue ) ) {
			$racketmanager->error_messages[] = __( 'Venue must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'venue';
		}
		if ( is_null( $cup_season->fixed_match_dates ) ) {
			$racketmanager->error_messages[] = __( 'Match date option must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'fixedMatchDates';
		}
		if ( is_null( $cup_season->home_away ) ) {
			$racketmanager->error_messages[] = __( 'Numer of legs must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'homeAway';
		}
		if ( empty( $cup_season->grade ) ) {
			$racketmanager->error_messages[] = __( 'Grade must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'grade';
		}
		if ( empty( $racketmanager->error_fields ) ) {
			$season = isset( $competition->seasons[ $cup_season->name ] ) ? $competition->seasons[ $cup_season->name ] : null;
			if ( $season ) {
				if ( empty( $season['date_open'] ) || $season['date_open'] !== $cup_season->date_open ) {
					$updates             = true;
					$season['date_open'] = $cup_season->date_open;
				}
				if ( empty( $season['date_end'] ) || $season['date_end'] !== $cup_season->date_end ) {
					$updates            = true;
					$season['date_end'] = $cup_season->date_end;
				}
				if ( empty( $season['date_start'] ) || $season['date_start'] !== $cup_season->date_start ) {
					$updates              = true;
					$season['date_start'] = $cup_season->date_start;
				}
				if ( empty( $season['date_closing'] ) || $season['date_closing'] !== $cup_season->date_closing ) {
					$updates                = true;
					$season['date_closing'] = $cup_season->date_closing;
				}
				if ( $season['venue'] !== $cup_season->venue ) {
					$updates         = true;
					$season['venue'] = $cup_season->venue;
				}
				if ( empty( $season['competition_code'] ) ) {
					if ( ! empty( $cup_season->competition_code ) ) {
						$updates                    = true;
						$season['competition_code'] = $cup_season->competition_code;
					} else {
						$season['competition_code'] = null;
					}
				} elseif ( $season['competition_code'] !== $cup_season->competition_code ) {
					$updates                    = true;
					$season['competition_code'] = $cup_season->competition_code;
				}
				if ( empty( $season['fixed_match_dates'] ) || $season['fixed_match_dates'] !== $cup_season->fixed_match_dates ) {
					$updates                     = true;
					$season['fixed_match_dates'] = $cup_season->fixed_match_dates;
				}
				if ( empty( $season['home_away'] ) || $season['home_away'] !== $cup_season->home_away ) {
					$updates             = true;
					$season['home_away'] = $cup_season->home_away;
				}
				if ( empty( $season['grade'] ) || $season['grade'] !== $cup_season->grade ) {
					$updates         = true;
					$season['grade'] = $cup_season->grade;
				}
				if ( $updates ) {
					$season_data                   = new \stdclass();
					$season_data->season           = $season['name'];
					$season_data->num_match_days   = $season['num_match_days'];
					$season_data->object_id        = $competition->id;
					$season_data->match_dates      = isset( $season['match_dates'] ) ? $season['match_dates'] : false;
					$season_data->fixed_dates      = isset( $season['fixed_match_dates'] ) ? $season['fixed_match_dates'] : false;
					$season_data->home_away        = isset( $season['home_away'] ) ? $season['home_away'] : false;
					$season_data->status           = 'live';
					$season_data->date_open        = $season['date_open'];
					$season_data->date_closing     = $season['date_closing'];
					$season_data->date_start       = $season['date_start'];
					$season_data->date_end         = $season['date_end'];
					$season_data->competition_code = $season['competition_code'];
					$season_data->type             = 'competition';
					$season_data->is_box           = false;
					$season_data->venue            = $season['venue'];
					$season_data->grade            = $season['grade'];
					$this->edit_season( $season_data );
				} else {
					$racketmanager->set_message( __( 'No updates', 'racketmanager' ), true );
				}
			} else {
				$competition_season = $this->add_season_to_competition( $cup_season->name, $competition->id );
				if ( $competition_season ) {
					$season_data                   = new \stdclass();
					$season_data->season           = $competition_season['name'];
					$season_data->num_match_days   = $competition_season['num_match_days'];
					$season_data->object_id        = $competition->id;
					$season_data->match_dates      = false;
					$season_data->fixed_dates      = false;
					$season_data->home_away        = false;
					$season_data->status           = 'live';
					$season_data->date_open        = $cup_season->date_open;
					$season_data->date_closing     = $cup_season->date_closing;
					$season_data->date_start       = $cup_season->date_start;
					$season_data->date_end         = $cup_season->date_end;
					$season_data->type             = 'competition';
					$season_data->is_box           = false;
					$season_data->competition_code = $cup_season->competition_code;
					$season_data->venue            = $cup_season->venue;
					$season_data->grade            = $cup_season->grade;
					$this->edit_season( $season_data );
				}
			}
		} else {
			$racketmanager->set_message( __( 'Errors found', 'racketmanager' ), true );
		}
	}
	/**
	 * Calculate team ratings function
	 *
	 * @param int $competition_id competition id.
	 * @param int $season season name.
	 * @return void
	 */
	private function calculate_team_ratings( $competition_id, $season ) {
		global $racketmanager;
		if ( $competition_id ) {
			$competition = get_competition( $competition_id );
			if ( $competition ) {
				if ( $season ) {
					$racketmanager->calculate_cup_ratings( $competition_id, $season );
				}
			}
		}
	}
	/**
	 * Schedule cup activities function
	 *
	 * @param int    $competition_id competition id.
	 * @param object $season season name.
	 * @return void
	 */
	private function schedule_open_activities( $competition_id, $season ) {
		$competition = get_competition( $competition_id );
		if ( $competition && ( $competition->is_pending || $competition->is_open ) ) {
			$this->schedule_cup_ratings( $competition_id, $season );
			$this->schedule_team_competition_emails( $competition_id, $season );
		}
	}
	/**
	 * Schedule cup ratings setting function
	 *
	 * @param int    $competition_id competition id.
	 * @param object $season season name.
	 * @return void
	 */
	private function schedule_cup_ratings( $competition_id, $season ) {
		global $racketmanager;
		if ( empty( $season->date_closing ) ) {
			$day            = intval( gmdate( 'd' ) );
			$month          = intval( gmdate( 'm' ) );
			$year           = intval( gmdate( 'Y' ) );
			$hour           = intval( gmdate( 'H' ) );
			$schedule_start = mktime( $hour, 0, 0, $month, $day, $year );
		} else {
			$schedule_date  = strtotime( $season->date_closing );
			$day            = intval( gmdate( 'd', $schedule_date ) );
			$month          = intval( gmdate( 'm', $schedule_date ) );
			$year           = intval( gmdate( 'Y', $schedule_date ) );
			$schedule_start = mktime( 23, 59, 0, $month, $day, $year );
		}
		$schedule_name   = 'rm_calculate_cup_ratings';
		$schedule_args[] = intval( $competition_id );
		$schedule_args[] = intval( $season->name );
		Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
		$success = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
		if ( ! $success ) {
			$racketmanager->set_message( __( 'Error scheduling cup ratings calculation', 'racketmanager' ), true );
		}
	}
}
