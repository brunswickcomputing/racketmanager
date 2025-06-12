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
	 * @var string|null
	 */
	public ?string $final_round = '';
	/**
	 * Id
	 *
	 * @var int
	 */
	public int $id;
	/**
	 * Group
	 *
	 * @var string|null
	 */
	public ?string $group = null;
	/**
	 * Date
	 *
	 * @var string
	 */
	public string $date;
	/**
	 * Original date
	 *
	 * @var string|null
	 */
	public ?string $date_original;
	/**
	 * Home team
	 *
	 * @var string
	 */
	public string $home_team;
	/**
	 * Away team
	 *
	 * @var string
	 */
	public string $away_team;
	/**
	 * Match day
	 *
	 * @var int|null
	 */
	public ?int $match_day;
	/**
	 * Location
	 *
	 * @var string|null
	 */
	public ?string $location;
	/**
	 * League  id
	 *
	 * @var int
	 */
	public int $league_id;
	/**
	 * Season
	 *
	 * @var string
	 */
	public string $season;
	/**
	 * Home points
	 *
	 * @var float|null
	 */
	public ?float $home_points = null;
	/**
	 * Away points
	 *
	 * @var float|null
	 */
	public ?float $away_points = null;
	/**
	 * Winning team id
	 *
	 * @var string
	 */
	public string $winner_id;
	/**
	 * Losing team id
	 *
	 * @var string
	 */
	public string $loser_id;
	/**
	 * Post id for match report
	 *
	 * @var int
	 */
	public int $post_id;
	/**
	 * Round for championship
	 *
	 * @var string
	 */
	public string $final;
	/**
	 * Custom
	 *
	 * @var string|array|null
	 */
	public string|array|null $custom = array();
	/**
	 * Match confirmed value
	 *
	 * @var string|null
	 */
	public ?string $confirmed;
	/**
	 * Home team score
	 *
	 * @var float|null
	 */
	public ?float $home_score;
	/**
	 * Away team score
	 *
	 * @var float|null
	 */
	public ?float $away_score;
	/**
	 * Match score
	 *
	 * @var string|null
	 */
	public ?string $score;
	/**
	 * Match set score
	 *
	 * @var string|null
	 */
	public ?string $set_score;
	/**
	 * Confirmed status display value
	 *
	 * @var string|int|null
	 */
	public string|int|null $confirmed_display;
	/**
	 * Page url
	 *
	 * @var string
	 */
	public string $page_url;
	/**
	 * Selected flag
	 *
	 * @var boolean
	 */
	public bool $is_selected;
	/**
	 * Match title
	 *
	 * @var string
	 */
	public string $match_title;
	/**
	 * Teams array
	 *
	 * @var array
	 */
	public array $teams;
	/**
	 * Match title
	 *
	 * @var string
	 */
	public string $title;
	/**
	 * Match date
	 *
	 * @var string
	 */
	public string $match_date;
	/**
	 * Match start time
	 *
	 * @var string
	 */
	public string $start_time;
	/**
	 * Match start hour
	 *
	 * @var string
	 */
	public string $hour;
	/**
	 * Match start minutes
	 *
	 * @var string
	 */
	public string $minutes;
	/**
	 * Tooltip title
	 *
	 * @var string
	 */
	public string $tooltip_title;
	/**
	 * Match report
	 *
	 * @var string|null
	 */
	public ?string $report;
	/**
	 * League object
	 *
	 * @var object|null
	 */
	public null|object $league;
	/**
	 * Is walkover variable
	 *
	 * @var boolean
	 */
	public bool $is_walkover = false;
	/**
	 * Is retired variable
	 *
	 * @var boolean
	 */
	public bool $is_retired = false;
	/**
	 * Is shared variable
	 *
	 * @var boolean
	 */
	public bool $is_shared = false;
	/**
	 * Is withdrawn variable
	 *
	 * @var boolean
	 */
	public bool $is_withdrawn = false;
	/**
	 * Is abandoned variable
	 *
	 * @var boolean
	 */
	public bool $is_abandoned = false;
	/**
	 * Sets variable
	 *
	 * @var array
	 */
	public mixed $sets = false;
	/**
	 * Round variable
	 *
	 * @var string|null
	 */
	public ?string $round;
	/**
	 * Comments variable
	 *
	 * @var array
	 */
	public mixed $comments = array();
	/**
	 * Leg variable
	 *
	 * @var int|null
	 */
	public ?int $leg;
	/**
	 * Status variable
	 *
	 * @var int|null
	 */
	public ?int $status = null;
	/**
	 * Linked match variable
	 *
	 * @var int|null
	 */
	public ?int $linked_match;
	/**
	 * Host variable
	 *
	 * @var string|null
	 */
	public ?string $host = null;
	/**
	 * Match Link variable
	 *
	 * @var string|null
	 */
	public ?string $link;
	/**
	 * Number of rubbers variable
	 *
	 * @var int|null
	 */
	public int|null $num_rubbers;
	/**
	 * Home points for match tie variable
	 *
	 * @var float|null
	 */
	public ?float $home_points_tie;
	/**
	 * Away points for match tie variable
	 *
	 * @var float|null
	 */
	public ?float $away_points_tie;
	/**
	 * Winner id for match tie variable
	 *
	 * @var string|null
	 */
	public ?string $winner_id_tie;
	/**
	 * Loser id for match tie variable
	 *
	 * @var string|null
	 */
	public ?string $loser_id_tie;
	/**
	 * Match tie Link variable
	 *
	 * @var string|null
	 */
	public ?string $link_tie;
	/**
	 * Home captain variable
	 *
	 * @var int|null
	 */
	public ?int $home_captain;
	/**
	 * Away captain variable
	 *
	 * @var int|null
	 */
	public ?int $away_captain;
	/**
	 * Pending status variable
	 *
	 * @var boolean
	 */
	public bool $is_pending;
	/**
	 * Cancelled status variable
	 *
	 * @var boolean
	 */
	public bool $is_cancelled;
	/**
	 * Previous home match object
	 *
	 * @var object|null
	 */
	public mixed $prev_home_match;
	/**
	 * Previous away match object
	 *
	 * @var object|null
	 */
	public mixed $prev_away_match;
	/**
	 * Date updated
	 *
	 * @var string|null
	 */
	public ?string $updated;
	/**
	 * Date result entered
	 *
	 * @var string|null
	 */
	public ?string $date_result_entered;
	/**
	 * Day
	 *
	 * @var int
	 */
	public int $day;
	/**
	 * Month
	 *
	 * @var int
	 */
	public int $month;
	/**
	 * Year
	 *
	 * @var int
	 */
	public int $year;
	/**
	 * Stats
	 *
	 * @var array
	 */
	public array $stats;
	/**
	 * Rubbers
	 *
	 * @var array
	 */
	public array $rubbers;
	/**
	 * Updated user
	 *
	 * @var string|null
	 */
	public ?string $updated_user;
	/**
	 * Class
	 *
	 * @var string
	 */
	public string $class;
	/**
	 * Player
	 *
	 * @var object
	 */
	public object $player;
	/**
	 * Type
	 *
	 * @var string
	 */
	public string $type;
	/**
	 * Event id
	 *
	 * @var int
	 */
	public int $event_id;
	/**
	 * Withdrawn
	 *
	 * @var boolean
	 */
	public bool $withdrawn;
	/**
	 * Confirmation overdue date
	 *
	 * @var string
	 */
	public string $confirmation_overdue_date;
	/**
	 * Result overdue date
	 *
	 * @var string
	 */
	public string $result_overdue_date;
	/**
	 * Overdue time
	 *
	 * @var string
	 */
	public string $overdue_time;
	/**
	 * Walkover
	 *
	 * @var string
	 */
	public string $walkover;
	/**
	 * Share
	 *
	 * @var string
	 */
	public string $share;
	/**
	 * Abandoned
	 *
	 * @var string
	 */
	public string $abandoned;
	/**
	 * Cancelled
	 *
	 * @var string
	 */
	public string $cancelled;
	/**
	 * Shared
	 *
	 * @var string
	 */
	public string $shared;
	/**
	 * Retired
	 *
	 * @var string
	 */
	public string $retired;
	/**
	 * Home title
	 *
	 * @var string|null
	 */
	public ?string $home_title;
	/**
	 * Away title
	 *
	 * @var string|null
	 */
	public ?string $away_title;
	/**
	 * Number of sets
	 *
	 * @var int|null
	 */
	public ?int $num_sets;
	/**
	 * Retrieve match instance
	 *
	 * @param int $match_id match id.
	 */
	public static function get_instance( int $match_id ) {
		global $wpdb;
		if ( ! $match_id ) {
			return false;
		}
		$match = wp_cache_get( $match_id, 'matches' );
		if ( ! $match ) {
			$match = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT `final` AS final_round, `group`, `home_team`, `away_team`, DATE_FORMAT(`date`, '%%Y-%%m-%%d %%H:%%i') AS date, DATE_FORMAT(`date_original`, '%%Y-%%m-%%d %%H:%%i') AS date_original, DATE_FORMAT(`date`, '%%e') AS day, DATE_FORMAT(`date`, '%%c') AS month, DATE_FORMAT(`date`, '%%Y') AS year, DATE_FORMAT(`date`, '%%H') AS `hour`, DATE_FORMAT(`date`, '%%i') AS `minutes`, `match_day`, `location`, `league_id`, `home_points`, `away_points`, `winner_id`, `loser_id`, `post_id`, `season`, `id`, `custom`, `updated`, `updated_user`, `confirmed`, `home_captain`, `away_captain`, `comments`, `status`, `host`, `linked_match`, `leg`, `winner_id_tie`, `loser_id_tie`, `home_points_tie`, `away_points_tie`, `updated`, `date_result_entered` FROM $wpdb->racketmanager_matches WHERE `id` = %d LIMIT 1",
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
	 * @param object|null $match Racketmanager_Match object.
	 */
	public function __construct( object $match = null ) {
		global $wp;
		if ( ! is_null( $match ) ) {
			if ( ! empty( $match->custom ) ) {
				$match->custom = stripslashes_deep( (array) maybe_unserialize( $match->custom ) );
				$match         = (object) array_merge( (array) $match, (array) $match->custom );
			}
			foreach ( get_object_vars( $match ) as $key => $value ) {
				$this->$key = $value;
			}
			if ( isset( $this->season ) ) {
				$wp->set_query_var( 'season', $this->season );
			}

			// get League Object.
			$this->league = get_league( $this->league_id );
			if ( ! isset( $this->id ) ) {
				$this->id = $this->add();
			}
			if ( empty( $this->league->num_rubbers ) ) {
				$this->num_rubbers = 0;
			} else {
				$this->num_rubbers = $this->get_rubbers( false, true );
			}
			$this->location    = empty( $this->location ) ? null : stripslashes( $this->location );
			$this->report      = empty( $this->post_id ) ? null : '<a href="' . get_permalink( $this->post_id ) . '">' . __( 'Report', 'racketmanager' ) . '</a>';
			$this->sets        = ! empty( $match->custom['sets'] ) ? $match->custom['sets'] : array();
			$this->is_walkover = false;
			$this->set_score();
			$this->is_walkover = false;
			$this->set_status_flags();
			if ( ! empty( $this->confirmed ) ) {
				$this->confirmed_display = match ( $this->confirmed ) {
					'Y' => __( 'Complete', 'racketmanager' ),
					'A' => __( 'Approved', 'racketmanager' ),
					'C' => __( 'Challenged', 'racketmanager' ),
					'P' => __( 'Pending', 'racketmanager' ),
					default => $this->confirmed,
				};
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
				$comments       = empty( $this->comments ) ? '' : $this->comments;
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
			$this->set_link();
			if ( empty( $this->winner_id ) ) {
				$this->is_pending = true;
			} else {
				$this->is_pending = false;
			}
			if ( 'final' === $this->final_round ) {
				if ( ! is_numeric( $this->home_team ) ) {
					$this->prev_home_match = $this->get_prev_round_matches( $this->home_team, $this->season, $this->league );
				}
				if ( ! is_numeric( $this->away_team ) ) {
					$this->prev_away_match = $this->get_prev_round_matches( $this->away_team, $this->season, $this->league );
				}
			}
		}
	}
	/**
	 * Function to set status flags
	 */
	private function set_status_flags(): void {
		$this->is_walkover  = false;
		$this->is_shared    = false;
		$this->is_retired   = false;
		$this->is_abandoned = false;
		$this->is_cancelled = false;
		$this->is_withdrawn = false;
		if ( ! empty( $this->status ) ) {
			switch ( $this->status ) {
				case 1:
					$this->is_walkover = true;
					break;
				case 2:
					$this->is_retired = true;
					break;
				case 3:
					$this->is_shared = true;
					break;
				case 6:
					$this->is_abandoned = true;
					break;
				case 7:
					$this->is_withdrawn = true;
					break;
				case 8:
					$this->is_cancelled = true;
					break;
				default:
					break;
			}
		}
	}
	/**
	 * Function to set match link
	 */
	private function set_link(): void {
		$this->link = null;
		if ( $this->league->is_championship ) {
			$match_ref = $this->final_round;
		} else {
			$match_ref = 'day' . $this->match_day;
		}
		if ( $this->league->event->is_box ) {
			$this->link = '/league/' . seo_url( $this->league->title ) . '/match/' . $this->id . '/';
		} elseif ( 'tournament' === $this->league->event->competition->type ) {
			$tournament_code = $this->league->event->competition->id . ',' . $this->season;
			$tournament      = get_tournament( $tournament_code, 'shortcode' );
			if ( $tournament ) {
				if ( ! empty( $this->teams['home']->title ) && ! empty( $this->teams['away']->title ) ) {
					$this->link = '/tournament/' . seo_url( $tournament->name ) . '/match/' . seo_url( $this->league->title ) . '/' . seo_url( $this->teams['home']->title ) . '-vs-' . seo_url( $this->teams['away']->title ) . '/' . $this->id . '/';
				}
			} else {
				$this->link = '/league/' . seo_url( $this->league->title ) . '/match/' . $this->id . '/';
			}
		} elseif ( ! empty( $this->teams['home']->title ) && ! empty( $this->teams['away']->title ) ) {
			$this->link = '/match/' . seo_url( $this->league->title ) . '/' . $this->season . '/' . $match_ref . '/' . seo_url( $this->teams['home']->title ) . '-vs-' . seo_url( $this->teams['away']->title ) . '/';
		} else {
			$this->link = null;
		}
		$this->link_tie = $this->link;
		if ( ! empty( $this->leg ) ) {
			$this->link .= 'leg-' . $this->leg . '/';
		}
	}
	/**
	 * Set score function
	 *
	 * @return void
	 */
	private function set_score(): void {
		if ( null !== $this->home_points && null !== $this->away_points ) {
			$this->home_score = $this->home_points;
			$this->away_score = $this->away_points;
			$this->score      = sprintf( '%g - %g', $this->home_score, $this->away_score );
			if ( ! empty( $this->league->num_rubbers ) ) {
				if ( '-1' === $this->home_team || '-1' === $this->away_team ) {
					$this->is_walkover = true;
					$set_score         = __( 'Walkover', 'racketmanager' );
				} else {
					$set_score = $this->score;
				}
			} else {
				$set_score  = '';
				$this->sets = ! empty( $this->custom['sets'] ) ? $this->custom['sets'] : array();
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
			}
		} else {
			$this->home_score = null;
			$this->away_score = null;
			$this->score      = null;
			$set_score        = null;
			if ( isset( $this->winner_id ) ) {
				if ( '-1' === $this->home_team || '-1' === $this->away_team ) {
					$set_score = $this->score;
				} else {
					$this->is_walkover = true;
					$set_score         = __( 'Walkover', 'racketmanager' );
				}
			}
		}
		$this->set_score = $set_score;
	}
	/**
	 * Get details of previous round match
	 *
	 * @param string $team_ref round and team position.
	 * @param string $season season.
	 * @param object $league league.
	 *
	 * @return object|null $prev_match previous match.
	 */
	private function get_prev_round_matches( string $team_ref, string $season, object $league ): ?object {
		$team  = explode( '_', $team_ref );
		$final = $team[1] ?? null;
		if ( ! empty( $final ) ) {
			$league = get_league( $league );
			if ( $league ) {
				$args['final']   = $final;
				$args['season']  = $season;
				$args['orderby'] = array( 'id' => 'ASC' );
				$prev_matches    = $league->get_matches( $args );
				if ( $prev_matches ) {
					$match_ref = $team[2] - 1;
					return $prev_matches[ $match_ref ];
				} else {
					return null;
				}
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	/**
	 * Add match
	 */
	public function add(): int {
		global $wpdb;
		$max_rubbers = 0;
		if ( ! empty( $this->league->num_rubbers ) ) {
			$max_rubbers = $this->league->num_rubbers;
			if ( $this->league->is_championship && ! empty( $this->league->current_season['home_away'] ) && ! empty( $this->leg ) && 2 === $this->leg && 'MPL' === $this->league->event->scoring ) {
				++$max_rubbers;
			} elseif ( '1' === $this->league->event->reverse_rubbers ) {
				$max_rubbers = $max_rubbers * 2;
			}
		}
		$sql = $wpdb->prepare(
			"INSERT INTO $wpdb->racketmanager_matches (date, home_team, away_team, match_day, location, league_id, season, final, custom, `group`, `host`) VALUES (%s, %s, %s, %d, %s, %d, %s, %s, %s, %s, %s)",
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
				new Racketmanager_rubber( $rubber );
			}
		}
		return $this->id;
	}
	/**
	 * Update leg and linked match function
	 *
	 * @param int $leg leg number.
	 * @param int $linked_match linked match id.
	 *
	 * @return void
	 */
	public function update_legs( int $leg, int $linked_match ): void {
		global $wpdb;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_matches SET `leg` = %d, `linked_match` = %d WHERE `id` = %d",
				$leg,
				$linked_match,
				$this->id,
			)
		);
		$this->leg = $leg;
		$this->linked_match = $linked_match;
		wp_cache_set( $this->id, $this, 'matches' );
	}
	/**
	 * Update match
	 */
	public function update(): ?string {
		global $wpdb;
		$update_count = $wpdb->query(
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_matches SET `date` = %s, `home_team` = %s, `away_team` = %s, `match_day` = %d, `location` = %s, `league_id` = %d, `group` = %s, `final` = %s, `custom` = %s, `host` = %s WHERE `id` = %d",
				$this->date,
				$this->home_team,
				$this->away_team,
				$this->match_day,
				$this->location,
				$this->league_id,
				$this->group,
				$this->final_round,
				maybe_serialize( $this->custom ),
				$this->host,
				$this->id
			)
		);
		wp_cache_set( $this->id, $this, 'matches' );
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
	public function update_sets( array $sets ): void {
		global $wpdb;
		$this->custom['sets'] = $sets;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_matches SET `custom` = %s WHERE `id` = %d",
				maybe_serialize( $this->custom ),
				$this->id
			)
		);
		$this->sets = $sets;
		wp_cache_set( $this->id, $this, 'matches' );
	}
	/**
	 * Delete match
	 */
	public function delete(): true {
		global $wpdb;
		$rubbers = $this->get_rubbers();
		foreach ( $rubbers as $rubber ) {
			$rubber->delete();
		}
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM $wpdb->racketmanager_matches WHERE `id` = %d",
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
	private function set_teams_details( string $team = 'both' ): void {
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
					$this->teams['home'] = $this->league->get_team_dtls( $this->home_team, $this->season );
					if ( is_object( $this->teams['home'] ) ) {
						if ( $this->league->is_championship ) {
							$this->teams['home']->rank = $this->league->get_rank( $this->home_team, $this->season );
						} else {
							$this->teams['home']->status = $this->league->get_status( $this->home_team, $this->season );
						}
					}
				}
			} else {
				$this->teams['home'] = $teams[$this->home_team] ?? null;
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
					$this->teams['away'] = $this->league->get_team_dtls( $this->away_team, $this->season );
					if ( is_object( $this->teams['away'] ) ) {
						if ( $this->league->is_championship ) {
							$this->teams['away']->rank = $this->league->get_rank( $this->away_team, $this->season );
						} else {
							$this->teams['away']->status = $this->league->get_status( $this->away_team, $this->season );
						}
					}
				}
			} else {
				$this->teams['away'] = $teams[$this->away_team] ?? null;
			}
		}
	}

	/**
	 * Get match title
	 *
	 * @return string
	 */
	public function get_title(): string {
		$home_team = $this->teams['home'];
		$away_team = $this->teams['away'];

		if ( isset( $this->title ) && ( ! $home_team || ! $away_team || $this->home_team === $this->away_team ) ) {
			$title = stripslashes( $this->title );
		} else {
			$home_team_name = $home_team->title ?? __('Unknown', 'racketmanager');
			$away_team_name = $away_team->title ?? __('Unknown', 'racketmanager');

			$title = sprintf( '%s - %s', $home_team_name, $away_team_name );
		}

		return $title;
	}

	/**
	 * Set match date
	 *
	 * @param string $date_format date format.
	 */
	public function set_date( string $date_format = '' ): void {
		global $racketmanager;
		if ( '' === $date_format ) {
			$date_format = $racketmanager->date_format;
		}
		$this->match_date = (str_starts_with($this->date, '0000-00-00')) ? 'N/A' : mysql2date( $date_format, $this->date );
		$this->set_tooltip_title();
	}

	/**
	 * Set match start time
	 *
	 * @param string $time_format time format.
	 */
	public function set_time( string $time_format = '' ): void {
		global $racketmanager;
		if ( empty( $time_format ) ) {
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
	private function set_tooltip_title(): void {
		$home_title = $this->teams['home']->title ?? null;
		$away_title = $this->teams['away']->title ?? null;
		// make tooltip title for last-5 standings.
		if ( empty( $this->home_points ) && empty( $this->away_points ) ) {
			$tooltip_title = 'Next Match: ' . $home_title . ' - ' . $away_title . ' [' . $this->match_date . ']';
		} elseif ( isset( $this->title ) ) {
			$tooltip_title = stripslashes( $this->title ) . ' [' . $this->match_date . ']';
		} else {
			$tooltip_title = $this->score . ' - ' . $home_title . ' - ' . $away_title . ' [' . $this->match_date . ']';
		}
		$this->tooltip_title = $tooltip_title;
	}
	/**
	 * Set tie points for multi legged match function
	 *
	 * @param int|null $home_points_tie home points for tie.
	 * @param int|null $away_points_tie away points for tie.
	 *
	 * @return void
	 */
	public function update_result_tie( int $home_points_tie = null, int $away_points_tie = null ): void {
		global $wpdb;
		$update = true;
		if ( 2 === $this->leg ) {
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
			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE $wpdb->racketmanager_matches SET `home_points_tie` = %f, `away_points_tie` = %f, `winner_id_tie` = %d, `loser_id_tie` = %d WHERE `id` = %d",
					$home_points_tie,
					$away_points_tie,
					$winner_id_tie,
					$loser_id_tie,
					$this->id
				)
			);
			wp_cache_set( $this->id, $this, 'matches' );
		}
	}
	/**
	 * Update result
	 *
	 * @param float|null $home_points_input home points.
	 * @param float|null $away_points_input away points.
	 * @param array|null $custom custom.
	 * @param string $confirmed match status field.
	 * @param string|null $match_status match status.
	 *
	 * @return boolean
	 */
	public function update_result( ?float $home_points_input, ?float $away_points_input, ?array $custom, string $confirmed = 'Y', ?string $match_status = '' ): bool {
		$bye            = false;
		$updated        = false;
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
		if ( ! empty( $this->num_rubbers ) ) {
			$stats                    = array();
			$stats['rubbers']['home'] = 0;
			$stats['rubbers']['away'] = 0;
			$stats['sets']['home']    = 0;
			$stats['sets']['away']    = 0;
			$stats['games']['home']   = 0;
			$stats['games']['away']   = 0;
			$rubbers                  = $this->get_rubbers();
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
					++$stats['rubbers']['home'];
				}
				if ( $this->away_team === $rubber->winner_id ) {
					++$away_win;
					++$stats['rubbers']['away'];
				}
				if ( '-1' === $rubber->winner_id ) {
					++$draw;
					$stats['rubbers']['home'] += 0.5;
					$stats['rubbers']['away'] += 0.5;
				}
				if ( is_numeric( $rubber->home_points ) ) {
					$home_points += floatval( $rubber->home_points );
				}
				if ( is_numeric( $rubber->away_points ) ) {
					$away_points += floatval( $rubber->away_points );
				}
				$stats['sets']['home']  += empty( $rubber->custom['stats']['sets']['home'] ) ? 0 : $rubber->custom['stats']['sets']['home'];
				$stats['sets']['away']  += empty( $rubber->custom['stats']['sets']['away'] ) ? 0 : $rubber->custom['stats']['sets']['away'];
				$stats['games']['home'] += empty( $rubber->custom['stats']['games']['home'] ) ? 0 : $rubber->custom['stats']['games']['home'];
				$stats['games']['away'] += empty( $rubber->custom['stats']['games']['away'] ) ? 0 : $rubber->custom['stats']['games']['away'];
			}
			$custom['stats'] = $stats;
			if ( is_array( $this->custom ) ) {
				unset( $this->custom['walkover'] );
				unset( $this->custom['share'] );
				unset( $this->custom['retired'] );
				unset( $this->custom['abandoned'] );
				unset( $this->custom['withdrawn'] );
				unset( $this->custom['cancelled'] );
			}
			if ( 'league' === $this->league->event->competition->type ) {
				$this->status = 0;
				if ( 7 === intval( $match_status ) || 8 === intval( $match_status ) ) {
					if ( 7 === intval( $match_status ) ) {
						$custom['withdrawn'] = true;
					} else {
						$custom['cancelled'] = true;
					}
					$this->status        = intval( $match_status );
					$home_points         = 0;
					$away_points         = 0;
				} else {
					if ( $home_walkover === $this->num_rubbers || $away_walkover === $this->num_rubbers ) {
						if ( $home_walkover === $this->num_rubbers ) {
							$custom['walkover'] = 'away';
						} else {
							$custom['walkover'] = 'home';
						}
						$this->custom = array_merge( (array) $this->custom, $custom );
						$this->status = 1;
					} elseif ( $shared === $this->num_rubbers ) {
						$custom['share'] = 'true';
						$this->custom    = array_merge( (array) $this->custom, $custom );
						$this->status    = 3;
					} elseif ( 6 === intval( $match_status ) ) {
						$custom['abandoned'] = true;
						$this->status        = 6;
						$this->is_abandoned  = true;
					}
					$point_rule          = $this->league->get_point_rule();
					$rubber_win          = ! empty( $point_rule['rubber_win'] ) ? $point_rule['rubber_win'] : 0;
					$rubber_draw         = ! empty( $point_rule['rubber_draw'] ) ? $point_rule['rubber_draw'] : 0;
					$matches_win         = ! empty( $point_rule['matches_win'] ) ? $point_rule['matches_win'] : 0;
					$matches_draw        = ! empty( $point_rule['matches_draw'] ) ? $point_rule['matches_draw'] : 0;
					$shared_match        = ! empty( $point_rule['shared_match'] ) ? $point_rule['shared_match'] : 0;
					$forwalkover_rubber  = empty( $point_rule['forwalkover_rubber'] ) ? 0 : $point_rule['forwalkover_rubber'];
					$walkover_penalty    = empty( $point_rule['forwalkover_match'] ) ? 0 : $point_rule['forwalkover_match'];
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
						if ( $home_win > $away_win ) {
							$home_points += $matches_win;
						} elseif ( $home_win < $away_win ) {
							$away_points += $matches_win;
						} else {
							$home_points += $matches_draw;
							$away_points += $matches_draw;
						}
						if ( 1 === $this->status ) {
							$home_points -= $walkover_penalty * $home_walkover;
							$away_points -= $walkover_penalty * $away_walkover;
						}
					}
				}
			} else {
				$this->status = 0;
			}
		} elseif ( empty( $home_points_input ) && empty( $away_points_input ) ) {
			if ( isset( $custom['sets'] ) ) {
				$this->sets = $custom['sets'];
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
		} else {
			$home_points = $home_points_input;
			$away_points = $away_points_input;
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
		if ( ! empty( $home_points ) || ! empty( $away_points ) || 'withdrawn' === $match_status || 7 === intval( $match_status ) || 8 === intval( $match_status ) ) {
			$prev_winner = $this->winner_id;
			$this->get_result( $home_points, $away_points );
			if ( $prev_winner !== $this->winner_id || floatval( $home_points ) !== $this->home_points || floatval( $away_points ) !== $this->away_points || $custom !== $this->custom || $confirmed !== $this->confirmed ) {
				$this->home_points = $home_points;
				$this->away_points = $away_points;
				$this->custom      = array_merge( (array) $this->custom, $custom );
				$this->confirmed   = $confirmed;
				foreach ( $this->custom as $key => $value ) {
					$this->{$key} = $value;
				}
				$this->update_result_database();
				$updated = true;
				if ( ! empty( $this->leg ) && 2 === $this->leg ) {
					$this->update_result_tie();
				}
				$this->set_score();
			}
		}
		if ( $updated ) {
			if ( '-1' !== $this->home_team && '-1' !== $this->away_team ) {
				$this->notify_favourites();
			}
		}
		return $updated;
	}
	/**
	 * Update result with penalty
	 *
	 * @param string $team_ref team to apply penalty.
	 * @param int $penalty penalty points.
	 */
	public function update_result_with_penalty( string $team_ref, int $penalty ): void {
		if ( 'home' === $team_ref ) {
			$this->home_points -= $penalty;
		} elseif ( 'away' === $team_ref ) {
			$this->away_points -= $penalty;
		}
		$this->get_result( $this->home_points, $this->away_points );
		$this->update_result_database();
		if ( ! empty( $this->leg ) && 2 === $this->leg ) {
			$this->update_result_tie();
		}
		$this->set_score();
		if ( '-1' !== $this->home_team && '-1' !== $this->away_team ) {
			$this->notify_favourites();
		}
	}
	/**
	 * Notify favourites
	 */
	private function notify_favourites(): void {
		global $racketmanager;
		$favourited_users = array();
		$users            = Racketmanager_Util::get_users_for_favourite( 'league', $this->league->id );
		foreach ( $users  as $user ) {
			$favourited_users[] = $user;
		}
		$users = Racketmanager_Util::get_users_for_favourite( 'competition', $this->league->event->id );
		foreach ( $users  as $user ) {
			$favourited_users[] = $user;
		}
		$teams = array( 'home', 'away' );
		foreach ( $teams as $team ) {
			if ( ! empty( $this->teams[ $team ]->affilatedclub ) ) {
				$users = Racketmanager_Util::get_users_for_favourite( 'club', $this->teams[ $team ]->affilatedclub );
				foreach ( $users  as $user ) {
					$favourited_users[] = $user;
				}
			}
			if ( ! empty( $this->teams[ $team ]->id ) ) {
				$users = Racketmanager_Util::get_users_for_favourite( 'team', $this->teams[ $team ]->id );
				foreach ( $users  as $user ) {
					$favourited_users[] = $user;
				}
			}
		}
		$favourited_users = array_unique( $favourited_users, SORT_REGULAR );
		if ( $favourited_users ) {
			$headers           = array();
			$from_email        = $racketmanager->get_confirmation_email( $this->league->event->competition->type );
			$headers[]         = 'From: ' . ucfirst( $this->league->event->competition->type ) . ' Secretary <' . $from_email . '>';
			$organisation_name = $racketmanager->site_name;
			$email_subject     = $racketmanager->site_name . ' - ' . $this->league->title . ' Result Notification';
			$favourite_url     = $racketmanager->site_url . '/member-account/favourites';
			$match_url         = $racketmanager->site_url . $this->link;
			foreach ( $favourited_users as $user ) {
				$user_details  = get_userdata( $user );
				$email_to      = $user_details->display_name . ' <' . $user_details->user_email . '>';
				$email_message = $racketmanager->shortcodes->load_template(
					'favourite-notification',
					array(
						'email_subject' => $email_subject,
						'from_email'    => $from_email,
						'match_url'     => $match_url,
						'favourite_url' => $favourite_url,
						'organisation'  => $organisation_name,
						'user'          => $user_details,
						'match'         => $this,
					),
					'email'
				);
				wp_mail( $email_to, $email_subject, $email_message, $headers );
			}
		}
	}
	/**
	 * Update result in database function
	 *
	 * @return void
	 */
	private function update_result_database(): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_matches SET `home_points` = %f, `away_points` = %f, `winner_id` = %d, `loser_id` = %d, `custom` = %s, `updated_user` = %d, `updated` = now(), `confirmed` = %s, `status` = %d WHERE `id` = %d",
				$this->home_points,
				$this->away_points,
				$this->winner_id,
				$this->loser_id,
				maybe_serialize( $this->custom ),
				get_current_user_id(),
				$this->confirmed,
				$this->status,
				$this->id
			)
		);
		$this->set_status_flags();
		wp_cache_set( $this->id, $this, 'matches' );
		if ( 'Y' === $this->confirmed ) {
			$report = $this->report_result();
			if ( $report ) {
				$this->delete_results_report();
				$results_report           = new stdClass();
				$results_report->match_id = $this->id;
				$results_report->data     = $report;
				new Racketmanager_Results_Report( $results_report );
			}
		}
	}
	/**
	 * Delete results_report function
	 *
	 * @return void
	 */
	private function delete_results_report(): void {
		global $wpdb;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM $wpdb->racketmanager_results_report WHERE `match_id` = %d",
				$this->id,
			)
		);
	}
	/**
	 * Update match result status
	 *
	 * @param string $match_confirmed match confirmed status.
	 * @param array|null $comments match comments.
	 * @param string $confirm_comments result confirm comments.
	 * @param string $user_team team of user.
	 * @param string $actioned_by actioned by team.
	 */
	public function update_match_result_status( string $match_confirmed, ?array $comments, string $confirm_comments, string $user_team, string $actioned_by ): string {
		global $wpdb;
		$userid = get_current_user_id();
		if ( ! empty( $actioned_by ) && 'home' === $actioned_by ) {
			$captain = 'home';
		} elseif ( ! empty( $actioned_by ) && 'away' === $actioned_by ) {
			$captain = 'away';
		} elseif ( 'home' === $user_team || 'both' === $user_team ) {
			$captain     = 'home';
			$actioned_by = 'home';
		} elseif ( 'away' === $user_team ) {
			$captain     = 'away';
			$actioned_by = 'away';
		} else {
			$captain = 'admin';
		}
		if ( 'P' === $match_confirmed ) {
			if ( 'home' === $actioned_by || 'away' === $actioned_by ) {
				$this->comments[ $actioned_by ] = $comments[$actioned_by] ?? null;
			} else {
				$this->comments['result'] = $comments['result'] ?? null;
			}
		} elseif ( 'A' === $match_confirmed || 'C' === $match_confirmed ) {
			if ( 'home' === $actioned_by || 'away' === $actioned_by ) {
				$this->comments[ $actioned_by ] = $confirm_comments;
			} else {
				$this->comments['result'] = $comments['result'] ?? null;
			}
		}
		if ( 'home' === $captain ) { // Home captain.
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE $wpdb->racketmanager_matches SET `updated_user` = %d, `updated` = now(), `confirmed` = %s, `home_captain` = %d, `comments` = %s WHERE `id` = %d",
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
					"UPDATE $wpdb->racketmanager_matches SET `updated_user` = %d, `updated` = now(), `confirmed` = %s, `away_captain` = %d, `comments` = %s WHERE `id` = %d",
					$userid,
					$match_confirmed,
					$userid,
					maybe_serialize( $this->comments ),
					$this->id
				)
			);
			return 'away';
		} else {
			if ( 'D' !== $match_confirmed ) {
				$match_confirmed = 'Y'; // Admin user.
			}
			$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE $wpdb->racketmanager_matches SET `updated_user` = %d, `updated` = now(), `confirmed` = %s, `comments` =%s WHERE `id` = %d",
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
	 * @param float|null $home_points home points.
	 * @param float|null$away_points away_points.
	 */
	public function get_result( ?float $home_points, ?float $away_points ): void {
		$match = array();
		if ( 7 === $this->status ) {
			$match['winner'] = -1;
			$match['loser']  = -1;
		} elseif ( ! empty( $this->custom['walkover'] ) || 1 === $this->status ) {
			if ( 'home' === $this->custom['walkover'] ) {
				$match['winner'] = $this->home_team;
				$match['loser']  = $this->away_team;
			} elseif ( 'away' === $this->custom['walkover'] ) {
				$match['winner'] = $this->away_team;
				$match['loser']  = $this->home_team;
			}
			$this->status = 1;
		} elseif ( ! empty( $this->custom['retired'] ) ) {
			if ( 'away' === $this->custom['retired'] ) {
				$match['winner'] = $this->home_team;
				$match['loser']  = $this->away_team;
			} elseif ( 'home' === $this->custom['retired'] ) {
				$match['winner'] = $this->away_team;
				$match['loser']  = $this->home_team;
			}
			$this->status = 2;
		} elseif ( ! empty( $this->custom['share'] ) ) {
			$match['winner'] = -1;
			$match['loser']  = -1;
			$this->status    = 3;
		} elseif ( ! empty( $this->custom['withdrawn'] ) ) {
			$match['winner'] = -1;
			$match['loser']  = -1;
			$this->status    = 7;
		} elseif ( '-1' === $this->home_team ) {
			$match['winner'] = $this->away_team;
			$match['loser']  = 0;
		} elseif ( '-1' === $this->away_team ) {
			$match['winner'] = $this->home_team;
			$match['loser']  = 0;
		} elseif ( is_null( $home_points ) && is_null( $away_points ) ) {
			$match['winner'] = 0;
			$match['loser']  = 0;
		} elseif ( $home_points > $away_points ) {
			$match['winner'] = $this->home_team;
			$match['loser']  = $this->away_team;
		} elseif ( $home_points < $away_points ) {
			$match['winner'] = $this->away_team;
			$match['loser']  = $this->home_team;
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
	 * @param false|int $player player_id (optional).
	 * @param boolean $count count number of rubbers.
	 *
	 * @return array|int
	 */
	public function get_rubbers( false|int $player = false, bool $count = false ): array|int {
		global $wpdb;

		if ( $count ) {
			$args[] = $this->id;
			$sql    = $wpdb->prepare(
				"SELECT count(*) FROM $wpdb->racketmanager_rubbers WHERE `match_id` = %d",
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
		$sql_start = "SELECT r.`id` FROM $wpdb->racketmanager_rubbers r";
		$sql       = ' WHERE `match_id` = ' . $this->id;
		if ( $player ) {
			$sql_start .= ", $wpdb->racketmanager_rubber_players rp";
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
	public function delete_result_check(): void {
		global $wpdb;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM $wpdb->racketmanager_results_checker WHERE `match_id` = %d",
				$this->id
			)
		);
	}

	/**
	 * Add entry to results checker for player errors on match result
	 *
	 * @param int $team team.
	 * @param int $player player.
	 * @param string $error error.
	 * @param int $rubber_id rubber id.
	 */
	public function add_player_result_check( int $team, int $player, string $error, int $rubber_id ): void {
		$result_check              = new stdClass();
		$result_check->league_id   = $this->league_id;
		$result_check->match_id    = $this->id;
		$result_check->team_id     = $team;
		$result_check->player_id   = $player;
		$result_check->rubber_id   = $rubber_id;
		$result_check->description = $error;
		new Racketmanager_Results_Checker( $result_check );
	}
	/**
	 * Add entry to results checker for errors on match result
	 *
	 * @param int $team team.
	 * @param string $error error.
	 */
	public function add_match_result_check( int $team, string $error ): void {
		$result_check              = new stdClass();
		$result_check->league_id   = $this->league_id;
		$result_check->match_id    = $this->id;
		$result_check->team_id     = $team;
		$result_check->description = $error;
		new Racketmanager_Results_Checker( $result_check );
	}
	/**
	 * Are there result checker entries for match
	 */
	public function has_result_check(): ?string {
		global $wpdb;
		return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"select count(*) FROM $wpdb->racketmanager_results_checker WHERE `match_id` = %d",
				$this->id
			)
		);
	}
	/**
	 * Set home / away team function
	 *
	 * @param string|null $home home team id.
	 * @param string|null $away away team id.
	 *
	 * @return object
	 */
	public function set_teams( ?string $home, ?string $away ): object {
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
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_matches SET `home_team` = %s, `away_team` = %s WHERE `id` = %d",
				$home,
				$away,
				$this->id
			)
		);
		if ( ( $this->league->event->competition->is_league || isset( $this->host ) ) && is_numeric( $this->home_team ) && is_numeric( $this->away_team ) ) {
			$this->set_date_and_location();
		}
		$this->set_link();
		wp_cache_set( $this->id, $this, 'matches' );
		return $this;
	}
	/**
	 * Set date and location function
	 *
	 * @return void
	 */
	public function set_date_and_location(): void {
		if ( empty( $this->host ) || 'home' === $this->host ) {
			if ( is_numeric( $this->home_team ) && '-1' !== $this->home_team ) {
				$this_day  = $this->teams['home']->match_day ?? null;
				$this_time = $this->teams['home']->match_time ?? null;
				$this->set_match_date( $this->date, $this_day, $this_time );
				$location = $this->teams['home']->club->shortcode ?? null;
				if ( $location ) {
					$this->set_location( $location );
				}
			}
		} elseif ( 'away' === $this->host ) {
			if ( is_numeric( $this->away_team ) && '-1' !== $this->away_team ) {
				$this_day  = $this->teams['away']->match_day ?? null;
				$this_time = $this->teams['away']->match_time ?? null;
				$this->set_match_date( $this->date, $this_day, $this_time );
				$location = $this->teams['away']->club->shortcode ?? null;
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
	 *
	 * @return void
	 */
	public function set_match_date( string $start_date, string $match_day, string $match_time ): void {
		if ( strlen( $start_date ) > 10 ) {
			$start_date = substr( $start_date, 0, 10 );
		}
		if ( ! empty( $match_day ) ) {
			$day = Racketmanager_Util::get_match_day_number( $match_day );
			if ( ! empty( $match_time ) ) {
				$match_date = Racketmanager_Util::amend_date( $start_date, $day );
				$match_date = $match_date . ' ' . $match_time;
				$this->update_match_date( $match_date );
			}
		}
	}
	/**
	 * Update match date function
	 *
	 * @param string $match_date original match date.
	 * @param string|null $original_date original match date (optional).
	 *
	 * @return object
	 */
	public function update_match_date( string $match_date, string $original_date = null ): object {
		global $wpdb;
		if ( ! empty( $match_date ) ) {
			$this->set_match_date_in_db( $match_date );
			if ( ! empty( $original_date ) && empty( $this->date_original ) ) {
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE $wpdb->racketmanager_matches SET `date_original` = %s WHERE `id` = %d",
						$original_date,
						$this->id
					)
				);
				$this->date_original = $original_date;
			}
			wp_cache_set( $this->id, $this, 'matches' );
			if ( $this->num_rubbers ) {
				$rubbers = $this->get_rubbers();
				foreach ( $rubbers as $rubber ) {
					$rubber       = get_rubber( $rubber );
					$rubber->date = $match_date;
					$rubber->update_date();
				}
			}
			if ( ! empty( $this->date_original ) ) {
				$this->notify_date_change();
			}
		}
		return $this;
	}
	/**
	 * Set match date in db function
	 *
	 * @param string $match_date match date.
	 */
	public function set_match_date_in_db( string $match_date ): void {
		global $wpdb;
		$this->date = $match_date;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_matches SET `date` = %s WHERE `id` = %d",
				$this->date,
				$this->id
			)
		);
		wp_cache_set( $this->id, $this, 'matches' );
	}
	/**
	 * Set location function
	 *
	 * @param string $location match location.
	 *
	 * @return void
	 */
	public function set_location( string $location ): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_matches SET `location` = %s WHERE `id` = %d",
				$location,
				$this->id
			)
		);
		$this->location = $location;
		wp_cache_set( $this->id, $this, 'matches' );
	}
	/**
	 * Get to email addresses function
	 *
	 * @return array
	 */
	public function get_email_to(): array {
		$to        = array();
		$opponents = array( 'home', 'away' );
		foreach ( $opponents as $opponent ) {
			$team = $this->teams[ $opponent ];
			if ( 'P' === $team->team_type ) {
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
		return $to;
	}
	/**
	 * Notify teams for next round
	 *
	 * @return boolean
	 */
	public function notify_next_match_teams(): bool {
		global $racketmanager;

		if ( ( ( -1 === $this->teams['home']->id || -1 === $this->teams['away']->id ) || ( ! isset( $this->host ) ) ) || ( 'S' === $this->teams['home']->team_type || 'S' === $this->teams['away']->team_type ) ) {
			return false;
		}
		$email_to = $this->get_email_to();
		if ( empty( $email_to ) ) {
			return false;
		}
		$email_from   = $racketmanager->get_confirmation_email( $this->league->event->competition->type );
		$headers      = array();
		$headers[]    = 'From: ' . ucfirst( $this->league->event->competition->type ) . ' Secretary <' . $email_from . '>';
		$headers[]    = 'cc: ' . ucfirst( $this->league->event->competition->type ) . ' Secretary <' . $email_from . '>';
		$message_args = array();
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
		$round_name                       = $this->league->championship->finals[ $this->final_round ]['name'];
		$message_args['round']            = $round_name;
		$message_args['competition_type'] = $this->league->event->competition->type;
		$message_args['emailfrom']        = $email_from;
		$email_message                    = racketmanager_match_notification( $this->id, $message_args );
		$subject                          = __( 'Match Details', 'racketmanager' ) . ' - ' . $round_name;
		if ( ! empty( $this->leg ) ) {
			$subject .= ' - ' . __( 'Leg', 'racketmanager' ) . ' ' . $this->leg;
		}
		$subject .= ' - ' . $this->league->title;
		wp_mail( $email_to, $subject, $email_message, $headers );
		return true;
	}
	/**
	 * Report result
	 *
	 * @param string|null $competition_code competition code (optional).
	 *
	 * @return object|null
	 */
	public function report_result( string $competition_code = null ): object|null {
		global $racketmanager;
		$result = null;
		if ( empty( $competition_code ) ) {
			$competition_season = $this->league->event->competition->seasons[$this->season] ?? null;
			$competition_code   = empty( $competition_season['competition_code'] ) ? $this->league->event->competition->competition_code : $competition_season['competition_code'];
		}
		if ( ! empty( $competition_code ) ) {
			$result                   = new stdClass();
			$result->tournament       = $racketmanager->site_name . ' ' . $this->league->event->competition->name;
			$result->code             = $competition_code;
			$result->organiser        = '';
			$result->venue            = '';
			$result->event_name       = $this->league->event->name;
			$result->grade            = $this->league->event->seasons[$this->season]['grade'] ?? $this->league->event->competition->grade;
			$result->event_end_date   = $this->league->event->competition->date_end;
			$result->event_start_date = $this->league->event->competition->date_start;
			$age_group = match ($this->league->event->age_limit) {
				8, 9, 10, 11, 12, 14, 16, 18, 21               => $this->league->event->age_limit . ' & Under',
				30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85 => $this->league->event->age_limit . ' & Over',
				default                                        => 'Open',
			};
			$result->age_group  = $age_group;
			$result->event_type = 'Singles';
			if ( 'D' === substr( $this->league->event->type, 1, 1 ) ) {
				$result->event_type = 'Doubles';
			}
			if (str_starts_with($this->league->event->type, 'M')) {
				$result->gender = 'Male';
			} elseif (str_starts_with($this->league->event->type, 'W')) {
				$result->gender = 'Female';
			} else {
				$result->gender = 'Mixed';
			}
			$result->draw_name = $this->league->title;
			if ( 'league' === $this->league->event->competition->type ) {
				$result->draw_type  = 'Round Robin';
				$result->draw_stage = 'MD - Main draw';
				$result->draw_size  = $this->league->num_teams_total;
				$result->round      = 'RR' . $this->match_day;
			} else {
				$result->draw_type = 'Elimination';
				if ( $this->league_id === $this->league->event->primary_league ) {
					$result->draw_stage = 'MD - Main draw';
				} else {
					$result->draw_stage = 'CD - Consolation draw';
				}
				$result->draw_size = $this->league->championship->num_teams_first_round;
				$result->round = match ($this->final_round) {
					'final'   => 'F',
					'semi'    => 'SF',
					'quarter' => 'QF',
					'last-16' => 'R16',
					'last-32' => 'R32',
					'last-64' => 'R64',
					default => 'RR1',
				};
			}
			$result->matches = array();
			if ( $this->league->num_rubbers ) {
				if ( ! $this->is_cancelled && ! $this->is_shared && ! $this->is_withdrawn ) {
					$rubbers = $this->get_rubbers();
					foreach ( $rubbers as $rubber ) {
						if ( ! $rubber->is_walkover && ! $rubber->is_shared && ! empty( $rubber->winner_id ) && ! empty( $rubber->loser_id ) ) {
							if ( $rubber->is_invalid ) {
								$score_home = 0;
								$score_away = 0;
								foreach ( $rubber->sets as $set ) {
									if ( $set['player1'] > $set['player2'] ) {
										++$score_home;
									} elseif ( $set['player2'] > $set['player1'] ) {
										++$score_away;
									}
								}
								if ( $score_home > $score_away ) {
									$winner_id = $this->home_team;
								} elseif ( $score_away > $score_home ) {
									$winner_id = $this->away_team;
								} else {
									$winner_id = null;
								}
							} else {
								$winner_id = $rubber->winner_id;
							}
							$result_match        = new stdClass();
							$result_match->match = $rubber->id;
							if ( $winner_id === $this->home_team ) {
								$winning_team   = 'home';
								$winning_player = 'player1';
								$losing_team    = 'away';
								$losing_player  = 'player2';
							} else {
								$winning_team   = 'away';
								$winning_player = 'player2';
								$losing_team    = 'home';
								$losing_player  = 'player1';
							}
							$result_match->winner_name   = $rubber->players[ $winning_team ]['1']->display_name;
							$result_match->winner_lta_no = $rubber->players[ $winning_team ]['1']->btm;
							$result_match->loser_name    = $rubber->players[ $losing_team ]['1']->display_name;
							$result_match->loser_lta_no  = $rubber->players[ $losing_team ]['1']->btm;
							if ( 'D' === substr( $this->league->event->type, 1, 1 ) ) {
								$result_match->winnerpartner        = $rubber->players[ $winning_team ]['2']->display_name;
								$result_match->winnerpartner_lta_no = $rubber->players[ $winning_team ]['2']->btm;
								$result_match->loserpartner         = $rubber->players[ $losing_team ]['2']->display_name;
								$result_match->loserpartner_lta_no  = $rubber->players[ $losing_team ]['2']->btm;
							}
							$result_match->score      = '';
							$result_match->score_code = '';
							if ( $rubber->is_retired ) {
								$result_match->score_code = 'Retired';
							}
							$result_match->match_date = mysql2date( 'Y-m-d', $this->match_date );
							$result_match             = $this->report_result_scores( $result_match, $rubber->sets, $winning_player, $losing_player );
							$result->matches[]        = $result_match;
						}
					}
				}
			} else {
				$result_match = new stdClass();
				if ( ! $this->is_walkover && '-1' !== $this->home_team && '-1' !== $this->away_team ) {
					$result_match->match = $this->id;
					if ( $this->winner_id === $this->home_team ) {
						$winning_team   = 'home';
						$winning_player = 'player1';
						$losing_team    = 'away';
						$losing_player  = 'player2';
					} else {
						$winning_team   = 'away';
						$winning_player = 'player2';
						$losing_team    = 'home';
						$losing_player  = 'player1';
					}
					$result_match->winner_name          = $this->teams[ $winning_team ]->players['1']->display_name;
					$result_match->winner_lta_no        = $this->teams[ $winning_team ]->players['1']->btm;
					$result_match->loser_name           = $this->teams[ $losing_team ]->players['1']->display_name;
					$result_match->loser_lta_no         = $this->teams[ $losing_team ]->players['1']->btm;
					$result_match->winnerpartner        = '';
					$result_match->winnerpartner_lta_no = '';
					$result_match->loserpartner         = '';
					$result_match->loserpartner_lta_no  = '';
					if ( 'D' === substr( $this->league->event->type, 1, 1 ) ) {
						$result_match->winnerpartner        = $this->teams[ $winning_team ]->players['2']->display_name;
						$result_match->winnerpartner_lta_no = $this->teams[ $winning_team ]->players['2']->btm;
						$result_match->loserpartner         = $this->teams[ $losing_team ]->players['2']->display_name;
						$result_match->loserpartner_lta_no  = $this->teams[ $losing_team ]->players['2']->btm;
					}
					$result_match->score      = '';
					$result_match->match_date = mysql2date( 'Y-m-d', $this->match_date );
					$result_match             = $this->report_result_scores( $result_match, $this->sets, $winning_player, $losing_player );
					$result_match->score_code = '';
					if ( $this->is_retired ) {
						$result_match->score_code = 'R';
					} elseif ( $this->is_walkover || empty( $result_match->score ) ) {
						$result_match->score_code = 'W';
					} elseif ( $this->is_shared || $this->is_cancelled ) {
						$result_match->score_code = 'N';
					}
					$result->matches[] = $result_match;
				}
			}
		}
		return $result;
	}
	/**
	 * Produce scores for reporting results
	 *
	 * @param object $result_match match result object.
	 * @param array $sets sets.
	 * @param string $winning_player winning player reference.
	 * @param string $losing_player losing player reference.
	 *
	 * @return object updated result_match object.
	 */
	private function report_result_scores( object $result_match, array $sets, string $winning_player, string $losing_player ): object {
		for ( $s = 1; $s <= 5; $s++ ) {
			$team1set = 'set' . $s . 'team1';
			$team2set = 'set' . $s . 'team2';
			$tiebreak = 'tiebreak' . $s;
			if ( ! empty( ( $sets[ $s ][ $winning_player ] ) ) || ! empty( $sets[ $s ][ $losing_player ] ) ) {
				$set = $sets[ $s ];
				if ( $s > 1 ) {
					$result_match->score .= ' ';
				}
				$match_tiebreak = false;
				if ( ( isset( $set['settype'] ) && 'MTB' === $set['settype'] ) || ( 3 === $s && '1' === $set[ $winning_player ] && '0' === $set[ $losing_player ] ) ) {
					$result_match->score .= '[';
					$match_tiebreak       = true;
				}
				if ( $match_tiebreak && ( empty( $set['settype'] ) || 'MTB' !== $set['settype'] ) ) {
					$set[ $winning_player ] = 10;
					$set[ $losing_player ]  = 8;
				}
				if ( '7' === $set[ $winning_player ] && '6' === $set[ $losing_player ] && empty( $set['tiebreak'] ) ) {
					$set['tiebreak'] = 5;
				}
				$result_match->score .= $set[ $winning_player ] . '-' . $set[ $losing_player ];
				if ( ! empty( $set['tiebreak'] ) ) {
					$result_match->score    .= '(' . $set['tiebreak'] . ')';
					$result_match->$tiebreak = $set['tiebreak'];
				} else {
					$result_match->$tiebreak = '';
				}
				if ( $match_tiebreak ) {
					$result_match->score .= ']';
				}
				$result_match->$team1set = $set[ $winning_player ];
				$result_match->$team2set = $set[ $losing_player ];
			} else {
				$result_match->$team1set = '';
				$result_match->$team2set = '';
				$result_match->$tiebreak = '';
			}
		}
		return $result_match;
	}
	/**
	 * Set match comments
	 *
	 * @param array $comments match comments.
	 */
	public function set_comments( array $comments ): void {
		global $wpdb;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_matches SET `comments` = %s WHERE `id` = %d",
				maybe_serialize( $comments ),
				$this->id
			)
		);
		$this->comments = $comments;
		wp_cache_set( $this->id, $this, 'matches' );
	}
	/**
	 * Set match status
	 *
	 * @param int $status match status.
	 */
	public function set_status( int $status ): void {
		global $wpdb;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_matches SET `status` = %s WHERE `id` = %d",
				$status,
				$this->id
			)
		);
		$this->status = $status;
		wp_cache_set( $this->id, $this, 'matches' );
	}
	/**
	 * Notify date change occurred
	 *
	 * @return boolean
	 */
	public function notify_date_change(): bool {
		global $racketmanager;

		if ( -1 === $this->teams['home']->id || -1 === $this->teams['away']->id ) {
			return false;
		}
		$email_to = $this->get_email_to();
		if ( empty( $email_to ) ) {
			return false;
		}
		$delay        = false;
		$email_from   = $racketmanager->get_confirmation_email( $this->league->event->competition->type );
		$headers      = array();
		$headers[]    = 'From: ' . wp_get_current_user()->display_name . ' <' . $email_from . '>';
		$headers[]    = 'cc: ' . ucfirst( $this->league->event->competition->type ) . ' Secretary <' . $email_from . '>';
		$message_args = array();
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
		} elseif ( 'league' === $this->league->event->competition->type ) {
			$message_args['competition'] = $this->league->event->competition->name;
		}
		if ( $this->league->is_championship ) {
			$round_name = $this->league->championship->finals[ $this->final_round ]['name'];
		} else {
			$round_name = null;
		}
		$message_args['match']            = $this->id;
		$message_args['round']            = $round_name;
		$message_args['new_date']         = $this->date;
		$message_args['original_date']    = $this->date_original;
		$message_args['competition_type'] = $this->league->event->competition->type;
		$message_args['emailfrom']        = $email_from;
		if ( $this->league->event->competition->is_tournament && $this->date > $this->date_original ) {
			$message_args['delay'] = true;
			$delay                 = true;
		}
		$subject = __( 'Match Date Change', 'racketmanager' );
		if ( $delay ) {
			$subject .= ' ' . __( 'DELAY', 'racketmanager' );
		}
		if ( $round_name ) {
			$subject .= ' - ' . $round_name;
		}
		if ( ! empty( $this->leg ) ) {
			$subject .= ' - ' . __( 'Leg', 'racketmanager' ) . ' ' . $this->leg;
		}
		$subject                      .= ' - ' . $this->league->title;
		$message_args['email_subject'] = $subject;
		$email_message                 = racketmanager_match_date_change_notification( $this->id, $message_args );
		wp_mail( $email_to, $subject, $email_message, $headers );
		return true;
	}
	/**
	 * Check whether match update allowed function
	 *
	 * @return object
	 */
	public function is_update_allowed(): object {
		global $racketmanager;
		$home_team           = $this->teams['home'];
		$away_team           = $this->teams['away'];
		$competition_type    = $this->league->event->competition->type;
		$result_status       = $this->confirmed;
		$user_can_update     = false;
		$user_type           = '';
		$user_team           = '';
		$message             = '';
		$match_approval_mode = false;
		$match_update        = false;
		if ( is_user_logged_in() ) {
			$userid = get_current_user_id();
			if ( $userid ) {
				if ( current_user_can( 'manage_racketmanager' ) ) {
					$user_type       = 'admin';
					$user_can_update = true;
					if ( 'P' === $result_status ) {
						$match_update = true;
					}
				} elseif ( empty( $home_team ) || empty( $away_team ) || empty( $home_team->club_id ) || empty( $away_team->club_id ) ) {
					$message = 'notTeamSet';
				} else {
					if ( isset( $home_team->club->matchsecretary ) && intval( $home_team->club->matchsecretary ) === $userid ) {
						$user_type = 'matchsecretary';
						$user_team = 'home';
					} elseif ( isset( $away_team->club->matchsecretary ) && intval( $away_team->club->matchsecretary ) === $userid ) {
						$user_type = 'matchsecretary';
						$user_team = 'away';
					} elseif ( isset( $home_team->captain_id ) && intval( $home_team->captain_id ) === $userid ) {
						$user_type = 'captain';
						$user_team = 'home';
					} elseif ( isset( $away_team->captain_id ) && intval( $away_team->captain_id ) === $userid ) {
						$user_type = 'captain';
						$user_team = 'away';
					} else {
						$message = 'notCaptain';
					}
					$options          = $racketmanager->get_options();
					$match_capability = $options[ $competition_type ]['matchCapability'];
					$result_entry     = $options[ $competition_type ]['resultEntry'];
					if ( 'none' === $match_capability ) {
						$message = 'noMatchCapability';
					} elseif ( 'captain' === $match_capability ) {
						if ( 'captain' === $user_type || 'matchsecretary' === $user_type ) {
							if ( 'home' === $user_team ) {
								if ( 'P' === $result_status || $this->is_pending ) {
									$user_can_update = true;
									$match_update    = true;
								}
							} elseif ( 'away' === $user_team && 'home' === $result_entry ) {
								if ( 'P' === $result_status ) {
									$user_can_update     = true;
									$match_approval_mode = true;
								}
							} elseif ( 'either' === $result_entry ) {
								$user_can_update = true;
							}
						}
					} elseif ( 'player' === $match_capability ) {
						if ( 'captain' === $user_type || 'matchsecretary' === $user_type ) {
							if ( 'either' === $result_entry || 'home' === $user_team || ( 'away' === $user_team && 'home' === $result_entry ) ) {
								if ( 'P' === $result_status ) {
									$user_can_update = true;
									if ( 'home' === $user_team ) {
										if ( empty( $this->away_captain ) ) {
											$match_update = true;
										} elseif ( empty( $this->home_captain ) ) {
											$match_approval_mode = true;
										}
									} elseif ( 'away' === $user_team ) {
										if ( empty( $this->home_captain ) ) {
											$match_update = true;
										} elseif ( empty( $this->away_captain ) ) {
											$match_approval_mode = true;
										}
									}
								} elseif ( $this->is_pending ) {
									$user_can_update = true;
								}
							}
						} else {
							$club             = get_club( $home_team->club_id );
							$home_club_player = $club->get_players(
								array(
									'count'  => true,
									'player' => $userid,
									'active' => true,
								)
							);
							if ( $home_club_player ) {
								$user_type = 'player';
								$user_team = 'home';
							}
							$club             = get_club( $away_team->club_id );
							$away_club_player = $club->get_players(
								array(
									'count'  => true,
									'player' => $userid,
									'active' => true,
								)
							);
							if ( $away_club_player ) {
								$user_type = 'player';
								if ( 'home' === $user_team ) {
									$user_team = 'both';
								} else {
									$user_team = 'away';
								}
							}
							if ( $user_team ) {
								if ( 'home' === $result_entry ) {
									if ( $this->is_pending ) {
										if ( 'home' === $user_team || 'both' === $user_team ) {
											$user_can_update = true;
										}
									} elseif ( 'P' === $result_status ) {
										if ( 'away' === $user_team || 'both' === $user_team ) {
											$user_can_update     = true;
											$match_approval_mode = true;
										}
									}
								} elseif ( 'either' === $result_entry ) {
									if ( 'P' === $result_status ) {
										if ( 'home' === $user_team || 'both' === $user_team ) {
											if ( empty( $this->home_captain ) ) {
												$user_can_update     = true;
												$match_approval_mode = true;
											} elseif ( $this->home_captain === $userid ) {
												$user_can_update = true;
											}
										} elseif ( 'away' === $user_team ) {
											if ( empty( $this->away_captain ) ) {
												$user_can_update     = true;
												$match_approval_mode = true;
											} elseif ( $this->away_captain === $userid ) {
												$user_can_update = true;
											}
										}
									} elseif ( $this->is_pending ) {
										$user_can_update = true;
									}
								}
							} else {
								$message = 'notTeamPlayer';
							}
						}
					}
				}
			} else {
				$message = 'notLoggedIn';
			}
		} else {
			$message = 'notLoggedIn';
		}
		$return                      = new stdClass();
		$return->user_can_update     = $user_can_update;
		$return->user_type           = $user_type;
		$return->user_team           = $user_team;
		$return->message             = $message;
		$return->match_approval_mode = $match_approval_mode;
		$return->match_update        = $match_update;
		return $return;
	}
	/**
	 * Update league with results of match
	 *
	 * @return object
	 */
	public function update_league_with_result(): object {
		$return                   = new stdClass();
		$league                   = get_league( $this->league_id );
		$matches[ $this->id ]     = $this->id;
		$home_points[ $this->id ] = $this->home_points;
		$away_points[ $this->id ] = $this->away_points;
		if ( $league->is_championship ) {
			if ( ! empty( $this->final_round ) ) {
				$round_data = $league->championship->get_finals( $this->final_round );
				$round      = $round_data['round'];
				$league->championship->update_final_results( $matches, $home_points, $away_points, array(), $round, $this->season );
				$return->msg     = __( 'Match saved and draw updated', 'racketmanager' );
				$return->updated = true;
			} else {
				$return->msg     = __( 'No round specified', 'racketmanager' );
				$return->updated = false;
			}
		} else {
			$league->update_standings( $this->season );
			$return->msg     = __( 'Result saved and league updated', 'racketmanager' );
			$return->updated = true;
		}
		return $return;
	}
	/**
	 * Reset match result function
	 */
	public function reset_result(): void {
		global $wpdb;
		if ( ! empty( $this->num_rubbers ) ) {
			$rubbers = $this->get_rubbers();
			foreach ( $rubbers as $rubber ) {
				$rubber->reset_result();
			}
		} else {
			$this->sets = null;
		}
		$this->home_points  = null;
		$this->away_points  = null;
		$this->winner_id    = '0';
		$this->loser_id     = '0';
		$this->status       = null;
		$this->custom       = array();
		$this->confirmed    = null;
		$this->home_captain = null;
		$this->away_captain = null;
		$wpdb->query(  //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_matches SET `home_points` = null, `away_points` = null, `winner_id` = %d, `loser_id` = %d, `custom` = %s, `updated_user` = %d, `updated` = now(), `confirmed` = null, `status` = null, `home_captain` = null, `away_captain` = null, `home_points_tie` = null, `away_points_tie` = null, `winner_id_tie` = null, `loser_id_tie` = null WHERE `id` = %d",
				$this->winner_id,
				$this->loser_id,
				maybe_serialize( $this->custom ),
				get_current_user_id(),
				$this->id
			)
		);
		if ( ! empty( $this->leg ) && 2 === $this->leg ) {
			$this->home_points_tie = null;
			$this->away_points_tie = null;
			$this->winner_id_tie   = null;
			$this->loser_id_tie    = null;
		}
		$this->home_score   = null;
		$this->away_score   = null;
		$this->score        = '';
		$this->is_walkover  = false;
		$this->is_shared    = false;
		$this->is_retired   = false;
		$this->is_abandoned = false;
		$this->is_cancelled = false;
		$this->is_withdrawn = false;
		$this->is_pending   = true;
		wp_cache_set( $this->id, $this, 'match' );
		$this->delete_result_check();
		if ( 'league' === $this->league->event->competition->type ) {
			$this->league->update_standings( $this->season );
		} elseif ( 'final' !== $this->final_round ) {
			$this_round            = $this->league->championship->get_finals( $this->final_round );
			$this_round_no         = $this_round['round'];
			$next_round_no         = $this_round_no + 1;
			$next_round            = $this->league->championship->get_final_keys( $next_round_no );
			$match_args['final']   = $this->final_round;
			$match_args['orderby'] = array( 'id' => 'ASC' );
			if ( empty( $this->leg ) || 2 === $this->leg ) {
				if ( ! empty( $this->leg ) ) {
					$match_args['leg'] = $this->leg;
				}
				$current_round_matches = $this->league->get_matches( $match_args );
				$i                     = 0;
				$found                 = false;
				foreach ( $current_round_matches as $match ) {
					if ( $this->id === $match->id ) {
						$found = true;
						break;
					}
					++$i;
				}
				if ( $found ) {
					$next_round_match_no = floor( $i / 2 );
					$match_args['final'] = $next_round;
					if ( ! empty( $this->leg ) ) {
						$match_args['leg'] = 1;
					}
					$next_round_details = $this->league->seasons[$this->season]['rounds'][$next_round] ?? null;
					$next_round_matches = $this->league->get_matches( $match_args );
					if ( $next_round_matches ) {
						$next_round_match = $next_round_matches[ $next_round_match_no ];
						if ( $next_round_match ) {
							if ( $next_round_match->is_pending ) {
								$team_ref = $i + 1;
								$new_team = '1_' . $this->final_round . '_' . $team_ref;
								if ( $i & 1 ) {
									$next_round_match->away_team = $new_team;
								} else {
									$next_round_match->home_team = $new_team;
								}
									$next_round_match->set_teams( $next_round_match->home_team, $next_round_match->away_team );
								if ( $this->league->event->competition->is_cup && ! empty( $next_round_details->date ) ) {
									$reset_date = true;
									$next_round_match->set_match_date( $next_round_details->date, 'monday', implode( ':', $this->league->event->competition->default_match_start_time ) );
								} else {
									$reset_date = false;
								}
								if ( ! empty( $next_round_match->linked_match ) ) {
									$linked_match = get_match( $next_round_match->linked_match );
									if ( $linked_match ) {
										$linked_match->set_teams( $next_round_match->home_team, $next_round_match->away_team );
										if ( $reset_date ) {
											$linked_match_date = gmdate( 'Y-m-d H:i:s', strtotime( $next_round_details->date . ' +14 day' ) );
											$linked_match->set_match_date( $linked_match_date, 'monday', implode( ':', $this->league->event->competition->default_match_start_time ) );
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
	/**
	 * Set date result entered
	 */
	public function set_result_entered(): void {
		global $wpdb;
		$this->date_result_entered = date( 'Y-m-d H:i:s' );
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_matches SET `date_result_entered` = %s WHERE `id` = %d",
				$this->date_result_entered,
				$this->id
			)
		);
		wp_cache_set( $this->id, $this, 'matches' );
	}
	/**
	 * Result notification
	 *
	 * @param string $match_status match status.
	 * @param string $match_message match message.
	 * @param false|string $match_updated_by match updated by value.
	 */
	public function result_notification( string $match_status, string $match_message, false|string $match_updated_by = false ): void {
		global $racketmanager;
		$admin_email           = $racketmanager->get_confirmation_email( $this->league->event->competition->type );
		$rm_options            = $racketmanager->get_options();
		$result_notification   = $rm_options[ $this->league->event->competition->type ]['resultNotification'];
		$confirmation_required = $rm_options[ $this->league->event->competition->type ]['confirmationRequired'];
		if ( $admin_email ) {
			$message_args               = array();
			$message_args['email_from'] = $admin_email;
			$message_args['league']     = $this->league->id;
			if ( $this->league->is_championship ) {
				$message_args['round'] = $this->final_round;
			} else {
				$message_args['matchday'] = $this->match_day;
			}
			$headers            = array();
			$confirmation_email = '';
			if ( 'P' === $match_status ) {
				if ( 'home' === $match_updated_by ) {
					if ( 'captain' === $result_notification ) {
						$confirmation_email = $this->teams['away']->contactemail;
					} elseif ( 'secretary' === $result_notification ) {
						$club               = get_club( $this->teams['away']->club_id );
						$confirmation_email = $club->match_secretary_email ?? '';
					}
				} elseif ( 'captain' === $result_notification ) {
					$confirmation_email = $this->teams['home']->contactemail;
				} elseif ( 'secretary' === $result_notification ) {
					$club               = get_club( $this->teams['away']->club_id );
					$confirmation_email = $club->match_secretary_email ?? '';
				}
			}
			$subject = $racketmanager->site_name . ' - ' . $this->league->title . ' - ' . $this->match_title . ' - ' . $match_message;
			if ( $confirmation_email ) {
				$email_to  = $confirmation_email;
				$headers[] = $racketmanager->get_from_user_email();
				$headers[] = 'cc: ' . ucfirst( $this->league->event->competition->type ) . ' Secretary <' . $admin_email . '>';
				if ( $confirmation_required ) {
					$subject .= ' - ' . __( 'Confirmation required', 'racketmanager' );
				}
				$message_args['confirmation_required'] = $confirmation_required;
				$message                               = racketmanager_captain_result_notification( $this->id, $message_args );
			} else {
				$email_to  = $admin_email;
				$headers[] = $racketmanager->get_from_user_email();
				if ( 'Y' === $match_status ) {
					if ( $this->has_result_check() ) {
						$message_args['errors'] = true;
						$subject               .= ' - ' . __( 'Check results', 'racketmanager' );
					} else {
						$message_args['complete'] = true;
						$subject                 .= ' - ' . __( 'Match complete', 'racketmanager' );
					}
				}
				$message = racketmanager_result_notification( $this->id, $message_args );
			}
			wp_mail( $email_to, $subject, $message, $headers );
		}
	}
	/**
	 * Chase match results
	 *
	 * @param false|string $time_period time Period that result is overdue.
	 *
	 * @return boolean $message_sent Indicator to show if message was sent.
	 */
	public function chase_match_result( false|string $time_period = false ): bool {
		global $racketmanager;
		$message_sent                = false;
		$headers                     = array();
		$from_email                  = $racketmanager->get_confirmation_email( $this->league->event->competition->type );
		$headers[]                   = 'From: ' . ucfirst( $this->league->event->competition->type ) . ' Secretary <' . $from_email . '>';
		$headers[]                   = 'cc: ' . ucfirst( $this->league->event->competition->type ) . ' Secretary <' . $from_email . '>';
		$message_args                = array();
		$message_args['time_period'] = $time_period;
		$message_args['from_email']  = $from_email;

		$email_subject = __( 'Match result pending', 'racketmanager' ) . ' - ' . $this->get_title() . ' - ' . $this->league->title;
		$email_to      = array();
		if ( $this->league->event->competition->is_tournament ) {
			$opponents = array( 'home', 'away' );
			foreach ( $opponents as $opponent ) {
				$players = $this->teams[$opponent]->players ?? array();
				foreach ( $players as $player ) {
					if ( ! empty( $player->email ) ) {
						$email_to[] = $player->fullname . '<' . $player->email . '>';
					}
				}
			}
		} else {
			$email_to[] = $this->teams['home']->captain . ' <' . $this->teams['home']->contactemail . '>';
			$club       = get_club( $this->teams['home']->club_id );
			if ( isset( $club->match_secretary_email ) ) {
				$headers[] = 'cc: ' . $club->match_secretary_name . ' <' . $club->match_secretary_email . '>';
			}
		}
		if ( $email_to ) {
			$email_message = racketmanager_result_outstanding_notification( $this->id, $message_args );
			wp_mail( $email_to, $email_subject, $email_message, $headers );
			$message_sent = true;
		}
		return $message_sent;
	}
	/**
	 * Chase match approval
	 *
	 * @param false|string $time_period time Period that match result confirmation is overdue.
	 * @param boolean $override Override indicator.
	 *
	 * @return boolean $message_sent Indicator to show if message was sent.
	 */
	public function chase_match_approval( false|string $time_period = false, bool $override = false ): bool {
		global $racketmanager;
		$rm_options                            = $racketmanager->get_options();
		$confirmation_required                 = $rm_options[ $this->league->event->competition->type ]['confirmationRequired'];
		$from_email                            = $racketmanager->get_confirmation_email( $this->league->event->competition->type );
		$message_args                          = array();
		$message_args['outstanding']           = true;
		$message_args['time_period']           = $time_period;
		$message_args['override']              = $override;
		$message_args['from_email']            = $from_email;
		$message_args['confirmation_required'] = $confirmation_required;
		$email_message                         = racketmanager_captain_result_notification( $this->id, $message_args );
		$msg_end                               = 'approval pending';
		if ( $override ) {
			$msg_end = 'complete';
		}
		$message_sent  = false;
		$headers       = array();
		$headers[]     = 'From: ' . ucfirst( $this->league->event->competition->type ) . ' Secretary <' . $from_email . '>';
		$headers[]     = 'cc: ' . ucfirst( $this->league->event->competition->type ) . ' Secretary <' . $from_email . '>';
		$email_subject = $racketmanager->site_name . ' - ' . $this->league->title . ' - ' . $this->get_title() . ' ' . $msg_end;
		$email_to      = '';
		if ( isset( $this->home_captain ) ) {
			if ( isset( $this->teams['away']->contactemail ) ) {
				$email_to = $this->teams['away']->captain . ' <' . $this->teams['away']->contactemail . '>';
				$club     = get_club( $this->teams['away']->club_id );
				if ( isset( $club->match_secretary_email ) ) {
					$headers[] = 'cc: ' . $club->match_secretary_name . ' <' . $club->match_secretary_email . '>';
				}
			}
		} elseif ( isset( $this->away_captain ) ) {
			if ( isset( $this->teams['home']->contactemail ) ) {
				$email_to = $this->teams['home']->captain . ' <' . $this->teams['home']->contactemail . '>';
				$club     = get_club( $this->teams['home']->club_id );
				if ( isset( $club->match_secretary_email ) ) {
					$headers[] = 'cc: ' . $club->match_secretary_name . ' <' . $club->match_secretary_email . '>';
				}
			}
		}
		if ( ! empty( $email_to ) ) {
			wp_mail( $email_to, $email_subject, $email_message, $headers );
			$message_sent = true;
		}
		return $message_sent;
	}
	/**
	 * Complete match result
	 *
	 * @param int $confirmation_timeout time Period that match result confirmation is overdue.
	 *
	 * @return int number of matches completed.
	 */
	public function complete_result( int $confirmation_timeout ): int {
		$this->chase_match_approval( $confirmation_timeout, 'override' );
		$league = get_league( $this->league_id );
		$league->set_finals( false );
		$result_matches               = array();
		$home_points                  = array();
		$away_points                  = array();
		$custom                       = array();
		$result_matches[ $this->id ] = $this->id;
		$home_points[ $this->id ]    = $this->home_points;
		$away_points[ $this->id ]    = $this->away_points;
		$custom[ $this->id ]         = $this->custom;
		return $league->update_match_results( $result_matches, $home_points, $away_points, $custom, $this->season );
	}
	/**
	 * Set confirmed status
	 */
	public function set_confirmed(): void {
		global $wpdb;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_matches SET `confirmed` = %s WHERE `id` = %d",
				$this->confirmed,
				$this->id
			)
		);
		wp_cache_set( $this->id, $this, 'matches' );
	}
}
