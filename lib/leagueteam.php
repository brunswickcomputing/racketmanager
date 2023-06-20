<?php
/**
 * Team API: League Team class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage LeagueTeam
 */

/**
 * Class to implement the LeagueTeam object
 */
final class LeagueTeam {


	/**
	 * number of done matches
	 *
	 * @var int
	 */
	public $done_matches = 0;

	/**
	 * number of won matches
	 *
	 * @var int
	 */
	public $won_matches = 0;

	/**
	 * number of draw matches
	 *
	 * @var int
	 */
	public $draw_matches = 0;

	/**
	 * number of lost matches
	 *
	 * @var int
	 */
	public $lost_matches = 0;

	/**
	 * percentage of won matches
	 *
	 * @var float
	 */
	public $winPercent = 0;

	/**
	 * retrieve team instance
	 *
	 * @param int $team_id
	 */

	/**
	 * win percent multiplication factor
	 *
	 * @var int
	 */
	private $pct_mult = 1;
	public $home;
	public $id;
	public $title;
	public $stadium;
	public $profile;
	public $status;
	public $affiliatedclub;
	public $affiliatedclubname;
	public $points_plus;
	public $add_points;
	public $points;
	public $sets_won;
	public $sets_allowed;
	public $games_won;
	public $games_allowed;
	public $points_minus;
	public $points2;
	public $points2_plus;
	public $points2_minus;
	public $diff;
	public $roster;
	public $player;
	public $playerId;
	public $league_id;
	public $next_match;
	public $prev_match;
	public $season;
	public $custom;
	public $class;
	public $captain;
	public $pointsFormatted;
	public $captainId;
	public $contactno;
	public $contactemail;
	public $match_day;
	public $match_time;
	public $oldRank;

	public static function get_instance( $leagueTeam_id ) {
		global $wpdb;
		$leagueTeam_id = (int) $leagueTeam_id;
		if ( ! $leagueTeam_id ) {
			return false;
		}

		$leagueTeam = wp_cache_get( $leagueTeam_id, 'leagueteams' );

		if ( ! $leagueTeam ) {
			$leagueTeam = $wpdb->get_row( $wpdb->prepare( "SELECT B.`id` AS `id`, B.`title`, B.`affiliatedclub`, B.`stadium`, B.`home`, A.`group`, B.`roster`, B.`profile`, A.`points_plus`, A.`points_minus`, A.`points2_plus`, A.`points2_minus`, A.`add_points`, A.`done_matches`, A.`won_matches`, A.`draw_matches`, A.`lost_matches`, A.`diff`, A.`league_id`, A.`id` AS `table_id`, A.`season`, A.`rank`, A.`status`, A.`custom` FROM {$wpdb->racketmanager_teams} B INNER JOIN {$wpdb->racketmanager_table} A ON B.id = A.team_id WHERE A.`id` = '%d' LIMIT 1", $leagueTeam_id ) );

			if ( ! $leagueTeam ) {
				return false;
			}
			$leagueTeam = new LeagueTeam( $leagueTeam );

			wp_cache_set( $leagueTeam->id, $leagueTeam, 'leagueteam' );
		}

		return $leagueTeam;
	}

