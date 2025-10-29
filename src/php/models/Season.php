<?php
/**
 * Season API: season class (moved to PSR-4)
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Season
 */

namespace Racketmanager\models;

/**
 * Class to implement the season object
 */
class Season {
    public string|null $name;
    private int $id;

    /**
     * Get class instance
     *
     * @param int $season_id id.
     */
    public static function get_instance( int $season_id ) {
        global $wpdb;
        if ( ! $season_id ) {
            return false;
        }
        $season = wp_cache_get( $season_id, 'season' );

        if ( ! $season ) {
            $season = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT `id`, `name` FROM $wpdb->racketmanager_seasons WHERE `id` = %d LIMIT 1",
                    $season_id
                )
            );  // db call ok.

            if ( ! $season ) {
                return false;
            }

            $season = new Season( $season );

            wp_cache_set( $season->id, $season, 'season' );
        }

        return $season;
    }

    /**
     * Construct class instance
     *
     * @param object|null $season invoice object.
     */
    public function __construct( ?object $season = null ) {
        if ( ! is_null( $season ) ) {
            foreach ( get_object_vars( $season ) as $key => $value ) {
                $this->$key = $value;
            }
            if ( ! isset( $this->id ) ) {
                $this->add();
            }
        }
    }

    /**
     * Add new season
     */
    private function add(): void {
        global $wpdb;
        $result = $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "INSERT INTO $wpdb->racketmanager_seasons (`name`) VALUES (%s)",
                $this->name,
            )
        );
        if ( $result ) {
            $this->id = $wpdb->insert_id;
        }
    }
    /**
     * Delete season
     */
    public function delete(): void {
        global $wpdb;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_seasons WHERE `id` = %d",
                $this->id
            )
        );
        wp_cache_delete( $this->id, 'season' );
    }

}
