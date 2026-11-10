<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Fixture\Ports;

use Racketmanager\Domain\Fixture\Fixture;

/**
 * Port interface for fixture maintenance workflows.
 */
interface Fixture_Maintenance_Service_Interface {
    /**
     * Chase match results for a fixture.
     *
     * @param Fixture      $fixture
     * @param false|string $time_period
     * @param string|null  $timeout
     * @param string|null  $penalty
     *
     * @return bool
     */
    public function chase_match_result( Fixture $fixture, false|string $time_period = false, ?string $timeout = null, ?string $penalty = null ): bool;

    /**
     * Complete match result for a fixture.
     *
     * @param Fixture $fixture
     * @param int     $confirmation_timeout
     *
     * @return int
     */
    public function complete_result( Fixture $fixture, int $confirmation_timeout ): int;

    /**
     * Chase match approval for a fixture.
     *
     * @param Fixture      $fixture
     * @param false|string $time_period
     * @param bool         $override
     * @param string|null  $timeout
     * @param string|null  $penalty
     *
     * @return bool
     */
    public function chase_match_approval( Fixture $fixture, false|string $time_period = false, bool $override = false, ?string $timeout = null, ?string $penalty = null ): bool;

    /**
     * Check result timeout for auto-confirmation.
     *
     * @param Fixture $fixture
     */
    public function check_result_timeout( Fixture $fixture ): void;

    /**
     * Delete results checker entries for a fixture.
     *
     * @param int $fixture_id
     */
    public function delete_result_checks( int $fixture_id ): void;

    /**
     * Delete result report for a fixture.
     *
     * @param int $fixture_id
     */
    public function delete_result_report( int $fixture_id ): void;

    /**
     * Save result report for a fixture.
     *
     * @param int    $fixture_id
     * @param object $data
     */
    public function save_result_report( int $fixture_id, object $data ): void;

    /**
     * Delete fixture by ID.
     *
     * @param int $id
     *
     * @return bool
     */
    public function delete_fixture( int $id ): bool;

    /**
     * Update fixture status.
     *
     * @param int $id
     * @param int $status
     *
     * @return bool
     */
    public function update_fixture_status( int $id, int $status ): bool;

    /**
     * Update fixture teams.
     *
     * @param int    $id
     * @param string $home_team
     * @param string $away_team
     *
     * @return bool
     */
    public function update_fixture_teams( int $id, string $home_team, string $away_team ): bool;

    /**
     * Update fixture date.
     *
     * @param int         $id
     * @param string      $date
     * @param string|null $original_date
     *
     * @return bool
     */
    public function update_fixture_date( int $id, string $date, ?string $original_date = null ): bool;
}
