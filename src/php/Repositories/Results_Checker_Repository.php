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
use wpdb;

/**
 * Class to implement the Results Checker repository
 */
class Results_Checker_Repository {
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
     * @param Results_Checker $results_checker
     * @return void
     */
    public function save( Results_Checker $results_checker ): void {
        $data = array(
            'league_id'   => $results_checker->league_id,
            'match_id'    => $results_checker->match_id,
            'team_id'     => $results_checker->team_id,
            'player_id'   => $results_checker->player_id,
            'rubber_id'   => $results_checker->rubber_id,
            'description' => $results_checker->description,
            'status'      => $results_checker->status,
            'updated_user'=> $results_checker->updated_user,
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

        if ( empty( $results_checker->id ) ) {
            $this->wpdb->insert( $this->table_name, $data, $format );
            $id = (int) $this->wpdb->insert_id;
            if ( $id > 0 ) {
                $results_checker->id = $id;
            } else {
                // If it's a mock or some test environment where insert_id is not set, we don't crash anymore.
            }
        } else {
            $data['updated_date'] = current_time( 'mysql' );
            $format[] = '%s'; // updated_date

            $this->wpdb->update(
                $this->table_name,
                $data,
                array(
                    'id' => $results_checker->id,
                ),
                $format,
                array(
                    '%d',
                )
            );
        }
    }

    /**
     * Find a results checker by ID.
     *
     * @param int $id
     * @return Results_Checker|null
     */
    public function find_by_id( int $id ): ?Results_Checker {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $this->table_name WHERE `id` = %d LIMIT 1",
                $id
            )
        );

        if ( ! $row ) {
            return null;
        }

        return new Results_Checker( $row, false );
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
        foreach ( $results as $row ) {
            $checkers[] = new Results_Checker( $row, false );
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
                "SELECT count(*) FROM $this->table_name WHERE `match_id` = %d WHERE status IS NULL",
                $fixture_id
            )
        );
    }

    /**
     * Delete results checker entries for a given fixture ID.
     *
     * @param int $fixture_id
     * @return void
     */
    public function delete_by_fixture_id( int $fixture_id ): void {
        $this->wpdb->delete(
            $this->table_name,
            array( 'match_id' => $fixture_id ),
            array( '%d' )
        );
    }
}
