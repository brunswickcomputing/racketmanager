<?php
/**
 * RacketManager-Rubber API: RacketManager-rubber class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Rubber
 */

namespace Racketmanager;

/**
 * Class to implement the Racketmanager_Rubber object
 */
final class Racketmanager_Rubber {
	/**
	 * Rubber id variable
	 *
	 * @var int
	 */
	public $id;
	/**
	 * Match id variable
	 *
	 * @var int
	 */
	public $match_id;
	/**
	 * Status variable
	 *
	 * @var int
	 */
	public $status;
	/**
	 * Custom variable
	 *
	 * @var array
	 */
	public $custom;
	/**
	 * Sets variable
	 *
	 * @var array
	 */
	public $sets;
	/**
	 * Rubber start time variable
	 *
	 * @var string
	 */
	public $start_time;
	/**
	 * Rubber hour variable
	 *
	 * @var string
	 */
	public $hour;
	/**
	 * Rubber minutes variable
	 *
	 * @var string
	 */
	public $minutes;
	/**
	 * Date variable
	 *
	 * @var string
	 */
	public $date;
	/**
	 * Rubber date variable
	 *
	 * @var string
	 */
	public $rubber_date;
	/**
	 * Home points variable
	 *
	 * @var float
	 */
	public $home_points;
	/**
	 * Away points variable
	 *
	 * @var float
	 */
	public $away_points;
	/**
	 * Home score variable
	 *
	 * @var float
	 */
	private $home_score;
	/**
	 * Away score variable
	 *
	 * @var float
	 */
	private $away_score;
	/**
	 * Score variable
	 *
	 * @var string
	 */
	public $score;
	/**
	 * Is walkover variable
	 *
	 * @var boolean
	 */
	public $is_walkover;
	/**
	 * Is retired variable
	 *
	 * @var boolean
	 */
	public $is_retired;
	/**
	 * Is shared variable
	 *
	 * @var boolean
	 */
	public $is_shared;
	/**
	 * Players variable
	 *
	 * @var array
	 */
	public $players;
	/**
	 * Home player 1 variable
	 *
	 * @var int
	 */
	public $home_player_1;
	/**
	 * Home player 2 variable
	 *
	 * @var int
	 */
	public $home_player_2;
	/**
	 * Away player 1 variable
	 *
	 * @var int
	 */
	public $away_player_1;
	/**
	 * Away player 2 variable
	 *
	 * @var int
	 */
	public $away_player_2;
	/**
	 * Rubber type variable
	 *
	 * @var string
	 */
	public $type;
	/**
	 * Rubber title variable
	 *
	 * @var string
	 */
	public $title;
	/**
	 * Rubber number variable
	 *
	 * @var int
	 */
	public $rubber_number;
	/**
	 * Winner id variable
	 *
	 * @var int
	 */
	public $winner_id;
	/**
	 * Loser id variable
	 *
	 * @var int
	 */
	public $loser_id;
	/**
	 * Get rubber instance function
	 *
	 * @param int $rubber_id rubber id.
	 * @return null|object rubber.
	 */
	public static function get_instance( $rubber_id = null ) {
		global $wpdb;
		$rubber_id = (int) $rubber_id;
		if ( ! $rubber_id ) {
			return false;
		}
		$rubber = wp_cache_get( $rubber_id, 'rubbers' );
		if ( ! $rubber ) {
			$rubber = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT `match_id`, `group`, `home_player_1`, `home_player_2`, `away_player_1`, `away_player_2`, DATE_FORMAT(`date`, '%%Y-%%m-%%d %%H:%%i') AS date, DATE_FORMAT(`date`, '%%e') AS day, DATE_FORMAT(`date`, '%%c') AS month, DATE_FORMAT(`date`, '%%Y') AS year, DATE_FORMAT(`date`, '%%H') AS `hour`, DATE_FORMAT(`date`, '%%i') AS `minutes`, `match_id`, `home_points`, `away_points`, `winner_id`, `loser_id`, `post_id`, `id`, `type`, `custom`, `rubber_number`, `status` FROM {$wpdb->racketmanager_rubbers} WHERE `id` =  %d",
					$rubber_id,
				)
			);
			if ( ! $rubber ) {
				return false;
			}
			$rubber = new RacketManager_Rubber( $rubber );
			wp_cache_set( $rubber_id, $rubber, 'rubbers' );
		}
		return $rubber;
	}
	/**
	 * Rubber construction function
	 *
	 * @param object|null $rubber rubber object.
	 */
	public function __construct( $rubber = null ) {
		global $racketmanager;
		if ( ! is_null( $rubber ) ) {
			if ( isset( $rubber->custom ) ) {
				$rubber->custom = stripslashes_deep( (array) maybe_unserialize( $rubber->custom ) );
				$rubber         = (object) array_merge( (array) $rubber, (array) $rubber->custom );
			}

			foreach ( get_object_vars( $rubber ) as $key => $value ) {
				$this->$key = $value;
			}
			if ( ! isset( $this->id ) ) {
				$this->id = $this->add();
			}
			$this->custom = stripslashes_deep( maybe_unserialize( $this->custom ) );
			$this->sets   = isset( $this->custom['sets'] ) ? $this->custom['sets'] : array();
			$rubber       = (object) array_merge( (array) $this, (array) $this->custom );

			$this->start_time  = ( '00:00' === $this->hour . ':' . $this->minutes ) ? '' : mysql2date( $racketmanager->time_format, $this->date );
			$this->rubber_date = ( substr( $this->date, 0, 10 ) === '0000-00-00' ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date );

			if ( null !== $this->home_points && null !== $this->away_points ) {
				$this->home_score = $this->home_points;
				$this->away_score = $this->away_points;
				$this->score      = sprintf( '%g - %g', $this->home_score, $this->away_score );
			} else {
				$this->home_score = '-';
				$this->away_score = '-';
				$this->score      = sprintf( '%g:%g', $this->home_score, $this->away_score );
			}
			$this->is_walkover = false;
			$this->is_retired  = false;
			$this->is_shared   = false;
			if ( ! empty( $this->custom['walkover'] ) ) {
				$this->is_walkover = true;
			}
			if ( ! empty( $this->custom['share'] ) ) {
				$this->is_shared = true;
			}
			if ( ! empty( $this->custom['retired'] ) ) {
				$this->is_retired = true;
			}
			$this->players = array();
			if ( ! empty( $this->home_player_1 ) ) {
				$this->players['home'][1] = $racketmanager->get_club_player( $this->home_player_1 );
			} else {
				$this->players['home'][1] = null;
			}
			if ( ! empty( $this->home_player_2 ) ) {
				$this->players['home'][2] = $racketmanager->get_club_player( $this->home_player_2 );
			} else {
				$this->players['home'][2] = null;
			}
			if ( ! empty( $this->away_player_1 ) ) {
				$this->players['away'][1] = $racketmanager->get_club_player( $this->away_player_1 );
			} else {
				$this->players['away'][1] = null;
			}
			if ( ! empty( $this->away_player_2 ) ) {
				$this->players['away'][2] = $racketmanager->get_club_player( $this->away_player_2 );
			} else {
				$this->players['away'][2] = null;
			}
			$this->title = $this->type . $this->rubber_number;
		}
	}
	/**
	 * Add rubber function
	 *
	 * @return int id of rubber inserted
	 */
	public function add() {
		global $wpdb;
		$insert = $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO {$wpdb->racketmanager_rubbers} (`date`, `match_id`, `rubber_number`, `type`) VALUES (%s, %d, %d, %s)",
				$this->date,
				$this->match_id,
				$this->rubber_number,
				$this->type
			)
		);
		if ( ! $insert ) {
			return false;
		}
		$this->id = $wpdb->insert_id;
		return $this->id;
	}
	/**
	 * Delete rubber function
	 */
	public function delete() {
		global $wpdb;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_rubbers} WHERE `id` = %d",
				$this->id
			)
		);
	}
	/**
	 * Update rubber result function
	 */
	public function update_result() {
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_rubbers} SET `home_points` = %f,`away_points` = %f,`home_player_1` = %s,`home_player_2` = %s,`away_player_1` = %s,`away_player_2` = %s,`winner_id` = %d,`loser_id` = %d,`custom` = %s, `status`= %d WHERE `id` = %d",
				$this->home_points,
				$this->away_points,
				$this->home_player_1,
				$this->home_player_2,
				$this->away_player_1,
				$this->away_player_2,
				$this->winner_id,
				$this->loser_id,
				maybe_serialize( $this->custom ),
				$this->status,
				$this->id,
			)
		);
		wp_cache_delete( $this->id, 'rubbers' );
	}
	/**
	 * Update rubber date function
	 */
	public function update_date() {
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_rubbers} SET `date` = %s WHERE `id` = %d",
				$this->date,
				$this->id,
			)
		);
	}
	/**
	 * Calculate result function
	 *
	 * @param array $points aray of data used to calculate points.
	 * @return object points data for home and away.
	 */
	public function calculate_result( $points ) {
		global $racketmanager;
		$home_points   = 0;
		$away_points   = 0;
		$home_team     = $points['home']['team'];
		$home_sets     = $points['home']['sets'];
		$home_walkover = isset( $points['home']['walkover'] ) ? 1 : 0;
		$home_retired  = isset( $points['home']['retired'] ) ? 1 : 0;
		$away_team     = $points['away']['team'];
		$away_sets     = $points['away']['sets'];
		$away_walkover = isset( $points['away']['walkover'] ) ? 1 : 0;
		$away_retired  = isset( $points['away']['retired'] ) ? 1 : 0;
		$shared_sets   = $points['shared']['sets'];
		$match         = get_match( $this->match_id );
		$league        = get_league( $match->league_id );
		$point_rule    = $league->get_point_rule();
		$forwin        = $point_rule['forwin'];
		$forwin_split  = $point_rule['forwin_split'];
		$forshare      = $point_rule['forshare'];
		$point_options = $racketmanager->get_options( 'player' );
		if ( empty( $home_sets ) || empty( $away_sets ) ) {
			if ( empty( $home_sets ) ) {
				$straight_sets_home = 0;
				$straight_sets_away = 1;
				$split_sets_home    = 0;
				$split_sets_away    = 0;
			} else {
				$straight_sets_home = 1;
				$straight_sets_away = 0;
				$split_sets_home    = 0;
				$split_sets_away    = 0;
			}
		} elseif ( empty( $home_sets ) ) {
			$straight_sets_home = 0;
			$straight_sets_away = 0;
			$split_sets_home    = 0;
			$split_sets_away    = 1;
		} else {
			$straight_sets_home = 0;
			$straight_sets_away = 0;
			$split_sets_home    = 1;
			$split_sets_away    = 0;
		}
		$home_points = $home_sets + ( $straight_sets_home * $forwin ) + ( $split_sets_home * $forwin_split ) + ( $shared_sets * $forshare ) - ( $home_walkover * $point_options['walkover']['rubber'] );
		$away_points = $away_sets + ( $straight_sets_away * $forwin ) + ( $split_sets_away * $forwin_split ) + ( $shared_sets * $forshare ) - ( $away_walkover * $point_options['walkover']['rubber'] );
		if ( $home_walkover || $away_walkover ) {
			if ( $home_walkover ) {
				$winner = $away_team;
				$loser  = $home_team;
			} else {
				$winner = $home_team;
				$loser  = $away_team;
			}
		} elseif ( $home_retired || $away_retired ) {
			if ( $home_retired ) {
				$winner = $away_team;
				$loser  = $home_team;
			} else {
				$winner = $home_team;
				$loser  = $away_team;
			}
		} elseif ( $home_points > $away_points ) {
			$winner = $home_team;
			$loser  = $away_team;
		} elseif ( $home_points < $away_points ) {
			$winner = $away_team;
			$loser  = $home_team;
		} else {
			$winner = -1;
			$loser  = -1;
		}
		$return         = new \stdClass();
		$return->home   = $home_points;
		$return->away   = $away_points;
		$return->winner = $winner;
		$return->loser  = $loser;
		return $return;
	}
}
