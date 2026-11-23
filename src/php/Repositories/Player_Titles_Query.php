<?php
/**
 * Player titles query executor.
 *
 * @package Racketmanager\Repositories
 */

declare( strict_types=1 );

namespace Racketmanager\Repositories;

use wpdb;

/**
 * Retrieves and groups final match titles for a player by season and tournament.
 */
class Player_Titles_Query {
    /**
     * WordPress database instance.
     *
     * @var wpdb
     */
    private wpdb $wpdb;

    /**
     * Initialise the query with a WordPress database connection.
     *
     * Uses the provided database object when supplied, otherwise falls back to the global WordPress database instance.
     *
     * @param wpdb|null $db Optional WordPress database connection.
     */
    public function __construct( ?wpdb $db = null ) {
        global $wpdb;
        $this->wpdb = $db ?? $wpdb;
    }

    /**
     * Get final match title records for a player, grouped by season and tournament.
     *
     * Retrieves finals involving teams associated with the player and organises the
     * results by season, then by tournament name.
     *
     * @param int|string $player_id Player ID.
     *
     * @return array
     */
    public function execute( int|string $player_id ): array {
        $team_players_table = $this->wpdb->prefix . 'racketmanager_team_players';
        $matches_table      = $this->wpdb->prefix . 'racketmanager_matches';
        $leagues_table      = $this->wpdb->prefix . 'racketmanager_leagues';
        $events_table       = $this->wpdb->prefix . 'racketmanager_events';
        $competitions_table = $this->wpdb->prefix . 'racketmanager_competitions';
        $tournaments_table  = $this->wpdb->prefix . 'racketmanager_tournaments';

        $sql     = $this->wpdb->prepare(
            "SELECT m.`season`, t.`name` as `tournament`, e.`name` as `draw`, l.`title` as `league_name`, l.`title` as `title`, tp.`team_id`, m.`winner_id`, m.`loser_id` FROM $team_players_table tp, $matches_table m, $leagues_table l, $events_table e, $competitions_table c, $tournaments_table t WHERE tp.`player_id` = %d AND (tp.`team_id` = m.`winner_id` OR tp.`team_id` = m.`loser_id`) AND m.`final` = 'final' AND m.`league_id` = l.`id` AND l.`event_id` = e.`id` AND e.competition_id = c.`id` AND t.`competition_id` = c.`id` AND t.`season` = m.`season` ORDER BY m.`season` DESC, t.`name`", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $player_id,
        );
        $matches = wp_cache_get( md5( $sql ), 'player_finals' );
        if ( ! $matches ) {
            $matches = $this->wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            wp_cache_set( md5( $sql ), $matches, 'player_finals' );
        }
        $seasons = array();
        foreach ( $matches as $match ) {
            $season     = $match->season;
            $tournament = $match->tournament;
            if ( false === array_key_exists( $season, $seasons ) ) {
                $seasons[ $season ] = array();
            }
            if ( false === array_key_exists( $tournament, $seasons[ $season ] ) ) {
                $seasons[ $season ][ $tournament ] = array();
            }
            $seasons[ $season ][ $tournament ][] = $match;
        }

        return $seasons;
    }
}
