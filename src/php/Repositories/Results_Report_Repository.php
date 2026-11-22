<?php
declare( strict_types=1 );
/**
 * Results_Report_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Communication\Results_Report;
use Racketmanager\Domain\Communication\Results_Report_Factory;
use Racketmanager\Repositories\Interfaces\Results_Report_Repository_Interface;
use wpdb;

/**
 * Class to implement the Results Report repository
 */
class Results_Report_Repository implements Results_Report_Repository_Interface {
    private wpdb $wpdb;
    private string $table_name;

    /**
     * Initialises the results report repository with a WordPress database connection.
     *
     * @param wpdb|null $wpdb Optional WordPress database connection. Defaults to the global database instance.
     *
     * @return void
     */
    public function __construct( ?wpdb $wpdb = null ) {
        $this->wpdb       = $wpdb ?? $GLOBALS['wpdb'];
        $this->table_name = $this->wpdb->prefix . 'racketmanager_results_report';
    }

    /**
     * Saves a results report entity to the repository.
     *
     * Creates a new record when the entity has no identifier, or updates the existing record when an identifier is present.
     *
     * @param object $entity Results report entity containing the match identifier and result object to persist.
     *
     * @return int|bool Inserted record identifier when a new record is created, true when an existing record is updated, or false on failure.
     */
    public function save( object $entity ): int|bool {
        /** @var Results_Report $entity */
        $data = array(
            'match_id'      => $entity->match_id,
            'result_object' => $entity->result_object,
        );

        $format = array(
            '%d', // match_id.
            '%s', // result_object.
        );

        if ( empty( $entity->id ) ) {
            $inserted = $this->wpdb->insert( $this->table_name, $data, $format );
            if ( $inserted ) {
                $entity->set_id( $this->wpdb->insert_id );
                return $entity->id;
            }
            return false;
        } else {
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
     * Finds a results report by its identifier.
     *
     * @param int|string|null $id Results report identifier.
     *
     * @return Results_Report|null Results report instance when found, or null when no matching report exists.
     */
    public function find_by_id( int|string|null $id ): ?Results_Report {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $this->table_name WHERE `id` = %d LIMIT 1",
                $id
            )
        );

        if ( ! $row ) {
            return null;
        }

        return Results_Report_Factory::from_object( $row );
    }

    /**
     * Finds a results report by its fixture identifier.
     *
     * @param int $fixture_id Fixture identifier used to locate the results report.
     *
     * @return Results_Report|null Results report for the fixture, or null if no matching report is found.
     */
    public function find_by_fixture_id( int $fixture_id ): ?Results_Report {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $this->table_name WHERE `match_id` = %d LIMIT 1",
                $fixture_id
            )
        );

        if ( ! $row ) {
            return null;
        }

        return Results_Report_Factory::from_object( $row );
    }

    /**
     * Deletes report records associated with the specified fixture.
     *
     * @param int $fixture_id Fixture identifier used to match report records for deletion.
     *
     * @return bool True if the delete operation completes successfully, false on database error.
     */
    public function delete_by_fixture_id( int $fixture_id ): bool {
        return $this->wpdb->delete(
            $this->table_name,
            array( 'match_id' => $fixture_id ),
            array( '%d' )
        ) !== false;
    }

    /**
     * Deletes a record from the repository by its identifier.
     *
     * @param int $id Record identifier to delete.
     *
     * @return bool True if the record was deleted, false otherwise.
     */
    public function delete( int $id ): bool {
        return (bool) $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );
    }
}
