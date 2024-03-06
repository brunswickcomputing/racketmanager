<?php
/**
 * RacketManager-Match API: RacketManager-match class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Match
 */

namespace Racketmanager;

use stdClass;

/**
 * Class to implement the Racketmanager_Match object
 */
final class Racketmanager_Match {

	/**
	 * Final round indicator
	 *
	 * @var string
	 */
	public $final_round = '';
	/**
	 * Id
	 *
	 * @var int
	 */
	public $id;
	/**
	 * Group
	 *
	 * @var string
	 */
	public $group;
	/**
	 * Date
	 *
	 * @var string
	 */
	public $date;
	/**
	 * Home team
	 *
	 * @var string
	 */
	public $home_team;
	/**
	 * Away team
	 *
	 * @var string
	 */
	public $away_team;
	/**
	 * Match day
	 *
	 * @var int
	 */
	public $match_day;
	/**
	 * Location
	 *
	 * @var string
	 */
	public $location;
	/**
	 * League  id
	 *
	 * @var int
	 */
	public $league_id;
	/**
	 * Season
	 *
	 * @var string
	 */
	public $season;
	/**
	 * Home points
	 *
	 * @var float
	 */
	public $home_points;
	/**
	 * Away points
	 *
	 * @var float
	 */
	public $away_points;
	/**
	 * Winning team id
	 *
	 * @var int
	 */
	public $winner_id;
	/**
	 * Losing team id
	 *
	 * @var int
	 */
	public $loser_id;
	/**
	 * Post id for match report
	 *
	 * @var int
	 */
	public $post_id;
	/**
	 * Rounf for championship
	 *
	 * @var string
	 */
	public $final;
	/**
	 * Custom
	 *
	 * @var array
	 */
	public $custom;
	/**
	 * Match confirmed value
	 *
	 * @var string
	 */
	public $confirmed;
	/**
	 * Home team score
	 *
	 * @var float
	 */
	public $home_score;
	/**
	 * Away team score
	 *
	 * @var float
	 */
	public $away_score;
	/**
	 * Match score
	 *
	 * @var string
	 */
	public $score;
	/**
	 * Match set score
	 *
	 * @var string
	 */
	public $set_score;
	/**
	 * Previous round set score for home team
	 *
	 * @var string
	 */
	public $home_prev_round_set_score;
	/**
	 * Previous round set score for away team
	 *
	 * @var string
	 */
	public $away_prev_round_set_score;
	/**
	 * Confirmed status display value
	 *
	 * @var int
	 */
	public $confirmed_display;
	/**
	 * Page url
	 *
	 * @var string
	 */
	public $page_url;
	/**
	 * Home flag
	 *
	 * @var boolean
	 */
	public $is_home;
	/**
	 * Selected flag
	 *
	 * @var boolean
	 */
	public $is_selected;
	/**
	 * Match title
	 *
	 * @var string
	 */
	public $match_title;
	/**
	 * Teams array
	 *
	 * @var array
	 */
	public $teams;
	/**
	 * Match title
	 *
	 * @var string
	 */
	public $title;
	/**
	 * Match date
	 *
	 * @var string
	 */
	public $match_date;
	/**
	 * Match start time
	 *
	 * @var string
	 */
	public $start_time;
	/**
	 * Match start hour
	 *
	 * @var string
	 */
	public $hour;
	/**
	 * Match start minutes
	 *
	 * @var string
	 */
	public $minutes;
	/**
	 * Tooltip title
	 *
	 * @var string
	 */
	public $tooltip_title;
	/**
	 * Match report
	 *
	 * @var string
	 */
	public $report;
	/**
	 * League object
	 *
	 * @var object
	 */
	public $league;
	/**
	 * Is walkover variable
	 *
	 * @var boolean
	 */
	public $is_walkover = false;
	/**
	 * Is retired variable
	 *
	 * @var boolean
	 */
	public $is_retired = false;
	/**
	 * Sets variable
	 *
	 * @var array
	 */
	public $sets = false;
	/**
	 * Round variable
	 *
	 * @var int
	 */
	public $round;
	/**
	 * Comments variable
	 *
	 * @var array
	 */
	public $comments;
	/**
	 * Leg variable
	 *
	 * @var int
	 */
	public $leg;
	/**
	 * Status variable
	 *
	 * @var int
	 */
	public $status;
	/**
	 * Linked match variable
	 *
	 * @var int
	 */
	public $linked_match;
	/**
	 * Host variable
	 *
	 * @var string
	 */
	public $host;
	/**
	 * Match Link variable
	 *
	 * @var string
	 */
	public $link;
	/**
	 * Number of rubbers variable
	 *
	 * @var int
	 */
	public $num_rubbers;
	/**
	 * Home points for match tie variable
	 *
	 * @var float
	 */
	public $home_points_tie;
	/**
	 * Away points for match tie variable
	 *
	 * @var float
	 */
	public $away_points_tie;
	/**
	 * Winner id for match tie variable
	 *
	 * @var float
	 */
	public $winner_id_tie;
	/**
	 * Loser id for match tie variable
	 *
	 * @var float
	 */
	public $loser_id_tie;
	/**
	 * Retrieve match instance
	 *
	 * @param int $match_id match id.
	 */
	public static function get_instance( $match_id ) {
		global $wpdb;

		$match_id = (int) $match_id;
		if ( ! $match_id ) {
			return false;
		}

		$match = wp_cache_get( $match_id, 'matches' );
		if ( ! $match ) {
			$match = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT `final` AS final_round, `group`, `home_team`, `away_team`, DATE_FORMAT(`date`, '%%Y-%%m-%%d %%H:%%i') AS date, DATE_FORMAT(`date`, '%%e') AS day, DATE_FORMAT(`date`, '%%c') AS month, DATE_FORMAT(`date`, '%%Y') AS year, DATE_FORMAT(`date`, '%%H') AS `hour`, DATE_FORMAT(`date`, '%%i') AS `minutes`, `match_day`, `location`, `league_id`, `home_points`, `away_points`, `winner_id`, `loser_id`, `post_id`, `season`, `id`, `custom`, `updated`, `updated_user`, `confirmed`, `home_captain`, `away_captain`, `comments`, `status`, `host`, `linked_match`, `leg`, `winner_id_tie`, `loser_id_tie`, `home_points_tie`, `away_points_tie` FROM {$wpdb->racketmanager_matches} WHERE `id` = %d LIMIT 1",
					$match_id
				)
			);

