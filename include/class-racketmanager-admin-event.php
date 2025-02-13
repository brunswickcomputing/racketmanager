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
			$season        = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
			$tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
			if ( $tournament_id ) {
				$tournament = get_tournament( $tournament_id );
			}
			$event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $event_id ) {
				$event = get_event( $event_id );
				if ( $event ) {
					$event->config              = (object) $event->settings;
					$event->config->num_sets    = $event->num_sets;
					$event->config->num_rubbers = $event->num_rubbers;
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
								$config->reverse_rubbers    = isset( $_POST['reverse_rubbers'] ) ? intval( $_POST['reverse_rubbers'] ) : null;
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
						$config->reverse_rubbers    = isset( $_POST['reverse_rubbers'] ) ? intval( $_POST['reverse_rubbers'] ) : null;
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
}
