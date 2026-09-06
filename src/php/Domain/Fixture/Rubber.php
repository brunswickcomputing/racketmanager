<?php
/**
 * RacketManager-Rubber API: RacketManager-rubber class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Rubber
 */

namespace Racketmanager\Domain\Fixture;

use DateMalformedStringException;
use DateTime;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Util\Util;
use stdClass;
use function Racketmanager\get_event;
use function Racketmanager\get_match;
use function Racketmanager\get_team;

/**
 * Class to implement the Rubber object
 */
class Rubber {
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
     * @var int
     */
    public int $winner_id;
    /**
     * Loser id variable
     *
     * @var int
     */
    public int $loser_id;
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
    private Registration_Service $registration_service;

    /**
     * Get rubber instance function
     *
     * @param int|null $rubber_id rubber id.
     * @return null|object rubber.
     */
    public static function get_instance( ?int $rubber_id = null ): object|null {
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
            $rubber = new Rubber( $rubber );
            wp_cache_set( $rubber_id, $rubber, 'rubbers' );
        }
        return $rubber;
    }
    /**
     * Rubber construction function
     *
     * @param object|null $rubber rubber object.
     */
    public function __construct( ?object $rubber = null ) {
        global $racketmanager;
        $c                          = $racketmanager->container;
        $this->registration_service = $c->get( 'registration_service' );

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
     * Get rubber id.
     *
     * @return int|false
     */
    public function get_id(): int|false {
        return $this->id;
    }

    /**
     * Set rubber id.
     *
     * @param int|false $id
     * @return void
     */
    public function set_id( int|false $id ): void {
        $this->id = $id;
    }

    /**
     * Get match id.
     *
     * @return int
     */
    public function get_match_id(): int {
        return $this->match_id;
    }

    /**
     * Set match id.
     *
     * @param int $match_id
     * @return void
     */
    public function set_match_id( int $match_id ): void {
        $this->match_id = $match_id;
    }

    /**
     * Get rubber date (DB format string).
     *
     * @return string
     */
    public function get_date(): string {
        return $this->date;
    }

    /**
     * Set rubber date (DB format string).
     *
     * @param string $date
     * @return void
     */
    public function set_date( string $date ): void {
        $this->date = $date;
    }

    /**
     * Get rubber group.
     *
     * @return string
     */
    public function get_group(): string {
        return $this->group;
    }

    /**
     * Set rubber group.
     *
     * @param string $group
     * @return void
     */
    public function set_group( string $group ): void {
        $this->group = $group;
    }

    /**
     * Get rubber type.
     *
     * @return string
     */
    public function get_type(): string {
        return $this->type;
    }

    /**
     * Set rubber type.
     *
     * @param string $type
     * @return void
     */
    public function set_type( string $type ): void {
        $this->type = $type;
    }

    /**
     * Get rubber number.
     *
     * @return int
     */
    public function get_rubber_number(): int {
        return $this->rubber_number;
    }

    /**
     * Set rubber number.
     *
     * @param int $rubber_number
     * @return void
     */
    public function set_rubber_number( int $rubber_number ): void {
        $this->rubber_number = $rubber_number;
    }

    /**
     * Get status.
     *
     * @return int|null
     */
    public function get_status(): ?int {
        return $this->status;
    }

    /**
     * Set status.
     *
     * @param int|null $status
     * @return void
     */
    public function set_status( ?int $status ): void {
        $this->status = $status;
    }

    /**
     * Get home points.
     *
     * @return float|null
     */
    public function get_home_points(): ?float {
        return $this->home_points;
    }

    /**
     * Set home points.
     *
     * @param float|null $home_points
     * @return void
     */
    public function set_home_points( ?float $home_points ): void {
        $this->home_points = $home_points;
    }

    /**
     * Get away points.
     *
     * @return float|null
     */
    public function get_away_points(): ?float {
        return $this->away_points;
    }

    /**
     * Set away points.
     *
     * @param float|null $away_points
     * @return void
     */
    public function set_away_points( ?float $away_points ): void {
        $this->away_points = $away_points;
    }

    /**
     * Get winner id.
     *
     * @return int
     */
    public function get_winner_id(): int {
        return (int) $this->winner_id;
    }

    /**
     * Set winner id.
     *
     * @param int $winner_id
     * @return void
     */
    public function set_winner_id( int $winner_id ): void {
        $this->winner_id = $winner_id;
    }

    /**
     * Get loser id.
     *
     * @return int
     */
    public function get_loser_id(): int {
        return (int) $this->loser_id;
    }

    /**
     * Set loser id.
     *
     * @param int $loser_id
     * @return void
     */
    public function set_loser_id( int $loser_id ): void {
        $this->loser_id = $loser_id;
    }

    /**
     * Get post id.
     *
     * @return int
     */
    public function get_post_id(): int {
        return $this->post_id;
    }

    /**
     * Set post id.
     *
     * @param int $post_id
     * @return void
     */
    public function set_post_id( int $post_id ): void {
        $this->post_id = $post_id;
    }

    /**
     * Get custom data.
     *
     * @return array
     */
    public function get_custom(): array {
        $custom = $this->custom;
        return is_array( $custom ) ? $custom : array();
    }

    /**
     * Set custom data.
     *
     * @param array $custom
     * @return void
     */
    public function set_custom( array $custom ): void {
        $this->custom = $custom;
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
    public function set_players( array $players ): void {
        global $wpdb;
        $this->players = array();
        foreach ( $players as $player_team => $player_ref ) {
            foreach ( $player_ref as $player_num => $player ) {
                if ( empty( $player ) ) {
                    continue;
                }
                $club_player = $this->registration_service->get_registration( (int) $player );
                if ( $club_player ) {
                    $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                        $wpdb->prepare(
                            "REPLACE INTO $wpdb->racketmanager_rubber_players ( `rubber_id`, `player_ref`, `player_team`, `player_id`, `club_player_id` ) VALUES ( %d, %d, %s, %d, %d )",
                            $this->id,
                            $player_num,
                            $player_team,
                            $club_player->user_id,
                            $club_player->registration_id,
                        )
                    );
                    $this->players[ $player_team ][ $player_num ] = $club_player;
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
            $this->players[ $player->player_team ][ $player->player_ref ] = $this->registration_service->get_registration( $player->club_player_id );
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
    public function is_walkover(): bool {
        return 2 === $this->status;
    }

    public function is_shared(): bool {
        return 4 === $this->status;
    }

    /**
     * Reset rubber result function
     *
     * @return void
     */
    public function reset_result(): void {
        global $wpdb;
        // Delete rubber players
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_rubber_players WHERE `rubber_id` = %d",
                $this->id,
            )
        );
        // Reset rubber result by initialising points/winner/loser/status/custom
        $this->players = array();
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
        wp_cache_set( $this->id, $this, 'rubbers' );
    }
}
