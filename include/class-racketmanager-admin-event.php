<?php
/**
 * RacketManager-Admin API: RacketManager-admin-event class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Event
 */

namespace Racketmanager;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration Event panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class RacketManager_Admin_Event extends RacketManager_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		global $racketmanager_ajax_admin;
		parent::__construct();
	}
	/**
	 * Handle config page function
	 *
	 * @return void
	 */
	public function display_config_page() {
		global $racketmanager;
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$racketmanager->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$racketmanager->printMessage();
		} else {
			$competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
			if ( $competition_id ) {
				$competition = get_competition( $competition_id );
				if ( ! $competition ) {
					$racketmanager->set_message( __( 'Competition not found', 'racketmanager' ), true );
					$racketmanager->printMessage();
					return;
				}
			} else {
				$racketmanager->set_message( __( 'Competition id not found', 'racketmanager' ), true );
				$racketmanager->printMessage();
				return;
			}
			$tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
			if ( $tournament_id ) {
				$tournament = get_tournament( $tournament_id );
			}
			$event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $event_id ) {
				$event    = get_event( $event_id );
				if ( $event ) {
					$event->config = (object) $event->settings;
					if ( isset( $_POST['updateEventConfig'] ) ) {
						if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-event-config' ) ) {
							$racketmanager->set_message( __( 'Security token invalid', 'racketmanager' ), true );
							$racketmanager->printMessage();
							return;
						} elseif ( isset( $_POST['event_id'] ) ) {
							if ( intval( $_POST['event_id'] ) !== $event_id ) {
								$racketmanager->set_message( __( 'Event id differs', 'racketmanager' ), true );
								$racketmanager->printMessage();
								return;
							} else {
								$config                     = new \stdClass();
								$config->name               = isset( $_POST['event_name'] ) ? sanitize_text_field( wp_unslash( $_POST['event_name'] ) ) : null;
								$config->type               = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : null;
								$config->age_limit          = isset( $_POST['age_limit'] ) ? sanitize_text_field( wp_unslash( $_POST['age_limit'] ) ) : null;
								$config->age_offset         = isset( $_POST['age_offset'] ) ? sanitize_text_field( wp_unslash( $_POST['age_offset'] ) ) : null;
								$config->scoring            = isset( $_POST['scoring'] ) ? sanitize_text_field( wp_unslash( $_POST['scoring'] ) ) : null;
								$config->num_sets           = isset( $_POST['num_sets'] ) ? intval( $_POST['num_sets'] ) : null;
								$config->num_rubbers        = isset( $_POST['num_rubbers'] ) ? intval( $_POST['num_rubbers'] ) : null;
								$config->match_days_allowed = isset( $_POST['match_days_allowed'] ) ? wp_unslash( $_POST['match_days_allowed'] ) : null;
								$config->offset             = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : null;
								$config->primary_league     = isset( $_POST['primary_league'] ) ? intval( $_POST['primary_league'] ) : null;
								$event->config              = $config;
								$updates                    = $event->set_config( $config );
								if ( $updates ) {
									$racketmanager->set_message( __( 'Event config updated', 'racketmanager' ) );
								} elseif ( empty( $racketmanager->error_messages ) ) {
									$racketmanager->set_message( __( 'No updates found', 'racketmanager' ), 'warning' );
								} else {
									$racketmanager->set_message( __( 'Errors found', 'racketmanager' ), true );
								}
								$racketmanager->printMessage();
							}
						}
					}
				} else {
					$racketmanager->set_message( __( 'Event not found', 'racketmanager' ), true );
					$racketmanager->printMessage();
					return;
				}
			} else {
				if ( isset( $_POST['addEventConfig'] ) ) {
					if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-event-config' ) ) {
						$racketmanager->set_message( __( 'Security token invalid', 'racketmanager' ), true );
						$racketmanager->printMessage();
					} elseif ( ! empty( $_POST['event_id'] ) ) {
						$racketmanager->set_message( __( 'Event id invalid for new event', 'racketmanager' ), true );
						$racketmanager->printMessage();
					} else {
						$config                     = new \stdClass();
						$config->name               = isset( $_POST['event_name'] ) ? sanitize_text_field( wp_unslash( $_POST['event_name'] ) ) : null;
						$config->type               = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : null;
						$config->age_limit          = isset( $_POST['age_limit'] ) ? sanitize_text_field( wp_unslash( $_POST['age_limit'] ) ) : null;
						$config->age_offset         = isset( $_POST['age_offset'] ) ? sanitize_text_field( wp_unslash( $_POST['age_offset'] ) ) : null;
						$config->scoring            = isset( $_POST['scoring'] ) ? sanitize_text_field( wp_unslash( $_POST['scoring'] ) ) : null;
						$config->num_sets           = isset( $_POST['num_sets'] ) ? intval( $_POST['num_sets'] ) : null;
						$config->num_rubbers        = isset( $_POST['num_rubbers'] ) ? intval( $_POST['num_rubbers'] ) : null;
						$config->match_days_allowed = isset( $_POST['match_days_allowed'] ) ? wp_unslash( $_POST['match_days_allowed'] ) : null;
						$config->offset             = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : null;
						$config->primary_league     = isset( $_POST['primary_league'] ) ? intval( $_POST['primary_league'] ) : null;
						if ( ! empty( $config->name ) ) {
							$event = new \stdClass();
							$event->name = $config->name;
							$event->competition_id = $competition->id;
							$event->num_sets       = null;
							$event->num_rubbers    = null;
							$event->type           = null;
							$event                 = new RacketManager_Event( $event );
							if ( $event ) {
								?>
								<script>
								let url = new URL(window.location.href);
								url.searchParams.append('event_id', <?php echo esc_attr( $event->id ); ?>);
								history.pushState('', '', url.toString());
								</script>
								<?php
								$event_season['name']           = $competition->current_season['name'];
								$event_season['home_away']      = $competition->current_season['home_away'];
								$event_season['num_match_days'] = $competition->current_season['num_match_days'];
								$event_season['match_dates']    = $competition->current_season['match_dates'];
								$event->add_season( $event_season );
								$league_id = $event->add_league( $event->name );
								if ( $league_id ) {
									if ( $competition->is_championship ) {
										$config->primary_league = $league_id;
										$league_title = $event->name . ' ' . __( 'Plate', 'racketmanger' );
										$event->add_league( $league_title );
									}
								}
								$event->config = $config;
								$updates       = $event->set_config( $config );
								if ( $updates ) {
									$racketmanager->set_message( __( 'Event config updated', 'racketmanager' ) );
								} elseif ( empty( $racketmanager->error_messages ) ) {
									$racketmanager->set_message( __( 'No updates found', 'racketmanager' ), 'warning' );
								} else {
									$racketmanager->set_message( __( 'Errors found', 'racketmanager' ), true );
								}
								$racketmanager->printMessage();
							} else {
								$racketmanager->set_message( __( 'Event creating event', 'racketmanager' ), true );
								$racketmanager->printMessage();
							}
						} else {
							$racketmanager->error_messages[] = __( 'Name must be set', 'racketmanager' );
							$racketmanager->error_fields[]   = 'name';
							$racketmanager->set_message( __( 'Error creating event', 'racketmanager' ), true );
							$racketmanager->printMessage();
						}
					}
				}
			}
			if ( empty( $tab ) ) {
				$tab = 'general';
			}
			$is_invalid = false;
			require RACKETMANAGER_PATH . 'admin/includes/event-config.php';
		}
	}
	/**
	 *
	 * Display create/edit season page
	 */
	public function display_season_modify_page() {
		global $racketmanager;
		$racketmanager->error_fields   = array();
		$racketmanager->error_messages = array();
		if ( ! current_user_can( 'edit_seasons' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} elseif ( isset( $_POST['addSeason'] ) ) {
			if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add-season' ) ) {
				$racketmanager->set_message( __( 'Security token invalid', 'racketmanager' ), true );
				$racketmanager->printMessage();
			} elseif ( isset( $_POST['competition_id'] ) ) {
				$competition_id = intval( $_POST['competition_id'] );
				$competition    = get_competition( $competition_id );
				if ( $competition ) {
					$season                            = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
					$current_season                    = new \stdClass();
					$current_season->name              = $season;
					$current_season->venue             = isset( $_POST['venue'] ) ? intval( $_POST['venue'] ) : null;
					$current_season->date_end          = isset( $_POST['dateEnd'] ) ? sanitize_text_field( wp_unslash( $_POST['dateEnd'] ) ) : null;
					$current_season->date_end          = $current_season->date_end;
					$current_season->date_open         = isset( $_POST['dateOpen'] ) ? sanitize_text_field( wp_unslash( $_POST['dateOpen'] ) ) : null;
					$current_season->date_open         = $current_season->date_open;
					$current_season->date_closing      = isset( $_POST['dateClose'] ) ? sanitize_text_field( wp_unslash( $_POST['dateClose'] ) ) : null;
					$current_season->date_start        = isset( $_POST['dateStart'] ) ? sanitize_text_field( wp_unslash( $_POST['dateStart'] ) ) : null;
					$current_season->date_start        = $current_season->date_start;
					$current_season->competition_code  = isset( $_POST['competition_code'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_code'] ) ) : null;
					$current_season->fixed_match_dates = isset( $_POST['fixedMatchDates'] ) ? ( 'true' === $_POST['fixedMatchDates'] ? true : false ) : false;
					$current_season->home_away         = isset( $_POST['homeAway'] ) ? ( 'true' === $_POST['homeAway'] ? true : false ) : false;
					$current_season->grade             = isset( $_POST['grade'] ) ? sanitize_text_field( wp_unslash( $_POST['grade'] ) ) : null;
					$current_season->max_teams         = isset( $_POST['max_teams'] ) ? intval( $_POST['max_teams'] ) : null;
					$current_season->teams_per_club    = isset( $_POST['teams_per_club'] ) ? intval( $_POST['teams_per_club'] ) : null;
					$current_season->teams_prom_relg   = isset( $_POST['teams_prom_relg'] ) ? intval( $_POST['teams_prom_relg'] ) : null;
					$current_season->lowest_promotion  = isset( $_POST['lowest_promotion'] ) ? intval( $_POST['lowest_promotion'] ) : null;
					$current_season->num_match_days    = isset( $_POST['num_match_days'] ) ? intval( $_POST['num_match_days'] ) : null;
					$current_season->round_length      = isset( $_POST['round_length'] ) ? intval( $_POST['round_length'] ) : null;
					$current_season->home_away_diff    = isset( $_POST['home_away_diff'] ) ? intval( $_POST['home_away_diff'] ) : 0;
					$current_season->filler_weeks      = isset( $_POST['filler_weeks'] ) ? intval( $_POST['filler_weeks'] ) : 0;
					$current_season->fee_competition   = isset( $_POST['feeClub'] ) ? floatval( $_POST['feeClub'] ) : 0;
					$current_season->fee_event         = isset( $_POST['feeTeam'] ) ? floatval( $_POST['feeTeam'] ) : 0;
					$current_season->fee_lead_time     = isset( $_POST['feeLeadTime'] ) ? intval( $_POST['feeLeadTime'] ) : 0;
					$current_season->fee_id            = isset( $_POST['feeId'] ) ? intval( $_POST['feeId'] ) : null;
					$this->set_competition_dates( $current_season, $competition );
					if ( $racketmanager->error ) {
						$racketmanager->printMessage();
					} else {
						$racketmanager->set_message( __( 'Season added to competition', 'racketmanager' ) );
						$this->schedule_open_activities( $competition->id, $current_season );
					}
				} else {
					$racketmanager->set_message( __( 'Competition not found', 'racketmanager' ), true );
				}
			}
			$racketmanager->printMessage();
		} elseif ( isset( $_POST['editSeason'] ) ) {
			if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-season' ) ) {
				$racketmanager->set_message( __( 'Security token invalid', 'racketmanager' ), true );
				$racketmanager->printMessage();
			} elseif ( isset( $_POST['competition_id'] ) ) {
				$competition_id = intval( $_POST['competition_id'] );
				$competition    = get_competition( $competition_id );
				if ( $competition ) {
					$season = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
					if ( $season ) {
						$current_season                    = new \stdClass();
						$current_season->name              = $season;
						$current_season->venue             = isset( $_POST['venue'] ) ? intval( $_POST['venue'] ) : null;
						$current_season->date_end          = isset( $_POST['dateEnd'] ) ? sanitize_text_field( wp_unslash( $_POST['dateEnd'] ) ) : null;
						$current_season->date_end          = $current_season->date_end;
						$current_season->date_open         = isset( $_POST['dateOpen'] ) ? sanitize_text_field( wp_unslash( $_POST['dateOpen'] ) ) : null;
						$current_season->date_open         = $current_season->date_open;
						$current_season->date_closing      = isset( $_POST['dateClose'] ) ? sanitize_text_field( wp_unslash( $_POST['dateClose'] ) ) : null;
						$current_season->date_start        = isset( $_POST['dateStart'] ) ? sanitize_text_field( wp_unslash( $_POST['dateStart'] ) ) : null;
						$current_season->date_start        = $current_season->date_start;
						$current_season->competition_code  = isset( $_POST['competition_code'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_code'] ) ) : null;
						$current_season->fixed_match_dates = isset( $_POST['fixedMatchDates'] ) ? ( 'true' === $_POST['fixedMatchDates'] ? true : false ) : false;
						$current_season->home_away         = isset( $_POST['homeAway'] ) ? ( 'true' === $_POST['homeAway'] ? true : false ) : false;
						$current_season->grade             = isset( $_POST['grade'] ) ? sanitize_text_field( wp_unslash( $_POST['grade'] ) ) : null;
						$current_season->max_teams         = isset( $_POST['max_teams'] ) ? intval( $_POST['max_teams'] ) : null;
						$current_season->teams_per_club    = isset( $_POST['teams_per_club'] ) ? intval( $_POST['teams_per_club'] ) : null;
						$current_season->teams_prom_relg   = isset( $_POST['teams_prom_relg'] ) ? intval( $_POST['teams_prom_relg'] ) : null;
						$current_season->lowest_promotion  = isset( $_POST['lowest_promotion'] ) ? intval( $_POST['lowest_promotion'] ) : null;
						$current_season->num_match_days    = isset( $_POST['num_match_days'] ) ? intval( $_POST['num_match_days'] ) : null;
						$current_season->round_length      = isset( $_POST['round_length'] ) ? intval( $_POST['round_length'] ) : null;
						$current_season->home_away_diff    = isset( $_POST['home_away_diff'] ) ? intval( $_POST['home_away_diff'] ) : 0;
						$current_season->filler_weeks      = isset( $_POST['filler_weeks'] ) ? intval( $_POST['filler_weeks'] ) : 0;
						$current_season->fee_competition   = isset( $_POST['feeClub'] ) ? floatval( $_POST['feeClub'] ) : 0;
						$current_season->fee_event         = isset( $_POST['feeTeam'] ) ? floatval( $_POST['feeTeam'] ) : 0;
						$current_season->fee_lead_time     = isset( $_POST['feeLeadTime'] ) ? intval( $_POST['feeLeadTime'] ) : 0;
						$current_season->fee_id            = isset( $_POST['feeId'] ) ? intval( $_POST['feeId'] ) : null;
						$this->set_competition_dates( $current_season, $competition );
						$this->schedule_open_activities( $competition->id, $current_season );
					} else {
						$racketmanager->set_message( __( 'Season not found', 'racketmanager' ), true );
					}
				} else {
					$racketmanager->set_message( __( 'Competition not found', 'racketmanager' ), true );
				}
			}
			$racketmanager->printMessage();
		} elseif ( isset( $_GET['competition_id'] ) ) {
			$competition_id = intval( $_GET['competition_id'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$competition    = get_competition( $competition_id );
			if ( $competition ) {
				$season = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
				if ( $season ) {
					$current_season = isset( $competition->seasons[ $season ] ) ? (object) $competition->seasons[ $season ] : null;
					if ( $current_season ) {
						$fee_competition = 0;
						$fee_event       = 0;
						$fee_status      = null;
						$fee_id          = null;
						$charges         = $racketmanager->get_charges(
							array(
								'competition' => $competition_id,
								'season'      => $season,
							)
						);
						switch ( count( $charges ) ) {
							case 1:
								$fee_competition = $charges[0]->fee_competition;
								$fee_event       = $charges[0]->fee_event;
								$fee_status      = $charges[0]->status;
								$fee_id          = $charges[0]->id;
								break;
							case 0:
								break;
							default:
								foreach ( $charges as $charge ) {
									$fee_competition += $charge->fee_competition;
									$fee_event       += $charge->fee_event;
									$fee_status       = $charge->status;
								}
								break;
						}
						$current_season->fee_competition = $fee_competition;
						$current_season->fee_event       = $fee_event;
						$current_season->fee_status      = $fee_status;
						$current_season->fee_id          = $fee_id;
					} else {
						$racketmanager->set_message( __( 'Season not found for competition', 'racketmanager' ), true );
					}
				} else {
					$racketmanager->set_message( __( 'Season not found', 'racketmanager' ), true );
				}
			} else {
				$racketmanager->set_message( __( 'Competition not found', 'racketmanager' ), true );
			}
			$racketmanager->printMessage();
		}
		$clubs = $this->get_clubs(
			array(
				'type' => 'affiliated',
			)
		);
		include_once RACKETMANAGER_PATH . 'admin/includes/season-edit.php';
	}
	/**
	 * Set season dates for competition season function
	 *
	 * @param object $current_season season details.
	 * @param object $competition competition details.
	 * @return void
	 */
	private function set_competition_dates( $current_season, $competition ) {
		global $racketmanager;
		$updates = false;
		if ( empty( $current_season->name ) ) {
			$racketmanager->error_fields[]   = 'season';
			$racketmanager->error_messages[] = __( 'Season not specified', 'racketmanager' );
		}
		if ( empty( $current_season->date_open ) ) {
			$racketmanager->error_messages[] = __( 'Opening date must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'date_open';
		}
		if ( empty( $current_season->date_end ) ) {
			$racketmanager->error_messages[] = __( 'End date must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'date_end';
		}
		if ( empty( $current_season->date_start ) ) {
			$racketmanager->error_messages[] = __( 'Start date must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'date_start';
		}
		if ( empty( $current_season->date_closing ) ) {
			$racketmanager->error_messages[] = __( 'Closing date must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'date_closing';
		}
		if ( $competition->is_league ) {
			if ( empty( $current_season->max_teams ) ) {
				$racketmanager->error_messages[] = __( 'Maximum number of teams must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'max_teams';
			}
			if ( empty( $current_season->teams_per_club ) ) {
				$racketmanager->error_messages[] = __( 'Number of teams per club must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'teams_per_club';
			}
			if ( empty( $current_season->teams_prom_relg ) ) {
				$racketmanager->error_messages[] = __( 'Number of promoted/relegated teams must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'teams_prom_relg';
			}
			if ( $current_season->teams_prom_relg > $current_season->teams_per_club ) {
				$racketmanager->error_messages[] = __( 'Number of promoted/relegated teams must be at most number of teams per club', 'racketmanager' );
				$racketmanager->error_fields[]   = 'teams_prom_relg';
			}
			if ( empty( $current_season->lowest_promotion ) ) {
				$racketmanager->error_messages[] = __( 'Lowest promotion position must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'lowest_promotion';
			}
			if ( empty( $current_season->num_match_days ) ) {
				$racketmanager->error_messages[] = __( 'Number of match days must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'num_match_days';
			}
			if ( empty( $current_season->round_length ) ) {
				$racketmanager->error_messages[] = __( 'Round length must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'round_length';
			}
			if ( is_null( $current_season->home_away_diff ) ) {
				$racketmanager->error_messages[] = __( 'Difference between fixtures must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'home_away_diff';
			}
			if ( is_null( $current_season->filler_weeks ) ) {
				$racketmanager->error_messages[] = __( 'Number of filler weeks must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'filler_weeks';
			}
		} elseif ( empty( $current_season->venue ) ) {
			$racketmanager->error_messages[] = __( 'Venue must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'venue';
		}
		if ( is_null( $current_season->fixed_match_dates ) ) {
			$racketmanager->error_messages[] = __( 'Match date option must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'fixedMatchDates';
		}
		if ( is_null( $current_season->home_away ) ) {
			$racketmanager->error_messages[] = __( 'Number of legs must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'homeAway';
		}
		if ( empty( $current_season->grade ) ) {
			$racketmanager->error_messages[] = __( 'Grade must be set', 'racketmanager' );
			$racketmanager->error_fields[]   = 'grade';
		}
		if ( empty( $current_season->fee_lead_time ) ) {
			if ( ! empty( $current_season->fee_event ) || ! empty( $current_season->fee_event ) ) {
				$racketmanager->error_messages[] = __( 'Fee lead time must be set', 'racketmanager' );
				$racketmanager->error_fields[]   = 'feeLeadTime';
			}
		}
		if ( empty( $racketmanager->error_fields ) ) {
			if ( ! empty( $current_season->fee_lead_time ) ) {
				$fee_lead_time = $current_season->fee_lead_time * 7;
				$fee_date      = Racketmanager_Util::amend_date( $current_season->date_start, $fee_lead_time, '-' );
			}
			if ( ! empty( $current_season->fee_id ) ) {
				$charge = get_charge( $current_season->fee_id );
				if ( $charge ) {
					if ( $charge->fee_competition !== $current_season->fee_competition ) {
						$charge->set_club_fee( $current_season->fee_competition );
						$charge_update = true;
					}
					if ( $charge->fee_event !== $current_season->fee_event ) {
						$charge->set_team_fee( $current_season->fee_event );
						$charge_update = true;
					}
					if ( $charge->date !== $fee_date ) {
						$charge->set_date( $fee_date );
						$charge_update = true;
					}
					if ( $charge_update ) {
						$this->schedule_invoice_send( $charge->id );
					}
				} elseif ( ! empty( $current_season->fee_competition ) || ! empty( $current_season->fee_event ) ) {
					$charge_create = true;
				}
			} elseif ( ! empty( $current_season->fee_competition ) || ! empty( $current_season->fee_event ) ) {
				$charge_create = true;
			}
			$season = isset( $competition->seasons[ $current_season->name ] ) ? $competition->seasons[ $current_season->name ] : null;
			if ( $season ) {
				if ( empty( $season['date_open'] ) || $season['date_open'] !== $current_season->date_open ) {
					$updates             = true;
					$season['date_open'] = $current_season->date_open;
				}
				if ( empty( $season['date_end'] ) || $season['date_end'] !== $current_season->date_end ) {
					$updates            = true;
					$season['date_end'] = $current_season->date_end;
				}
				if ( empty( $season['date_start'] ) || $season['date_start'] !== $current_season->date_start ) {
					$updates              = true;
					$season['date_start'] = $current_season->date_start;
				}
				if ( empty( $season['date_closing'] ) || $season['date_closing'] !== $current_season->date_closing ) {
					$updates                = true;
					$season['date_closing'] = $current_season->date_closing;
				}
				if ( $competition->is_league ) {
					if ( empty( $season['max_teams'] ) || $season['max_teams'] !== $current_season->max_teams ) {
						$updates             = true;
						$season['max_teams'] = $current_season->max_teams;
					}
					if ( empty( $season['teams_per_club'] ) || $season['teams_per_club'] !== $current_season->teams_per_club ) {
						$updates                  = true;
						$season['teams_per_club'] = $current_season->teams_per_club;
					}
					if ( empty( $season['teams_prom_relg'] ) || $season['teams_prom_relg'] !== $current_season->teams_prom_relg ) {
						$updates                   = true;
						$season['teams_prom_relg'] = $current_season->teams_prom_relg;
					}
					if ( empty( $season['lowest_promotion'] ) || $season['lowest_promotion'] !== $current_season->lowest_promotion ) {
						$updates                    = true;
						$season['lowest_promotion'] = $current_season->lowest_promotion;
					}
					if ( empty( $season['num_match_days'] ) || $season['num_match_days'] !== $current_season->num_match_days ) {
						$match_days_change        = true;
						$updates                  = true;
						$season['num_match_days'] = $current_season->num_match_days;
					}
					if ( empty( $season['round_length'] ) || $season['round_length'] !== $current_season->round_length ) {
						$updates                = true;
						$season['round_length'] = $current_season->round_length;
					}
					if ( empty( $season['home_away_diff'] ) || $season['home_away_diff'] !== $current_season->home_away_diff ) {
						$updates                  = true;
						$season['home_away_diff'] = $current_season->home_away_diff;
					}
					if ( empty( $season['filler_weeks'] ) || $season['filler_weeks'] !== $current_season->filler_weeks ) {
						$updates                = true;
						$season['filler_weeks'] = $current_season->filler_weeks;
					}
				} elseif ( $season['venue'] !== $current_season->venue ) {
					$updates         = true;
					$season['venue'] = $current_season->venue;
				}
				if ( empty( $season['competition_code'] ) ) {
					if ( ! empty( $current_season->competition_code ) ) {
						$updates                    = true;
						$season['competition_code'] = $current_season->competition_code;
					} else {
						$season['competition_code'] = null;
					}
				} elseif ( $season['competition_code'] !== $current_season->competition_code ) {
					$updates                    = true;
					$season['competition_code'] = $current_season->competition_code;
				}
				if ( empty( $season['fixed_match_dates'] ) || $season['fixed_match_dates'] !== $current_season->fixed_match_dates ) {
					$updates                     = true;
					$season['fixed_match_dates'] = $current_season->fixed_match_dates;
				}
				if ( empty( $season['home_away'] ) || $season['home_away'] !== $current_season->home_away ) {
					$updates             = true;
					$season['home_away'] = $current_season->home_away;
				}
				if ( empty( $season['grade'] ) || $season['grade'] !== $current_season->grade ) {
					$updates         = true;
					$season['grade'] = $current_season->grade;
				}
				if ( $updates ) {
					if ( ! empty( $match_days_change ) ) {
						$season['match_dates'] = $this->set_match_dates( $current_season );
					}
					$competition->update_season( $season );
					$events = $competition->get_events();
					if ( $events ) {
						$event_season                   = array();
						$event_season['name']           = $season['name'];
						$event_season['home_away']      = $season['home_away'];
						$event_season['num_match_days'] = $season['num_match_days'];
						$event_season['match_dates']    = $season['match_dates'];
						foreach ( $events as $event ) {
							$event->update_season( $event_season );
						}
					}
				} elseif ( empty( $charge_create ) && empty( $charge_update ) ) {
					$racketmanager->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
				}
			} else {
				$current_season->match_dates = $this->set_match_dates( $current_season );
				$season                      = (array) $current_season;
				$competition->add_season( $season );
				$events = $competition->get_events();
				if ( $events ) {
					$event_season                 = new \stdClass();
					$event_season->name           = $current_season->name;
					$event_season->home_away      = $current_season->home_away;
					$event_season->num_match_days = $current_season->num_match_days;
					$event_season->match_dates    = $current_season->match_dates;
					$season_event                 = (array) $event_season;
					foreach ( $events as $event ) {
						$event->add_season( $season_event );
					}
				}
				$charge_create = true;
			}
			if ( ! empty( $charge_create ) ) {
				$charge                  = new \stdClass();
				$charge->competition_id  = $competition->id;
				$charge->season          = $current_season->name;
				$charge->date            = $fee_date;
				$charge->fee_competition = $current_season->fee_competition;
				$charge->fee_event       = $current_season->fee_event;
				$charge                  = new Racketmanager_Charges( $charge );
				$this->schedule_invoice_send( $charge->id );
			}
		} else {
			$racketmanager->set_message( __( 'Errors found', 'racketmanager' ), true );
		}
	}
	/**
	 * Set match dates function
	 *
	 * @param object $season season details.
	 * @return array of match dates.
	 */
	private function set_match_dates( $season ) {
		$match_dates  = array();
		$date_start   = $season->date_start;
		$round_length = $season->round_length;
		if ( $season->home_away ) {
			$halfway = $season->num_match_days / 2;
		} else {
			$halfway = null;
		}
		for ( $i = 0; $i < $season->num_match_days; ++$i ) {
			if ( $i === $halfway ) {
				if ( $season->home_away_diff ) {
					$days_diff  = $season->home_away_diff * 7;
					$date_start = Racketmanager_Util::amend_date( $date_start, $days_diff );
				}
			}
			$match_dates[ $i ] = $date_start;
			$date_start        = Racketmanager_Util::amend_date( $date_start, $round_length );
		}
		return $match_dates;
	}
	/**
	 * Schedule opening activities function
	 *
	 * @param int    $competition_id competition id.
	 * @param object $season season name.
	 * @return void
	 */
	private function schedule_open_activities( $competition_id, $season ) {
		$competition = get_competition( $competition_id );
		if ( $competition ) {
			$this->schedule_team_competition_emails( $competition_id, $season );
		}
	}
	/**
	 * Schedule invoice send function
	 *
	 * @param int $charge_id charge id.
	 * @return void
	 */
	private function schedule_invoice_send( $charge_id ) {
		$charge = get_charge( $charge_id );
		if ( $charge ) {
			$today = gmdate( 'Y-m-d' );
			if ( $today < $charge->date ) {
				$schedule_date   = strtotime( $charge->date );
				$day             = intval( gmdate( 'd', $schedule_date ) );
				$month           = intval( gmdate( 'm', $schedule_date ) );
				$year            = intval( gmdate( 'Y', $schedule_date ) );
				$schedule_start  = mktime( 00, 00, 01, $month, $day, $year );
				$schedule_name   = 'rm_send_invoices';
				$schedule_args[] = intval( $charge_id );
				Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
				$success = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
				if ( ! $success ) {
					error_log( __( 'Error scheduling invoice sending', 'racketmanager' ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
			}
		}
	}
}
