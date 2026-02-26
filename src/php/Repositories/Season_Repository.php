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
use wpdb;

class Season_Repository {

    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb       = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_seasons';
    }

    public function save( Season $season ): bool|int {
        $data        = array(
            'name' => $season->get_name(),
        );
        $data_format = array(
            '%s',
        );
        if ( empty( $season->get_id() ) ) {
            $result = $this->wpdb->insert(
                $this->table_name,
                $data,
                $data_format,
            );
            $season->set_id( $this->wpdb->insert_id );
            wp_cache_set( $season->get_id(), $season, 'seasons' );

            return $result !== false;
        } else {
            wp_cache_set( $season->get_id(), $season, 'seasons' );

            return $this->wpdb->update(
                $this->table_name,
                $data, // Data to update
                array(
                    'id' => $season->get_id()
                ), // Where clause
                $data_format,
                array(
                    '%d'
                ) // Where format
            );
        }

    }

    public function find_by_id( null|int|string $season_id ): ?Season {
        if ( ! $season_id ) {
            return null;
        }
        if ( is_numeric( $season_id ) ) {
            $season_id = (int) $season_id;
            $search    = $this->wpdb->prepare(
                '`id` = %d',
                $season_id
            );
        } else {
            $search = $this->wpdb->prepare(
                '`name` = %s',
                $season_id
            );
        }
        $season = wp_cache_get( $season_id, 'seasons' );

        if ( ! $season ) {
            $season = $this->wpdb->get_row(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT * FROM `$this->table_name` WHERE $search"
            );
            if ( ! $season ) {
                return null;
            }
            $season = new Season( $season );
            wp_cache_set( $season_id, $season, 'seasons' );
        }

        return $season;
    }

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

    public function delete( int $id ): int|false {
        return $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );
    }

}
