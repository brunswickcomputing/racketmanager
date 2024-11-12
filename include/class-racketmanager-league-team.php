<?php
/**
 * Racketmanager_League_Team API: League Team class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage League_Team
 */

namespace Racketmanager;

/**
 * Class to implement the League_Team object
 */
final class Racketmanager_League_Team {


	/**
	 * Number of done matches
	 *
	 * @var int
	 */
	public $done_matches = 0;

	/**
	 * Number of won matches
	 *
	 * @var int
	 */
	public $won_matches = 0;

	/**
	 * Number of draw matches
	 *
	 * @var int
	 */
	public $draw_matches = 0;

	/**
	 * Number of lost matches
	 *
	 * @var int
	 */
	public $lost_matches = 0;

	/**
	 * Percentage of won matches
	 *
	 * @var float
	 */
	public $win_percent = 0;

	/**
	 * Retrieve team instance
	 *
	 * @param int $team_id
	 */

	/**
	 * Win percent multiplication factor
	 *
	 * @var int
	 */
	private $pct_mult = 1;
	/**
	 * Home variable
	 *
	 * @var boolean
	 */
	public $home;
	/**
	 * Team id variable
	 *
	 * @var int
	 */
	public $id;
	/**
	 * Table id variable
	 *
	 * @var int
	 */
	public $table_id;
	/**
	 * Title variable
	 *
	 * @var string
	 */
	public $title;
	/**
	 * Stadium variable
	 *
	 * @var string
	 */
	public $stadium;
	/**
	 * Profile variable
	 *
	 * @var string
	 */
	public $profile;
	/**
	 * Status variable
	 *
	 * @var string
	 */
	public $status;
	/**
	 * Status text variable
	 *
	 * @var string
	 */
	public $status_text;
	/**
	 * Status icon variable
	 *
	 * @var string
	 */
	public $status_icon;
	/**
	 * Club variable
	 *
	 * @var int
	 */
	public $affiliatedclub;
	/**
	 * Club name variable
	 *
	 * @var string
	 */
	public $affiliatedclubname;
	/**
	 * Club object variable
	 *
	 * @var object
	 */
	public $club;
	/**
	 * Positive points variable
	 *
	 * @var int
	 */
	public $points_plus;
	/**
	 * Additional points variable
	 *
	 * @var int
	 */
	public $add_points;
	/**
	 * Points variable
	 *
	 * @var int
	 */
	public $points;
	/**
	 * Sets won variable
	 *
	 * @var int
	 */
	public $sets_won;
	/**
	 * Sets lost variable
	 *
	 * @var int
	 */
	public $sets_allowed;
	/**
	 * Games won variable
	 *
	 * @var int
	 */
	public $games_won;
	/**
	 * Games lost variable
	 *
	 * @var int
	 */
	public $games_allowed;
	/**
	 * Points minus variable
	 *
	 * @var int
	 */
	public $points_minus;
	/**
	 * Secondary points variable
	 *
	 * @var int
	 */
	public $points2;
	/**
	 * Secondary points plus variable
	 *
	 * @var int
	 */
	public $points2_plus;
	/**
	 * Secondary points minus variable
	 *
	 * @var int
	 */
	public $points2_minus;
	/**
	 * Points difference variable
	 *
	 * @var int
	 */
	public $diff;
	/**
	 * Roster variable
	 *
	 * @var array
	 */
	public $roster;
	/**
	 * Player variable
	 *
	 * @var id
	 */
	public $player;
	/**
	 * Player id variable
	 *
	 * @var int
	 */
	public $player_id;
	/**
	 * League id variable
	 *
	 * @var int
	 */
	public $league_id;
	/**
	 * Next match variable
	 *
	 * @var object
	 */
	public $next_match;
	/**
	 * Previous match variable
	 *
	 * @var object
	 */
	public $prev_match;
	/**
	 * Season variable
	 *
	 * @var string
	 */
	public $season;
	/**
	 * Custom variable
	 *
	 * @var string
	 */
	public $custom;
	/**
	 * Display class variable
	 *
	 * @var string
	 */
	public $class;
	/**
	 * Captain name variable
	 *
	 * @var string
	 */
	public $captain;
	/**
	 * Formatted points variable
	 *
	 * @var string
	 */
	public $points_formatted;
	/**
	 * Captain id variable
	 *
	 * @var int
	 */
	public $captain_id;
	/**
	 * Contact number variable
	 *
	 * @var string
	 */
	public $contactno;
	/**
	 * Content email variable
	 *
	 * @var string
	 */
	public $contactemail;
	/**
	 * Match day variable
	 *
	 * @var string
	 */
	public $match_day;
	/**
	 * Match time variable
	 *
	 * @var string
	 */
	public $match_time;
	/**
	 * Old rank
	 *
	 * @var int
	 */
	public $old_rank;
	/**
	 * Team ref variable
	 *
	 * @var string
	 */
	public $team_ref;
	/**
	 * Team type variable
	 *
	 * @var string
	 */
	public $team_type;
	/**
	 * Is withdrawn variable
	 *
	 * @var boolean
	 */
	public $is_withdrawn;
	/**
	 * Players variable
	 *
	 * @var array
	 */
	public $players = array();
	/**
	 * Get instance function
	 *
	 * @param int $league_team_id league team id.
	 * @return boolean|object
	 */
	public static function get_instance( $league_team_id ) {
		global $wpdb;
		$league_team_id = (int) $league_team_id;
		if ( ! $league_team_id ) {
			return false;
		}

		$league_team = wp_cache_get( $league_team_id, 'leagueteams' );

		if ( ! $league_team ) {
			$league_team = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT B.`id` AS `id`, B.`title`, B.`affiliatedclub`, B.`stadium`, B.`home`, A.`group`, B.`roster`, B.`profile`, A.`points_plus`, A.`points_minus`, A.`points2_plus`, A.`points2_minus`, A.`add_points`, A.`done_matches`, A.`won_matches`, A.`draw_matches`, A.`lost_matches`, A.`diff`, A.`league_id`, A.`id` AS `table_id`, A.`season`, A.`rank`, A.`status`, A.`custom`, B.`team_type`, A.`rating` FROM {$wpdb->racketmanager_teams} B INNER JOIN {$wpdb->racketmanager_table} A ON B.id = A.team_id WHERE A.`id` = %d LIMIT 1",
					$league_team_id
				)
			); // db call ok.

