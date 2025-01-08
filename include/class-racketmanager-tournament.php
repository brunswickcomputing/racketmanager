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
			$this->link                 = '/tournament/' . seo_url( $this->name ) . '/';
			$this->date_display         = ( substr( $this->date, 0, 10 ) === '0000-00-00' ) ? 'TBC' : mysql2date( $racketmanager->date_format, $this->date );
			$this->date_closing_display = ( substr( $this->date_closing, 0, 10 ) === '0000-00-00' ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date_closing );
			$this->date_open_display    = empty( $this->date_open ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date_open );
			$this->date_start_display   = empty( $this->date_start ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date_start );
			$today                      = gmdate( 'Y-m-d' );
			$this->current_phase        = 'complete';
			if ( $today > $this->date ) {
				$this->current_phase = 'end';
				$this->is_complete   = true;
			} else {
				$this->current_phase = '';
				if ( ! empty( $this->date_start ) && $today >= $this->date_start ) {
					$this->current_phase = 'start';
					$this->is_started    = true;
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
			$err_fld[] = 'date_open';
		}
		if ( empty( $tournament->date_closing ) ) {
			$valid     = false;
			$err_msg[] = __( 'Closing date is required', 'racketmanager' );
			$err_fld[] = 'date_close';
		} elseif ( ! empty( $tournament->date_open ) && $tournament->date_closing <= $tournament->date_open ) {
			$valid     = false;
			$err_msg[] = __( 'Closing date must be after open date', 'racketmanager' );
			$err_fld[] = 'date_close';
		}
		if ( empty( $tournament->date_withdrawal ) ) {
			$valid     = false;
			$err_msg[] = __( 'Withdrawal date is required', 'racketmanager' );
			$err_fld[] = 'date_withdraw';
		} elseif ( ! empty( $tournament->date_closing ) && $tournament->date_withdrawal <= $tournament->date_closing ) {
			$valid     = false;
			$err_msg[] = __( 'Withdrawal date must be after closing date', 'racketmanager' );
			$err_fld[] = 'date_close';
		}
		if ( empty( $tournament->date_start ) ) {
			$valid     = false;
			$err_msg[] = __( 'Start date is required', 'racketmanager' );
			$err_fld[] = 'date_start';
		} elseif ( ! empty( $tournament->date_withdrawal ) && $tournament->date_start <= $tournament->date_withdrawal ) {
			$valid     = false;
			$err_msg[] = __( 'Start date must be after withdrawal date', 'racketmanager' );
			$err_fld[] = 'date_start';
		}
		if ( empty( $tournament->date ) ) {
			$valid     = false;
			$err_msg[] = __( 'End date is required', 'racketmanager' );
			$err_fld[] = 'date_end';
		} elseif ( ! empty( $tournament->date_start ) && $tournament->date <= $tournament->date_start ) {
			$valid     = false;
			$err_msg[] = __( 'End date must be after start date', 'racketmanager' );
			$err_fld[] = 'date_end';
		}
		if ( $valid ) {
			$return->valid = true;
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
			$racketmanager->set_message( __( 'No updates', 'racketmanager' ) );
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
			$racketmanager->set_message( __( 'No updates', 'racketmanager' ) );
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
			$racketmanager->set_message( __( 'No updates', 'racketmanager' ) );
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
		);
		$args     = array_merge( $defaults, $args );
		$orderby  = $args['orderby'];
		$count    = $args['count'];

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
			$sql = 'SELECT `player_id`, `status`';
		}
		$sql          .= " FROM {$wpdb->racketmanager_tournament_entries} WHERE `tournament_id` = %d";
		$search_terms  = array();
		$search_args   = array();
		$search_args[] = $this->id;
		if ( $status ) {
			if ( 'pending' === $status ) {
				$search_terms[] = '`status` = 0';
			} elseif ( 'confirmed' === $status ) {
				$search_terms[] = '`status` = 1';
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
				$player->status           = $tournament_entry->status;
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
		if ( empty( $this->date_open ) ) {
			$day            = intval( gmdate( 'd' ) );
			$month          = intval( gmdate( 'm' ) );
			$year           = intval( gmdate( 'Y' ) );
			$hour           = intval( gmdate( 'H' ) );
			$schedule_start = mktime( $hour, 0, 0, $month, $day, $year );
		} else {
			$schedule_date  = strtotime( $this->date_open . ' -1 day' );
			$day            = intval( gmdate( 'd', $schedule_date ) );
			$month          = intval( gmdate( 'm', $schedule_date ) );
			$year           = intval( gmdate( 'Y', $schedule_date ) );
			$schedule_start = mktime( 12, 0, 0, $month, $day, $year );
		}
		$schedule_name = 'rm_calculate_tournament_ratings';
		$schedule_args = array( intval( $this->id ) );
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
			$schedule_date  = strtotime( $this->date_open );
			$day            = intval( gmdate( 'd', $schedule_date ) );
			$month          = intval( gmdate( 'm', $schedule_date ) );
			$year           = intval( gmdate( 'Y', $schedule_date ) );
			$schedule_start = mktime( 00, 00, 01, $month, $day, $year );
		}
		$schedule_name   = 'rm_notify_tournament_entry_open';
		$schedule_args[] = intval( $this->id );
		Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
		$success = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
		if ( ! $success ) {
			$racketmanager->set_message( __( 'Error scheduling tournament open emails', 'racketmanager' ), true );
		} elseif ( ! empty( $this->date_closing ) ) {
			$chase_date     = strtotime( $this->date_closing . ' -7 day' );
			$day            = intval( gmdate( 'd', $chase_date ) );
			$month          = intval( gmdate( 'm', $chase_date ) );
			$year           = intval( gmdate( 'Y', $chase_date ) );
			$schedule_start = mktime( 00, 00, 01, $month, $day, $year );
			$schedule_name  = 'rm_notify_tournament_entry_reminder';
			Racketmanager_Util::clear_scheduled_event( $schedule_name, $schedule_args );
			$success = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
			if ( ! $success ) {
				$racketmanager->set_message( __( 'Error scheduling tournament reminder emails', 'racketmanager' ), true );
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
		$limit   = 1;
		$sql     = $wpdb->prepare(
			"SELECT DISTINCT(te.player_id) FROM {$wpdb->racketmanager_tournament_entries} te, {$wpdb->racketmanager_tournaments} t INNER JOIN (SELECT `id` FROM {$wpdb->racketmanager_tournaments} WHERE `competition_id` = %d AND `id` != %d ORDER BY `id` DESC LIMIT %d) t1 ON t.`id` = t1.`id` WHERE te.`tournament_id` = t.`id` AND t.`competition_id` = %d AND te.`player_id` IN (SELECT DISTINCT `player_id` FROM {$wpdb->racketmanager_club_players} WHERE `removed_date` IS NULL) AND te.`player_id` NOT IN (SELECT `player_id` FROM {$wpdb->racketmanager_tournament_entries} WHERE `tournament_id` = %d)",
			$this->competition_id,
			$this->id,
			$limit,
			$this->competition_id,
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
}
