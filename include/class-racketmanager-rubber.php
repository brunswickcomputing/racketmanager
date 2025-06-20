<?php
/**
 * RacketManager-Rubber API: RacketManager-rubber class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Rubber
 */

namespace Racketmanager;

use DateMalformedStringException;
use DateTime;
use stdClass;

/**
 * Class to implement the Racketmanager_Rubber object
 */
final class Racketmanager_Rubber {
	/**
	 * Rubber id variable
	 *
	 * @var int|false
	 */
	public int|false $id;
	/**
	 * Match id variable
	 *
	 * @var int
	 */
	public int $match_id;
	/**
	 * Status variable
	 *
	 * @var int|null
	 */
	public ?int $status;
	/**
	 * Custom variable
	 *
	 * @var array
	 */
	public mixed $custom = array();
	/**
	 * Sets variable
	 *
	 * @var array
	 */
	public mixed $sets;
	/**
	 * Rubber start time variable
	 *
	 * @var string|int|false
	 */
	public string|int|false $start_time;
	/**
	 * Rubber hour variable
	 *
	 * @var string
	 */
	public string $hour;
	/**
	 * Rubber minutes variable
	 *
	 * @var string
	 */
	public string $minutes;
	/**
	 * Date variable
	 *
	 * @var string
	 */
	public string $date;
	/**
	 * Rubber date variable
	 *
	 * @var string|int|false
	 */
	public string|int|false $rubber_date;
	/**
	 * Home points variable
	 *
	 * @var float|null
	 */
	public ?float $home_points = null;
	/**
	 * Away points variable
	 *
	 * @var float|null
	 */
	public ?float $away_points = null;
	/**
	 * Score variable
	 *
	 * @var string
	 */
	public string $score;
	/**
	 * Is walkover variable
	 *
	 * @var boolean
	 */
	public bool $is_walkover;
	/**
	 * Is retired variable
	 *
	 * @var boolean
	 */
	public bool $is_retired;
	/**
	 * Is shared variable
	 *
	 * @var boolean
	 */
	public bool $is_shared;
	/**
	 * Is abandoned variable
	 *
	 * @var boolean
	 */
	public bool $is_abandoned;
	/**
	 * Is invalid variable
	 *
	 * @var boolean
	 */
	public bool $is_invalid;
	/**
	 * Players variable
	 *
	 * @var array
	 */
	public array $players;
	/**
	 * Rubber type variable
	 *
	 * @var string
	 */
	public string $type;
	/**
	 * Rubber title variable
	 *
	 * @var string
	 */
	public string $title;
	/**
	 * Rubber number variable
	 *
	 * @var int
	 */
	public int $rubber_number;
	/**
	 * Winner id variable
	 *
	 * @var string
	 */
	public string $winner_id;
	/**
	 * Loser id variable
	 *
	 * @var string
	 */
	public string $loser_id;
	/**
	 * Reverse rubbers variable
	 *
	 * @var boolean
	 */
	public bool $reverse_rubbers;
	/**
	 * Reverse rubber variable
	 *
	 * @var boolean
	 */
	public bool $reverse_rubber;
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
	 * Class
	 *
	 * @var string
	 */
	public string $class;
	/**
	 * Group
	 *
	 * @var string
	 */
	public string $group;
	/**
	 * Post id
	 *
	 * @var int
	 */
	public int $post_id;
	/**
	 * Walkover
	 *
	 * @var string
	 */
	public string $walkover;
	/**
	 * Invalid
	 *
	 * @var string
	 */
	public string $invalid;
	/**
	 * Abandoned
	 *
	 * @var string
	 */
	public string $abandoned;
	/**
	 * Share
	 *
	 * @var string
	 */
	public string $share;
	/**
	 * Cancelled
	 *
	 * @var string
	 */
	public string $cancelled;
	/**
	 * Retired
	 *
	 * @var string
	 */
	public string $retired;
	/**
	 * Get rubber instance function
	 *
	 * @param int|null $rubber_id rubber id.
	 * @return null|object rubber.
	 */
	public static function get_instance(int $rubber_id = null ): object|null {
		global $wpdb;
		$rubber_id = (int) $rubber_id;
		if ( ! $rubber_id ) {
			return null;
		}
		$rubber = wp_cache_get( $rubber_id, 'rubbers' );
		if ( ! $rubber ) {
			$rubber = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT `id`, `match_id`, `group`, DATE_FORMAT(`date`, '%%Y-%%m-%%d %%H:%%i') AS date, DATE_FORMAT(`date`, '%%e') AS day, DATE_FORMAT(`date`, '%%c') AS month, DATE_FORMAT(`date`, '%%Y') AS year, DATE_FORMAT(`date`, '%%H') AS `hour`, DATE_FORMAT(`date`, '%%i') AS `minutes`, `home_points`, `away_points`, `winner_id`, `loser_id`, `post_id`, `type`, `custom`, `rubber_number`, `status` FROM $wpdb->racketmanager_rubbers WHERE `id` =  %d",
					$rubber_id,
				)
			);
			if ( ! $rubber ) {
				return null;
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
	public function __construct(object $rubber = null ) {
		global $racketmanager;
		if ( ! is_null( $rubber ) ) {
			if ( ! empty( $rubber->custom ) ) {
				$custom = stripslashes_deep( (array) maybe_unserialize( $rubber->custom ) );
				$rubber = (object) array_merge( (array) $rubber, (array) $custom );
			}

			foreach ( get_object_vars( $rubber ) as $key => $value ) {
				$this->$key = $value;
			}
			if ( ! isset( $this->id ) ) {
				$this->id = $this->add();
			}
			$this->custom = stripslashes_deep( maybe_unserialize( $this->custom ) );
			$this->sets   = $this->custom['sets'] ?? array();
			$rubber       = (object) array_merge( (array) $this, (array) $this->custom );

			$this->rubber_date = ( str_starts_with( $this->date, '0000-00-00' ) ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date );
			$this->year        = substr( $this->date, 0, 4 );
			$this->month       = substr( $this->date, 5, 2 );
			$this->day         = substr( $this->date, 8, 2 );
			$time              = substr( $this->date, 11, 5);
			$this->hour        = substr( $time, 0, 2 );
			$this->minutes     = substr( $time, 3, 2 );
			$this->start_time  = ( '00:00' === $time ) ? '' : mysql2date( $racketmanager->time_format, $this->date );

			if ( null !== $this->home_points && null !== $this->away_points ) {
				$home_score  = $this->home_points;
				$away_score  = $this->away_points;
				$this->score = sprintf( '%g - %g', $home_score, $away_score );
			} else {
				$home_score = '-';
				$away_score = '-';
				$this->score      = sprintf( '%g:%g', $home_score, $away_score );
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
	 * @return false|int id of rubber inserted
	 */
	public function add(): false|int {
		global $wpdb;
		$insert = $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO $wpdb->racketmanager_rubbers (`date`, `match_id`, `rubber_number`, `type`) VALUES (%s, %d, %d, %s)",
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
	public function delete(): void {
		global $wpdb;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM $wpdb->racketmanager_rubbers WHERE `id` = %d",
				$this->id
			)
		);
	}
	/**
	 * Update rubber result function
	 */
	public function update_result(): void {
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_rubbers SET `home_points` = %f,`away_points` = %f, `winner_id` = %d,`loser_id` = %d,`custom` = %s, `status`= %d WHERE `id` = %d",
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
	public function set_players(array $players ): void {
		global $wpdb;
		foreach ( $players as $player_team => $player_ref ) {
			foreach ( $player_ref as $player_num => $player ) {
				$club_player = get_club_player( $player );
				if ( $club_player ) {
					$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"REPLACE INTO $wpdb->racketmanager_rubber_players ( `rubber_id`, `player_ref`, `player_team`, `player_id`, `club_player_id` ) VALUES ( %d, %d, %s, %d, %d )",
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
	public function update_date(): void {
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_rubbers SET `date` = %s WHERE `id` = %d",
				$this->date,
				$this->id,
			)
		);
	}
	/**
	 * Calculate result function
	 *
	 * @param array $points array of data used to calculate points.
	 * @return object points data for home and away.
	 */
	public function calculate_result( array $points ): object {
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
		$shared_sets        = $points['shared']['sets'] ?? 0;
		$match              = get_match( $this->match_id );
		$league             = get_league( $match->league_id );
		$point_rule         = $league->get_point_rule();
		$forwin             = $point_rule['forwin'];
		$forwin_split       = $point_rule['forwin_split'];
		$forshare           = $point_rule['forshare'];
		$forwalkover_rubber = empty( $point_rule['forwalkover_rubber'] ) ? 0 : $point_rule['forwalkover_rubber'];
		if ( $home_invalid ) {
			$invalid_points_home = $forwalkover_rubber;
			$invalid_points_away = 0;
		} elseif ( $away_invalid ) {
			$invalid_points_away = $forwalkover_rubber;
			$invalid_points_home = 0;
		} elseif ( $both_invalid ) {
			$invalid_points_home = $forwalkover_rubber;
			$invalid_points_away = $forwalkover_rubber;
		} else {
			$invalid_points_home = 0;
			$invalid_points_away = 0;
		}
		if ( $shared_sets === $league->num_sets ) {
			$straight_sets_home = 0;
			$straight_sets_away = 0;
			$split_sets_home    = 0;
			$split_sets_away    = 0;
		} elseif ( ( empty( $home_sets ) || empty( $away_sets ) ) && empty( $shared_sets ) ) {
			if ( empty( $home_sets ) && empty( $away_sets ) ) {
				$straight_sets_home = 0;
				$straight_sets_away = 0;
			} elseif ( empty( $home_sets ) ) {
				$straight_sets_home = 0;
				$straight_sets_away = 1;
			} else {
				$straight_sets_home = 1;
				$straight_sets_away = 0;
			}
			$split_sets_home    = 0;
			$split_sets_away    = 0;
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
		$home_points = $home_sets + ( $straight_sets_home * $forwin ) + ( $split_sets_home * $forwin_split ) + ( $shared_sets * $forshare ) - ( $home_walkover * $forwalkover_rubber ) - $invalid_points_home;
		$away_points = $away_sets + ( $straight_sets_away * $forwin ) + ( $split_sets_away * $forwin_split ) + ( $shared_sets * $forshare ) - ( $away_walkover * $forwalkover_rubber ) - $invalid_points_away;
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
		$return         = new stdClass();
		$return->home   = $home_points;
		$return->away   = $away_points;
		$return->winner = $winner;
		$return->loser  = $loser;
		return $return;
	}
	/**
	 * Get players for rubber function
	 */
	public function get_players(): void {
		global $wpdb;
		$players = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT `id`, `player_ref`, `player_team`, `player_id`, `club_player_id` FROM $wpdb->racketmanager_rubber_players WHERE `rubber_id` = %s",
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
					"SELECT `description` FROM $wpdb->racketmanager_results_checker WHERE `rubber_id` = %d AND `player_id` = %d AND (`status` IS NULL OR `status` != 1)",
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
	public function check_players(): array {
		global $racketmanager, $wpdb;
		$return      = array();
		$player_wtns = array();
		$match       = get_match( $this->match_id );
		if ( $match ) {
			$options          = $racketmanager->get_options( 'checks' );
			$register_options = $racketmanager->get_options( 'rosters' );
			$player_options   = $racketmanager->get_options( 'player' );
			$opponents        = array( 'home', 'away' );
			foreach ( $opponents as $opponent ) {
				$team_ref         = $opponent . '_team';
				$team_id          = $match->$team_ref;
				$team             = get_team( $team_id );
				$this_team_number = null;
				if ( $team ) {
					$team_name        = $team->title;
					$team_name_array  = explode( ' ', $team_name );
					$this_team_number = end( $team_name_array );
				}
				$player_wtns[ $opponent ] = 0;
				$players                  = $this->players[ $opponent ];
				foreach ( $players as $player ) {
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
						$player_wtn                   = isset( $player->wtn[ $type ] ) ? floatval( $player->wtn[ $type ] ) : 40.9;
						$player_wtns[ $opponent ]    += $player_wtn;
						if ( ! empty( $player->locked ) ) {
							$error = __( 'locked', 'racketmanager' );
							$match->add_player_result_check( $team->id, $player->id, $error, $this->id );
						}
						if ( ! empty( $match->league->event->competition->rules['leadTimecheck'] ) ) {
							if ( ! empty( $options['leadTimecheck'] ) && isset( $options['rosterLeadTime'] ) && isset( $player->created_date ) ) {
								try {
									$match_date = new DateTime($match->date);
								} catch ( DateMalformedStringException) {
									$match_date = null;
								}
								try {
									$roster_date = new DateTime($player->created_date);
								} catch ( DateMalformedStringException) {
									$roster_date = null;
								}
								if ( $roster_date && $match_date ) {
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
                                    $age_check = Racketmanager_Util::check_age_within_limit( $player_age, $age_limit, $player->gender, $match->league->event->age_offset );
                                    if ( ! $age_check->valid ) {
                                        $error = $age_check->msg;
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
									"SELECT count(*) FROM $wpdb->racketmanager_matches m, $wpdb->racketmanager_rubbers r, $wpdb->racketmanager_rubber_players rp WHERE m.`id` = r.`match_id` AND r.`id` = rp.`rubber_id` AND m.`season` = %s AND m.`match_day` = %d AND  m.`league_id` != %d AND m.`league_id` in (SELECT l.`id` from $wpdb->racketmanager l, $wpdb->racketmanager_events c WHERE l.`event_id` = (SELECT `event_id` FROM $wpdb->racketmanager WHERE `id` = %d)) AND rp.`club_player_id` = %d",
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
															"SELECT count(*) FROM $wpdb->racketmanager_matches m, $wpdb->racketmanager_rubbers r, $wpdb->racketmanager_rubber_players rp WHERE m.`id` = r.`match_id` AND r.`id` = rp.`rubber_id` AND m.`season` = %s AND m.`match_day` < %d AND m.`league_id` in (SELECT l.`id` from $wpdb->racketmanager l, $wpdb->racketmanager_events e WHERE l.`event_id` = (SELECT `event_id` FROM $wpdb->racketmanager WHERE `id` = %d)) AND rp.`club_player_id` = %d",
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
									$team_play     = array();
									foreach ( $player_stats as $player_stat ) {
										foreach ( $player_stat->matchdays as $match_day ) {
											$team_num = substr( $match_day->team_title, -1 );
											if ( isset( $teamplay[ $team_num ] ) ) {
												++$team_play[ $team_num ];
											} else {
												$team_play[ $team_num ] = 1;
											}
										}
										foreach ( $team_play as $team_num => $played ) {
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
		$return['wtns'] = $player_wtns;
		return $return;
	}
	/**
	 * Reset rubber result function
	 *
	 * @return void
	 */
	public function reset_result(): void {
		global $wpdb;
		$this->home_points = null;
		$this->away_points = null;
		$this->winner_id   = 0;
		$this->loser_id    = 0;
		$this->custom      = null;
		$this->status      = null;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE $wpdb->racketmanager_rubbers SET `home_points` = null,`away_points` = null, `winner_id` = %d,`loser_id` = %d,`custom` = null, `status`= null WHERE `id` = %d",
				$this->winner_id,
				$this->loser_id,
				$this->id,
			)
		);
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM $wpdb->racketmanager_rubber_players WHERE `rubber_id` = %d",
				$this->id,
			)
		);
		$this->players = array();
		wp_cache_set( $this->id, $this, 'rubbers' );
	}
}