	/**
	 * Constructor
	 *
	 * @param object $leagueTeam LeagueTeam object.
	 */
	public function __construct( $leagueTeam = null ) {
		if ( ! is_null( $leagueTeam ) ) {
			if ( isset( $leagueTeam->custom ) ) {
				$leagueTeam->custom = stripslashes_deep( (array) maybe_unserialize( $leagueTeam->custom ) );
				$leagueTeam         = (object) array_merge( (array) $leagueTeam, (array) $leagueTeam->custom );
			}

			foreach ( get_object_vars( $leagueTeam ) as $key => $value ) {
				$key        = trim( $key );
				$this->$key = $value;
			}

			$this->title   = htmlspecialchars( stripslashes( $this->title ), ENT_QUOTES );
			$this->stadium = stripslashes( $this->stadium );

			$this->points_plus += $this->add_points; // add or substract extra points
			$this->points       = array(
				'plus'  => $this->points_plus,
				'minus' => $this->points_minus,
			);
			$this->points2      = array(
				'plus'  => $this->points2_plus,
				'minus' => $this->points2_minus,
			);
			$this->diff         = ( $this->diff > 0 ) ? '+' . $this->diff : $this->diff;
			$this->winPercent();

			$this->profile = intval( $this->profile );

			$standingStatus = Racketmanager_Util::getStandingStatus();
			if ( isset( $standingStatus[ $this->status ] ) ) {
				$this->status = $standingStatus[ $this->status ];
			}
			$this->affiliatedclubname = get_club( $this->affiliatedclub )->name;
			if ( $this->status == 'P' && $this->roster != null ) {
				$i = 1;
				foreach ( $this->roster as $player ) {
					$teamplayer           = get_player( $player );
					$this->player[ $i ]   = $teamplayer->fullname;
					$this->playerId[ $i ] = $player;
					$i++;
				}
			}
		}
	}

	/**
	 * compute win percentage
	 */
	public function winPercent() {
		$this->winPercent = ( $this->done_matches > 0 ) ? round( ( $this->won_matches + $this->draw_matches / 2 ) / $this->done_matches, 3 ) * $this->pct_mult : 0;
	}

