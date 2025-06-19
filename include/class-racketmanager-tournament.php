<?php
/**
 * Racketmanager_Tournament API: tournament class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Tournament
 */

namespace Racketmanager;

use stdClass;

/**
 * Class to implement the Tournament object
 */
final class Racketmanager_Tournament {

	/**
	 * Id
	 *
	 * @var int
	 */
	public int $id;
	/**
	 * Tournament name
	 *
	 * @var string
	 */
	public string $name;
	/**
	 * Competition id
	 *
	 * @var int|null
	 */
	public ? int $competition_id;
	/**
	 * Competition
	 *
	 * @var object|null
	 */
	public null|object $competition;
	/**
	 * Tournament season
	 *
	 * @var string|null
	 */
	public ? string $season;
	/**
	 * Number of courts available for matches
	 *
	 * @var int|null
	 */
	public ?int $num_courts;
	/**
	 * Start time
	 *
	 * @var string|null
	 */
	public ?string $start_time;
	/**
	 * Date
	 *
	 * @var string
	 */
	public string $date;
	/**
	 * Date display
	 *
	 * @var string|int|false
	 */
	public string|int|false $date_display;
	/**
	 * Closing Date
	 *
	 * @var string
	 */
	public string $date_closing;
	/**
	 * Closing Date display
	 *
	 * @var string|false
	 */
	public string|false $date_closing_display;
	/**
	 * Date withdrawal variable
	 *
	 * @var string|null
	 */
	public ?string $date_withdrawal;
	/**
	 * Date withdrawal display
	 *
	 * @var string|false
	 */
	public string|false $date_withdrawal_display;
	/**
	 * Date open variable
	 *
	 * @var string|null
	 */
	public ?string $date_open;
	/**
	 * Date open display variable
	 *
	 * @var string|false
	 */
	public string|false $date_open_display;
	/**
	 * Date start variable
	 *
	 * @var string|null
	 */
	public ?string $date_start;
	/**
	 * Date start display variable
	 *
	 * @var string|false
	 */
	public string|false $date_start_display;
	/**
	 * Venue
	 *
	 * @var int|null
	 */
	public ? int $venue;
	/**
	 * Venue name
	 *
	 * @var string
	 */
	public string $venue_name;
	/**
	 * Is tournament active
	 *
	 * @var boolean
	 */
	public bool $is_active;
	/**
	 * Order of play
	 *
	 * @var string|array|null
	 */
	public string|array|null $order_of_play = array();
	/**
	 * Time increment for finals day matches
	 *
	 * @var string|null
	 */
	public ?string $time_increment;
	/**
	 * Competitions variable
	 *
	 * @var array
	 */
	public array $competitions;
	/**
	 * Events variable
	 *
	 * @var array
	 */
	public array $events = array();
	/**
	 * Players variable
	 *
	 * @var array
	 */
	public array $players = array();
	/**
	 * Current phase variable
	 *
	 * @var string
	 */
	public string $current_phase;
	/**
	 * Is complete
	 *
	 * @var boolean
	 */
	public bool $is_complete = false;
	/**
	 * Is started
	 *
	 * @var boolean
	 */
	public bool $is_started = false;
	/**
	 * Is closed
	 *
	 * @var boolean
	 */
	public bool $is_closed = false;
	/**
	 * Is withdrawal
	 *
	 * @var boolean
	 */
	public bool $is_withdrawal = false;
	/**
	 * Is open
	 *
	 * @var boolean
	 */
	public bool $is_open = false;
	/**
	 * Link variable
	 *
	 * @var string
	 */
	public string $link;
	/**
	 * Competition code variable
	 *
	 * @var string|null
	 */
	public ?string $competition_code;
	/**
	 * Finals variable
	 *
	 * @var array
	 */
	public array $finals;
	/**
	 * Grade variable
	 *
	 * @var string|null
	 */
	public ?string $grade;
	/**
	 * Fees
	 *
	 * @var object
	 */
	public object $fees;
	/**
	 * Charge object
	 *
	 * @var object
	 */
	public object $charge;
	/**
	 * Number of entries
	 *
	 * @var int|null
	 */
	public ?int $num_entries;
	/**
	 * Type
	 *
	 * @var string
	 */
	public string $type;
	/**
	 * Date end
	 *
	 * @var string
	 */
	public string $date_end;
	/**
	 * Matches
	 *
	 * @var array
	 */
	public array $matches;
	/**
	 * Payments
	 *
	 * @var array|null
	 */
	public ?array $payments;
	/**
	 * Entries
	 *
	 * @var array
	 */
	public array $entries;
	/**
	 * Match dates
	 *
	 * @var array
	 */
	public array $match_dates;

