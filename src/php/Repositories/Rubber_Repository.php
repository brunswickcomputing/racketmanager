<?php
/**
 * Rubber_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use wpdb;

/**
 * Class to implement the rubber repository
 */
class Rubber_Repository implements Rubber_Repository_Interface {
    private wpdb $wpdb;
    private string $table_name;
    private string $players_table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_rubbers';
        $this->players_table_name = $this->wpdb->prefix . 'racketmanager_rubber_players';
    }

    public function save( object $entity ): bool|int {
        /** @var Rubber $entity */
        $data = array(
            'date'          => $entity->get_date(),
            'match_id'      => $entity->get_match_id(),
            'rubber_number' => $entity->get_rubber_number(),
            'type'          => $entity->get_type(),
            'group'         => $entity->get_group(),
            'home_points'   => $entity->get_home_points(),
            'away_points'   => $entity->get_away_points(),
            'winner_id'     => $entity->get_winner_id(),
            'loser_id'      => $entity->get_loser_id(),
            'post_id'       => $entity->get_post_id(),
            'custom'        => maybe_serialize( $entity->get_custom() ),
            'status'        => $entity->get_status(),
        );

        $format = array(
            '%s', // date
            '%d', // match_id
            '%d', // rubber_number
            '%s', // type
            '%s', // group
            '%f', // home_points
            '%f', // away_points
            '%s', // winner_id
            '%s', // loser_id
            '%d', // post_id
            '%s', // custom
            '%d', // status
        );

        if ( empty( $entity->get_id() ) ) {
            $inserted = $this->wpdb->insert( $this->table_name, $data, $format );
            if ( $inserted ) {
                $entity->set_id( $this->wpdb->insert_id );
                return $this->wpdb->insert_id;
            }
            return false;
        } else {
            return $this->wpdb->update(
                $this->table_name,
                $data,
                array(
                    'id' => $entity->get_id(),
                ),
                $format,
                array(
                    '%d',
                )
            ) !== false;
        }
    }

    public function find_by_id( $id ): ?Rubber {
        if ( empty( $id ) ) {
            return null;
        }
        $rubber = wp_cache_get( $id, 'rubbers' );

        if ( ! $rubber ) {
            $rubber = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM $this->table_name WHERE `id` = %d LIMIT 1",
                    $id
                )
            );

            if ( ! $rubber ) {
                return null;
            }
            $rubber = new Rubber( $rubber );

            wp_cache_set( $rubber->id, $rubber, 'rubbers' );
        }

        return $rubber;
    }

    /**
     * Find all rubbers for a given fixture ID.
     *
     * @param int $fixture_id
     * @param int|null $player_id Optional player ID to filter by.
     *
     * @return Rubber[]
     */
    public function find_by_fixture_id( int $fixture_id, ?int $player_id = null ): array {
        $sql_select = "SELECT r.* FROM $this->table_name r";
        $sql_where  = $this->wpdb->prepare( " WHERE r.`match_id` = %d", $fixture_id );

        if ( $player_id ) {
            $sql_select .= ", $this->players_table_name rp";
            $sql_where  .= $this->wpdb->prepare( " AND r.`id` = rp.`rubber_id` AND rp.`player_id` = %d", $player_id );
        }

        $sql = $sql_select . $sql_where . " ORDER BY r.`date` ASC, r.`id` ASC";

        $results = $this->wpdb->get_results( $sql );

        $rubbers = [];
        foreach ( $results as $row ) {
            $rubbers[] = new Rubber( $row );
        }

        return $rubbers;
    }

    /**
     * Count rubbers for a given fixture ID.
     *
     * @param int $fixture_id
     * @param int|null $player_id Optional player ID to filter by.
     *
     * @return int
     */
    public function count_by_fixture_id( int $fixture_id, ?int $player_id = null ): int {
        if ( ! $player_id ) {
            return (int) $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT count(*) FROM $this->table_name WHERE `match_id` = %d",
                    $fixture_id
                )
            );
        }

        $sql = $this->wpdb->prepare(
            "SELECT count(*) FROM $this->table_name r, $this->players_table_name rp WHERE r.`id` = rp.`rubber_id` AND r.`match_id` = %d AND rp.`player_id` = %d",
            $fixture_id,
            $player_id
        );

        return (int) $this->wpdb->get_var( $sql );
    }
    /**
     * Delete rubbers for a given fixture ID.
     *
     * @param int $fixture_id
     * @return bool
     */
    public function delete_by_fixture_id( int $fixture_id ): bool {
        return $this->wpdb->delete(
            $this->table_name,
            array( 'match_id' => $fixture_id ),
            array( '%d' )
        ) !== false;
    }

    /**
     * Update rubber dates for a given fixture ID.
     *
     * @param int $fixture_id
     * @param string $date
     * @return bool
     */
    public function update_date_by_fixture_id( int $fixture_id, string $date ): bool {
        return $this->wpdb->update(
            $this->table_name,
            array( 'date' => $date ),
            array( 'match_id' => $fixture_id ),
            array( '%s' ),
            array( '%d' )
        ) !== false;
    }

    /**
     * Delete a rubber by its ID.
     *
     * @param int $id
     *
     * @return bool True on success, false on failure.
     */
    public function delete( int $id ): bool {
        return $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) ) !== false;
    }
}
