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
	public $closing_date;
	/**
	 * Closing Date display
	 *
	 * @var string
	 */
	public $closing_date_display;
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
	 * Is tournament open for enties
	 *
	 * @var boolean
	 */
	public $open;
	/**
	 * Is tournament active
	 *
	 * @var boolean
	 */
	public $active;
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
	 * Link variable
	 *
	 * @var string
	 */
	public $link;
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
				$search_terms = explode( ' ', $tournament_id );
				$type         = $search_terms[0];
				$season       = $search_terms[1];
				$search       = $wpdb->prepare(
					'`type` = %s AND `season` = %s',
					$type,
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
					"SELECT `id`, `name`, `competition_id`, `season`, `venue`, DATE_FORMAT(`date`, '%%Y-%%m-%%d') AS date, DATE_FORMAT(`closingdate`, '%%Y-%%m-%%d') AS closing_date, `date_start`, `date_open`,`numcourts` AS `num_courts`, `starttime`, `timeincrement` AS `time_increment`, `orderofplay` FROM {$wpdb->racketmanager_tournaments} WHERE $search"
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
		global $racketmanager;
		if ( ! is_null( $tournament ) ) {
			foreach ( $tournament as $key => $value ) {
				$this->$key = $value;
			}

			if ( ! isset( $this->id ) ) {
				$this->id = $this->add();
			}
			$this->link                 = '/tournament/' . seo_url( $this->name ) . '/';
			$this->date_display         = ( substr( $this->date, 0, 10 ) === '0000-00-00' ) ? 'TBC' : mysql2date( $racketmanager->date_format, $this->date );
			$this->closing_date_display = ( substr( $this->closing_date, 0, 10 ) === '0000-00-00' ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->closing_date );
			$this->date_open_display    = empty( $this->date_open ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date_open );
			$this->date_start_display   = empty( $this->date_start ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date_start );
			$today                      = gmdate( 'Y-m-d' );
			$this->current_phase        = 'complete';
			if ( $today > $this->date ) {
				$this->current_phase = 'end';
			} else {
				$this->current_phase = '';
				if ( ! empty( $this->date_start ) && $today > $this->date_start ) {
					$this->current_phase = 'start';
				} elseif ( ! empty( $this->closing_date ) && $today > $this->closing_date ) {
					$this->current_phase = 'close';
				} elseif ( ! empty( $this->date_open ) && $today > $this->date_open ) {
					$this->current_phase = 'open';
				}
			}
			if ( empty( $this->venue ) ) {
				$this->venue      = '';
				$this->venue_name = 'TBC';
			} else {
				$this->venue_name = get_club( $tournament->venue )->name;
			}

			if ( isset( $this->closing_date ) && $this->closing_date >= gmdate( 'Y-m-d' ) ) {
				$this->open = true;
			} else {
				$this->open = false;
			}
			if ( isset( $this->date ) && $this->date >= gmdate( 'Y-m-d' ) ) {
				$this->active = true;
			} else {
				$this->active = false;
			}
			$this->orderofplay = (array) maybe_unserialize( $this->orderofplay );
			if ( $this->competition_id ) {
				$this->competition = get_competition( $this->competition_id );
			}
		}
	}

	/**
	 * Add tournament
	 */
	private function add() {
		global $wpdb, $racketmanager;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO {$wpdb->racketmanager_tournaments} (`name`, `competition_id`, `season`, `venue`, `date_open`, `closingdate`, `date_start`, `date`, `starttime` ) VALUES (%s, %s, %d, %d, %s, %s, %s, %s, %s )",
				$this->name,
				$this->competition_id,
				$this->season,
				$this->venue,
				$this->date_open,
				$this->closing_date,
				$this->date_start,
				$this->date,
				$this->starttime
			)
		);
		$racketmanager->set_message( __( 'Tournament added', 'racketmanager' ) );
		$this->id          = $wpdb->insert_id;
		$this->orderofplay = '';
		return $this->id;
	}

	/**
	 * Update tournament
	 *
	 * @param object $updated updated tournament values.
	 * @return boolean
	 */
	public function update( $updated ) {
		global $wpdb, $racketmanager;
		$valid = true;
		if ( ! empty( $updated->date_open ) ) {
			if ( ! empty( $updated->closing_date ) ) {
				if ( $updated->closing_date <= $updated->date_open ) {
					$valid = false;
					$racketmanager->set_message( __( 'Closing date not after open date', 'racketmanager' ), true );
				} elseif ( ! empty( $updated->date_start ) ) {
					if ( $updated->date_start < $updated->closing_date ) {
						$valid = false;
						$racketmanager->set_message( __( 'Start date not after closing date', 'racketmanager' ), true );
					} elseif ( ! empty( $updated->date ) ) {
						if ( $updated->date <= $updated->closing_date ) {
							$valid = false;
							$racketmanager->set_message( __( 'End date not after start date', 'racketmanager' ), true );
						}
					} else {
						$valid = false;
						$racketmanager->set_message( __( 'End date not set', 'racketmanager' ), true );
					}
				} else {
					$valid = false;
					$racketmanager->set_message( __( 'Start date not set', 'racketmanager' ), true );
				}
			} else {
				$valid = false;
				$racketmanager->set_message( __( 'Closing date not set', 'racketmanager' ), true );
			}
		} else {
			$valid = false;
			$racketmanager->set_message( __( 'Open date not set', 'racketmanager' ), true );
		}
		if ( $valid ) {
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_tournaments} SET `name` = %s, `competition_id` = %d, `season` = %s, `venue` = %d, `date_open` = %s, `closingdate` = %s, `date_start` = %s, `date` = %s, `starttime` = %s WHERE `id` = %d",
					$updated->name,
					$updated->competition_id,
					$updated->season,
					$updated->venue,
					$updated->date_open,
					$updated->closing_date,
					$updated->date_start,
					$updated->date,
					$updated->starttime,
					$this->id
				)
			);
			$racketmanager->set_message( __( 'Tournament updated', 'racketmanager' ) );
			return true;
		} else {
			return false;
		}
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
			wp_cache_flush_group( 'tournaments' );
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_tournaments} SET `starttime` = %s, `numcourts` = %d, `timeincrement` = %s WHERE `id` = %d",
					$starttime,
					$num_courts,
					$time_increment,
					$this->id
				)
			);
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
			$orderofplay = maybe_serialize( $orderofplay );
			wp_cache_flush_group( 'tournaments' );
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_tournaments} SET `orderofplay` = %s WHERE `id` = %d",
					$orderofplay,
					$this->id
				)
			);
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
			$orderofplay = maybe_serialize( $orderofplay );
			wp_cache_flush_group( 'tournaments' );
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_tournaments} SET `orderofplay` = %s WHERE `id` = %d",
					$orderofplay,
					$this->id
				)
			);
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

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_tournaments} WHERE `id` = %d",
				$this->id
			)
		);
		$racketmanager->set_message( __( 'Tournament Deleted', 'racketmanager' ) );
		wp_cache_flush_group( 'tournaments' );
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
				$event        = get_event( $event );
				$event->teams = $event->get_teams(
					array(
						'season' => $this->season,
					)
				);
				if ( $event->teams ) {
					$event_teams = array();
					foreach ( $event->teams as $team ) {
						foreach ( $team->player as $player ) {
							$players[] = $player;
						}
						$event_team                 = new \stdClass();
						$event_team->player         = $team->player;
						$event_team->player_id      = $team->player_id;
						$event_team->title          = $team->name;
						$event_teams[ $team->name ] = $event_team;
					}
					$event->teams = array_unique( $event_teams, SORT_REGULAR );
				}
				$event->players = array_unique( $players );
				$this->events[] = $event;
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
		$defaults = array(
			'count' => false,
		);
		$args     = array_merge( $defaults, $args );
		$count    = $args['count'];
		if ( empty( $this->players ) ) {
			$this->get_events();
			foreach ( $this->events as $event ) {
				$this->players = array_merge( $this->players, $event->players );
			}
			$this->players = array_unique( $this->players );
			asort( $this->players );
		}
		if ( $count ) {
			return count( $this->players );
		} else {
			return $this->players;
		}
	}
}