			if ( ! $match ) {
				return false;
			}
			$match = new Racketmanager_Match( $match );

			wp_cache_set( $match->id, $match, 'matches' );
		}

		return $match;
	}

	/**
	 * Constructor
	 *
	 * @param object $match Racketmanager_Match object.
	 */
	public function __construct( $match = null ) {
		global $wp;
		if ( ! is_null( $match ) ) {
			if ( isset( $match->custom ) ) {
				$match->custom = stripslashes_deep( (array) maybe_unserialize( $match->custom ) );
				$match         = (object) array_merge( (array) $match, (array) $match->custom );
			}

			foreach ( get_object_vars( $match ) as $key => $value ) {
				$this->$key = $value;
			}
			$wp->set_query_var( 'season', $this->season );

			// get League Object.
			$this->league = get_league();
			if ( is_null( $this->league ) || ( ! is_null( $this->league ) && $this->league->id !== $this->league_id ) ) {
				$this->league = get_league( $this->league_id );
			}
			if ( ! isset( $this->id ) ) {
				$this->id = $this->add();
			}
			if ( empty( $this->league->num_rubbers ) ) {
				$this->num_rubbers = 0;
			} else {
				$this->num_rubbers = $this->get_rubbers( false, true );
			}
			$this->location    = '' !== $this->location ? stripslashes( $this->location ) : '';
			$this->report      = ( $this->post_id ) ? '<a href="' . get_permalink( $this->post_id ) . '">' . __( 'Report', 'racketmanager' ) . '</a>' : '';
			$this->sets        = ! empty( $match->custom['sets'] ) ? $match->custom['sets'] : array();
			$this->is_walkover = false;
			if ( null !== $this->home_points && null !== $this->away_points ) {
				$this->home_score = $this->home_points;
				$this->away_score = $this->away_points;
				$this->score      = sprintf( '%g - %g', $this->home_score, $this->away_score );
				if ( ! empty( $this->league->num_rubbers ) ) {
					if ( -1 === intval( $this->home_team ) || -1 === intval( $this->away_team ) ) {
						$this->is_walkover = true;
						$set_score         = __( 'Walkover', 'racketmanager' );
					} else {
						$set_score = $this->score;
					}
					$this->set_score = $set_score;
				} else {
					$set_score  = '';
					$this->sets = ! empty( $match->custom['sets'] ) ? $match->custom['sets'] : array();
					$s          = 1;
					foreach ( $this->sets as $set ) {
						if ( isset( $set['player1'] ) && '' !== $set['player1'] && isset( $set['player2'] ) && '' !== $set['player2'] ) {
							$set_score .= $set['player1'] . '-' . $set['player2'] . ' ';
							if ( $set['player1'] > $set['player2'] ) {
								$set['winner'] = 'player1';
							} elseif ( $set['player1'] < $set['player2'] ) {
								$set['winner'] = 'player2';
							}
						}
						$this->sets[ $s ] = $set;
						++$s;
					}
					$this->custom['sets'] = $this->sets;
					if ( '' === $set_score || ! empty( $this->custom['walkover'] ) ) {
						$this->is_walkover = true;
						$set_score         = __( 'Walkover', 'racketmanager' );
					}
					if ( ! empty( $this->custom['retired'] ) ) {
						$this->is_retired = true;
					}
					$this->set_score = $set_score;
				}
			} else {
				$this->home_score = '';
				$this->away_score = '';
				$this->score      = '';
				$set_score        = '';
				if ( $this->winner_id ) {
					if ( '-1' === $this->home_team || '-1' === $this->away_team ) {
						$set_score = $this->score;
					} else {
						$this->is_walkover = true;
						$set_score         = __( 'Walkover', 'racketmanager' );
					}
				}
				$this->set_score = $set_score;
			}
			if ( 'Y' === $this->confirmed ) {
				$this->confirmed_display = __( 'Complete', 'racketmanager' );
			} elseif ( 'A' === $this->confirmed ) {
				$this->confirmed_display = __( 'Approved', 'racketmanager' );
			} elseif ( 'C' === $this->confirmed ) {
				$this->confirmed_display = __( 'Challenged', 'racketmanager' );
			} elseif ( 'P' === $this->confirmed ) {
				$this->confirmed_display = __( 'Pending', 'racketmanager' );
			} else {
				$this->confirmed_display = $this->confirmed;
			}
			if ( is_admin() ) {
				$url = '';
			} else {
				$url = esc_url( get_permalink() );
				$url = add_query_arg( 'match_' . $this->league_id, $this->id, $url );
				foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$url = add_query_arg( $key, htmlspecialchars( wp_strip_all_tags( $value ) ), $url );
				}
				$url = remove_query_arg( 'team_' . $this->league_id, $url );
			}
			$this->page_url = esc_url( $url );
			$this->set_teams_details();
			$this->match_title = $this->get_title();
			$this->set_date();
			$this->set_time();
			// set selected marker.
			if ( isset( $_GET[ 'match_' . $this->league_id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$this->is_selected = true;
			}
			$this->comments = maybe_unserialize( $this->comments );
			if ( ! is_array( $this->comments ) ) {
				$comments       = $this->comments;
				$this->comments = array();
				$away_comment   = strpos( $comments, __( 'Away:', 'racketmanager' ) );
				if ( $away_comment ) {
					$away_comment           = substr( $comments, $away_comment + 5 );
					$this->comments['away'] = $away_comment;
				} else {
					$this->comments['away'] = '';
				}
				$home_comment = strpos( $comments, __( 'Home:', 'racketmanager' ) );
				if ( $home_comment ) {
					$home_comment           = substr( $comments, $home_comment + 5 );
					$this->comments['home'] = $home_comment;
				} else {
					$this->comments['home'] = '';
				}
				$this->comments['result'] = $comments;
			} elseif ( ! isset( $this->comments['result'] ) ) {
				$this->comments['result'] = '';
			}
			if ( $this->league->is_championship ) {
				$match_ref = $this->final_round;
			} else {
				$match_ref = 'day' . $this->match_day;
			}
			$this->link = '/match/' . seo_url( $this->league->title ) . '/' . $this->season . '/' . $match_ref . '/' . seo_url( $this->teams['home']->title ) . '-vs-' . seo_url( $this->teams['away']->title ) . '/';
		}
	}

	/**
	 * Add match
	 */
	public function add() {
		global $wpdb;
		$max_rubbers = 0;
		if ( ! empty( $this->league->num_rubbers ) ) {
			$max_rubbers = $this->league->num_rubbers;
			if ( $this->league->is_championship && ! empty( $this->league->current_season['homeAway'] ) && ! empty( $this->leg ) && 2 === $this->leg && 'MPL' === $this->league->event->scoring ) {
				++$max_rubbers;
			}
		}
		$sql = $wpdb->prepare(
			"INSERT INTO {$wpdb->racketmanager_matches} (date, home_team, away_team, match_day, location, league_id, season, final, custom, `group`, `host`) VALUES (%s, %s, %s, %d, %s, %d, %s, %s, %s, %s, %s)",
			$this->date,
			$this->home_team,
			$this->away_team,
			$this->match_day,
			$this->location,
			$this->league_id,
			$this->season,
			$this->final_round,
			maybe_serialize( $this->custom ),
			$this->group,
			$this->host,
		);
		$sql = str_replace( "''", 'NULL', $sql );
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql,
		);
		$this->id = $wpdb->insert_id;
		if ( $this->league->num_rubbers ) {
			for ( $ix = 1; $ix <= $max_rubbers; $ix++ ) {
				$rubber = new stdClass();
				$type   = $this->league->type;
				if ( 'MD' === $this->league->type ) {
					$type = 'MD';
				} elseif ( 'WD' === $this->league->type ) {
					$type = 'WD';
				} elseif ( 'XD' === $this->league->type ) {
					$type = 'XD';
				} elseif ( 'LD' === $this->league->type ) {
					if ( 1 === $ix ) {
						$type = 'WD';
					} elseif ( 2 === $ix ) {
						$type = 'MD';
					} elseif ( 3 === $ix ) {
						$type = 'XD';
					}
				}
				$rubber->type          = $type;
				$rubber->rubber_number = $ix;
				$rubber->date          = $this->date;
				$rubber->match_id      = $this->id;
				$rubber                = new Racketmanager_rubber( $rubber );
			}
		}
		return $this->id;
	}
	/**
	 * Update leg and linked match function
	 *
	 * @param int $leg leg number.
	 * @param int $linked_match linked match id.
	 * @return void
	 */
	public function update_legs( $leg, $linked_match ) {
		global $wpdb;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_matches} SET `leg` = %d, `linked_match` = %d WHERE `id` = %d",
				$leg,
				$linked_match,
				$this->id,
			)
		);
	}
	/**
	 * Update match
	 */
	public function update() {
		global $wpdb;
		$update_count = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_matches} SET `date` = %s, `home_team` = %s, `away_team` = %s, `match_day` = %d, `location` = %s, `league_id` = %d, `group` = %s, `final` = %s, `custom` = %s WHERE `id` = %d",
				$this->date,
				$this->home_team,
				$this->away_team,
				$this->match_day,
				$this->location,
				$this->league_id,
				$this->group,
				$this->final_round,
				maybe_serialize( $this->custom ),
				$this->id
			)
		);
		wp_cache_delete( $this->id, 'matches' );
		if ( 0 === $update_count ) {
			$msg = __( 'No updates', 'racketmanager' );
		} else {
			$msg = __( 'Match updated', 'racketmanager' );
		}
		return $msg;
	}
	/**
	 * Update sets function
	 *
	 * @param array $sets array of sets.
	 */
	public function update_sets( $sets ) {
		global $wpdb;
		$this->custom['sets'] = $sets;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_matches} SET `custom` = %s WHERE `id` = %d",
				maybe_serialize( $this->custom ),
				$this->id
			)
		);
		$this->sets = $sets;
	}
	/**
	 * Delete match
	 */
	public function delete() {
		global $wpdb;
		$rubbers = $this->get_rubbers();
		foreach ( $rubbers as $rubber ) {
			$rubber->delete();
		}
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_matches} WHERE `id` = %d",
				$this->id
			)
		);
		return true;
	}

	/**
	 * Get Team objects
	 *
	 * @param string $team indicator for which team details to get.
	 */
	private function set_teams_details( $team = 'both' ) {
		// get championship final rounds teams.
		if ( $this->league->championship instanceof Racketmanager_Championship && $this->final_round ) {
			$teams = $this->league->championship->get_final_teams( $this->final_round );
		}
		if ( 'both' === $team || 'home' === $team ) {
			if ( is_numeric( $this->home_team ) ) {
				if ( '-1' === $this->home_team ) {
					$this->teams['home'] = (object) array(
						'id'     => -1,
						'title'  => 'Bye',
						'player' => array(),
					);
				} else {
					$this->teams['home'] = $this->league->get_team_dtls( $this->home_team );
					if ( $this->league->is_championship && is_object( $this->teams['home'] ) ) {
						$this->teams['home']->rank = $this->league->get_rank( $this->home_team, $this->season );
					}
				}
			} else {
				$this->teams['home'] = $teams[ $this->home_team ];
			}
		}
		if ( 'both' === $team || 'away' === $team ) {
			if ( is_numeric( $this->away_team ) ) {
				if ( '-1' === $this->away_team ) {
					$this->teams['away'] = (object) array(
						'id'     => -1,
						'title'  => 'Bye',
						'player' => array(),
					);
				} else {
					$this->teams['away'] = $this->league->get_team_dtls( $this->away_team );
					if ( $this->league->is_championship && is_object( $this->teams['away'] ) ) {
						$this->teams['away']->rank = $this->league->get_rank( $this->away_team, $this->season );
					}
				}
			} else {
				$this->teams['away'] = $teams[ $this->away_team ];
			}
		}
	}

	/**
	 * Get match title
	 *
	 * @return string
	 */
	public function get_title() {
		$home_team = $this->teams['home'];
		$away_team = $this->teams['away'];

		if ( isset( $this->title ) && ( ! $home_team || ! $away_team || $this->home_team === $this->away_team ) ) {
			$title = stripslashes( $this->title );
		} else {
			$home_team_name = $this->is_home ? '<strong>' . $home_team->title . '</strong>' : $home_team->title;
			$away_team_name = $this->is_home ? '<strong>' . $away_team->title . '</strong>' : $away_team->title;

			$title = sprintf( '%s - %s', $home_team_name, $away_team_name );
		}

		return $title;
	}

	/**
	 * Set match date
	 *
	 * @param string $date_format date format.
	 */
	public function set_date( $date_format = '' ) {
		global $racketmanager;
		if ( '' === $date_format ) {
			$date_format = $racketmanager->date_format;
		}
		$this->match_date = ( substr( $this->date, 0, 10 ) === '0000-00-00' ) ? 'N/A' : mysql2date( $date_format, $this->date );
		$this->set_tooltip_title();
	}

	/**
	 * Set match start time
	 *
	 * @param string $time_format time format.
	 */
	public function set_time( $time_format = '' ) {
		global $racketmanager;
		if ( '' === $time_format ) {
			$time_format = $racketmanager->time_format;
		}
		$this->start_time = mysql2date( $time_format, $this->date );
		if ( '00:00' === $this->start_time ) {
			$this->start_time = '';
		}
	}

	/**
	 * Set tooltip title
	 */
	private function set_tooltip_title() {
		// make tooltip title for last-5 standings.
		if ( '' === $this->home_points && '' === $this->away_points ) {
			$tooltip_title = 'Next Match: ' . $this->teams['home']->title . ' - ' . $this->teams['away']->title . ' [' . $this->match_date . ']';
		} elseif ( isset( $this->title ) ) {
			$tooltip_title = stripslashes( $this->title ) . ' [' . $this->match_date . ']';
		} else {
			$tooltip_title = $this->score . ' - ' . $this->teams['home']->title . ' - ' . $this->teams['away']->title . ' [' . $this->match_date . ']';
		}
		$this->tooltip_title = $tooltip_title;
	}
	/**
	 * Set tie points for multi legged match function
	 *
	 * @param int $home_points_tie home poinst for tie.
	 * @param int $away_points_tie away points for tie.
	 * @return void
	 */
	public function update_result_tie( $home_points_tie = null, $away_points_tie = null ) {
		global $wpdb;
		$update = true;
		if ( '2' === $this->leg ) {
			if ( is_null( $home_points_tie ) ) {
				$home_points_tie = $this->home_points;
				$away_points_tie = $this->away_points;
				if ( ! empty( $this->linked_match ) ) {
					$linked_match = get_match( $this->linked_match );
					if ( $linked_match && ! empty( $linked_match->winner_id ) ) {
						$home_points_tie += $linked_match->home_points;
						$away_points_tie += $linked_match->away_points;
					} else {
						$update = false;
					}
				} else {
					$update = false;
				}
			}
		} else {
			$update = false;
		}
		if ( $update ) {
			if ( $home_points_tie > $away_points_tie ) {
				$winner_id_tie = $this->home_team;
				$loser_id_tie  = $this->away_team;
			} elseif ( $home_points_tie < $away_points_tie ) {
				$winner_id_tie = $this->away_team;
				$loser_id_tie  = $this->home_team;
			} else {
				$winner_id_tie = -1;
				$loser_id_tie  = -1;
			}
			$this->home_points_tie = $home_points_tie;
			$this->away_points_tie = $away_points_tie;
			$this->winner_id_tie   = $winner_id_tie;
			$this->loser_id_tie    = $loser_id_tie;
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_matches} SET `home_points_tie` = %f, `away_points_tie` = %f, `winner_id_tie` = %d, `loser_id_tie` = %d WHERE `id` = %d",
					$home_points_tie,
					$away_points_tie,
					$winner_id_tie,
					$loser_id_tie,
					$this->id
				)
			);
			wp_cache_delete( $this->id, 'matches' );
		}
	}
	/**
	 * Update result
	 *
	 * @param float  $home_points_input home points.
	 * @param float  $away_points_input away points.
	 * @param string $custom custom.
	 * @param string $confirmed match status field.
	 * @return boolean
	 */
	public function update_result( $home_points_input, $away_points_input, $custom, $confirmed = 'Y' ) {
		global $racketmanager;
		$bye            = false;
		$updated        = false;
		$home_win       = 0;
		$away_win       = 0;
		$winning_points = $this->league->num_sets_to_win;
		if ( empty( $home_points_input ) && '-1' === $this->home_team ) {
			$home_points_input = 0;
			$away_points_input = $winning_points;
			$bye               = true;
		}
		if ( empty( $away_points_input ) && '-1' === $this->away_team ) {
			$home_points_input = $winning_points;
			$away_points_input = 0;
			$bye               = true;
		}
		$home_win         = 0;
		$away_win         = 0;
		$draw             = 0;
		$shared           = 0;
		$home_points      = 0;
		$away_points      = 0;
		$home_walkover    = 0;
		$away_walkover    = 0;
		$walkover_penalty = 0;
		if ( ! empty( $this->num_rubbers ) ) {
			$rubbers = $this->get_rubbers();
			foreach ( $rubbers as $rubber ) {
				switch ( $rubber->status ) {
					case 1:
						if ( $this->home_team === $rubber->winner_id ) {
							++$away_walkover;
						} elseif ( $this->away_team === $rubber->winner_id ) {
							++$home_walkover;
						}
						break;
					case 3:
						++$shared;
						break;
					default:
						break;
				}
				if ( $this->home_team === $rubber->winner_id ) {
					++$home_win;
				}
				if ( $this->away_team === $rubber->winner_id ) {
					++$away_win;
				}
				if ( '-1' === $rubber->winner_id ) {
					++$draw;
				}
				if ( is_numeric( $rubber->home_points ) ) {
					$home_points += floatval( $rubber->home_points );
				}
				if ( is_numeric( $rubber->away_points ) ) {
					$away_points += floatval( $rubber->away_points );
				}
			}
			if ( 'league' === $this->league->event->competition->type ) {
				if ( $home_walkover === $this->num_rubbers || $away_walkover === $this->num_rubbers ) {
					if ( $home_walkover === $this->num_rubbers ) {
						$custom['walkover'] = 'home';
					} elseif ( $away_walkover === $this->num_rubbers ) {
						$custom['walkover'] = 'away';
					}
					$this->custom = array_merge( (array) $this->custom, (array) $custom );
					$this->status = 1;
				} elseif ( $shared === $this->num_rubbers ) {
					$this->status = 3;
				}
				$point_rule         = $this->league->get_point_rule();
				$rubber_win         = ! empty( $point_rule['rubber_win'] ) ? $point_rule['rubber_win'] : 0;
				$rubber_draw        = ! empty( $point_rule['rubber_draw'] ) ? $point_rule['rubber_draw'] : 0;
				$shared_match       = ! empty( $point_rule['shared_match'] ) ? $point_rule['shared_match'] : 0;
				$forwalkover_rubber = empty( $point_rule['forwalkover_rubber'] ) ? 0 : $point_rule['forwalkover_rubber'];
				$walkover_penalty   = empty( $point_rule['forwalkover_match'] ) ? 0 : $point_rule['forwalkover_match'];
				if ( ! empty( $point_rule['match_result'] ) && 'rubber_count' === $point_rule['match_result'] ) {
					if ( 1 === $this->status ) {
						$home_points = $home_win * $rubber_win - $forwalkover_rubber * $home_walkover - $walkover_penalty * $home_walkover;
						$away_points = $away_win * $rubber_win - $forwalkover_rubber * $away_walkover - $walkover_penalty * $away_walkover;
					} elseif ( 3 === $this->status ) {
						$home_points = $shared_match * $this->num_rubbers;
						$away_points = $shared_match * $this->num_rubbers;
					} else {
						$home_points = $home_win * $rubber_win + $draw * $rubber_draw - $forwalkover_rubber * $home_walkover;
						$away_points = $away_win * $rubber_win + $draw * $rubber_draw - $forwalkover_rubber * $away_walkover;
					}
				} else {
					$home_points -= $walkover_penalty * $home_walkover;
					$away_points -= $walkover_penalty * $away_walkover;
				}
			} else {
				$this->status = 0;
			}
		} else {
			if ( isset( $custom['sets'] ) ) {
				$this->sets = $custom['sets'];
			}
			foreach ( $this->sets as $set ) {
				if ( isset( $set['player1'] ) && isset( $set['player2'] ) ) {
					if ( $set['player1'] > $set['player2'] ) {
						++$home_points;
					} elseif ( $set['player1'] < $set['player2'] ) {
						++$away_points;
					}
				}
			}
		}
		if ( empty( $home_points ) && empty( $away_points ) ) {
			if ( ! empty( $home_points_input ) ) {
				$home_points = $home_points_input;
				if ( ! $bye ) {
					$custom['walkover'] = 'home';
					$this->is_walkover  = true;
				}
			}
			if ( ! empty( $away_points_input ) ) {
				$away_points = $away_points_input;
				if ( ! $bye ) {
					$custom['walkover'] = 'away';
					$this->is_walkover  = true;
				}
			}
		}
		if ( ! empty( $home_points ) || ! empty( $away_points ) ) {
			$prev_winner = $this->winner_id;
			$this->get_result( $home_points, $away_points );
			if ( $prev_winner !== $this->winner_id || floatval( $home_points ) !== $this->home_points || floatval( $away_points ) !== $this->away_points || $custom !== $this->custom || $confirmed !== $this->confirmed ) {
				$this->home_points = $home_points;
				$this->away_points = $away_points;
				$this->custom      = array_merge( (array) $this->custom, (array) $custom );
				$this->confirmed   = $confirmed;
				foreach ( $this->custom as $key => $value ) {
					$this->{$key} = $value;
				}
				$this->update_result_database();
				$updated = true;
				if ( ! empty( $this->leg ) && '2' === $this->leg ) {
					$this->update_result_tie();
				}
			}
		}
		return $updated;
	}
	/**
	 * Update result in database function
	 *
	 * @return void
	 */
	private function update_result_database() {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_matches} SET `home_points` = %f, `away_points` = %f, `winner_id` = %d, `loser_id` = %d, `custom` = %s, `updated_user` = %d, `updated` = now(), `confirmed` = %s WHERE `id` = %d",
				$this->home_points,
				$this->away_points,
				intval( $this->winner_id ),
				intval( $this->loser_id ),
				maybe_serialize( $this->custom ),
				get_current_user_id(),
				$this->confirmed,
				$this->id
			)
		);
		wp_cache_delete( $this->id, 'matches' );
	}
	/**
	 * Update match status
	 *
	 * @param string $match_confirmed match confirmed status.
	 * @param string $comments match comments.
	 * @param string $confirm_comments result confirm comments.
	 * @param string $user_team team of user.
	 * @param string $actioned_by actioned by team.
	 */
	public function update_match_status( $match_confirmed, $comments, $confirm_comments, $user_team, $actioned_by ) {
		global $wpdb;
		$userid = get_current_user_id();
		if ( ! empty( $actioned_by ) && 'home' === $actioned_by ) {
			$captain = 'home';
		} elseif ( ! empty( $actioned_by ) && 'away' === $actioned_by ) {
			$captain = 'away';
		} elseif ( 'home' === $user_team ) {
			$captain     = 'home';
			$actioned_by = 'home';
		} elseif ( 'away' === $user_team ) {
			$captain     = 'away';
			$actioned_by = 'away';
		} elseif ( 'both' === $user_team ) {
			$captain     = 'home';
			$actioned_by = 'home';
		} else {
			$captain = 'admin';
		}
		if ( 'P' === $match_confirmed ) {
			if ( 'home' === $actioned_by || 'away' === $actioned_by ) {
				$this->comments[ $actioned_by ] = $comments['result'];
			} else {
				$this->comments['result'] = $comments['result'];
			}
		} elseif ( 'A' === $match_confirmed || 'C' === $match_confirmed ) {
			if ( 'home' === $actioned_by || 'away' === $actioned_by ) {
				$this->comments[ $actioned_by ] = $confirm_comments;
			} else {
				$this->comments['result'] = $comments['result'];
			}
		}
		if ( 'home' === $captain ) { // Home captain.
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_matches} SET `updated_user` = %d, `updated` = now(), `confirmed` = %s, `home_captain` = %d, `comments` = %s WHERE `id` = %d",
					$userid,
					$match_confirmed,
					$userid,
					maybe_serialize( $this->comments ),
					$this->id
				)
			);
			return 'home';
		} elseif ( 'away' === $captain ) { // Away captain.
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_matches} SET `updated_user` = %d, `updated` = now(), `confirmed` = %s, `away_captain` = %d, `comments` = %s WHERE `id` = %d",
					$userid,
					$match_confirmed,
					$userid,
					maybe_serialize( $this->comments ),
					$this->id
				)
			);
			return 'away';
		} else {
			$match_confirmed = 'A'; // Admin user.
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_matches} SET `updated_user` = %d, `updated` = now(), `confirmed` = %s, `comments` =%s WHERE `id` = %d",
					get_current_user_id(),
					$match_confirmed,
					maybe_serialize( $this->comments ),
					$this->id
				)
			);
			return 'admin';
		}
	}

	/**
	 * Determine match result
	 *
	 * @param float $home_points home points.
	 * @param float $away_points away_points.
	 */
	public function get_result( $home_points, $away_points ) {
		$match = array();
		if ( ! empty( $this->custom['walkover'] ) || 1 === $this->status ) {
			if ( 'home' === $this->custom['walkover'] ) {
				$match['loser']  = $this->home_team;
				$match['winner'] = $this->away_team;
			} elseif ( 'away' === $this->custom['walkover'] ) {
				$match['loser']  = $this->away_team;
				$match['winner'] = $this->home_team;
			}
		} elseif ( ! empty( $this->custom['retired'] ) ) {
			if ( 'away' === $this->custom['retired'] ) {
				$match['winner'] = $this->home_team;
				$match['loser']  = $this->away_team;
			} elseif ( 'home' === $this->custom['retired'] ) {
				$match['winner'] = $this->away_team;
				$match['loser']  = $this->home_team;
			}
		} elseif ( $home_points > $away_points ) {
			$match['winner'] = $this->home_team;
			$match['loser']  = $this->away_team;
		} elseif ( '-1' === $this->home_team ) {
			$match['winner'] = $this->away_team;
			$match['loser']  = 0;
		} elseif ( '-1' === $this->away_team ) {
			$match['winner'] = $this->home_team;
			$match['loser']  = 0;
		} elseif ( $home_points < $away_points ) {
			$match['winner'] = $this->away_team;
			$match['loser']  = $this->home_team;
		} elseif ( 'NULL' === $home_points && 'NULL' === $away_points ) {
			$match['winner'] = 0;
			$match['loser']  = 0;
		} else {
			$match['winner'] = -1;
			$match['loser']  = -1;
		}
		$this->winner_id = $match['winner'];
		$this->loser_id  = $match['loser'];
	}
	/**
	 * Gets rubbers from database
	 *
	 * @param int     $player player_id (optional).
	 * @param boolean $count count number of rubbers.
	 * @return array
	 */
	public function get_rubbers( $player = false, $count = false ) {
		global $wpdb;

		if ( $count ) {
			$args[] = $this->id;
			$sql    = $wpdb->prepare(
				"SELECT count(*) FROM {$wpdb->racketmanager_rubbers} WHERE `match_id` = %d",
				$args
			);
			// Use WordPress cache for counting rubbers.
			$rubbers = wp_cache_get( md5( $sql ), 'num_rubbers' );
			if ( ! $rubbers ) {
				$rubbers = intval(
					$wpdb->get_var(
						// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						$sql
					)
				); // db call ok.
				wp_cache_set( md5( $sql ), $rubbers, 'num_rubbers' );
			}
			return $rubbers;
		}
		$sql_start = "SELECT r.`id` FROM {$wpdb->racketmanager_rubbers} r";
		$sql       = ' WHERE `match_id` = ' . $this->id;
		if ( $player ) {
			$sql_start .= ", {$wpdb->racketmanager_rubber_players} rp";
			$sql       .= " AND r.`id` = rp.`rubber_id` AND `player_id` = '$player'";
		}
		$sql  = $sql_start . $sql;
		$sql .= ' ORDER BY `date` ASC, `id` ASC';

		$rubbers = wp_cache_get( md5( $sql ), 'rubbers' );
		if ( ! $rubbers ) {
			$rubbers = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			);  // db call ok.
			wp_cache_set( md5( $sql ), $rubbers, 'rubbers' );
		}

		$class = '';
		foreach ( $rubbers as $i => $rubber ) {
			$rubber        = get_rubber( $rubber->id );
			$class         = ( 'alternate' === $class ) ? '' : 'alternate';
			$rubber->class = $class;
			$rubbers[ $i ] = $rubber;
		}

		return $rubbers;
	}

	/**
	 * Delete result checker entries for match
	 */
	public function delete_result_check() {
		global $wpdb;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_results_checker} WHERE `match_id` = %d",
				$this->id
			)
		);
	}

	/**
	 * Add entry to results checker for errors on match result
	 *
	 * @param int    $team team.
	 * @param int    $player player.
	 * @param string $error error.
	 */
	public function add_result_check( $team, $player, $error ) {
		global $wpdb;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO {$wpdb->racketmanager_results_checker} (`league_id`, `match_id`, `team_id`, `player_id`, `description`) values ( %d, %d, %d, %d, %s) ",
				$this->league_id,
				$this->id,
				$team,
				$player,
				$error
			)
		);
	}

	/**
	 * Are there result checker entries for match
	 */
	public function has_result_check() {
		global $wpdb;
		return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"select count(*) FROM {$wpdb->racketmanager_results_checker} WHERE `match_id` = %d",
				$this->id
			)
		);
	}
	/**
	 * Set home / away team function
	 *
	 * @param int $home home team id.
	 * @param int $away away team id.
	 * @return void
	 */
	public function set_teams( $home, $away ) {
		global $wpdb;
		if ( empty( $home ) ) {
			$home = $this->home_team;
		} else {
			$this->home_team = $home;
			$this->set_teams_details( 'home' );
		}
		if ( empty( $away ) ) {
			$away = $this->away_team;
		} else {
			$this->away_team = $away;
			$this->set_teams_details( 'away' );
		}
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_matches} SET `home_team` = %s, `away_team` = %s WHERE `id` = %d",
				$home,
				$away,
				$this->id
			)
		);
		wp_cache_delete( $this->id, 'matches' );
		if ( isset( $this->host ) && is_numeric( $this->home_team ) && is_numeric( $this->away_team ) ) {
			$this->set_date_and_location();
		}
	}
	/**
	 * Set date and location function
	 *
	 * @return void
	 */
	public function set_date_and_location() {
		if ( 'home' === $this->host ) {
			if ( is_numeric( $this->home_team ) && '-1' !== $this->home_team ) {
				$this_day  = isset( $this->teams['home']->match_day ) ? $this->teams['home']->match_day : null;
				$this_time = isset( $this->teams['home']->match_time ) ? $this->teams['home']->match_time : null;
				$this->set_match_date( $this->match_date, $this_day, $this_time );
				$location = isset( $this->teams['home']->club->shortcode ) ? $this->teams['home']->club->shortcode : null;
				if ( $location ) {
					$this->set_location( $location );
				}
			}
		} elseif ( 'away' === $this->host ) {
			if ( is_numeric( $this->away_team ) && '-1' !== $this->away_team ) {
				$this_day  = isset( $this->teams['away']->match_day ) ? $this->teams['away']->match_day : null;
				$this_time = isset( $this->teams['away']->match_time ) ? $this->teams['away']->match_time : null;
				$this->set_match_date( $this->match_date, $this_day, $this_time );
				$location = isset( $this->teams['away']->club->shortcode ) ? $this->teams['away']->club->shortcode : null;
				if ( $location ) {
					$this->set_location( $location );
				}
			}
		}
	}
	/**
	 * Set match date function
	 *
	 * Adjust match date based on team match date and time
	 *
	 * @param string $start_date original match date.
	 * @param string $match_day match day.
	 * @param string $match_time match time.
	 * @return void
	 */
	public function set_match_date( $start_date, $match_day, $match_time ) {
		global $wpdb;
		if ( ! empty( $match_day ) ) {
			$day = Racketmanager_Util::get_match_day_number( $match_day );
			if ( ! empty( $match_time ) ) {
				$match_date = gmdate( 'Y-m-d', strtotime( $start_date . " +$day day" ) ) . ' ' . $match_time;
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->racketmanager_matches} SET `date` = %s WHERE `id` = %d",
						$match_date,
						$this->id
					)
				);
				wp_cache_delete( $this->id, 'matches' );
				if ( $this->num_rubbers ) {
					$rubbers = $this->get_rubbers();
					foreach ( $rubbers as $rubber ) {
						$rubber       = get_rubber( $rubber );
						$rubber->date = $match_date;
						$rubber->update_date();
					}
				}
			}
		}
	}
	/**
	 * Set location function
	 *
	 * @param string $location match location.
	 * @return void
	 */
	public function set_location( $location ) {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_matches} SET `location` = %s WHERE `id` = %d",
				$location,
				$this->id
			)
		);
		wp_cache_delete( $this->id, 'matches' );
	}
	/**
	 * Get previous round set score function
	 *
	 * @param string $team home or away team.
	 * @return string previous round set score.
	 */
	public function get_prev_round_set_score( $team ) {
		$prev_round_set_score = null;
		if ( $this->league->is_championship ) {
			$current_round = $this->league->championship->get_finals( $this->final_round );
			$this->round   = $current_round;
			$prev_round    = $current_round['round'] - 1;
			if ( $prev_round ) {
				$prev_round_name = $this->league->championship->get_final_keys( $prev_round );
				$team_id         = $this->teams[ $team ]->id;
				if ( '-1' !== $team_id ) {
					$prev_matches = $this->league->get_matches(
						array(
							'season'           => $this->season,
							'team_id'          => $team_id,
							'final'            => $prev_round_name,
							'winner_id'        => $team_id,
							'reset_query_args' => true,
						)
					);
					if ( $prev_matches ) {
						$prev_round_set_score = $prev_matches[0]->set_score;
					}
				}
			}
		}

		return $prev_round_set_score;
	}
	/**
	 * Notify teams for next round
	 *
	 * @return boolean
	 */
	public function notify_next_match_teams() {
		global $racketmanager;

		if ( ( -1 === $this->teams['home']->id || -1 === $this->teams['away']->id ) || ( ! isset( $this->host ) ) ) {
			return false;
		}
		$to        = array();
		$opponents = array( 'home', 'away' );
		foreach ( $opponents as $opponent ) {
			$team = $this->teams[ $opponent ];
			if ( 'P' === $team->status ) {
				$player_id = $team->player_id;
				foreach ( $player_id as $player ) {
					$player = get_player( $player );
					if ( ! empty( $player->email ) ) {
						$to[] = $player->fullname . '<' . $player->email . '>';
					}
				}
			} elseif ( ! empty( $team->contactemail ) ) {
				$to[] = $team->captain . '<' . $team->contactemail . '>';
			}
		}
		if ( empty( $to ) ) {
			return false;
		}
		$email_from                      = $racketmanager->get_confirmation_email( $this->league->event->competition->type );
		$organisation_name               = $racketmanager->site_name;
		$round_name                      = $this->league->championship->finals[ $this->final_round ]['name'];
		$message_args                    = array();
		$message_args['round']           = $round_name;
		$message_args['competitiontype'] = $this->league->event->competition->type;
		if ( 'tournament' === $this->league->event->competition->type ) {
			$tournaments                = $racketmanager->get_tournaments(
				array(
					'competition_id' => $this->league->event->competition_id,
					'season'         => $this->season,
				)
			);
			$tournament                 = $tournaments[0];
			$message_args['tournament'] = $tournament->id;
		} elseif ( 'cup' === $this->league->event->competition->type ) {
			$message_args['competition'] = $this->league->event->competition->name;
		}
		$message_args['emailfrom'] = $email_from;
		$email_message             = racketmanager_match_notification( $this->id, $message_args );
		$headers                   = array();
		$headers[]                 = 'From: ' . ucfirst( $this->league->event->competition->type ) . ' Secretary <' . $email_from . '>';
		$headers[]                 = 'cc: ' . ucfirst( $this->league->event->competition->type ) . ' Secretary <' . $email_from . '>';
		$subject                   = $organisation_name . ' - ' . $this->league->title . ' - ' . $round_name . ' - Match Details';
		if ( ! empty( $this->leg ) ) {
			$subject .= ' - ' . __( 'Leg', 'racketmanager' ) . ' ' . $this->leg;
		}
		wp_mail( $to, $subject, $email_message, $headers );
		return true;
	}
}
