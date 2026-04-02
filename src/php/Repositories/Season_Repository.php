<?php
/**
 * Season_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Season;
use Racketmanager\Repositories\Interfaces\Season_Repository_Interface;
use wpdb;

/**
 * Class to implement the Season repository
 */
class Season_Repository implements Season_Repository_Interface {

    private wpdb $wpdb;
    private string $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb       = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_seasons';
    }

    /**
     * Save a season.
     *
     * @param object $entity
     *
     * @return bool|int
     */
    public function save( object $entity ): bool|int {
        /** @var Season $entity */
        $data        = array(
            'name' => $entity->get_name(),
        );
        $data_format = array(
            '%s',
        );
        if ( empty( $entity->get_id() ) ) {
            $inserted = $this->wpdb->insert(
                $this->table_name,
                $data,
                $data_format,
            );
            if ( $inserted ) {
                $entity->set_id( $this->wpdb->insert_id );
                wp_cache_set( $entity->get_id(), $entity, 'seasons' );
                return $this->wpdb->insert_id;
            }
            return false;
        } else {
            wp_cache_set( $entity->get_id(), $entity, 'seasons' );

            return $this->wpdb->update(
                $this->table_name,
                $data, // Data to update
                array(
                    'id' => $entity->get_id()
                ), // Where clause
                $data_format,
                array(
                    '%d'
                ) // Where format
            ) !== false;
        }
    }

    /**
     * Find a season by its ID.
     *
     * @param int|string|null $id
     * @param string $type
     *
     * @return Season|null
     */
    public function find_by_id( int|string|null $id, string $type = 'id' ): ?Season {
        if ( ! $id ) {
            return null;
        }
        if ( 'id' === $type ) {
            $id     = (int) $id;
            $search = $this->wpdb->prepare(
                '`id` = %d',
                $id
            );
        } else {
            $search = $this->wpdb->prepare(
                '`name` = %s',
                $id
            );
        }
        $season = wp_cache_get( $id, 'seasons' );

        if ( ! $season ) {
            $season = $this->wpdb->get_row(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT * FROM `$this->table_name` WHERE $search"
            );
            if ( ! $season ) {
                return null;
            }
            $season = new Season( $season );
            wp_cache_set( $id, $season, 'seasons' );
        }

        return $season;
    }

    /**
     * Find all seasons.
     *
     * @return Season[]
     */
    public function find_all(): array {
        $query   = "SELECT * FROM `$this->table_name` ORDER BY `name` DESC";
        $results = $this->wpdb->get_results( $query );

        return array_map(
            function ( $row ) {
                return new Season( $row );
            },
            $results
        );
    }

    /**
     * Delete a season by ID.
     *
     * @param int $id
     *
     * @return bool
     */
    public function delete( int $id ): bool {
        return $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) ) !== false;
    }

}
