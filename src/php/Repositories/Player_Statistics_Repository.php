<?php
declare( strict_types=1 );
/**
 * Player_Statistics_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repository
 */

namespace Racketmanager\Repositories;

use Racketmanager\Repositories\Interfaces\Player_Statistics_Repository_Interface;
use Racketmanager\Util\Util;
use wpdb;

/**
 * Class to implement the Player statistics read-model repository
 */
class Player_Statistics_Repository implements Player_Statistics_Repository_Interface {
    private wpdb $wpdb;

    /**
     * Create a new Player_Statistics_Repository instance.
     *
     * @param wpdb|null $db
     */
    public function __construct(
        ?wpdb $db = null
    ) {
        global $wpdb;
        $this->wpdb = $db ?? $wpdb;
    }

    /**
     * Competitions (with season) in which the player has played a rubber.
     *
     * @param int $player_id
     * @param string|null $type
     * @param string|null $season
     *
     * @return array
     */
    public function find_competitions_played( int $player_id, ?string $type = null, ?string $season = null ): array {
        $wpdb         = $this->wpdb;
        $search_terms = array();
        if ( $type ) {
            $search_terms[] = $wpdb->prepare( 'c.`type` = %s', $type );
        }
        if ( $season ) {
            $search_terms[] = $wpdb->prepare( 'm.`season` = %s', $season );
        }
        $sql          = $wpdb->prepare(
            "SELECT c.id, c.name, m.season FROM $wpdb->racketmanager_rubber_players rp, $wpdb->racketmanager_rubbers r, $wpdb->racketmanager_matches m, $wpdb->racketmanager l, $wpdb->racketmanager_events e, $wpdb->racketmanager_competitions c WHERE rp.`player_id` = %d AND rp.rubber_id = r.id AND r.match_id = m.id AND m.league_id = l.id AND l.event_id = e.id AND e.competition_id = c.id",
            $player_id
        );
        $sql         .= Util::search_string( $search_terms );
        $sql         .= ' GROUP BY c.id, c.name, m.season';
        $sql         .= ' ORDER BY m.season DESC, c.name ASC';
        $competitions = wp_cache_get( md5( $sql ), 'player_competitions' );
        if ( ! $competitions ) {
            $competitions = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
            wp_cache_set( md5( $sql ), $competitions, 'player_competitions' );
        }
        return is_array( $competitions ) ? $competitions : array();
    }

    /**
     * Rubber-level rows for the player's team statistics.
     *
     * @param int $player_id
     * @param string|null $season
     *
     * @return array
     */
    public function find_team_stats( int $player_id, ?string $season = null ): array {
        $wpdb         = $this->wpdb;
        $search_terms = array();
        if ( $season ) {
            $search_terms[] = $wpdb->prepare( 'm.`season` = %s', $season );
        }
        $search  = Util::search_string( $search_terms );
        $sql     = $wpdb->prepare(
            "SELECT 'teams' as `stat_type`, m.`season`, e.`type` ,rp.`player_team`, m.`home_team`, m.`away_team`, r.`winner_id`, r.`loser_id` FROM $wpdb->racketmanager_rubber_players rp, $wpdb->racketmanager_rubbers r, $wpdb->racketmanager_matches m, $wpdb->racketmanager l, $wpdb->racketmanager_events e WHERE rp.`player_id` = %d AND rp.`rubber_id` = r.`id` AND r.`match_id` = m.`id` AND m.`league_id` = l.`id` AND l.`event_id` = e.`id` ",
            $player_id
        );
        $sql    .= $search . ' ORDER BY m.`season`, e.`type`';
        $matches = wp_cache_get( md5( $sql ), 'player_stats_teams' );
        if ( ! $matches ) {
            $matches = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
            wp_cache_set( md5( $sql ), $matches, 'player_stats_teams' );
        }
        return is_array( $matches ) ? $matches : array();
    }

    /**
     * Match-level rows for the player's player statistics.
     *
     * @param int $player_id
     * @param string|null $season
     *
     * @return array
     */
    public function find_player_stats( int $player_id, ?string $season = null ): array {
        $wpdb         = $this->wpdb;
        $search_terms = array();
        if ( $season ) {
            $search_terms[] = $wpdb->prepare( 'm.`season` = %s', $season );
        }
        $search  = Util::search_string( $search_terms );
        $sql     = $wpdb->prepare(
            "SELECT 'players' as `stat_type`,m.`season`, e.`type` ,tp.`team_id`, m.`home_team`, m.`away_team`, m.`winner_id`, m.`loser_id` FROM $wpdb->racketmanager_team_players tp, $wpdb->racketmanager_teams t,$wpdb->racketmanager_matches m, $wpdb->racketmanager l, $wpdb->racketmanager_events e WHERE tp.`player_id` = %d AND tp.`team_id` = t.`id` AND (m.`home_team` = t.`id` OR m.`away_team` = t.`id`) AND m.`league_id` = l.`id` AND l.`event_id` = e.`id` ",
            $player_id
        );
        $sql    .= $search . ' ORDER BY m.`season`, e.`type`';
        $matches = wp_cache_get( md5( $sql ), 'player_stats_players' );
        if ( ! $matches ) {
            $matches = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
            wp_cache_set( md5( $sql ), $matches, 'player_stats_players' );
        }
        return is_array( $matches ) ? $matches : array();
    }
}