	/**
	 * get next match
	 *
	 * @return Match next Match object
	 */
	public function getNextMatch() {
		$league           = get_league( $this->league_id );
		$this->next_match = $league->getMatches(
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
	 * get previous match
	 *
	 * @return Match previous Match object
	 */
	public function getPrevMatch() {
		$league           = get_league( $this->league_id );
		$this->prev_match = $league->getMatches(
			array(
				'team_id'     => $this->id,
				'time'        => 'prev',
				'limit'       => 1,
				'reset_limit' => true,
			)
		);
		if ( $this->prev_match && $this->prev_match->score == '' ) {
			$this->prev_match->score = 'N/A';
		}
		return $this->prev_match;
	}

	/**
	 * get last 5 icons for standings table. original code by LaMonte Forthun
	 *
	 * @return string
	 */
	public function last5() {
		$league = get_league( $this->league_id );
		$league->setSeason();

		$last5 = '<span>';
		// get next scheduled match
		$next_result = $league->getMatches(
			array(
				'time'             => 'next',
				'team_id'          => $this->id,
				'match_day'        => -1,
				'limit'            => 1,
				'reset_query_args' => true,
			)
		);
		if ( $next_result ) {
			$last5 .= '<span  class="N last5-bg" title="' . $next_result->tooltipTitle . '">&nbsp;</span>';
		} else {
			$last5 .= '<span class="N last5-bg" title="' . __( 'Next Match: No Game Scheduled', 'racketmanager' ) . '">&nbsp;</span>';
		}

		// get last 5 match results
		$last_results = $league->getMatches(
			array(
				'time'             => 'prev',
				'team_id'          => $this->id,
				'match_day'        => -1,
				'limit'            => 5,
				'reset_query_args' => true,
			)
		);
		foreach ( $last_results as $key => $match ) {
			$class = array();
			if ( $this->id == $match->winner_id ) {
				$class[] = 'W';
			} elseif ( $this->id == $match->loser_id ) {
				$class[] = 'L';
			} elseif ( $match->winner_id == -1 && $match->loser_id == -1 ) {
				$class[] = 'D';
			} else {
				$class[] = 'N';
			}

			if ( $key == 2 ) {
				$class[] = 'clear';
			}
			$last5 .= '<span class="' . implode( ' ', $class ) . ' last5-bg" title="' . $match->tooltipTitle . '">&nbsp;</span>';
		}

		$last5 .= '</span>';

		return $last5;
	}

	/**
	 * get number of finished matches for team
	 *
	 * @return int
	 */
	public function getNumDoneMatches() {
		global $wpdb;

		$league = get_league();
		if ( is_null( $league ) ) {
			$league = get_league( $this->league_id );
		}
		$num_matches = $league->getMatches(
			array(
				'count'            => true,
				'team_id'          => $this->id,
				'home_points'      => 'not_empty',
				'away_points'      => 'not_empty',
				'limit'            => false,
				'cache'            => false,
				'match_day'        => -1,
				'reset_query_args' => true,
			)
		);
		$num_matches = apply_filters( 'racketmanager_done_matches_' . $league->sport, $num_matches, $this->id, $league->id );

		$this->done_matches = $num_matches;

		// re-compute win percentage
		$this->winPercent();

		return $num_matches;
	}

	/**
	 * get number of won matches
	 *
	 * @return int
	 */
	public function getNumWonMatches() {
		global $wpdb, $racketmanager;

		$league = get_league();
		if ( is_null( $league ) ) {
			$league = get_league( $this->league_id );
		}
		$num_won = $league->getMatches(
			array(
				'count'            => true,
				'winner_id'        => $this->id,
				'limit'            => false,
				'cache'            => false,
				'match_day'        => -1,
				'reset_query_args' => true,
			)
		);
		$num_won = apply_filters( 'racketmanager_won_matches_' . $league->sport, $num_won, $this->id, $league->id );

		$this->won_matches = $num_won;

		// re-compute win percentage
		$this->winPercent();

		return $num_won;
	}

	/**
	 * get number of draw matches
	 *
	 * @return int
	 */
	public function getNumDrawMatches() {
		global $wpdb;

		$league = get_league();
		if ( is_null( $league ) ) {
			$league = get_league( $this->league_id );
		}
		$num_draw = $league->getMatches(
			array(
				'count'            => true,
				'team_id'          => $this->id,
				'winner_id'        => -1,
				'loser_id'         => -1,
				'limit'            => false,
				'cache'            => false,
				'match_day'        => -1,
				'reset_query_args' => true,
			)
		);
		$num_draw = apply_filters( 'racketmanager_tie_matches_' . $league->sport, $num_draw, $this->id, $league->id );

		$this->draw_matches = $num_draw;

		// re-compute win percentage
		$this->winPercent();

		return $num_draw;
	}

	/**
	 * get number of lost matches
	 *
	 * @return int
	 */
	public function getNumLostMatches() {
		global $wpdb;

		$league = get_league();
		if ( is_null( $league ) ) {
			$league = get_league( $this->league_id );
		}
		$num_lost = $league->getMatches(
			array(
				'count'            => true,
				'loser_id'         => $this->id,
				'limit'            => false,
				'cache'            => false,
				'match_day'        => -1,
				'reset_query_args' => true,
			)
		);
		$num_lost = apply_filters( 'racketmanager_lost_matches_' . $league->sport, $num_lost, $this->id, $league->id );

		$this->lost_matches = $num_lost;

		// re-compute win percentage
		$this->winPercent();

		return $num_lost;
	}
}

/**
 * get LeagueTeam object
 *
 * @param int|LeagueTeam|null LeagueTeam ID or leagueteam object. Defaults to global $leagueteam
 * @return leagueTeam|null
 */
function get_leagueTeam( $leagueTeam = null ) {
	if ( empty( $leagueTeam ) && isset( $GLOBALS['leagueTeam'] ) ) {
		$leagueTeam = $GLOBALS['leagueTeam'];
	}

	if ( $leagueTeam instanceof LeagueTeam ) {
		$_leagueTeam = $leagueTeam;
	} elseif ( is_object( $leagueTeam ) ) {
		$_leagueTeam = new LeagueTeam( $leagueTeam );
	} else {
		$_leagueTeam = LeagueTeam::get_instance( $leagueTeam );
	}

	if ( ! $_leagueTeam ) {
		return null;
	}

	return $_leagueTeam;
}
