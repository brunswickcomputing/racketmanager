<?php
/**
 * Racketmanager_Tournament API: tournament class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Tournament
 */

namespace Racketmanager;

/**
 * Class to implement the Tournament object
 */
final class Racketmanager_Tournament {

	/**
	 * Id
	 *
	 * @var int
	 */
	public $id;
	/**
	 * Tournament name
	 *
	 * @var string
	 */
	public $name;
	/**
	 * Competition id
	 *
	 * @var int
	 */
	public $competition_id;
	/**
	 * Competition
	 *
	 * @var object
	 */
	public $competition;
	/**
	 * Tournament season
	 *
	 * @var string
	 */
	public $season;
	/**
	 * Number of courts available for matches
	 *
	 * @var int
	 */
	public $num_courts;
	/**
	 * Start time
	 *
	 * @var string
	 */
	public $starttime;
	/**
	 * Date
	 *
	 * @var string
	 */
	public $date;
	/**
	 * Date display
	 *
	 * @var string
	 */
	public $date_display;
	/**
	 * Closing Date
	 *
	 * @var string
	 */
	public $date_closing;
	/**
	 * Closing Date display
	 *
	 * @var string
	 */
	public $date_closing_display;
	/**
	 * Date withdrawal variable
	 *
	 * @var string
	 */
	public $date_withdrawal;
	/**
	 * Date withdrawal display
	 *
	 * @var string
	 */
	public $date_withdrawal_display;
	/**
	 * Date open variable
	 *
	 * @var string
	 */
	public $date_open;
	/**
	 * Date open display variable
	 *
	 * @var string
	 */
	public $date_open_display;
	/**
	 * Date start variable
	 *
	 * @var string
	 */
	public $date_start;
	/**
	 * Date start display variable
	 *
	 * @var string
	 */
	public $date_start_display;
	/**
	 * Venue
	 *
	 * @var id
	 */
	public $venue;
	/**
	 * Venue name
	 *
	 * @var string
	 */
	public $venue_name;
	/**
	 * Is tournament active
	 *
	 * @var boolean
	 */
	public $is_active;
	/**
	 * Order of play
	 *
	 * @var string
	 */
	public $orderofplay;
	/**
	 * Time increment for finals day matches
	 *
	 * @var string
	 */
	public $time_increment;
	/**
	 * Competitions variable
	 *
	 * @var array
	 */
	public $competitions;
	/**
	 * Events variable
	 *
	 * @var array
	 */
	public $events = array();
	/**
	 * Players variable
	 *
	 * @var array
	 */
	public $players = array();
	/**
	 * Current phase variable
	 *
	 * @var string
	 */
	public $current_phase;
	/**
	 * Is complete
	 *
	 * @var boolean
	 */
	public $is_complete = false;
	/**
	 * Is started
	 *
	 * @var boolean
	 */
	public $is_started = false;
	/**
	 * Is closed
	 *
	 * @var boolean
	 */
	public $is_closed = false;
	/**
	 * Is withdrawal
	 *
	 * @var boolean
	 */
	public $is_withdrawal = false;
	/**
	 * Is open
	 *
	 * @var boolean
	 */
	public $is_open = false;
	/**
	 * Link variable
	 *
	 * @var string
	 */
	public $link;
	/**
	 * Competition code variable
	 *
	 * @var string
	 */
	public $competition_code;
	/**
	 * Finals variable
	 *
	 * @var array
	 */
	public $finals;
	/**
	 * Grade variable
	 *
	 * @var string
	 */
	public $grade;
	/**
	 * Fees
	 *
	 * @var object
	 */
	public $fees;
	/**
	 * Charge object
	 *
	 * @var object
	 */
	public $charge;
	/**
	 * Retrieve tournament instance
	 *
	 * @param int    $tournament_id tournament id.
	 * @param string $search_term search term - defaults to id.
	 * @return object
	 */
	public static function get_instance( $tournament_id, $search_term = 'id' ) {
		global $wpdb;
		if ( ! $tournament_id ) {
			return false;
		}
		switch ( $search_term ) {
			case 'name':
				$search = $wpdb->prepare(
					'`name` = %s',
					$tournament_id
				);
				break;
			case 'shortcode':
				$search_terms   = explode( ',', $tournament_id );
				$competition_id = $search_terms[0];
				$season         = $search_terms[1];
				$search         = $wpdb->prepare(
					'`competition_id` = %d AND `season` = %s',
					intval( $competition_id ),
					$season,
				);
				break;
			case 'id':
			default:
				$tournament_id = (int) $tournament_id;
				$search        = $wpdb->prepare(
					'`id` = %d',
					$tournament_id
				);
				break;
		}
		$tournament = wp_cache_get( $tournament_id, 'tournaments' );

		if ( ! $tournament ) {
			$tournament = $wpdb->get_row(
				$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT `id`, `name`, `competition_id`, `season`, `venue`, DATE_FORMAT(`date`, '%%Y-%%m-%%d') AS date, DATE_FORMAT(`date_closing`, '%%Y-%%m-%%d') AS `date_closing`, `date_start`, `date_open`, `date_withdrawal`, `grade`,`numcourts` AS `num_courts`, `starttime`, `timeincrement` AS `time_increment`, `orderofplay`, `competition_code` FROM {$wpdb->racketmanager_tournaments} WHERE $search"
				)
			); // db call ok.
			if ( ! $tournament ) {
				return false;
			}
			$tournament = new Racketmanager_Tournament( $tournament );
			wp_cache_set( $tournament_id, $tournament, 'tournaments' );
		}

