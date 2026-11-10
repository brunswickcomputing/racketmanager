<?php
/**
 * League cache invalidation collaborator.
 *
 * @package Racketmanager\Repositories
 */

declare( strict_types=1 );

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Competition\League;

/**
 * Handles pre-mutation state capture and cache invalidation for leagues.
 */
class League_Cache_Invalidator {
    /**
     * WordPress database object.
     *
     * @var object|null
     */
    private ?object $wpdb;

    /**
     * Leagues table name.
     *
     * @var string
     */
    private string $table_name;

    /**
     * Constructor.
     *
     * @param object|null $wpdb       Database object.
     * @param string|null $table_name Table name.
     */
    public function __construct( ?object $wpdb = null, ?string $table_name = '' ) {
        $this->wpdb       = $wpdb;
        $this->table_name = $table_name ?? '';
    }

    /**
     * Capture snapshot of league state prior to mutation.
     *
     * @param int    $id      League ID.
     * @param string $columns Columns to select when reading from database.
     * @return array{title: ?string, event_id: ?int, seasons: array} League state snapshot.
     */
    public function capture_snapshot( int $id, string $columns = '*' ): array {
        $existing = wp_cache_get( $id, 'leagues' );
        if ( $existing instanceof League ) {
            return $this->snapshot_from_league( $existing );
        }

        $db_row = null;
        if ( $this->wpdb && ! empty( $this->table_name ) ) {
            $query = $this->wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name and columns interpolation.
                "SELECT $columns FROM $this->table_name WHERE `id` = %d LIMIT 1",
                $id
            );
            $db_row = $this->wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Pre-prepared query.
        }

        if ( $db_row ) {
            return $this->snapshot_from_db_row( $db_row );
        }

        return array(
            'title'    => null,
            'event_id' => null,
            'seasons'  => array(),
        );
    }

    /**
     * Invalidate caches after league deletion.
     *
     * @param int   $id       League ID.
     * @param array $snapshot Pre-deletion league snapshot.
     * @return void
     */
    public function invalidate_after_delete( int $id, array $snapshot ): void {
        wp_cache_delete( $id, 'leagues' );

        if ( ! empty( $snapshot['title'] ) ) {
            wp_cache_delete( $snapshot['title'], 'leagues' );
        }

        $this->invalidate_match_days( $id, $snapshot['seasons'] );

        if ( ! empty( $snapshot['event_id'] ) ) {
            $this->invalidate_event_leagues_cache( (int) $snapshot['event_id'] );
        }
    }

    /**
     * Invalidate caches after title or sequence update.
     *
     * @param int    $id        League ID.
     * @param string $new_title New title.
     * @param array  $snapshot  Pre-update league snapshot.
     * @return void
     */
    public function invalidate_after_update_title( int $id, string $new_title, array $snapshot ): void {
        wp_cache_delete( $id, 'leagues' );
        wp_cache_delete( $new_title, 'leagues' );

        $old_title = $snapshot['title'] ?? null;
        if ( ! empty( $old_title ) && $old_title !== $new_title ) {
            wp_cache_delete( $old_title, 'leagues' );
        }

        if ( ! empty( $snapshot['event_id'] ) ) {
            $this->invalidate_event_leagues_cache( (int) $snapshot['event_id'] );
        }
    }

    /**
     * Invalidate caches after serialized seasons update.
     *
     * @param int   $id          League ID.
     * @param array $new_seasons New seasons configuration.
     * @param array $snapshot    Pre-update league snapshot.
     * @return void
     */
    public function invalidate_after_update_seasons( int $id, array $new_seasons, array $snapshot ): void {
        wp_cache_delete( $id, 'leagues' );

        if ( ! empty( $snapshot['title'] ) ) {
            wp_cache_delete( $snapshot['title'], 'leagues' );
        }

        $all_seasons = array_unique( array_merge( array_map( 'strval', array_keys( $new_seasons ) ), $snapshot['seasons'] ) );
        $this->invalidate_match_days( $id, $all_seasons );

        if ( ! empty( $snapshot['event_id'] ) ) {
            $this->invalidate_event_leagues_cache( (int) $snapshot['event_id'] );
        }
    }

    /**
     * Invalidate event leagues cache version.
     *
     * @param int|null $event_id Event ID.
     * @return void
     */
    public function invalidate_event_leagues_cache( ?int $event_id ): void {
        if ( empty( $event_id ) ) {
            return;
        }
        $version = (int) wp_cache_get( 'ver_event_leagues_' . $event_id, 'leagues' );
        wp_cache_set( 'ver_event_leagues_' . $event_id, $version + 1, 'leagues' );
    }

    /**
     * Get event leagues cache version.
     *
     * @param int|null $event_id Event ID.
     * @return int Cache version number.
     */
    public function get_event_leagues_version( ?int $event_id ): int {
        if ( empty( $event_id ) ) {
            return 0;
        }
        return (int) wp_cache_get( 'ver_event_leagues_' . $event_id, 'leagues' );
    }

    /**
     * Extract snapshot data from a League entity.
     *
     * @param League $league League entity.
     * @return array{title: ?string, event_id: ?int, seasons: array}
     */
    private function snapshot_from_league( League $league ): array {
        $seasons = array();
        if ( isset( $league->seasons ) && is_array( $league->seasons ) ) {
            $seasons = array_map( 'strval', array_keys( $league->seasons ) );
        }

        return array(
            'title'    => ! empty( $league->title ) ? $league->title : null,
            'event_id' => $league->event_id ?? null,
            'seasons'  => $seasons,
        );
    }

    /**
     * Extract snapshot data from a database row object.
     *
     * @param object $db_row Database row object.
     * @return array{title: ?string, event_id: ?int, seasons: array}
     */
    private function snapshot_from_db_row( object $db_row ): array {
        $seasons = array();
        if ( ! empty( $db_row->seasons ) ) {
            $unserialized = maybe_unserialize( $db_row->seasons );
            if ( is_array( $unserialized ) ) {
                $seasons = array_map( 'strval', array_keys( $unserialized ) );
            }
        }

        return array(
            'title'    => ! empty( $db_row->title ) ? $db_row->title : null,
            'event_id' => isset( $db_row->event_id ) ? (int) $db_row->event_id : null,
            'seasons'  => $seasons,
        );
    }

    /**
     * Invalidate match day caches for specified seasons.
     *
     * @param int   $league_id League ID.
     * @param array $seasons   Season keys.
     * @return void
     */
    private function invalidate_match_days( int $league_id, array $seasons ): void {
        foreach ( $seasons as $season_key ) {
            wp_cache_delete( 'last_' . $league_id . '_' . $season_key, 'leagues_match_days' );
            wp_cache_delete( 'next_' . $league_id . '_' . $season_key, 'leagues_match_days' );
            wp_cache_delete( 'current_' . $league_id . '_' . $season_key, 'leagues_match_days' );
        }
    }
}
