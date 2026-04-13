<?php
/**
 * Results_Checker_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Results_Checker;
use Racketmanager\Domain\DTOs\Results_Checker_Data;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use wpdb;

/**
 * Class to implement the Results Checker repository
 */
class Results_Checker_Repository implements Results_Checker_Repository_Interface {
    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_results_checker';
    }

    /**
     * Save a results checker entry.
     *
     * @param object $entity
     *
     * @return int|bool
     */
    public function save( object $entity ): bool|int {
        /** @var Results_Checker $entity */
        $data = array(
            'league_id'    => $entity->league_id,
            'match_id'     => $entity->match_id,
            'team_id'      => $entity->team_id,
            'player_id'    => $entity->player_id,
            'rubber_id'    => $entity->rubber_id,
            'description'  => $entity->description,
            'status'       => $entity->status,
            'updated_user' => $entity->updated_user,
        );

        $format = array(
            '%d', // league_id
            '%d', // match_id
            '%d', // team_id
            '%d', // player_id
            '%d', // rubber_id
            '%s', // description
            '%d', // status
            '%d', // updated_user
        );

        if ( empty( $entity->id ) ) {
            $inserted = $this->wpdb->insert( $this->table_name, $data, $format );
            if ( $inserted ) {
                $entity->id = (int) $this->wpdb->insert_id;
                return $entity->id;
            }
            return false;
        } else {
            $data['updated_date'] = current_time( 'mysql' );
            $format[] = '%s'; // updated_date

            return $this->wpdb->update(
                $this->table_name,
                $data,
                array(
                    'id' => $entity->id,
                ),
                $format,
                array(
                    '%d',
                )
            ) !== false;
        }
    }

    /**
     * Find a results checker by ID.
     *
     * @param int|string|null $id
     *
     * @return Results_Checker|null
     */
    public function find_by_id( int|string|null $id ): ?Results_Checker {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $this->table_name WHERE `id` = %d LIMIT 1",
                $id
            )
        );

        if ( ! $row ) {
            return null;
        }

        $data = new Results_Checker_Data(
            id: (int) $row->id,
            league_id: (int) $row->league_id,
            match_id: (int) $row->match_id,
            team_id: (int) $row->team_id,
            player_id: isset( $row->player_id ) ? (int) $row->player_id : null,
            rubber_id: isset( $row->rubber_id ) ? (int) $row->rubber_id : null,
            description: $row->description ?? '',
            status: isset( $row->status ) ? (int) $row->status : null,
            updated_user: isset( $row->updated_user ) ? (int) $row->updated_user : null,
            updated_date: $row->updated_date ?? null
        );

        return new Results_Checker( $data );
    }

    /**
     * Find results checkers for a given fixture ID.
     *
     * @param int $fixture_id
     * @return Results_Checker[]
     */
    public function find_by_fixture_id( int $fixture_id ): array {
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM $this->table_name WHERE `match_id` = %d ORDER BY `id`",
                $fixture_id
            )
        );

        $checkers = [];
        if ( is_array( $results ) ) {
            foreach ( $results as $row ) {
            $data = new Results_Checker_Data(
                id: (int) $row->id,
                league_id: (int) $row->league_id,
                match_id: (int) $row->match_id,
                team_id: (int) $row->team_id,
                player_id: isset( $row->player_id ) ? (int) $row->player_id : null,
                rubber_id: isset( $row->rubber_id ) ? (int) $row->rubber_id : null,
                description: $row->description ?? '',
                status: isset( $row->status ) ? (int) $row->status : null,
                updated_user: isset( $row->updated_user ) ? (int) $row->updated_user : null,
                updated_date: $row->updated_date ?? null
            );
            $checkers[] = new Results_Checker( $data );
        }
    }

        return $checkers;
    }

    /**
     * Check if a fixture has any results checker entries.
     *
     * @param int $fixture_id
     * @return bool
     */
    public function has_results_check( int $fixture_id ): bool {
        return (bool) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT count(*) FROM $this->table_name WHERE `match_id` = %d AND status IS NULL",
                $fixture_id
            )
        );
    }

    /**
     * Delete results checker entries for a given fixture ID.
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

    public function delete( int $id ): bool {
        return (bool) $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );
    }
}