	/**
	 * Retrieve tournament instance
	 *
	 * @param int|string $tournament_id tournament id.
	 * @param string $search_term search term - defaults to id.
	 * @return object|boolean /boolean
	 */
	public static function get_instance(int|string $tournament_id, string $search_term = 'id' ): object|bool {
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
					"SELECT `id`, `name`, `competition_id`, `season`, `venue`, DATE_FORMAT(`date`, '%%Y-%%m-%%d') AS date, DATE_FORMAT(`date_closing`, '%%Y-%%m-%%d') AS `date_closing`, `date_start`, `date_open`, `date_withdrawal`, `grade`, `num_entries`, `numcourts` AS `num_courts`, `starttime` as `start_time`, `timeincrement` AS `time_increment`, `orderofplay` as `order_of_play`, `competition_code` FROM $wpdb->racketmanager_tournaments WHERE $search"
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
	 * @param object|null $tournament Tournament object.
	 */
	public function __construct( object $tournament = null ) {
		global $racketmanager, $wp;
		if ( ! is_null( $tournament ) ) {
			foreach ( $tournament as $key => $value ) {
				$this->$key = $value;
			}

			if ( ! isset( $this->id ) ) {
				$this->add();
			}
			$this->link                    = '/tournament/' . seo_url( $this->name ) . '/';
			$this->date_display            = ( str_starts_with( $this->date, '0000-00-00') ) ? 'TBC' : mysql2date( $racketmanager->date_format, $this->date );
			$this->date_closing_display    = ( str_starts_with( $this->date_closing, '0000-00-00' ) ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date_closing );
			$this->date_withdrawal_display = ( str_starts_with( $this->date_closing, '0000-00-00' ) ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date_withdrawal );
			$this->date_open_display       = empty( $this->date_open ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date_open );
			$this->date_start_display      = empty( $this->date_start ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date_start );
			$today                         = gmdate( 'Y-m-d' );
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
				$this->venue      = null;
				$this->venue_name = 'TBC';
			} else {
				$this->venue_name = get_club( $tournament->venue )->shortcode;
			}
			if ( isset( $this->date_closing ) && $this->date_closing <= gmdate( 'Y-m-d' ) ) {
				$this->is_active = true;
			} else {
				$this->is_active = false;
			}
			$this->order_of_play = (array) maybe_unserialize( $this->order_of_play );
			if ( $this->competition_id ) {
				$this->competition = get_competition( $this->competition_id );
			}
			$finals     = array();
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
	private function add(): void {
		global $wpdb, $racketmanager;
		$validate = $this->validate( $this );
		if ( $validate->valid ) {
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"INSERT INTO $wpdb->racketmanager_tournaments (`name`, `competition_id`, `season`, `venue`, `date_open`, `date_closing`, `date_withdrawal`, `date_start`, `date`, `competition_code`, `grade`, `num_entries` ) VALUES (%s, %s, %d, %d, %s, %s, %s, %s, %s, %s, %s, %d )",
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
					$this->num_entries,
				)
			);
			$racketmanager->set_message( __( 'Tournament added', 'racketmanager' ) );
			$this->id                      = $wpdb->insert_id;
			$this->order_of_play             = '';
			$racketmanager->error_fields   = null;
			$racketmanager->error_messages = null;
		} else {
			$racketmanager->error_fields   = $validate->fld;
			$racketmanager->error_messages = $validate->msg;
			$racketmanager->set_message( __( 'Error creating tournament', 'racketmanager' ), true );
		}
	}

