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
	 * Is abandoned variable
	 *
	 * @var boolean
	 */
	public $is_abandoned;
	/**
	 * Is invalid variable
	 *
	 * @var boolean
	 */
	public $is_invalid;
	/**
	 * Players variable
	 *
	 * @var array
	 */
	public $players;
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
	 * Reverse rubbers variable
	 *
	 * @var boolean
	 */
	public $reverse_rubbers;
	/**
	 * Reverse rubber variable
	 *
	 * @var boolean
	 */
	public $reverse_rubber;
	/**
	 * Day
	 *
	 * @var int
	 */
	public $day;
	/**
	 * Month
	 *
	 * @var int
	 */
	public $month;
	/**
	 * Year
	 *
	 * @var int
	 */
	public $year;
	/**
	 * Stats
	 *
	 * @var array
	 */
	public $stats;
	/**
	 * Class
	 *
	 * @var string
	 */
	public $class;
	/**
	 * Group
	 *
	 * @var string
	 */
	public $group;
	/**
	 * Post id
	 *
	 * @var int
	 */
	public $post_id;
	/**
	 * Walkover
	 *
	 * @var string
	 */
	public $walkover;
	/**
	 * Invalid
	 *
	 * @var string
	 */
	public $invalid;
	/**
	 * Abandoned
	 *
	 * @var string
	 */
	public $abandoned;
	/**
	 * Share
	 *
	 * @var string
	 */
	public $share;
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
					"SELECT `match_id`, `group`, DATE_FORMAT(`date`, '%%Y-%%m-%%d %%H:%%i') AS date, DATE_FORMAT(`date`, '%%e') AS day, DATE_FORMAT(`date`, '%%c') AS month, DATE_FORMAT(`date`, '%%Y') AS year, DATE_FORMAT(`date`, '%%H') AS `hour`, DATE_FORMAT(`date`, '%%i') AS `minutes`, `match_id`, `home_points`, `away_points`, `winner_id`, `loser_id`, `post_id`, `id`, `type`, `custom`, `rubber_number`, `status` FROM {$wpdb->racketmanager_rubbers} WHERE `id` =  %d",
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
			$this->is_walkover  = false;
			$this->is_retired   = false;
			$this->is_shared    = false;
			$this->is_abandoned = false;
			$this->is_invalid   = false;
			if ( ! empty( $this->custom['walkover'] ) ) {
				$this->is_walkover = true;
			}
			if ( ! empty( $this->custom['share'] ) ) {
				$this->is_shared = true;
			}
			if ( ! empty( $this->custom['retired'] ) ) {
				$this->is_retired = true;
			}
			if ( ! empty( $this->custom['abandoned'] ) ) {
				$this->is_abandoned = true;
			}
			if ( ! empty( $this->custom['invalid'] ) ) {
				$this->is_invalid = true;
			}
			$this->players = array();
			$this->get_players();
			$this->title          = $this->type . $this->rubber_number;
			$match                = get_match( $rubber->match_id );
			$this->reverse_rubber = false;
			if ( $match->league->event->reverse_rubbers ) {
				$this->reverse_rubbers = true;
				if ( $this->rubber_number > $match->league->num_rubbers ) {
					$this->reverse_rubber = true;
				}
			} else {
				$this->reverse_rubbers = false;
			}
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
				"UPDATE {$wpdb->racketmanager_rubbers} SET `home_points` = %f,`away_points` = %f, `winner_id` = %d,`loser_id` = %d,`custom` = %s, `status`= %d WHERE `id` = %d",
				$this->home_points,
				$this->away_points,
				$this->winner_id,
				$this->loser_id,
				maybe_serialize( $this->custom ),
				$this->status,
				$this->id,
			)
		);
		wp_cache_set( $this->id, $this, 'rubbers' );
	}
	/**
	 * Set players function
	 *
	 * @param array $players array of players.
	 */
	public function set_players( $players ) {
		global $racketmanager, $wpdb;
		foreach ( $players as $player_team => $player_ref ) {
			foreach ( $player_ref as $player_num => $player ) {
				$club_player = get_club_player( $player );
				if ( $club_player ) {
					$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"REPLACE INTO {$wpdb->racketmanager_rubber_players} ( `rubber_id`, `player_ref`, `player_team`, `player_id`, `club_player_id` ) VALUES ( %d, %d, %s, %d, %d )",
							$this->id,
							$player_num,
							$player_team,
							$club_player->player->id,
							$club_player->id,
						)
					);
					$player = $club_player->player;
					if ( $player ) {
						$this->players[ $player_team ][ $player_num ]                 = $player;
						$this->players[ $player_team ][ $player_num ]->club_player_id = $club_player->id;
					}
				}
			}
		}
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
		$home_points        = 0;
		$away_points        = 0;
		$home_team          = $points['home']['team'];
		$home_sets          = $points['home']['sets'];
		$home_walkover      = isset( $points['home']['walkover'] ) ? 1 : 0;
		$home_retired       = isset( $points['home']['retired'] ) ? 1 : 0;
		$home_invalid       = isset( $points['home']['invalid'] ) ? 1 : 0;
		$away_team          = $points['away']['team'];
		$away_sets          = $points['away']['sets'];
		$away_walkover      = isset( $points['away']['walkover'] ) ? 1 : 0;
		$away_retired       = isset( $points['away']['retired'] ) ? 1 : 0;
		$away_invalid       = isset( $points['away']['invalid'] ) ? 1 : 0;
		$both_invalid       = isset( $points['both']['invalid'] ) ? 1 : 0;
		$shared_sets        = isset( $points['shared']['sets'] ) ? $points['shared']['sets'] : 0;
		$match              = get_match( $this->match_id );
		$league             = get_league( $match->league_id );
		$point_rule         = $league->get_point_rule();
		$forwin             = $point_rule['forwin'];
		$forwin_split       = $point_rule['forwin_split'];
		$forshare           = $point_rule['forshare'];
		$forwalkover_rubber = empty( $point_rule['forwalkover_rubber'] ) ? 0 : $point_rule['forwalkover_rubber'];
		if ( $shared_sets === $league->num_sets ) {
			$straight_sets_home = 0;
			$straight_sets_away = 0;
			$split_sets_home    = 0;
			$split_sets_away    = 0;
		} elseif ( ( empty( $home_sets ) || empty( $away_sets ) ) && empty( $shared_sets ) ) {
			if ( empty( $home_sets ) && empty( $away_sets ) ) {
				$straight_sets_home = 0;
				$straight_sets_away = 0;
				$split_sets_home    = 0;
				$split_sets_away    = 0;
			} elseif ( empty( $home_sets ) ) {
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
		$home_points = $home_sets + ( $straight_sets_home * $forwin ) + ( $split_sets_home * $forwin_split ) + ( $shared_sets * $forshare ) - ( $home_walkover * $forwalkover_rubber );
		$away_points = $away_sets + ( $straight_sets_away * $forwin ) + ( $split_sets_away * $forwin_split ) + ( $shared_sets * $forshare ) - ( $away_walkover * $forwalkover_rubber );
		if ( $home_walkover || $away_walkover ) {
			if ( $home_walkover && $away_walkover ) {
				$winner = -1;
				$loser  = -1;
			} elseif ( $home_walkover ) {
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
		} elseif ( $both_invalid ) {
			$winner = -1;
			$loser  = -1;
		} elseif ( $home_invalid || $away_invalid ) {
			if ( $home_invalid ) {
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
	/**
	 * Get players for rubber function
	 */
	public function get_players() {
		global $wpdb;
		$players = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT `id`, `player_ref`, `player_team`, `player_id`, `club_player_id` FROM {$wpdb->racketmanager_rubber_players} WHERE `rubber_id` = %s",
				$this->id
			)
		);

		foreach ( $players as $player ) {
			$this->players[ $player->player_team ][ $player->player_ref ]                 = get_player( $player->player_id );
			$this->players[ $player->player_team ][ $player->player_ref ]->club_player_id = $player->club_player_id;
			$this->players[ $player->player_team ][ $player->player_ref ]->description    = null;
			$this->players[ $player->player_team ][ $player->player_ref ]->class          = null;
			$player_errors = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT `description` FROM {$wpdb->racketmanager_results_checker} WHERE `rubber_id` = %d AND `player_id` = %d AND (`status` IS NULL OR `status` != 1)",
					$this->id,
					$player->player_id,
				)
			);
			if ( $player_errors ) {
				$this->players[ $player->player_team ][ $player->player_ref ]->class = 'is-ineligible';
				foreach ( $player_errors as $player_error ) {
					if ( ! empty( $this->players[ $player->player_team ][ $player->player_ref ]->description ) ) {
						$this->players[ $player->player_team ][ $player->player_ref ]->description .= ', ';
					}
					$this->players[ $player->player_team ][ $player->player_ref ]->description .= $player_error->description;
				}
			}
		}
	}
	/**
	 * Check players function
	 */
	public function check_players() {
		global $racketmanager, $wpdb;
		$return = array();
		$player_ratings = array();
		$player_wtns    = array();
		$match          = get_match( $this->match_id );
		if ( $match ) {
			$options          = $racketmanager->get_options( 'checks' );
			$register_options = $racketmanager->get_options( 'rosters' );
			$player_options   = $racketmanager->get_options( 'player' );
			$opponents        = array( 'home', 'away' );
			foreach ( $opponents as $opponent ) {
				$team_ref = $opponent . '_team';
				$team_id  = $match->$team_ref;
				$team     = get_team( $team_id );
				if ( $team ) {
					$team_name        = $team->title;
					$team_name_array  = explode( ' ', $team_name );
					$this_team_number = end( $team_name_array );
				}
				$player_ratings[ $opponent ] = 0;
				$player_wtns[ $opponent ]    = 0;
				$players                     = $this->players[ $opponent ];
				foreach ( $players as $player ) {
					if ( empty( $player ) ) {
						$error = __( 'Player not selected', 'racketmanager' );
						$match->add_player_result_check( $team->id, 0, $error, $this->id );
						break;
					}
					if ( $player ) {
						if ( ! empty( $player->system_record ) ) {
							if ( 'M' === $player->gender ) {
								$gender = 'male';
							} elseif ( 'F' === $player->gender ) {
								$gender = 'female';
							} else {
								$gender = 'unknown';
							}
							if ( isset( $player_options['unregistered'][ $gender ] ) && intval( $player->id ) === intval( $player_options['unregistered'][ $gender ] ) ) {
								$error = __( 'Unregistered player', 'racketmanager' );
								$match->add_player_result_check( $team->id, $player->id, $error, $this->id );
							}
							break;
						}
						$type                         = substr( $match->league->event->type, 1, 1 );
						$player_rating                = isset( $player->rating[ $type ] ) ? $player->rating[ $type ] : 0;
						$player_wtn                   = isset( $player->wtn[ $type ] ) ? floatval( $player->wtn[ $type ] ) : 40.9;
						$player_ratings[ $opponent ] += $player_rating;
						$player_wtns[ $opponent ]    += $player_wtn;
						if ( ! empty( $player->locked ) ) {
							$error = __( 'locked', 'racketmanager' );
							$match->add_player_result_check( $team->id, $player->id, $error, $this->id );
						}
						if ( ! empty( $match->league->event->competition->rules['leadTimecheck'] ) ) {
							if ( ! empty( $options['leadTimecheck'] ) && isset( $options['rosterLeadTime'] ) && isset( $player->created_date ) ) {
								$match_date  = new \DateTime( $match->date );
								$roster_date = new \DateTime( $player->created_date );
								$date_diff   = $roster_date->diff( $match_date );
								$interval    = $date_diff->days * 24;
								$interval   += $date_diff->h;
								if ( $interval < intval( $options['rosterLeadTime'] ) ) {
									/* translators: %d: number of hours */
									$error = sprintf( __( 'registered with club only %d hours before match', 'racketmanager' ), $interval );
									$match->add_player_result_check( $team->id, $player->id, $error, $this->id );
								} elseif ( $date_diff->invert ) {
									/* translators: %d: number of hours */
									$error = sprintf( __( 'registered with club %d hours after match', 'racketmanager' ), $interval );
									$match->add_player_result_check( $team->id, $player->id, $error, $this->id );
								}
							}
						}
						if ( ! empty( $match->league->event->competition->rules['ageLimitCheck'] ) ) {
							if ( ! empty( $options['ageLimitCheck'] ) && ! empty( $match->league->event->age_limit ) && 'open' !== $match->league->event->age_limit ) {
								if ( empty( $player->age ) ) {
									$error = __( 'no age provided', 'racketmanager' );
									$match->add_player_result_check( $team->id, $player->id, $error, $this->id );
								} else {
									if ( ! empty( $match->league->event->competition->seasons[ $match->season ]['date_end'] ) ) {
										$date_end = $match->league->event->competition->seasons[ $match->season ]['date_end'];
									} elseif ( ! empty( $match->league->event->seasons[ $match->season ]['match_dates'] ) ) {
										$date_end = end( $match->league->event->seasons[ $match->season ]['match_dates'] );
									} else {
										$date_end = null;
									}
									if ( empty( $date_end ) ) {
										$player_age = $player->age;
									} else {
										$player_age = substr( $date_end, 0, 4 ) - intval( $player->year_of_birth );
									}
									$age_limit = $match->league->event->age_limit;
									if ( $age_limit >= 30 ) {
										if ( ! empty( $match->league->event->age_offset ) && 'F' === $player->gender ) {
											$age_limit -= $match->league->event->age_offset;
										}
										if ( $player_age < $age_limit ) {
											/* translators: %1$d: player age, %2$d: event age limit */
											$error = sprintf( __( 'player age (%1$d) less than event age limit (%2$d)', 'racketmanager' ), $player_age, $age_limit );
											$match->add_player_result_check( $team->id, $player->id, $error, $this->id );
										}
									} elseif ( $player_age > $age_limit ) {
										/* translators: %1$d: player age, %2$d: event age limit */
										$error = sprintf( __( 'player age (%1$d) greater than event age limit (%2$d)', 'racketmanager' ), $player_age, $age_limit );
										$match->add_player_result_check( $team->id, $player->id, $error, $this->id );
									}
								}
							}
						}
						if ( isset( $register_options['btm'] ) && '1' === $register_options['btm'] && empty( $player->btm ) ) {
							$error = __( 'LTA tennis number missing', 'racketmanager' );
							$match->add_player_result_check( $team->id, $player->id, $error, $this->id );
						}
						if ( isset( $match->match_day ) ) {
							$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
								$wpdb->prepare(
									"SELECT count(*) FROM {$wpdb->racketmanager_matches} m, {$wpdb->racketmanager_rubbers} r, {$wpdb->racketmanager_rubber_players} rp WHERE m.`id` = r.`match_id` AND r.`id` = rp.`rubber_id` AND m.`season` = %s AND m.`match_day` = %d AND  m.`league_id` != %d AND m.`league_id` in (SELECT l.`id` from {$wpdb->racketmanager} l, {$wpdb->racketmanager_events} c WHERE l.`event_id` = (SELECT `event_id` FROM {$wpdb->racketmanager} WHERE `id` = %d)) AND rp.`club_player_id` = %d",
									$match->season,
									$match->match_day,
									$match->league_id,
									$match->league_id,
									$player->club_player_id,
								)
							);
							if ( $count > 0 ) {
								/* translators: %d: match day */
								$error = sprintf( __( 'already played on match day %d', 'racketmanager' ), $match->match_day );
								$match->add_player_result_check( $team->id, $player->id, $error, $this->id );
							}
							if ( ! empty( $match->league->event->competition->rules['playedRounds'] ) ) {
								if ( isset( $options['playedRounds'] ) ) {
									$competition = get_competition( $match->league->event->competition->id );
									if ( $competition ) {
										$competition_season = $competition->seasons[ $match->season ];
										if ( $competition_season ) {
											if ( ! empty( $competition_season['fixed_match_dates'] ) ) {
												$league         = get_league( $match->league_id );
												$num_match_days = $league->event->seasons[ $match->season ]['num_match_days'];
												if ( $match->match_day > ( $num_match_days - $options['playedRounds'] ) ) {
													$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
														$wpdb->prepare(
															"SELECT count(*) FROM {$wpdb->racketmanager_matches} m, {$wpdb->racketmanager_rubbers} r, {$wpdb->racketmanager_rubber_players} rp WHERE m.`id` = r.`match_id` AND r.`id` = rp.`rubber_id` AND m.`season` = %s AND m.`match_day` < %d AND m.`league_id` in (SELECT l.`id` from {$wpdb->racketmanager} l, {$wpdb->racketmanager_events} e WHERE l.`event_id` = (SELECT `event_id` FROM {$wpdb->racketmanager} WHERE `id` = %d)) AND rp.`club_player_id` = %d",
															$match->season,
															$match->match_day,
															$match->league_id,
															$player->club_player_id
														)
													);
													if ( 0 === intval( $count ) ) {
														/* translators: %d: number of played rounds */
														$error = sprintf( __( 'not played before the final %d match days', 'racketmanager' ), $options['playedRounds'] );
														$match->add_player_result_check( $team->id, $player->id, $error, $this->id );
													}
												}
											}
										}
									}
								}
							}
							if ( ! empty( $match->league->event->competition->rules['playerLocked'] ) ) {
								if ( isset( $options['playerLocked'] ) ) {
									$event        = get_event( $match->league->event_id );
									$player_stats = $event->get_player_stats(
										array(
											'season' => $match->season,
											'player' => $player->club_player_id,
										)
									);
									$teamplay     = array();
									foreach ( $player_stats as $player_stat ) {
										foreach ( $player_stat->matchdays as $match_day ) {
											$team_num = substr( $match_day->team_title, -1 );
											if ( isset( $teamplay[ $team_num ] ) ) {
												++$teamplay[ $team_num ];
											} else {
												$teamplay[ $team_num ] = 1;
											}
										}
										foreach ( $teamplay as $team_num => $played ) {
											if ( $team_num < $this_team_number && $played > $options['playerLocked'] ) {
												/* translators: %d: team number */
												$error = sprintf( __( 'locked to team %d', 'racketmanager' ), $team_num );
												$match->add_player_result_check( $team->id, $player->id, $error, $this->id );
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
		$return['ratings'] = $player_ratings;
		$return['wtns']    = $player_wtns;
		return $return;
	}
	/**
	 * Reset rubber result function
	 *
	 * @return void
	 */
	public function reset_result() {
		global $wpdb;
		$this->home_points = null;
		$this->away_points = null;
		$this->winner_id   = 0;
		$this->loser_id    = 0;
		$this->custom      = null;
		$this->status      = null;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_rubbers} SET `home_points` = null,`away_points` = null, `winner_id` = %d,`loser_id` = %d,`custom` = null, `status`= null WHERE `id` = %d",
				$this->winner_id,
				$this->loser_id,
				$this->id,
			)
		);
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_rubber_players} WHERE `rubber_id` = %d",
				$this->id,
			)
		);
		$this->players = array();
		wp_cache_set( $this->id, $this, 'rubbers' );
	}
}
