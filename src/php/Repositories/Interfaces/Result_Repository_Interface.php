<?php
/**
 * Result_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

declare( strict_types=1 );

namespace Racketmanager\Repositories\Interfaces;

/**
 * Interface to implement the Result repository
 */
interface Result_Repository_Interface {
    /**
     * Update match result in database.
     *
     * @param int $fixture_id
     * @param array $data
     * @param string|null $user_team
     * @param int|null $user_id
     * @return bool
     */
    public function update_result( int $fixture_id, array $data, ?string $user_team = null, ?int $user_id = null ): bool;

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
    public function update_result_tie( int $fixture_id, float $home_points_tie, float $away_points_tie, int $winner_id_tie, int $loser_id_tie ): bool;

    /**
     * Delete results report for a fixture.
     *
     * @param int $fixture_id
     * @return bool
     */
    public function delete_results_report( int $fixture_id ): bool;

    /**
     * Save results report for a fixture.
     *
     * @param int $fixture_id
     * @param mixed $report_data
     * @return bool|int
     */
    public function save_results_report( int $fixture_id, mixed $report_data ): bool|int;
}