			if ( ! $league_team ) {
				return false;
			}
			$league_team = new Racketmanager_League_Team( $league_team );

			wp_cache_set( $league_team->id, $league_team, 'leagueteam' );
		}

		return $league_team;
	}

	/**
	 * Constructor
	 *
	 * @param object $league_team Racketmanager_League_Team object.
	 */
	public function __construct( $league_team = null ) {
		if ( ! is_null( $league_team ) ) {
			if ( isset( $league_team->custom ) ) {
				$league_team->custom = stripslashes_deep( (array) maybe_unserialize( $league_team->custom ) );
				$league_team         = (object) array_merge( (array) $league_team, (array) $league_team->custom );
			}

			foreach ( get_object_vars( $league_team ) as $key => $value ) {
				$key        = trim( $key );
				$this->$key = $value;
			}

			$this->title   = htmlspecialchars( stripslashes( $this->title ), ENT_QUOTES );
			$this->stadium = stripslashes( $this->stadium );

			$this->points_plus += $this->add_points; // add or substract extra points.
			$this->points       = array(
				'plus'  => $this->points_plus,
				'minus' => $this->points_minus,
			);
			$this->points2      = array(
				'plus'  => $this->points2_plus,
				'minus' => $this->points2_minus,
			);
			$this->diff         = ( $this->diff > 0 ) ? '+' . $this->diff : $this->diff;
			$this->win_percent();

			$this->profile = intval( $this->profile );

			$this->status_text = Racketmanager_Util::get_standing_status( $this->status );
			if ( ! empty( $this->affiliatedclub ) ) {
				$this->club               = get_club( $this->affiliatedclub );
				$this->affiliatedclubname = $this->club->name;
			} else {
				$this->club               = null;
				$this->affiliatedclubname = null;
			}
			$this->roster = maybe_unserialize( $this->roster );
			if ( 'P' === $this->team_type && null !== $this->roster ) {
				$team          = get_team( $this->id );
				$this->players = $team->players;
				$i             = 1;
				foreach ( $this->players as $player ) {
					$this->player[ $i ]    = $player->fullname;
					$this->player_id[ $i ] = $player->id;
					++$i;
				}
			}
			if ( 'W' === $this->status ) {
				$this->is_withdrawn = true;
			} else {
				$this->is_withdrawn = false;
			}
			$this->status_icon = '';
			if ( '+' === $this->status ) {
				$this->status_icon = 'icon-arrow-up';
			} elseif ( '-' === $this->status ) {
				$this->status_icon = 'icon-arrow-down';
			} elseif ( '=' === $this->status ) {
				$this->status_icon = 'icon-dot';
			}
			if ( strpos( $this->title, '_' ) !== false ) {
				$team_name  = null;
				$name_array = explode( '_', $this->title );
				if ( '1' === $name_array[0] ) {
					$team_name = __( 'Winner of', 'racketmanager' );
				} elseif ( '2' === $name_array[0] ) {
					$team_name = __( 'Loser of', 'racketmanager' );
				}
				if ( ! empty( $team_name ) && is_numeric( $name_array[2] ) ) {
					$match = get_match( $name_array[2] );
					if ( $match ) {
						$team_name .= ' ' . $match->teams['home']->title . ' ' . __( 'vs', 'racketmanager' ) . ' ' . $match->teams['away']->title;
					}
				}
				if ( ! empty( $team_name ) ) {
					$this->team_ref = $this->title;
					$this->title    = $team_name;
				}
			}
		}
	}

	/**
	 * Compute win percentage
	 */
	public function win_percent() {
		$this->win_percent = $this->done_matches > 0 ? round( ( $this->won_matches + $this->draw_matches / 2 ) / $this->done_matches, 3 ) * $this->pct_mult : 0;
	}

	/**
	 * Get next match
	 *
	 * @return Match next Match object
	 */
	public function get_next_match() {
		$league           = get_league( $this->league_id );
		$this->next_match = $league->get_matches(
			array(
				'team_id'     => $this->id,
				'time'        => 'next',
				'limit'       => 1,
				'reset_limit' => true,
			)
		);

		return $this->next_match;
	}


	/**
	 * Get previous match
	 *
	 * @return Match previous Match object
	 */
	public function get_prev_match() {
		$league           = get_league( $this->league_id );
		$this->prev_match = $league->get_matches(
			array(
				'team_id'     => $this->id,
				'time'        => 'prev',
				'limit'       => 1,
				'reset_limit' => true,
			)
		);
		if ( $this->prev_match && '' === $this->prev_match->score ) {
			$this->prev_match->score = 'N/A';
		}
		return $this->prev_match;
	}

	/**
	 * Get last 5 icons for standings table
	 *
	 * @return string
	 */
	public function last5() {
		$league = get_league( $this->league_id );
		$league->set_season();
		ob_start();
		// get last 5 match results.
		$matches = $league->get_matches(
			array(
				'time'             => 'prev',
				'team_id'          => $this->id,
				'match_day'        => -1,
				'limit'            => 5,
				'reset_query_args' => true,
			)
		);
		?>
		<ul class="list--inline list">
			<?php
			foreach ( $matches as $match ) {
				if ( $this->id === $match->winner_id ) {
					$match_status_class = 'winner';
					$match_status_text  = 'W';
				} elseif ( $this->id === $match->loser_id ) {
					$match_status_class = 'loser';
					$match_status_text  = 'L';
				} elseif ( '-1' === $match->winner_id && '-1' === $match->loser_id ) {
					$match_status_class = 'tie';
					$match_status_text  = 'T';
				} else {
					$match_status_class = 'unknown';
					$match_status_text  = '?';
				}
				?>
				<li class="list__item">
					<span class="match__status <?php echo esc_attr( $match_status_class ); ?>"  data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_attr( $match->tooltip_title ); ?>">
						<?php echo esc_html( $match_status_text ); ?>
					</span>
				</li>
				<?php
			}
			?>
		</ul>
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	/**
	 * Get number of finished matches for team
	 *
	 * @return int
	 */
	public function get_num_done_matches() {
		$league = get_league();
		if ( is_null( $league ) ) {
			$league = get_league( $this->league_id );
		}
		$num_matches = $league->get_matches(
			array(
				'count'            => true,
				'team_id'          => $this->id,
				'home_points'      => 'not_empty',
				'away_points'      => 'not_empty',
				'limit'            => false,
				'cache'            => false,
				'match_day'        => -1,
				'reset_query_args' => true,
				'withdrawn'        => false,
				'status'           => array(
					'status_code' => 'Cancelled',
					'compare'     => 'not',
				),
			)
		);
		$num_matches = apply_filters( 'racketmanager_done_matches_' . $league->sport, $num_matches, $this->id, $league->id );

		$this->done_matches = $num_matches;

		// re-compute win percentage.
		$this->win_percent();

		return $num_matches;
	}

	/**
	 * Get number of won matches
	 *
	 * @return int
	 */
	public function get_num_won_matches() {
		$league = get_league();
		if ( is_null( $league ) ) {
			$league = get_league( $this->league_id );
		}
		$num_won = $league->get_matches(
			array(
				'count'            => true,
				'winner_id'        => $this->id,
				'limit'            => false,
				'cache'            => false,
				'match_day'        => -1,
				'reset_query_args' => true,
				'withdrawn'        => false,
				'status'           => array(
					'status_code' => 'Cancelled',
					'compare'     => 'not',
				),
			)
		);
		$num_won = apply_filters( 'racketmanager_won_matches_' . $league->sport, $num_won, $this->id, $league->id );

		$this->won_matches = $num_won;

		// re-compute win percentage.
		$this->win_percent();

		return $num_won;
	}

	/**
	 * Get number of draw matches
	 *
	 * @return int
	 */
	public function get_num_draw_matches() {
		$league = get_league();
		if ( is_null( $league ) ) {
			$league = get_league( $this->league_id );
		}
		$num_draw = $league->get_matches(
			array(
				'count'            => true,
				'team_id'          => $this->id,
				'winner_id'        => -1,
				'loser_id'         => -1,
				'limit'            => false,
				'cache'            => false,
				'match_day'        => -1,
				'reset_query_args' => true,
				'withdrawn'        => false,
				'status'           => array(
					'status_code' => 'Cancelled',
					'compare'     => 'not',
				),
			)
		);
		$num_draw = apply_filters( 'racketmanager_tie_matches_' . $league->sport, $num_draw, $this->id, $league->id );

		$this->draw_matches = $num_draw;

		// re-compute win percentage.
		$this->win_percent();

		return $num_draw;
	}

	/**
	 * Get number of lost matches
	 *
	 * @return int
	 */
	public function get_num_lost_matches() {
		$league = get_league();
		if ( is_null( $league ) ) {
			$league = get_league( $this->league_id );
		}
		$num_lost = $league->get_matches(
			array(
				'count'            => true,
				'loser_id'         => $this->id,
				'limit'            => false,
				'cache'            => false,
				'match_day'        => -1,
				'reset_query_args' => true,
				'withdrawn'        => false,
				'status'           => array(
					'status_code' => 'Cancelled',
					'compare'     => 'not',
				),
			)
		);
		$num_lost = apply_filters( 'racketmanager_lost_matches_' . $league->sport, $num_lost, $this->id, $league->id );

		$this->lost_matches = $num_lost;

		// re-compute win percentage.
		$this->win_percent();

		return $num_lost;
	}
	/**
	 * Set rating
	 *
	 * @param object $team team object.
	 * @param object $event event object.
	 */
	public function set_rating( $team, $event ) {
		global $wpdb;
		if ( ! empty( $team->players ) ) {
			$type        = substr( $event->type, 1, 1 );
			$team_rating = 0;
			foreach ( $team->players as $player ) {
				$rating = $player->rating[ $type ];
				if ( is_numeric( $rating ) ) {
					$team_rating += $rating;
				}
			}
			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_table} SET `rating` = %d WHERE `id` = %d",
					$team_rating,
					$this->table_id
				)
			);
		}
	}
}