	/**
	 * Update tournament
	 *
	 * @param object $updated updated tournament values.
	 * @return boolean
	 */
	public function update(object $updated ): bool {
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
			$this->start_time       = $updated->start_time;
			$this->competition_code = $updated->competition_code;
			$this->grade            = $updated->grade;
			$this->num_entries      = $updated->num_entries;
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE $wpdb->racketmanager_tournaments SET `name` = %s, `competition_id` = %d, `season` = %s, `venue` = %d, `date_open` = %s, `date_closing` = %s, `date_withdrawal` = %s, `date_start` = %s, `date` = %s, `starttime` = %s, `competition_code` = %s, `grade` = %s, `num_entries` = %d WHERE `id` = %d",
					$updated->name,
					$updated->competition_id,
					$updated->season,
					$updated->venue,
					$updated->date_open,
					$updated->date_closing,
					$updated->date_withdrawal,
					$updated->date_start,
					$updated->date,
					$updated->start_time,
					$updated->competition_code,
					$this->grade,
					$this->num_entries,
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
	 * Function to validate new or updated tournament
	 *
	 * @param object $tournament tournament details.
	 * @return object
	 */
	private function validate(object $tournament ): object {
		$return  = new stdClass();
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
		if ( empty( $tournament->num_entries ) ) {
			$valid     = false;
			$err_msg[] = __( 'Number of entries is required', 'racketmanager' );
			$err_fld[] = 'num_entries';
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
				$charge                  = new stdClass();
				$charge->competition_id  = $this->competition_id;
				$charge->season          = $this->season;
				$charge->date            = $this->date_start;
				$charge->fee_competition = $tournament->fees->competition;
				$charge->fee_event       = $tournament->fees->event;
				$this->charge            = new Charges( $charge );
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
	 * @param string $start_time start time.
	 * @param int    $num_courts number of courts.
	 * @param string $time_increment time increment for matches.
	 * @return boolean updates performed
	 */
	public function update_plan(string $start_time, int $num_courts, string $time_increment ): bool {
		global $wpdb, $racketmanager;

		$update = false;
		if ( $start_time !== $this->start_time || $num_courts !== $this->num_courts || $time_increment !== $this->time_increment ) {
			$this->start_time      = $start_time;
			$this->num_courts     = $num_courts;
			$this->time_increment = $time_increment;
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE $wpdb->racketmanager_tournaments SET `starttime` = %s, `numcourts` = %d, `timeincrement` = %s WHERE `id` = %d",
					$start_time,
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
	public function save_plan(array $courts, array $start_times, array $matches, array $match_times ): bool {
		global $wpdb, $racketmanager;
		$order_of_play = array();
		$num_courts    = count( $courts );
		for ( $i = 0; $i < $num_courts; $i++ ) {
			$order_of_play[ $i ]['court']      = $courts[ $i ];
			$order_of_play[ $i ]['start_time'] = $start_times[ $i ];
			$order_of_play[ $i ]['matches']    = $matches[ $i ];
			$num_matches                       = count( $matches[ $i ] );
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
							$match->set_match_date_in_db( $date );
							$match->set_location( $location );
						}
					}
				}
			}
		}
		if ( $order_of_play !== $this->order_of_play ) {
			$this->order_of_play = $order_of_play;
			$order_of_play       = maybe_serialize( $order_of_play );
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE $wpdb->racketmanager_tournaments SET `orderofplay` = %s WHERE `id` = %d",
					$order_of_play,
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
	public function reset_plan(): bool {
		global $wpdb, $racketmanager;

		$updates       = false;
		$order_of_play = array();
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
				$match->set_match_date_in_db( $date );
				$match->set_location( $location );
				$updates = true;
			}
		}
		if ( $order_of_play !== $this->order_of_play ) {
			$this->order_of_play = $order_of_play;
			$order_of_play     = maybe_serialize( $order_of_play );
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE $wpdb->racketmanager_tournaments SET `orderofplay` = %s WHERE `id` = %d",
					$order_of_play,
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
	public function delete(): void {
		global $wpdb, $racketmanager;
		$schedule_name = 'rm_calculate_tournament_ratings';
		$schedule_args = array( $this->id );
		Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
		$schedule_name = 'rm_notify_tournament_entry_open';
		Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
		$schedule_name = 'rm_notify_tournament_entry_reminder';
		Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM $wpdb->racketmanager_tournaments WHERE `id` = %d",
				$this->id
			)
		);
		$racketmanager->set_message( __( 'Tournament Deleted', 'racketmanager' ) );
		wp_cache_delete( $this->id, 'tournaments' );
	}
	/**
	 * Get events function
	 *
	 * @param false|string $name name of event (optional).
	 * @return array|object
	 */
	public function get_events( false|string $name = false ): object|array {
		$competition = get_competition( $this->competition_id );
		if ( $competition ) {
			$events       = $competition->get_events();
			$this->events = array();
			foreach ( $events as $event ) {
				$event = get_event( $event );
				if ( $name ) {
					if ( $event->name === ucwords( $name ) ) {
						return $event;
					}
				} else {
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
				}
				$this->events[] = $event;
			}
		}
		return $this->events;
	}
	/**
	 * Get players function
	 *
	 * @param array $args optional arguments.
	 * @return array|int
	 */
	public function get_players(array $args = array() ): array|int {
		global $wpdb;

		$defaults = array(
			'orderby' => array(),
			'count'   => false,
			'player'  => false,
			'active'  => false,
		);
		$args     = array_merge( $defaults, $args );
		$orderby  = $args['orderby'];
		$count    = $args['count'];
		$player   = $args['player'];
		$active   = $args['active'];

		if ( $count ) {
			$sql = 'SELECT COUNT(distinct(`player_id`))';
		} else {
			$sql = 'SELECT DISTINCT `player_id`';
		}
		$sql          .= " FROM $wpdb->racketmanager_team_players tp, $wpdb->racketmanager_table t, $wpdb->racketmanager l, $wpdb->racketmanager_events e  WHERE tp.`team_id` = t.`team_id` AND t.`league_id` = l.`id` AND l.`event_id` = e.`id` AND e.`competition_id` = %d AND t.`season` = %d";
		$search_terms  = array();
		$search_args   = array();
		$search_args[] = $this->competition_id;
		$search_args[] = $this->season;
		if ( $player ) {
			$search_terms[] = 'tp.`player_id` = %d';
			$search_args[]  = $player;
		}
		if ( $active ) {
			$search_terms[] = "( t.team_id in (SELECT `home_team` FROM $wpdb->racketmanager_matches m WHERE t.league_id = m.league_id AND t.season = m.season AND m.winner_id = 0) or t.team_id in (SELECT `away_team` FROM $wpdb->racketmanager_matches m WHERE t.league_id = m.league_id AND t.season = m.season AND m.winner_id = 0) )";
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
	 * @return array|int
	 */
	public function get_entries(array $args = array() ): array|int {
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
		$sql          .= " FROM $wpdb->racketmanager_tournament_entries WHERE `tournament_id` = %d";
		$search_terms  = array();
		$search_args   = array();
		$search_args[] = $this->id;
		if ( $status ) {
			if ( 'pending' === $status ) {
				$search_terms[] = '`status` = 0';
			} elseif ( 'unpaid' === $status ) {
				if ( ! empty( $this->charge ) ) {
					$search_terms[] = '`status` = 2';
					$search_terms[] = "`player_id` IN (SELECT `player_id` FROM $wpdb->racketmanager_invoices WHERE `charge_id` = %d AND `status` != 'paid')";
					$search_args[]  = $this->charge->id;
				} else {
					$search_terms[] = '`status` = 99';
				}
			} elseif ( 'confirmed' === $status ) {
				$search_terms[] = '`status` = 2';
				if ( ! empty( $this->charge ) ) {
					$search_terms[] = "`player_id` NOT IN (SELECT `player_id` FROM $wpdb->racketmanager_invoices WHERE `charge_id` = %d AND `status` != 'paid')";
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
	public function schedule_tournament_ratings(): void {
		global $racketmanager;
		$date_schedule  = Racketmanager_Util::amend_date( $this->date_closing, 1 );
		$schedule_date  = strtotime( $date_schedule );
		$day            = intval( gmdate( 'd', $schedule_date ) );
		$month          = intval( gmdate( 'm', $schedule_date ) );
		$year           = intval( gmdate( 'Y', $schedule_date ) );
		$schedule_start = mktime( 00, 00, 01, $month, $day, $year );
		$schedule_name  = 'rm_calculate_tournament_ratings';
		$schedule_args  = array( $this->id );
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
	public function get_match_dates(): array {
		global $wpdb;
		return $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT distinct DATE_FORMAT(m.`date`, %s) AS `date` FROM $wpdb->racketmanager_matches AS m, $wpdb->racketmanager AS l, $wpdb->racketmanager_events e WHERE m.`league_id` = l.`id` AND l.`event_id` = e.`id` AND e.`competition_id` = %d AND m.`season` = %d ORDER BY 1 ",
				'%Y-%m-%d',
				$this->competition_id,
				$this->season,
			)
		);
	}
	/**
	 * Schedule tournament activities function
	 *
	 * @return void
	 */
	public function schedule_activities(): void {
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
	private function schedule_emails(): void {
		$schedule_args = array();
		if ( ! empty( $this->date_open ) ) {
			$schedule_date   = strtotime( $this->date_open );
			$day             = intval( gmdate( 'd', $schedule_date ) );
			$month           = intval( gmdate( 'm', $schedule_date ) );
			$year            = intval( gmdate( 'Y', $schedule_date ) );
			$schedule_start  = mktime( 00, 00, 01, $month, $day, $year );
			$schedule_name   = 'rm_notify_tournament_entry_open';
			$schedule_args[] = $this->id;
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
	public function notify_entry_open(): object {
		global $racketmanager;
		$return           = new stdClass();
		$msg              = array();
		$url              = $racketmanager->site_url . '/entry-form/' . seo_url( $this->name ) . '-tournament/';
		$competition_name = $this->name . ' ' . __( 'Tournament', 'racketmanager' );
		if ( empty( $return->error ) ) {
			$clubs = $racketmanager->get_clubs(
				array(
					'type' => 'affiliated',
				)
			);
			$player_args            = array();
			$player_args['limit']   = 2;
			$player_args['entered'] = true;
			$players                = $this->get_player_list( $player_args );

			$headers    = array();
			$from_email = $racketmanager->get_confirmation_email( 'tournament' );
			if ( $from_email ) {
				$message_sent      = false;
				$headers[]         = 'From: Tournament Secretary <' . $from_email . '>';
				$headers[]         = 'cc: Tournament Secretary <' . $from_email . '>';
				$organisation_name = $racketmanager->site_name;
				$account_link      = '<a href="' . $racketmanager->site_url . '/member-account/" style="text-decoration: none; color: #006800;">' . __( 'link', 'racketmanager' ) . '</a>';
				foreach ( $players as $player ) {
					$email_subject = $racketmanager->site_name . ' - ' . ucwords( $competition_name ) . ' ' . __( 'Entry Open', 'racketmanager' );
					$email_to      = $player->display_name . ' <' . $player->email . '>';
					$action_url    = $url;
					$email_message = $racketmanager->shortcodes->load_template(
						'tournament-entry-open',
						array(
							'email_subject' => $email_subject,
							'from_email'    => $from_email,
							'action_url'    => $action_url,
							'organisation'  => $organisation_name,
							'tournament'    => $this,
							'addressee'     => $player->display_name,
							'type'          => 'player',
							'account_link'  => $account_link,
						),
						'email'
					);
					wp_mail( $email_to, $email_subject, $email_message, $headers );
					$message_sent = true;
				}

				foreach ( $clubs as $club ) {
					$email_subject = $racketmanager->site_name . ' - ' . ucwords( $competition_name ) . ' ' . __( 'Entry Open', 'racketmanager' ) . ' - ' . $club->name;
					$email_to      = $club->match_secretary_name . ' <' . $club->match_secretary_email . '>';
					$action_url    = $url . seo_url( $club->shortcode ) . '/';
					$email_message = $racketmanager->shortcodes->load_template(
						'tournament-entry-open',
						array(
							'email_subject' => $email_subject,
							'from_email'    => $from_email,
							'action_url'    => $action_url,
							'organisation'  => $organisation_name,
							'tournament'    => $this,
							'addressee'     => $club->match_secretary_name,
							'type'          => 'club',
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
	 * Notify previous entrants entries open reminder
	 *
	 * @return object notification status
	 */
	public function notify_entry_reminder(): object {
		global $racketmanager;
		$return           = new stdClass();
		$msg              = array();
		$url              = $racketmanager->site_url . '/entry-form/' . seo_url( $this->name ) . '-tournament/';
		$competition_name = $this->name . ' ' . __( 'Tournament', 'racketmanager' );
		$date_closing     = date_create( $this->date_closing );
		$now              = date_create();
		$remaining_time   = date_diff( $now, $date_closing );
		$days_remaining   = $remaining_time->days;
		$args             = array();
		$args['limit']    = 2;
		$args['entered']  = false;
		$players          = $this->get_player_list( $args );
		if ( $players ) {
			$headers    = array();
			$from_email = $racketmanager->get_confirmation_email( 'tournament' );
			if ( $from_email ) {
				$message_sent      = false;
				$headers[]         = 'From: Tournament Secretary <' . $from_email . '>';
				$headers[]         = 'cc: Tournament Secretary <' . $from_email . '>';
				$organisation_name = $racketmanager->site_name;
				$account_link      = '<a href="' . $racketmanager->site_url . '/member-account/" style="text-decoration: none; color: #006800;">' . __( 'link', 'racketmanager' ) . '</a>';
				foreach ( $players as $player ) {
					$email_subject = $racketmanager->site_name . ' - ' . ucwords( $competition_name ) . ' ' . __( 'Entry Open', 'racketmanager' ) . ' - ' . __( 'Reminder', 'racketmanager' );
					$email_to      = $player->display_name . ' <' . $player->email . '>';
					$action_url    = $url;
					$email_message = $racketmanager->shortcodes->load_template(
						'tournament-entry-open',
						array(
							'email_subject'  => $email_subject,
							'from_email'     => $from_email,
							'action_url'     => $action_url,
							'organisation'   => $organisation_name,
							'tournament'     => $this,
							'addressee'      => $player->display_name,
							'days_remaining' => $days_remaining,
							'type'           => 'reminder',
							'account_link'   => $account_link,
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
	 * Get list of players function
	 *
	 * @param array $args argument array.
	 * @return array array of player objects
	 */
	private function get_player_list(array $args ): array {
		global $wpdb;
		$defaults = array(
			'limit'   => 1,
			'entered' => false,
		);
		$args_input = array_merge( $defaults, $args );
		$limit     = $args_input['limit'];
		$entered   = $args_input['entered'];
		$search_terms  = array();
		$search_args   = array();
		$search_args[] = $this->competition->age_group;
		$search_args[] = $this->id;
		$search_args[] = $limit;
		$search_args[] = $this->competition->age_group;
		if ( ! $entered ) {
			$search_terms[] = "te.`player_id` NOT IN (SELECT `player_id` FROM $wpdb->racketmanager_tournament_entries WHERE `tournament_id` = %d)";
			$search_args[]  = $this->id;
		}
		$search        = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' AND ';
			$search .= implode( ' AND ', $search_terms );
		}
		$sql     = $wpdb->prepare(
			"SELECT DISTINCT(te.player_id) FROM $wpdb->racketmanager_tournament_entries te, $wpdb->racketmanager_competitions c, $wpdb->racketmanager_tournaments t INNER JOIN (SELECT t.`id` FROM $wpdb->racketmanager_tournaments t, $wpdb->racketmanager_competitions c WHERE t.`competition_id` = c.`id` AND c.`age_group` = %s AND t.`id` != %d ORDER BY t.`id` DESC LIMIT %d) t1 ON t.`id` = t1.`id` WHERE te.`tournament_id` = t.`id` AND c.`id` = t.`competition_id` AND c.`age_group` = %s AND te.`player_id` IN (SELECT DISTINCT `player_id` FROM $wpdb->racketmanager_club_players WHERE `removed_date` IS NULL) " . $search,
				$search_args
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
				} elseif ( in_array( '1', $player->opt_ins, true ) ) {
					$players[ $i ] = $player;
				} else {
					unset( $players[ $i ] );
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
	public function get_fees(): object {
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
		$fees              = new stdClass();
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
	 * @return bool|int payment required indicator
	 */
	public function set_player_entry(object $entry ): bool|int {
		global $racketmanager;
		$updates = false;
		$player  = get_player( $entry->player_id );
		$player->update_btm( $entry->btm );
		$player->update_contact( $entry->contactno, $entry->contactemail );
		$club               = get_club( $entry->club_id );
		$fee_due            = 0;
		$tournament_entries = array();
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
					if ( count( $teams ) ) {
						$existing_entry = true;
						$team           = $teams[0];
					} else {
						$new_entry    = true;
					}
					if ( substr( $event->type, 1, 1 ) === 'D' ) {
						$is_doubles = true;
						$partner_id = $entry->partners[$event->id] ?? null;
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
							}
							$this->set_tournament_entry( $partner_id );
						} else {
							$team = get_team( $player->display_name );
							if ( ! $team ) {
								$new_team  = true;
							}
						}
						if ( $new_team ) {
							$team             = new stdClass();
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
							$league_entry?->set_player_rating($team, $event);
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
		if ( empty( $entry->fee ) ) {
			$status = 0;
		} else {
			$fee_due = $entry->fee - $entry->paid;
			if ( empty( $fee_due ) ) {
				$this->cancel_player_invoices( $entry->player_id );
				$status = 0;
			} else {
				if ( $fee_due > 0 ) {
					if ( $entry->player_id !== get_current_user_id() ) {
						$status = 4;
					} else {
						$status = 1;
					}
				} else {
					$status = 2;
				}
				$this->create_player_invoice( $entry->player_id, $fee_due );
			}
		}
		if ( $updates ) {
			$player->set_opt_in( '1' );
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
	 * @param int $player_id player id.
	 * @param string $fee payment required value.
	 * @return void
	 */
	private function create_player_invoice(int $player_id, string $fee ): void {
		global $racketmanager;
		if ( empty( $player_id ) || empty( $fee ) ) {
			return;
		}
		if ( $this->charge ) {
			$this->cancel_player_invoices( $player_id );
			$invoice            = new stdClass();
			$invoice->charge_id = $this->charge->id;
			$invoice->player_id = $player_id;
			$invoice->date      = gmdate( 'Y-m-d' );
			$invoice            = new Invoice( $invoice );
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
	 * @param int $player_id player id.
	 * @return void
	 */
	private function cancel_player_invoices( int $player_id ): void {
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
	 * @param int $player player id.
	 * @param false|int $club club id - false if partner entry.
	 * @param false|string $payment_required payment required value.
	 * @return void
	 */
	private function set_tournament_entry(int $player, false|int $club = false, false|string $payment_required = false ): void {
		if ( $club ) {
			$status = 2;
		} else {
			$status = 0;
		}
		$search           = $this->id . '_' . $player;
		$tournament_entry = get_tournament_entry( $search, 'key' );
		if ( $tournament_entry ) {
			if ( $club ) {
				if ( empty( $tournament_entry->club_id ) ) {
					$tournament_entry->set_club( $club );
				}
				if ( $tournament_entry->status !== $status ) {
					$tournament_entry->set_status( $status );
					$tournament_entry->set_fee( $payment_required );
				}
			}
		} else {
			$tournament_entry                = new stdClass();
			$tournament_entry->status        = $status;
			$tournament_entry->tournament_id = $this->id;
			$tournament_entry->player_id     = $player;
			$tournament_entry->fee           = $payment_required;
			if ( $club ) {
				$tournament_entry->club_id = $club;
			}
			new Racketmanager_Tournament_Entry( $tournament_entry );
		}
	}

	/**
	 * Withdraw tournament entry
	 *
	 * @param $player_id
	 * @return int|string refund amount.
	 */
	public function withdraw_player_entry( $player_id ): int|string {
		global $racketmanager;
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
			$email_message                    = $racketmanager->shortcodes->load_template(
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
	 * @param int $player player id.
	 * @return void
	 */
	private function set_tournament_entry_withdrawn( int $player ): void {
		$search           = $this->id . '_' . $player;
		$tournament_entry = get_tournament_entry( $search, 'key' );
		$tournament_entry?->set_status(3);
	}
	/**
	 * Function to get payments due for tournament
	 *
	 * @param array $args_input arguments to search invoices.
	 * @return array|null payments or null
	 */
	public function get_payments( array $args_input ): ?array {
		global $racketmanager;
		$defaults = array(
			'status' => array(),
			'player' => false,
		);
		$args_input = array_merge( $defaults, $args_input );
		$status     = $args_input['status'];
		$player     = $args_input['player'];
		if ( ! empty( $this->charge ) ) {
			$args['charge'] = $this->charge->id;
			$args['player'] = $player;
			$args['status'] = $status;
			$payments = $racketmanager->get_invoices( $args );
		} else {
			$payments = null;
		}
		return $payments;
	}
	/**
	 * Function to set team ratings for tournament
	 */
	public function calculate_player_team_ratings(): void {
		$events      = $this->get_events();
		foreach( $events as $event ) {
			$type  = substr( $event->type, 1, 1 );
			$teams = $event->get_teams();
			foreach( $teams as $team ) {
				$team_rating = 0;
				if ( ! empty( $team->players ) ) {
					foreach( $team->players as $player ) {
						$rating = empty( $player->wtn[ $type ] ) ? 40.9 : floatval( $player->wtn[ $type ] );
                        $team_rating += $rating;
                    }
					$league_team = get_league_team( $team->table_id );
					$league_team?->set_rating($team_rating);
				}
			}
		}
	}
	/**
	 * Contact Competition Teams
	 *
	 * @param string $email_message message.
	 * @param bool   $active active only indicator.
	 *
	 * @return boolean
	 */
	public function contact_teams( string $email_message, bool $active = false ): bool {
		global $racketmanager;
		$email_message = str_replace( '\"', '"', $email_message );
		$headers       = array();
		$email_from    = $racketmanager->get_confirmation_email( $this->competition->type );
		$headers[]     = 'From: ' . ucfirst( $this->competition->type ) . ' Secretary <' . $email_from . '>';
		$headers[]     = 'cc: ' . ucfirst( $this->competition->type ) . ' Secretary <' . $email_from . '>';
		$email_subject = $racketmanager->site_name . ' - ' . $this->name . ' - Important Message';
		$email_to      = array();
		$players       = $this->get_players( array( 'active' => $active ) );
		foreach ( $players as $player_name ) {
			$player = get_player( $player_name, 'name' );
			if ( $player && ! empty( $player->email ) ) {
				$headers[] = 'bcc: ' . $player->display_name . ' <' . $player->email . '>';
			}
		}
		wp_mail( $email_to, $email_subject, $email_message, $headers );
		$racketmanager->set_message( __( 'Message sent', 'racketmanager' ) );
		return true;
	}
}
