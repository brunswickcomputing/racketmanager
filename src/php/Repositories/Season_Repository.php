<?php
declare( strict_types=1 );
/**
 * Season_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Season\Season;
use Racketmanager\Domain\Season\Season_Factory;
use Racketmanager\Repositories\Interfaces\Season_Repository_Interface;
use wpdb;

/**
 * Class to implement the Season repository
 */
class Season_Repository implements Season_Repository_Interface {

    private object $wpdb;
    private string $table_name;

    /**
     * Constructor
     *
     * @param object|null $wpdb
     */
    public function __construct( ?object $wpdb = null ) {
        if ( null === $wpdb ) {
            global $wpdb;
        }
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
                wp_cache_set( 'id_' . $entity->get_id(), $entity, 'seasons' );
                if ( $entity->get_name() ) {
                    wp_cache_set( 'name_' . $entity->get_name(), $entity, 'seasons' );
                }
                return $this->wpdb->insert_id;
            }
            return false;
        } else {
            wp_cache_set( 'id_' . $entity->get_id(), $entity, 'seasons' );
            if ( $entity->get_name() ) {
                wp_cache_set( 'name_' . $entity->get_name(), $entity, 'seasons' );
            }

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
     * Find a season by its ID or Name.
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
            $id_val    = (int) $id;
            $search    = $this->wpdb->prepare(
                '`id` = %d',
                $id_val
            );
            $cache_key = 'id_' . $id_val;
        } else {
            $search    = $this->wpdb->prepare(
                '`name` = %s',
                (string) $id
            );
            $cache_key = 'name_' . $id;
        }
        $season = wp_cache_get( $cache_key, 'seasons' );

        if ( ! $season ) {
            $season = $this->wpdb->get_row(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT * FROM `$this->table_name` WHERE $search"
            );
            if ( ! $season ) {
                return null;
            }
            $season = Season_Factory::from_object( $season );
            wp_cache_set( $cache_key, $season, 'seasons' );
            if ( 'id' === $type && $season->get_name() ) {
                wp_cache_set( 'name_' . $season->get_name(), $season, 'seasons' );
            } elseif ( 'id' !== $type && $season->get_id() ) {
                wp_cache_set( 'id_' . $season->get_id(), $season, 'seasons' );
            }
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
                return Season_Factory::from_object( $row );
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
        $season = $this->find_by_id( $id, 'id' );
        if ( $season ) {
            wp_cache_delete( 'id_' . $id, 'seasons' );
            if ( $season->get_name() ) {
                wp_cache_delete( 'name_' . $season->get_name(), 'seasons' );
            }
        }
        return $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) ) !== false;
    }

}
