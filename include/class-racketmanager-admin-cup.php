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
	 * Constructor
	 */
	public function __construct() {
	}
	/**
	 * Display cups page
	 */
	public function display_cups_page(): void {
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
	public function display_cup_seasons_page(): void {
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
			require RACKETMANAGER_PATH . 'admin/includes/show-seasons.php';
		}
	}
	/**
	 * Display cup season overview
	 */
	public function display_cup_overview_page(): void {
		if ( isset( $_GET['competition_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$competition_id = intval( $_GET['competition_id'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$competition    = get_competition( $competition_id );
			if ( $competition ) {
				$season = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( $season ) {
					if ( isset( $competition->seasons[ $season ] ) ) {
						$competition->events = $competition->get_events();
						$i                   = 0;
						foreach ( $competition->events as $event ) {
							$leagues = $event->get_leagues();
							if ( $leagues ) {
								$competition->events[ $i ]->leagues = $leagues;
							}
							++$i;
							$leagues = $event->get_leagues();
						}
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
	public function display_cup_draw_page(): void {
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
						require RACKETMANAGER_PATH . 'admin/cup/draw.php';
					}
				}
			}
		}
	}
	/**
	 * Display cup setup
	 */
	public function display_cup_setup_page(): void {
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
				} else {
					$valid          = true;
					$competition_id = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
					$season         = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
					$this->calculate_team_ratings( $competition_id, $season );
					$this->set_message( __( 'Cup ratings set', 'racketmanager' ) );
				}
				$this->printMessage();
			}
			$season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
			$competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $competition_id ) {
				$competition = get_competition( $competition_id );
				if ( $competition ) {
					$season_data = $competition->seasons[ $season ];
					$match_dates = $season_data['match_dates'];
					if ( empty( $match_dates ) ) {
						$date_end     = date_create( $season_data['date_end'] );
						$day_end      = date_format( $date_end, 'N' );
						$day_adjust   = $day_end - 1;
						$end_date     = Racketmanager_Util::amend_date( $season_data['date_end'], $day_adjust, '-' );
						$round_length = $season_data['round_length'] ?? 7;
						$match_date   = null;
						$i            = 0;
						foreach( $competition->finals as $final ) {
							$r = $final['round'] - 1;
							if ( 0 === $i ) {
								$match_date = $season_data['date_end'];
							} elseif ( 1 === $i ) {
								if ( $competition->fixed_match_dates ) {
									$match_date = Racketmanager_Util::amend_date( $end_date, $round_length, '-' );
								} else {
									$match_date = Racketmanager_Util::amend_date( $season_data['date_end'], 7 );
								}
							} elseif ( 0 === $r && $competition->fixed_match_dates ) {
								$match_date = $competition->date_start;
							} else {
								$match_date = Racketmanager_Util::amend_date( $match_date, $round_length, '-' );
							}
							$match_dates[ $r ] = $match_date;
							++$i;
						}
					}
					require RACKETMANAGER_PATH . 'admin/cup/setup.php';
				}
			}
		}
	}
	/**
	 * Display event setup
	 */
	public function display_setup_event_page(): void {
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
					$action    = sanitize_text_field( wp_unslash( $_POST['action'] ) );
					$league_id = isset( $_POST['league_id'] ) ? intval( $_POST['league_id'] ) : null;
					$season    = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
					$rounds    = $_POST['rounds'] ?? null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$league    = get_league( $league_id );
					if ( $league ) {
						$this->set_championship_matches( $league, $season, $rounds, $action );
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
					$this->calculate_team_ratings( $competition_id, $season );
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
								if ( empty( $league->event->seasons[ $season ]['match_dates'] ) ) {
									if ( empty( $league->event->offset ) ) {
										$match_dates = $league->event->competition->seasons[ $season ]['match_dates'];
									} elseif( isset( $league->event->competition->seasons[ $season ]['match_dates'] ) && is_array( $league->event->competition->seasons[ $season ]['match_dates'] ) ) {
										$i = 0;
										$num_match_dates = count( $league->event->competition->seasons[ $season ]['match_dates'] );
										foreach( $league->event->competition->seasons[ $season ]['match_dates'] as $match_date ) {
											if ( $i === $num_match_dates - 1 ) {
												$match_dates[ $i ] = $match_date;
											} else {
												$match_dates[ $i ] = Racketmanager_Util::amend_date( $match_date, $league->event->offset, '+', 'week' );
											}
											++$i;
										}
									} else {
										$match_dates = array();
									}
								} else {
									$match_dates = $league->event->seasons[ $season ]['match_dates'];
								}
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
	 * Display cup plan page
	 */
	public function display_cup_plan_page(): void {
		global $racketmanager;
		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$competition = null;
			$season      = null;
			if ( isset( $_POST['savePlan'] ) ) {
				check_admin_referer( 'racketmanager_cup-planner' );
				if ( isset( $_POST['competition_id'] ) ) {
					// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$competition = get_competition( intval( $_POST['competition_id'] ) );
					if ( $competition ) {
						$season = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
						if ( $season ) {
							$courts     = $_POST['court'] ?? null;
							$start_time = $_POST['startTime'] ?? null;
							$matches    = $_POST['match'] ?? null;
							$match_time = $_POST['matchtime'] ?? null;
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
							$matches = $_POST['match'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
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
							$start_time     = isset( $_POST['startTime'] ) ? sanitize_text_field( wp_unslash( $_POST['startTime'] ) ) : null;
							$num_courts     = isset( $_POST['numCourts'] ) ? intval( $_POST['numCourts'] ) : null;
							$time_increment = isset( $_POST['timeIncrement'] ) ? sanitize_text_field( wp_unslash( $_POST['timeIncrement'] ) ) : null;
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
	public function display_cup_matches_page(): void {
		global $competition;
		if ( ! current_user_can( 'edit_matches' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
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
				if ( $competition ) {
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
	 * Display cup match page
	 */
	public function display_cup_match_page(): void {
		if ( ! current_user_can( 'edit_matches' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
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
									include_once RACKETMANAGER_PATH . '/admin/includes/match.php';
								}
							}
						}
					}
				}
			}
		}
	}
}
