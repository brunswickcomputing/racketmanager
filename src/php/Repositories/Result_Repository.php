<?php
/**
 * Result_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

declare( strict_types=1 );

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Communication\Results_Report;
use Racketmanager\Repositories\Interfaces\Result_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Report_Repository_Interface;
use stdClass;
use wpdb;

/**
 * Class to implement the Result repository
 */
class Result_Repository implements Result_Repository_Interface {
    private wpdb $wpdb;
    private string $table_name;
    private Results_Report_Repository_Interface $results_report_repository;

    public function __construct( ?wpdb $wpdb = null, ?Results_Report_Repository_Interface $results_report_repository = null ) {
        $this->wpdb                      = $wpdb ?? $GLOBALS['wpdb'];
        $this->table_name                = $this->wpdb->prefix . 'racketmanager_matches';
        $this->results_report_repository = $results_report_repository ?? new Results_Report_Repository( $this->wpdb );
    }

    /**
     * Update match result in database.
     *
     * @param int $fixture_id
     * @param array $data
     * @param string|null $user_team
     * @param int|null $user_id
     * @return bool
     */
    public function update_result( int $fixture_id, array $data, ?string $user_team = null, ?int $user_id = null ): bool {
        $user_id = $user_id ?? get_current_user_id();

        $home_points = $data['home_points'] ?? 0;
        $away_points = $data['away_points'] ?? 0;
        $winner_id   = $data['winner_id'] ?? 0;
        $loser_id    = $data['loser_id'] ?? 0;
        $custom      = isset( $data['custom'] ) ? maybe_serialize( $data['custom'] ) : '';
        $confirmed   = $data['confirmed'] ?? 'Y';
        $status      = $data['status'] ?? 0;

        $query = $this->wpdb->prepare(
            "UPDATE $this->table_name SET `home_points` = %f, `away_points` = %f, `winner_id` = %d, `loser_id` = %d, `custom` = %s, `updated_user` = %d, `updated` = now(), `confirmed` = %s, `status` = %d WHERE `id` = %d",
            $home_points,
            $away_points,
            $winner_id,
            $loser_id,
            $custom,
            $user_id,
            $confirmed,
            $status,
            $fixture_id
        );

        $result = $this->wpdb->query( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching

        if ( false === $result ) {
            return false;
        }

        if ( $user_team ) {
            if ( 'both' === $user_team || 'home' === $user_team ) {
                $this->wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                    $this->wpdb->prepare(
                        "UPDATE $this->table_name SET `home_captain` = %d WHERE `id` = %d",
                        $user_id,
                        $fixture_id
                    )
                );
            } elseif ( 'away' === $user_team ) {
                $this->wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                    $this->wpdb->prepare(
                        "UPDATE $this->table_name SET `away_captain` = %d WHERE `id` = %d",
                        $user_id,
                        $fixture_id
                    )
                );
            }
        }

        return true;
    }

    /**
     * Update aggregate tie result in database.
     *
     * @param int $fixture_id
     * @param float $home_points_tie
     * @param float $away_points_tie
     * @param int $winner_id_tie
     * @param int $loser_id_tie
     * @return bool
     */
    public function update_result_tie( int $fixture_id, float $home_points_tie, float $away_points_tie, int $winner_id_tie, int $loser_id_tie ): bool {
        $result = $this->wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $this->wpdb->prepare(
                "UPDATE $this->table_name SET `home_points_tie` = %f, `away_points_tie` = %f, `winner_id_tie` = %d, `loser_id_tie` = %d WHERE `id` = %d",
                $home_points_tie,
                $away_points_tie,
                $winner_id_tie,
                $loser_id_tie,
                $fixture_id
            )
        );

        return false !== $result;
    }

    /**
     * Delete results report for a fixture.
     *
     * @param int $fixture_id
     * @return bool
     */
    public function delete_results_report( int $fixture_id ): bool {
        return $this->results_report_repository->delete_by_fixture_id( $fixture_id );
    }

    /**
     * Save results report for a fixture.
     *
     * @param int $fixture_id
     * @param mixed $report_data
     * @return bool|int
     */
    public function save_results_report( int $fixture_id, mixed $report_data ): bool|int {
        $this->delete_results_report( $fixture_id );

        $results_report                = new stdClass();
        $results_report->match_id      = $fixture_id;
        if ( is_string( $report_data ) ) {
            $results_report->result_object = $report_data;
            $results_report->data          = json_decode( $report_data );
        } else {
            $results_report->data          = (object) $report_data;
            $results_report->result_object = wp_json_encode( $report_data );
        }

        $report_entity = new Results_Report( $results_report );
        return $this->results_report_repository->save( $report_entity );
    }
}