		return $tournament;
	}

	/**
	 * Constructor
	 *
	 * @param object $tournament Tournament object.
	 */
	public function __construct( $tournament = null ) {
		global $racketmanager, $wp;
		if ( ! is_null( $tournament ) ) {
			foreach ( $tournament as $key => $value ) {
				$this->$key = $value;
			}

			if ( ! isset( $this->id ) ) {
				$this->add();
			}
			$this->link                    = '/tournament/' . seo_url( $this->name ) . '/';
			$this->date_display            = ( substr( $this->date, 0, 10 ) === '0000-00-00' ) ? 'TBC' : mysql2date( $racketmanager->date_format, $this->date );
			$this->date_closing_display    = ( substr( $this->date_closing, 0, 10 ) === '0000-00-00' ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date_closing );
			$this->date_withdrawal_display = ( substr( $this->date_closing, 0, 10 ) === '0000-00-00' ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date_withdrawal );
			$this->date_open_display       = empty( $this->date_open ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date_open );
			$this->date_start_display      = empty( $this->date_start ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date_start );
			$today                         = gmdate( 'Y-m-d' );
			$this->current_phase           = 'complete';
			if ( $today > $this->date ) {
				$this->current_phase = 'end';
				$this->is_complete   = true;
			} else {
				$this->current_phase = '';
				if ( ! empty( $this->date_start ) && $today >= $this->date_start ) {
					$this->current_phase = 'start';
					$this->is_started    = true;
				} elseif ( ! empty( $this->date_withdrawal ) && $today > $this->date_withdrawal ) {
					$this->current_phase = 'withdraw';
					$this->is_withdrawal = true;
				} elseif ( ! empty( $this->date_closing ) && $today > $this->date_closing ) {
					$this->current_phase = 'close';
					$this->is_closed     = true;
				} elseif ( ! empty( $this->date_open ) && $today >= $this->date_open ) {
					$this->current_phase = 'open';
					$this->is_open       = true;
				}
			}
			if ( empty( $this->venue ) ) {
				$this->venue      = '';
				$this->venue_name = 'TBC';
			} else {
				$this->venue_name = get_club( $tournament->venue )->shortcode;
			}
			if ( isset( $this->date_closing ) && $this->date_closing <= gmdate( 'Y-m-d' ) ) {
				$this->is_active = true;
			} else {
				$this->is_active = false;
			}
			$this->orderofplay = (array) maybe_unserialize( $this->orderofplay );
			if ( $this->competition_id ) {
				$this->competition = get_competition( $this->competition_id );
			}
			$finals     = array();
			$num_teams  = 64;
			$max_rounds = 6;
			$r          = $max_rounds;
			for ( $round = 1; $round <= $max_rounds; ++$round ) {
				$num_teams      = pow( 2, $round );
				$num_matches    = $num_teams / 2;
				$key            = Racketmanager_Util::get_final_key( $num_teams );
				$name           = Racketmanager_Util::get_final_name( $key );
				$finals[ $key ] = array(
					'key'         => $key,
					'name'        => $name,
					'num_matches' => $num_matches,
					'num_teams'   => $num_teams,
					'round'       => $r,
				);
				--$r;
			}
			$this->finals = $finals;
			$wp->set_query_var( 'season', $this->season );
			$charge_key = $this->competition_id . '_' . $this->season;
			$charge     = get_charge( $charge_key );
			if ( $charge ) {
				$this->charge = $charge;
			}
		}
	}

	/**
	 * Add tournament
	 */
	private function add() {
		global $wpdb, $racketmanager;
		$validate = $this->validate( $this );
		if ( $validate->valid ) {
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"INSERT INTO {$wpdb->racketmanager_tournaments} (`name`, `competition_id`, `season`, `venue`, `date_open`, `date_closing`, `date_withdrawal`, `date_start`, `date`, `competition_code`, `grade` ) VALUES (%s, %s, %d, %d, %s, %s, %s, %s, %s, %s, %s )",
					$this->name,
					$this->competition_id,
					$this->season,
					$this->venue,
					$this->date_open,
					$this->date_closing,
					$this->date_withdrawal,
					$this->date_start,
					$this->date,
					$this->competition_code,
					$this->grade,
				)
			);
			$racketmanager->set_message( __( 'Tournament added', 'racketmanager' ) );
			$this->id                      = $wpdb->insert_id;
			$this->orderofplay             = '';
			$racketmanager->error_fields   = null;
			$racketmanager->error_messages = null;
			return true;
		} else {
			$racketmanager->error_fields   = $validate->fld;
			$racketmanager->error_messages = $validate->msg;
			$racketmanager->set_message( __( 'Error creating tournament', 'racketmanager' ), true );
			return false;
		}
	}

	/**
	 * Update tournament
	 *
	 * @param object $updated updated tournament values.
	 * @return boolean
	 */
	public function update( $updated ) {
		global $wpdb, $racketmanager;
		$validate = $this->validate( $updated );
		if ( $validate->valid ) {
			$this->name             = $updated->name;
			$this->competition_id   = $updated->competition_id;
			$this->season           = $updated->season;
			$this->venue            = $updated->venue;
			$this->date_open        = $updated->date_open;
			$this->date_closing     = $updated->date_closing;
			$this->date_withdrawal  = $updated->date_withdrawal;
			$this->date_start       = $updated->date_start;
			$this->date             = $updated->date;
			$this->starttime        = $updated->starttime;
			$this->competition_code = $updated->competition_code;
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_tournaments} SET `name` = %s, `competition_id` = %d, `season` = %s, `venue` = %d, `date_open` = %s, `date_closing` = %s, `date_withdrawal` = %s, `date_start` = %s, `date` = %s, `starttime` = %s, `competition_code` = %s, `grade` = %s WHERE `id` = %d",
					$updated->name,
					$updated->competition_id,
					$updated->season,
					$updated->venue,
					$updated->date_open,
					$updated->date_closing,
					$updated->date_withdrawal,
					$updated->date_start,
					$updated->date,
					$updated->starttime,
					$updated->competition_code,
					$this->grade,
					$this->id
				)
			);
			wp_cache_set( $this->id, $this, 'tournaments' );
			$racketmanager->set_message( __( 'Tournament updated', 'racketmanager' ) );
			$racketmanager->error_fields   = null;
			$racketmanager->error_messages = null;
			return true;
		} else {
			$racketmanager->set_message( __( 'Error updating tournament', 'racketmanager' ), true );
			$racketmanager->error_fields   = $validate->fld;
			$racketmanager->error_messages = $validate->msg;
			return false;
		}
	}
	/**
	 * Fucntion to validate new or updated tournament
	 *
	 * @param object $tournament tournament details.
	 * @return object
	 */
	private function validate( $tournament ) {
		$return  = new \stdClass();
		$valid   = true;
		$err_msg = array();
		$err_fld = array();
		if ( empty( $tournament->name ) ) {
			$valid     = false;
			$err_msg[] = __( 'Name is required', 'racketmanager' );
			$err_fld[] = 'tournamentName';
		}
		if ( empty( $tournament->competition_id ) ) {
			$valid     = false;
			$err_msg[] = __( 'Competition is required', 'racketmanager' );
			$err_fld[] = 'competition_id';
		}
		if ( empty( $tournament->season ) ) {
			$valid     = false;
			$err_msg[] = __( 'Season is required', 'racketmanager' );
			$err_fld[] = 'season';
		}
		if ( empty( $tournament->venue ) ) {
			$valid     = false;
			$err_msg[] = __( 'Venue is required', 'racketmanager' );
			$err_fld[] = 'venue';
		}
		if ( empty( $tournament->grade ) ) {
			$valid     = false;
			$err_msg[] = __( 'Grade is required', 'racketmanager' );
			$err_fld[] = 'grade';
		}
		if ( empty( $tournament->date_open ) ) {
			$valid     = false;
			$err_msg[] = __( 'Opening date is required', 'racketmanager' );
			$err_fld[] = 'dateOpen';
		}
		if ( empty( $tournament->date_closing ) ) {
			$valid     = false;
			$err_msg[] = __( 'Closing date is required', 'racketmanager' );
			$err_fld[] = 'dateClose';
		} elseif ( ! empty( $tournament->date_open ) && $tournament->date_closing <= $tournament->date_open ) {
			$valid     = false;
			$err_msg[] = __( 'Closing date must be after open date', 'racketmanager' );
			$err_fld[] = 'dateClose';
		}
		if ( empty( $tournament->date_withdrawal ) ) {
			$valid     = false;
			$err_msg[] = __( 'Withdrawal date is required', 'racketmanager' );
			$err_fld[] = 'dateWithdraw';
		} elseif ( ! empty( $tournament->date_closing ) && $tournament->date_withdrawal <= $tournament->date_closing ) {
			$valid     = false;
			$err_msg[] = __( 'Withdrawal date must be after closing date', 'racketmanager' );
			$err_fld[] = 'dateClose';
		}
		if ( empty( $tournament->date_start ) ) {
			$valid     = false;
			$err_msg[] = __( 'Start date is required', 'racketmanager' );
			$err_fld[] = 'dateStart';
		} elseif ( ! empty( $tournament->date_withdrawal ) && $tournament->date_start <= $tournament->date_withdrawal ) {
			$valid     = false;
			$err_msg[] = __( 'Start date must be after withdrawal date', 'racketmanager' );
			$err_fld[] = 'dateStart';
		}
		if ( empty( $tournament->date ) ) {
			$valid     = false;
			$err_msg[] = __( 'End date is required', 'racketmanager' );
			$err_fld[] = 'dateEnd';
		} elseif ( ! empty( $tournament->date_start ) && $tournament->date <= $tournament->date_start ) {
			$valid     = false;
			$err_msg[] = __( 'End date must be after start date', 'racketmanager' );
			$err_fld[] = 'dateEnd';
		}
		if ( $valid ) {
			$return->valid = true;
			$charge_create = false;
			if ( ! empty( $tournament->fees->id ) ) {
				$charge = get_charge( $tournament->fees->id );
				if ( $charge ) {
					if ( $charge->fee_competition !== $tournament->fees->competition ) {
						$charge->set_club_fee( $tournament->fees->competition );
					}
					if ( $charge->fee_event !== $tournament->fees->event ) {
						$charge->set_team_fee( $tournament->fees->event );
					}
				} elseif ( ! empty( $tournament->fees->competition ) || ! empty( $tournament->fees->event ) ) {
					$charge_create = true;
				}
			} elseif ( ! empty( $tournament->fees->competition ) || ! empty( $tournament->fees->event ) ) {
				$charge_create = true;
			}
			if ( $charge_create ) {
				$charge                  = new \stdClass();
				$charge->competition_id  = $this->competition_id;
				$charge->season          = $this->season;
				$charge->date            = $this->date_start;
				$charge->fee_competition = $tournament->fees->competition;
				$charge->fee_event       = $tournament->fees->event;
				$this->charge            = new Racketmanager_Charges( $charge );
			}
		} else {
			$return->valid = false;
			$return->msg   = $err_msg;
			$return->fld   = $err_fld;
		}
		return $return;
	}
	/**
	 * Update tournament plan
	 *
	 * @param text $starttime start time.
	 * @param int  $num_courts number of courts.
	 * @param text $time_increment time increment for matches.
	 * @return boolean updates performed
	 */
	public function update_plan( $starttime, $num_courts, $time_increment ) {
		global $wpdb, $racketmanager;

		$update = false;
		if ( $starttime !== $this->starttime || $num_courts !== $this->num_courts || $time_increment !== $this->time_increment ) {
			$this->starttime      = $starttime;
			$this->num_courts     = $num_courts;
			$this->time_increment = $time_increment;
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_tournaments} SET `starttime` = %s, `numcourts` = %d, `timeincrement` = %s WHERE `id` = %d",
					$starttime,
					$num_courts,
					$time_increment,
					$this->id
				)
			);
			wp_cache_set( $this->id, $this, 'tournaments' );
			$racketmanager->set_message( __( 'Tournament updated', 'racketmanager' ) );
			$update = true;
		} else {
			$racketmanager->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
		}
		return $update;
	}

	/**
	 * Update tournament plan
	 *
	 * @param array $courts number of courts available.
	 * @param array $start_times start times of matches.
	 * @param array $matches matches.
	 * @param array $match_times match times.
	 * @return boolean updates performed
	 */
	public function save_plan( $courts, $start_times, $matches, $match_times ) {
		global $wpdb, $racketmanager;
		$orderofplay = array();
		$num_courts  = count( $courts );
		for ( $i = 0; $i < $num_courts; $i++ ) {
			$orderofplay[ $i ]['court']     = $courts[ $i ];
			$orderofplay[ $i ]['starttime'] = $start_times[ $i ];
			$orderofplay[ $i ]['matches']   = $matches[ $i ];
			$num_matches                    = count( $matches[ $i ] );
			for ( $m = 0; $m < $num_matches; $m++ ) {
				$match_id = trim( $matches[ $i ][ $m ] );
				if ( ! empty( $match_id ) ) {
					$time  = strtotime( $start_times[ $i ] ) + $match_times[ $i ][ $m ];
					$match = get_match( $match_id );
					if ( $match ) {
						$month    = str_pad( $match->month, 2, '0', STR_PAD_LEFT );
						$day      = str_pad( $match->day, 2, '0', STR_PAD_LEFT );
						$date     = $match->year . '-' . $month . '-' . $day . ' ' . gmdate( 'H:i', $time );
						$location = $courts[ $i ];
						if ( $date !== $match->date || $location !== $match->location ) {
							$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
								$wpdb->prepare(
									"UPDATE {$wpdb->racketmanager_matches} SET `date` = %s, `location` = %s WHERE `id` = %d",
									$date,
									$location,
									$match_id
								)
							);
						}
					}
				}
			}
		}
		if ( $orderofplay !== $this->orderofplay ) {
			$this->orderofplay = $orderofplay;
			$orderofplay       = maybe_serialize( $orderofplay );
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_tournaments} SET `orderofplay` = %s WHERE `id` = %d",
					$orderofplay,
					$this->id
				)
			);
			wp_cache_set( $this->id, $this, 'tournaments' );
			$racketmanager->set_message( __( 'Tournament plan updated', 'racketmanager' ) );
		} else {
			$racketmanager->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
		}
		return true;
	}

	/**
	 * Reset tournament plan
	 *
	 * @return boolean updates performed
	 */
	public function reset_plan() {
		global $wpdb, $racketmanager;

		$updates       = true;
		$orderofplay   = array();
		$final_matches = $racketmanager->get_matches(
			array(
				'season'         => $this->season,
				'final'          => 'final',
				'competition_id' => $this->competition_id,
			)
		);

		foreach ( $final_matches as $match ) {
			$month    = str_pad( $match->month, 2, '0', STR_PAD_LEFT );
			$day      = str_pad( $match->day, 2, '0', STR_PAD_LEFT );
			$date     = $match->year . '-' . $month . '-' . $day . ' 00:00';
			$location = '';
			if ( $date !== $match->date || $location !== $match->location ) {
				$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"UPDATE {$wpdb->racketmanager_matches} SET `date` = %s, `location` = %s WHERE `id` = %d",
						$date,
						$location,
						$match->id
					)
				);
				$updates = true;
			}
		}
		if ( $orderofplay !== $this->orderofplay ) {
			$this->orderofplay = $orderofplay;
			$orderofplay       = maybe_serialize( $orderofplay );
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_tournaments} SET `orderofplay` = %s WHERE `id` = %d",
					$orderofplay,
					$this->id
				)
			);
			wp_cache_set( $this->id, $this, 'tournaments' );
			$updates = true;
		}
		if ( $updates ) {
			$racketmanager->set_message( __( 'Tournament plan reset', 'racketmanager' ) );
		} else {
			$racketmanager->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
		}
		return $updates;
	}
	/**
	 * Delete tournament
	 */
	public function delete() {
		global $wpdb, $racketmanager;
		$schedule_name = 'rm_calculate_tournament_ratings';
		$schedule_args = array( intval( $this->id ) );
		Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
		$schedule_name = 'rm_notify_tournament_entry_open';
		Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
		$schedule_name = 'rm_notify_tournament_entry_reminder';
		Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_tournaments} WHERE `id` = %d",
				$this->id
			)
		);
		$racketmanager->set_message( __( 'Tournament Deleted', 'racketmanager' ) );
		wp_cache_delete( $this->id, 'tournaments' );
	}
	/**
	 * Get events function
	 *
	 * @return array
	 */
	public function get_events() {
		$competition = get_competition( $this->competition_id );
		if ( $competition ) {
			$players      = array();
			$events       = $competition->get_events();
			$this->events = array();
			foreach ( $events as $event ) {
				$event               = get_event( $event );
				$event->team_count   = $event->get_teams(
					array(
						'season' => $this->season,
						'count'  => true,
					)
				);
				$event->player_count = $event->get_players(
					array(
						'season' => $this->season,
						'count'  => true,
					)
				);
				$this->events[]      = $event;
			}
		}
		return $this->events;
	}
	/**
	 * Get players function
	 *
	 * @param array $args optional arguments.
	 * @return array
	 */
	public function get_players( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'orderby' => array(),
			'count'   => false,
			'player'  => false,
		);
		$args     = array_merge( $defaults, $args );
		$orderby  = $args['orderby'];
		$count    = $args['count'];
		$player   = $args['player'];

		if ( $count ) {
			$sql = 'SELECT COUNT(distinct(`player_id`))';
		} else {
			$sql = 'SELECT DISTINCT `player_id`';
		}
		$sql          .= " FROM {$wpdb->racketmanager_team_players} tp, {$wpdb->racketmanager_table} t, {$wpdb->racketmanager} l, {$wpdb->racketmanager_events} e  WHERE tp.`team_id` = t.`team_id` AND t.`league_id` = l.`id` AND l.`event_id` = e.`id` AND e.`competition_id` = %d AND t.`season` = %d";
		$search_terms  = array();
		$search_args   = array();
		$search_args[] = $this->competition_id;
		$search_args[] = $this->season;
		if ( $player ) {
			$search_terms[] = 'tp.`player_id` = %d';
			$search_args[]  = $player;
		}
		$search        = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' AND ';
			$search .= implode( ' AND ', $search_terms );
		}
		$orderby_string = '';
		$order          = '';
		$i              = 0;
		foreach ( $orderby as $order => $direction ) {
			if ( ! in_array( $direction, array( 'DESC', 'ASC', 'desc', 'asc' ), true ) ) {
				$direction = 'ASC';
			}
			$orderby_string .= '`' . $order . '` ' . $direction;
			if ( $i < ( count( $orderby ) - 1 ) ) {
				$orderby_string .= ',';
			}
			++$i;
		}
		if ( $orderby_string ) {
			$order = ' ORDER BY ' . $orderby_string;
		}
		$sql .= $search;
		if ( $count ) {
			$sql = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql,
				$search_args,
			);
			$num_players = wp_cache_get( md5( $sql ), 'tournament_players' );
			if ( ! $num_players ) {
				$num_players = $wpdb->get_var(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql
				); // db call ok.
				wp_cache_set( md5( $sql ), $num_players, 'tournament_players' );
			}
			return $num_players;
		}
		$sql .= $order;
		$sql  = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql,
			$search_args,
		);
		$players = wp_cache_get( md5( $sql ), 'tournament_players' );
		if ( ! $players ) {
			$players = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $players, 'tournament_players' );
		}
		$i = 0;
		foreach ( $players as $player ) {
			$player = get_player( $player->player_id );
			if ( $player ) {
				$players[ $i ] = $player;
			}
			++$i;
		}
		$this->players = $players;
		return $this->players;
	}
	/**
	 * Get Tournament Entries function
	 *
	 * @param array $args optional arguments.
	 * @return array
	 */
	public function get_entries( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'orderby' => array(),
			'count'   => false,
			'status'  => false,
		);
		$args     = array_merge( $defaults, $args );
		$orderby  = $args['orderby'];
		$count    = $args['count'];
		$status   = $args['status'];
		if ( $count ) {
			$sql = 'SELECT COUNT(*)';
		} else {
			$sql = 'SELECT `id`, `player_id`, `status`, `club_id`';
		}
		$sql          .= " FROM {$wpdb->racketmanager_tournament_entries} WHERE `tournament_id` = %d";
		$search_terms  = array();
		$search_args   = array();
		$search_args[] = $this->id;
		if ( $status ) {
			if ( 'pending' === $status ) {
				$search_terms[] = '`status` = 0';
			} elseif ( 'unpaid' === $status ) {
				if ( ! empty( $this->charge ) ) {
					$search_terms[] = '`status` = 2';
					$search_terms[] = "`player_id` IN (SELECT `player_id` FROM {$wpdb->racketmanager_invoices} WHERE `charge_id` = %d AND `status` != 'paid')";
					$search_args[]  = $this->charge->id;
				} else {
					$search_terms[] = '`status` = 99';
				}
			} elseif ( 'confirmed' === $status ) {
				$search_terms[] = '`status` = 2';
				if ( ! empty( $this->charge ) ) {
					$search_terms[] = "`player_id` NOT IN (SELECT `player_id` FROM {$wpdb->racketmanager_invoices} WHERE `charge_id` = %d AND `status` != 'paid')";
					$search_args[]  = $this->charge->id;
				}
			} elseif ( 'withdrawn' === $status ) {
				$search_terms[] = '`status` = 3';
			}
		}
		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' AND ';
			$search .= implode( ' AND ', $search_terms );
		}
		$orderby_string = '';
		$order          = '';
		$i              = 0;
		foreach ( $orderby as $order => $direction ) {
			if ( ! in_array( $direction, array( 'DESC', 'ASC', 'desc', 'asc' ), true ) ) {
				$direction = 'ASC';
			}
			$orderby_string .= '`' . $order . '` ' . $direction;
			if ( $i < ( count( $orderby ) - 1 ) ) {
				$orderby_string .= ',';
			}
			++$i;
		}
		if ( $orderby_string ) {
			$order = ' ORDER BY ' . $orderby_string;
		}
		$sql .= $search;
		if ( $count ) {
			$sql = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql,
				$search_args,
			);
			$num_players = wp_cache_get( md5( $sql ), 'tournament_entries' );
			if ( ! $num_players ) {
				$num_players = $wpdb->get_var(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql
				); // db call ok.
				wp_cache_set( md5( $sql ), $num_players, 'tournament_entries' );
			}
			return $num_players;
		}
		$sql .= $order;
		$sql  = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql,
			$search_args,
		);
		$tournament_entries = wp_cache_get( md5( $sql ), 'tournament_entries' );
		if ( ! $tournament_entries ) {
			$tournament_entries = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $tournament_entries, 'tournament_entries' );
		}
		$i = 0;
		foreach ( $tournament_entries as $tournament_entry ) {
			$player = get_player( $tournament_entry->player_id );
			if ( $player ) {
				$player->status   = $tournament_entry->status;
				$player->entry_id = $tournament_entry->id;
				$player->club     = null;
				if ( $tournament_entry->club_id ) {
					$club = get_club( $tournament_entry->club_id );
					if ( $club ) {
						$player->club = $club;
					}
				}
				$tournament_entries[ $i ] = $player;
			}
			++$i;
		}
		return $tournament_entries;
	}
	/**
	 * Schedule tournament ratings setting function
	 *
	 * @return void
	 */
	public function schedule_tournament_ratings() {
		global $racketmanager;
		$schedule_date  = strtotime( $this->date_open );
		$day            = intval( gmdate( 'd', $schedule_date ) );
		$month          = intval( gmdate( 'm', $schedule_date ) );
		$year           = intval( gmdate( 'Y', $schedule_date ) );
		$schedule_start = mktime( 12, 0, 0, $month, $day, $year );
		$schedule_name  = 'rm_calculate_tournament_ratings';
		$schedule_args  = array( intval( $this->id ) );
		Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
		$success = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
		if ( ! $success ) {
			$racketmanager->set_message( __( 'Error scheduling tournament ratings calculation', 'racketmanager' ), true );
		}
	}
	/**
	 * Get unique match dates function
	 *
	 * @return array
	 */
	public function get_match_dates() {
		global $wpdb;
		$matches = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT distinct DATE_FORMAT(m.`date`, %s) AS `date` FROM {$wpdb->racketmanager_matches} AS m, {$wpdb->racketmanager} AS l, {$wpdb->racketmanager_events} e WHERE m.`league_id` = l.`id` AND l.`event_id` = e.`id` AND e.`competition_id` = %d AND m.`season` = %d ORDER BY 1 ASC",
				'%Y-%m-%d',
				$this->competition_id,
				$this->season,
			)
		);
		return $matches;
	}
	/**
	 * Schedule tournament activities function
	 *
	 * @return void
	 */
	public function schedule_activities() {
		if ( ! $this->is_closed && ! $this->is_active ) {
			$this->schedule_tournament_ratings();
			$this->schedule_emails();
		}
	}
	/**
	 * Schedule tournament emails function
	 *
	 * @return void
	 */
	private function schedule_emails() {
		global $racketmanager;
		if ( ! empty( $this->date_open ) ) {
			$schedule_date   = strtotime( $this->date_open );
			$day             = intval( gmdate( 'd', $schedule_date ) );
			$month           = intval( gmdate( 'm', $schedule_date ) );
			$year            = intval( gmdate( 'Y', $schedule_date ) );
			$schedule_start  = mktime( 00, 00, 01, $month, $day, $year );
			$schedule_name   = 'rm_notify_tournament_entry_open';
			$schedule_args[] = intval( $this->id );
			Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
			$success = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
			if ( ! $success ) {
				error_log( __( 'Error scheduling tournament open emails', 'racketmanager' ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
		if ( ! empty( $this->date_closing ) ) {
			$chase_date     = Racketmanager_Util::amend_date( $this->date_closing, 7, '-' );
			$day            = substr( $chase_date, 8, 2 );
			$month          = substr( $chase_date, 5, 2 );
			$year           = substr( $chase_date, 0, 4 );
			$schedule_start = mktime( 00, 00, 01, $month, $day, $year );
			$schedule_name  = 'rm_notify_tournament_entry_reminder';
			Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
			$success = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
			if ( ! $success ) {
				error_log( __( 'Error scheduling tournament reminder emails', 'racketmanager' ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
	}
	/**
	 * Notify clubs entries open
	 *
	 * @return object notification status
	 */
	public function notify_entry_open() {
		global $racketmanager_shortcodes, $racketmanager;

		$return           = new \stdClass();
		$msg              = array();
		$date_closing     = $this->date_closing_display;
		$date_start       = $this->date_open_display;
		$date_end         = $this->date_display;
		$url              = $racketmanager->site_url . '/entry-form/' . seo_url( $this->name ) . '-tournament/';
		$competition_name = $this->name . ' ' . __( 'Tournament', 'racketmanager' );
		$is_championship  = true;
		if ( empty( $return->error ) ) {
			$clubs = $racketmanager->get_clubs(
				array(
					'type' => 'affiliated',
				)
			);

			$headers    = array();
			$from_email = $racketmanager->get_confirmation_email( 'tournament' );
			if ( $from_email ) {
				$headers[]         = 'From: Tournament Secretary <' . $from_email . '>';
				$headers[]         = 'cc: Tournament Secretary <' . $from_email . '>';
				$organisation_name = $racketmanager->site_name;

				foreach ( $clubs as $club ) {
					$email_subject = $racketmanager->site_name . ' - ' . ucwords( $competition_name ) . ' ' . __( 'Entry Open', 'racketmanager' ) . ' - ' . $club->name;
					$email_to      = $club->match_secretary_name . ' <' . $club->match_secretary_email . '>';
					$action_url    = $url . seo_url( $club->shortcode ) . '/';
					$email_message = $racketmanager_shortcodes->load_template(
						'tournament-entry-open',
						array(
							'email_subject' => $email_subject,
							'from_email'    => $from_email,
							'action_url'    => $action_url,
							'organisation'  => $organisation_name,
							'tournament'    => $this,
							'addressee'     => $club->match_secretary_name,
						),
						'email'
					);
					wp_mail( $email_to, $email_subject, $email_message, $headers );
					$message_sent = true;
				}
				if ( $message_sent ) {
					$return->msg = __( 'Match secretaries notified', 'racketmanager' );
				} else {
					$return->error = true;
					$msg[]         = __( 'No notification', 'racketmanager' );
				}
			} else {
				$return->error = true;
				$msg[]         = __( 'No secretary email', 'racketmanager' );
			}
		}
		if ( ! empty( $return->error ) ) {
			$return->msg = __( 'Notification error', 'racketmanager' );
			foreach ( $msg as $error ) {
				$return->msg .= '<br>' . $error;
			}
		}
		return $return;
	}
	/**
	 * Notify previous entrants entries open reminder
	 *
	 * @return object notification status
	 */
	public function notify_entry_reminder() {
		global $racketmanager_shortcodes, $racketmanager;

		$return           = new \stdClass();
		$msg              = array();
		$date_closing     = $this->date_closing_display;
		$date_start       = $this->date_open_display;
		$date_end         = $this->date_display;
		$url              = $racketmanager->site_url . '/entry-form/' . seo_url( $this->name ) . '-tournament/';
		$competition_name = $this->name . ' ' . __( 'Tournament', 'racketmanager' );
		$is_championship  = true;
		$date_closing     = date_create( $this->date_closing );
		$now              = date_create();
		$remaining_time   = date_diff( $date_closing, $now, true );
		$days_remaining   = $remaining_time->days;
		$players          = $this->get_not_entered_player_list();
		if ( $players ) {
			$headers    = array();
			$from_email = $racketmanager->get_confirmation_email( 'tournament' );
			if ( $from_email ) {
				$headers[]         = 'From: Tournament Secretary <' . $from_email . '>';
				$headers[]         = 'cc: Tournament Secretary <' . $from_email . '>';
				$organisation_name = $racketmanager->site_name;

				foreach ( $players as $player ) {
					$email_subject = $racketmanager->site_name . ' - ' . ucwords( $competition_name ) . ' ' . __( 'Entry Open', 'racketmanager' ) . ' - ' . __( 'Reminder', 'racketmanager' );
					$email_to      = $player->display_name . ' <' . $player->email . '>';
					$action_url    = $url;
					$email_message = $racketmanager_shortcodes->load_template(
						'tournament-entry-open',
						array(
							'email_subject'  => $email_subject,
							'from_email'     => $from_email,
							'action_url'     => $action_url,
							'organisation'   => $organisation_name,
							'tournament'     => $this,
							'addressee'      => $player->display_name,
							'days_remaining' => $days_remaining,
						),
						'email'
					);
					wp_mail( $email_to, $email_subject, $email_message, $headers );
					$message_sent = true;
				}
				if ( $message_sent ) {
					$return->msg = __( 'Players notified', 'racketmanager' );
				} else {
					$return->error = true;
					$msg[]         = __( 'No notification', 'racketmanager' );
				}
			} else {
				$return->error = true;
				$msg[]         = __( 'No secretary email', 'racketmanager' );
			}
		}
		if ( ! empty( $return->error ) ) {
			$return->msg = __( 'Notification error', 'racketmanager' );
			foreach ( $msg as $error ) {
				$return->msg .= '<br>' . $error;
			}
		}
		return $return;
	}
	/**
	 * Get list of players not yet entered into tournament function
	 *
	 * @return array array of player objects
	 */
	private function get_not_entered_player_list() {
		global $wpdb;
		$limit   = 2;
		$sql     = $wpdb->prepare(
			"SELECT DISTINCT(te.player_id) FROM {$wpdb->racketmanager_tournament_entries} te, {$wpdb->racketmanager_competitions} c, {$wpdb->racketmanager_tournaments} t INNER JOIN (SELECT t.`id` FROM {$wpdb->racketmanager_tournaments} t, {$wpdb->racketmanager_competitions} c WHERE t.`competition_id` = c.`id` AND c.`age_group` = %s AND t.`id` != %d ORDER BY t.`id` DESC LIMIT %d) t1 ON t.`id` = t1.`id` WHERE te.`tournament_id` = t.`id` AND c.`id` = t.`competition_id` AND c.`age_group` = %s AND te.`player_id` IN (SELECT DISTINCT `player_id` FROM {$wpdb->racketmanager_club_players} WHERE `removed_date` IS NULL) AND te.`player_id` NOT IN (SELECT `player_id` FROM {$wpdb->racketmanager_tournament_entries} WHERE `tournament_id` = %d)",
			$this->competition->age_group,
			$this->id,
			$limit,
			$this->competition->age_group,
			$this->id,
		);
		$players = wp_cache_get( md5( $sql ), 'tournament_players' );
		if ( ! $players ) {
			$players = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $players, 'tournament_players' );
		}
		foreach ( $players as $i => $player_ref ) {
			$player = get_player( $player_ref->player_id );
			if ( $player ) {
				if ( empty( $player->email ) ) {
					unset( $players[ $i ] );
				} else {
					$players[ $i ] = $player;
				}
			}
		}
		return $players;
	}
	/**
	 * Get fees for tournament function
	 *
	 * @return object fees
	 */
	public function get_fees() {
		global $racketmanager;
		$args                = array();
		$args['competition'] = $this->competition_id;
		$args['season']      = $this->season;
		$charges             = $racketmanager->get_charges( $args );
		$competition_fee     = null;
		$event_fee           = null;
		$fee_id              = null;
		$fee_status          = null;
		if ( $charges ) {
			$competition_fee = 0;
			$event_fee       = 0;
			foreach ( $charges as $charge ) {
				$competition_fee += $charge->fee_competition;
				$event_fee       += $charge->fee_event;
				$fee_id           = $charge->id;
				$fee_status       = $charge->status;
			}
		}
		$fees              = new \stdClass();
		$fees->competition = $competition_fee;
		$fees->event       = $event_fee;
		$fees->id          = $fee_id;
		$fees->status      = $fee_status;
		$this->fees        = $fees;
		return $this->fees;
	}
	/**
	 * Set player entry function
	 *
	 * @param object $entry entry details.
	 * @return boolean payment required indicator
	 */
	public function set_player_entry( $entry ) {
		global $racketmanager;
		$updates = false;
		$player = get_player( $entry->player_id );
		$player->update_btm( $entry->btm );
		$player->update_contact( $entry->contactno, $entry->contactemail );
		$player_name = $player->display_name;
		$club        = get_club( $entry->club_id );
		$fee_due     = 0;
		$tournament_entries = array();
		if ( empty( $entry->fee ) ) {
			$status = 0;
		} else {
			$fee_due = $entry->fee - $entry->paid;
			if ( empty( $fee_due ) ) {
				$this->cancel_player_invoices( $entry->player_id );
				$status = 0;
			} else {
				if ( $fee_due > 0 ) {
					$status = 1;
				} else {
					$status = 2;
				}
				$this->create_player_invoice( $entry->player_id, $fee_due );
			}
		}
		$this->set_tournament_entry( $player->id, $club->id, $fee_due );
		foreach ( $entry->all_events as $event_id ) {
			$event = get_event( $event_id );
			if ( $event ) {
				if ( ! empty( $event->primary_league ) ) {
					$league = get_league( $event->primary_league );
				} else {
					$leagues = $event->get_leagues();
					$league  = get_league( $leagues[0] );
				}
				$teams = $event->get_teams(
					array(
						'player' => $entry->player_id,
						'season' => $this->season,
					)
				);
				if ( isset( $entry->events[ $event_id ] ) ) {
					$tournament_entry = array();
					$partner          = '';
					$partner_name     = '';
					$partner_id       = '';
					$new_team         = false;
					$existing_entry   = false;
					$new_entry        = false;
					$team             = null;
					$is_doubles       = false;
					if ( 1 === count( $teams ) ) {
						$existing_entry = true;
						$entry_status   = 'existing';
						$team           = $teams[0];
					} elseif ( count( $teams ) > 1 ) {
						$entry_status = 'multi';
					} else {
						$new_entry    = true;
						$entry_status = 'new';
					}
					if ( substr( $event->type, 1, 1 ) === 'D' ) {
						$is_doubles = true;
						$partner_id = isset( $entry->partners[ $event->id ] ) ? $entry->partners[ $event->id ] : null;
						if ( $partner_id ) {
							$partner = get_player( $partner_id );
							if ( $partner ) {
								$partner_name                = $partner->fullname;
								$tournament_entry['partner'] = $partner_name;
							}
						}
						if ( $existing_entry ) {
							$same_partner = false;
							foreach ( $team->players as $team_player ) {
								if ( $team_player->id === $partner_id ) {
									$same_partner = true;
									break;
								}
							}
							if ( ! $same_partner ) {
								$league->delete_team( $team->team_id, $this->season );
								$new_entry    = true;
								$entry_status = 'replace';
							}
						}
					}
					if ( $new_entry ) {
						if ( $is_doubles ) {
							$team_args            = array();
							$team_args['player']  = $player->id;
							$team_args['partner'] = $partner->id;
							$teams                = $racketmanager->get_teams( $team_args );
							if ( $teams ) {
								$team = $teams[0];
							} else {
								$new_team  = true;
								$team_name = $player->display_name . ' / ' . $partner_name;
							}
							$this->set_tournament_entry( $partner_id );
						} else {
							$team = get_team( $player->display_name );
							if ( ! $team ) {
								$new_team  = true;
							}
						}
						if ( $new_team ) {
							$team             = new \stdClass();
							$team->player1    = $player->display_name;
							$team->player1_id = $player->id;
							$team->player2    = $partner_name;
							$team->player2_id = $partner_id;
							$team->type       = $league->type;
							$team->team_type  = 'P';
							$team->club_id    = $club->id;
							$team             = new Racketmanager_Team( $team );
						}
						$team->set_event( $league->event_id, $player->id, $player->contactno, $player->email );
						$league_entry_id = $league->add_team( $team->id, $this->season );
						if ( $league_entry_id ) {
							$league_entry = get_league_team( $league_entry_id );
							if ( $league_entry ) {
								$league_entry->set_player_rating( $team, $event );
							}
						}
						$updates = true;
					}
					$tournament_entry['event_name'] = $event->name;
					$tournament_entries[]           = $tournament_entry;
				} else {
					foreach ( $teams as $team ) {
						$updates = true;
						$league->delete_team( $team->team_id, $this->season );
					}
				}
			}
		}
		if ( $updates ) {
			$email_to                            = $player->display_name . ' <' . $player->email . '>';
			$email_from                          = $racketmanager->get_confirmation_email( 'tournament' );
			$email_subject                       = $racketmanager->site_name . ' - ' . $this->name . ' Tournament Entry';
			$action_url                          = $racketmanager->site_url . '/entry-form/' . seo_url( $this->name ) . '-tournament/';
			$tournament_link                     = '<a href="' . $racketmanager->site_url . ( $this->link ) . '/">' . $this->name . '</a>';
			$headers                             = array();
			$secretary_email                     = __( 'Tournament Secretary', 'racketmanager' ) . ' <' . $email_from . '>';
			$headers[]                           = 'From: ' . $secretary_email;
			$headers[]                           = 'Cc: ' . $secretary_email;
			$template                            = 'tournament-entry';
			$template_args['tournament_name']    = $this->name;
			$template_args['tournament_link']    = $tournament_link;
			$template_args['action_url']         = $action_url;
			$template_args['tournament_entries'] = $tournament_entries;
			$template_args['organisation']       = $racketmanager->site_name;
			$template_args['season']             = $this->season;
			$template_args['contactno']          = $player->contactno;
			$template_args['contactemail']       = $player->email;
			$template_args['player']             = $player;
			$template_args['club']               = $club->name;
			$template_args['comments']           = $entry->comments;
			$template_args['contact_email']      = $email_from;
			$racketmanager->email_entry_form( $template, $template_args, $email_to, $email_subject, $headers );
		} elseif ( empty( $status ) ) {
			$status = 3;
		}
		return $status;
	}
	/**
	 * Create invoice for player function
	 *
	 * @param int    $player player id.
	 * @param string $fee payment required value.
	 * @return void
	 */
	private function create_player_invoice( $player_id, $fee ) {
		global $racketmanager;
		if ( empty( $player_id ) || empty( $fee ) ) {
			return;
		}
		if ( $this->charge ) {
			$this->cancel_player_invoices( $player_id );
			$invoice            = new \stdClass();
			$invoice->charge_id = $this->charge->id;
			$invoice->player_id = $player_id;
			$invoice->date      = gmdate( 'Y-m-d' );
			$invoice            = new Racketmanager_Invoice( $invoice );
			$invoice->set_amount( $fee );
			$player         = get_player( $player_id );
			$charge         = get_charge( $this->charge );
			$details        = $charge->get_player_entry( $player );
			$args           = array();
			$paid_amount    = 0;
			$args['player'] = $player_id;
			$args['charge'] = $this->charge->id;
			$args['status'] = 'paid';
			$args['before'] = $invoice->id;
			$prev_invoices  = $racketmanager->get_invoices( $args );
			foreach ( $prev_invoices as $prev_invoice ) {
				$paid_amount += $prev_invoice->amount;
			}
			$details->paid = $paid_amount;
			$invoice->set_details( $details );
		}
	}
	/**
	 * Cancel outstanding invoices for player function
	 *
	 * @param int    $player player id.
	 * @return void
	 */
	private function cancel_player_invoices( $player_id ) {
		if ( empty( $player_id ) ) {
			return;
		}
		if ( $this->charge ) {
			$args['player'] = $player_id;
			$args['status'] = 'open';
			$outstanding_payments = $this->get_payments( $args );
			foreach ( $outstanding_payments as $payment ) {
				$payment->delete();
			}
		}
	}
	/**
	 * Set tournament entry function
	 *
	 * @param int    $player player id.
	 * @param int    $club club id - false if partner entry.
	 * @param string $payment_required payment required value.
	 * @return void
	 */
	private function set_tournament_entry( $player, $club = false, $payment_required = false ) {
		if ( $club ) {
			$status = 2;
		} else {
			$status = 0;
		}
		$search           = $this->id . '_' . $player;
		$tournament_entry = get_tournament_entry( $search, 'key' );
		if ( $tournament_entry ) {
			if ( $club ) {
				if ( $tournament_entry->status !== $status ) {
					$tournament_entry->set_status( $status );
					$tournament_entry->set_fee( $payment_required );
				}
			}
		} else {
			$tournament_entry                = new \stdClass();
			$tournament_entry->status        = $status;
			$tournament_entry->tournament_id = $this->id;
			$tournament_entry->player_id     = $player;
			$tournament_entry->fee           = $payment_required;
			if ( $club ) {
				$tournament_entry->club_id = $club;
			}
			$tournament_entry = new Racketmanager_Tournament_Entry( $tournament_entry );
		}
	}
	/**
	 * Withdraw tournament entry
	 *
	 * @param int    $player player id.
	 * @return string refund amount.
	 */
	public function withdraw_player_entry( $player_id ) {
		global $racketmanager, $racketmanager_shortcodes;
		$amount_refund = 0;
		$updates       = false;
		$player        = get_player( $player_id );
		if ( $player ) {
			$events = $this->competition->get_events();
			foreach ( $events as $event ) {
				$teams  = $event->get_teams(
					array(
						'player' => $player_id,
						'season' => $this->season,
					)
				);
				$league = get_league( $event->primary_league );
				if ( $league ) {
					foreach ( $teams as $team ) {
						$updates = true;
						$league->delete_team( $team->team_id, $this->season );
					}
				}
			}
		}
		if ( $updates ) {
			$amount_paid    = 0;
			$args['player'] = $player_id;
			$args['status'] = 'paid';
			$payments = $this->get_payments( $args );
			foreach ( $payments as $payment ) {
				$amount_paid += $payment->amount;
			}
			if ( $amount_paid ) {
				$amount_refund = 0 - $amount_paid;
				$this->create_player_invoice( $player_id, $amount_refund );
			} else {
				$this->cancel_player_invoices( $player->id );
			}
			$this->set_tournament_entry_withdrawn( $player->id );
			$email_to                         = $player->display_name . ' <' . $player->email . '>';
			$email_from                       = $racketmanager->get_confirmation_email( 'tournament' );
			$email_subject                    = $racketmanager->site_name . ' - ' . $this->name . ' Tournament Withdrawal';
			$action_url                       = $racketmanager->site_url . '/entry-form/' . seo_url( $this->name ) . '-tournament/';
			$tournament_link                  = '<a href="' . $racketmanager->site_url . ( $this->link ) . '/">' . $this->name . '</a>';
			$headers                          = array();
			$secretary_email                  = __( 'Tournament Secretary', 'racketmanager' ) . ' <' . $email_from . '>';
			$headers[]                        = 'From: ' . $secretary_email;
			$headers[]                        = 'Cc: ' . $secretary_email;
			$template                         = 'tournament-withdrawal';
			$template_args['tournament']      = $this;
			$template_args['tournament_name'] = $this->name;
			$template_args['tournament_link'] = $tournament_link;
			$template_args['action_url']      = $action_url;
			$template_args['organisation']    = $racketmanager->site_name;
			$template_args['player']          = $player;
			$template_args['contact_email']   = $email_from;
			$email_message                    = $racketmanager_shortcodes->load_template(
				$template,
				$template_args,
				'email'
			);
			wp_mail( $email_to, $email_subject, $email_message, $headers );
		}
		return $amount_refund;
	}
	/**
	 * Set tournament entry to withdrawn
	 *
	 * @param int    $player player id.
	 * @return void
	 */
	private function set_tournament_entry_withdrawn( $player ) {
		$search           = $this->id . '_' . $player;
		$tournament_entry = get_tournament_entry( $search, 'key' );
		if ( $tournament_entry ) {
			$tournament_entry->set_status( 3 );
		}
	}
	/**
	 * Function to get payments due for tournament
	 *
	 * @param array $args_input arguments to search invoices.
	 * @return array payments or null
	 */
	public function get_payments( $args_input ) {
		global $racketmanager;
		$defaults = array(
			'status' => array(),
			'player' => false,
		);
		$args_input = array_merge( $defaults, $args_input );
		$status     = $args_input['status'];
		$player     = $args_input['player'];
		if ( $this->charge ) {
			$args['charge'] = $this->charge->id;
			$args['player'] = $player;
			$args['status'] = $status;
			$payments = $racketmanager->get_invoices( $args );
		} else {
			$payments = null;
		}
		return $payments;
	}
}
