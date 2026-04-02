<?php
/**
 * Results_Report_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Results_Report;
use Racketmanager\Repositories\Interfaces\Results_Report_Repository_Interface;
use wpdb;

/**
 * Class to implement the Results Report repository
 */
class Results_Report_Repository implements Results_Report_Repository_Interface {
    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_results_report';
    }

    /**
     * Save a results report entry.
     *
     * @param Results_Report $results_report
     * @return int|bool
     */
    public function save( Results_Report $results_report ) {
        $data = array(
            'match_id'      => $results_report->match_id,
            'result_object' => $results_report->result_object,
        );

        $format = array(
            '%d', // match_id
            '%s', // result_object
        );

        if ( empty( $results_report->id ) ) {
            $inserted = $this->wpdb->insert( $this->table_name, $data, $format );
            if ( $inserted ) {
                $results_report->id = (int) $this->wpdb->insert_id;
                return $results_report->id;
            }
            return false;
        } else {
            return $this->wpdb->update(
                $this->table_name,
                $data,
                array(
                    'id' => $results_report->id,
                ),
                $format,
                array(
                    '%d',
                )
            ) !== false;
        }
    }

    /**
     * Find a results report by ID.
     *
     * @param int $id
     * @return Results_Report|null
     */
    public function find_by_id( int $id ): ?Results_Report {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $this->table_name WHERE `id` = %d LIMIT 1",
                $id
            )
        );

        if ( ! $row ) {
            return null;
        }

        return new Results_Report( $row, false );
    }

    /**
     * Find results report for a given fixture ID.
     *
     * @param int $fixture_id
     * @return Results_Report|null
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

        return new Results_Report( $row, false );
    }

    /**
     * Delete results report entries for a given fixture ID.
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
}
