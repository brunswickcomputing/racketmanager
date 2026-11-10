<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Fixture\Ports;

use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Reset_Response;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Response;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Result\Result;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Team_Result_Confirmation_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Team_Result_Update_Request;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;

/**
 * Port interface for managing fixture results and state transitions.
 */
interface Fixture_Result_Manager_Interface {
    /**
     * Handle updating fixture results from request.
     *
     * @param Fixture $fixture
     * @param Fixture_Result_Update_Request $request
     *
     * @return Fixture_Update_Response
     */
    public function handle_fixture_result_update( Fixture $fixture, Fixture_Result_Update_Request $request ): Fixture_Update_Response;

    /**
     * Handle team result update.
     *
     * @param Fixture $fixture
     * @param Team_Result_Update_Request $request
     * @param League_Repository_Interface|null $league_repository
     *
     * @return Team_Result_Response
     */
    public function handle_team_result_update( Fixture $fixture, Team_Result_Update_Request $request, ?League_Repository_Interface $league_repository = null ): Team_Result_Response;

    /**
     * Handle team result confirmation.
     *
     * @param Fixture $fixture
     * @param Team_Result_Confirmation_Request $request
     * @param League_Repository_Interface|null $league_repository
     *
     * @return Team_Result_Response
     */
    public function handle_team_result_confirmation( Fixture $fixture, Team_Result_Confirmation_Request $request, ?League_Repository_Interface $league_repository = null ): Team_Result_Response;

    /**
     * Update match result.
     *
     * @param Fixture $fixture
     * @param Result $result
     * @param string|null $confirmed
     * @param League_Repository_Interface|null $league_repository
     *
     * @return Fixture_Update_Response
     */
    public function update_result( Fixture $fixture, Result $result, ?string $confirmed = null, ?League_Repository_Interface $league_repository = null ): Fixture_Update_Response;

    /**
     * Update a batch of match results and refresh the league table.
     *
     * @param League $league
     * @param array $matches
     * @param array $home_points
     * @param array $away_points
     * @param array $custom
     * @param string $season
     * @param false|string $final_round
     * @param string $confirmed
     * @param Fixture_Repository_Interface|null $fixture_repository
     *
     * @return int Number of updated matches.
     */
    public function update_match_results(
        League $league,
        array $matches,
        array $home_points,
        array $away_points,
        array $custom,
        string $season,
        false|string $final_round = false,
        string $confirmed = 'Y',
        ?Fixture_Repository_Interface $fixture_repository = null
    ): int;

    /**
     * Reset the result of a fixture.
     *
     * @param Fixture $fixture
     *
     * @return Fixture_Reset_Response
     */
    public function reset_result( Fixture $fixture ): Fixture_Reset_Response;

    /**
     * Confirm fixture result.
     *
     * @param Fixture $fixture
     * @param string $actioned_by
     * @param string|null $confirm_comments
     * @param Result|null $result
     * @param League_Repository_Interface|null $league_repository
     *
     * @return Fixture_Update_Response
     */
    public function confirm_result( Fixture $fixture, string $actioned_by = '', ?string $confirm_comments = null, ?Result $result = null, ?League_Repository_Interface $league_repository = null ): Fixture_Update_Response;

    /**
     * Check whether update is allowed.
     *
     * @param Fixture $fixture
     *
     * @return object
     */
    public function is_update_allowed( Fixture $fixture ): object;

    /**
     * Update result for tied fixtures.
     *
     * @param Fixture $fixture
     *
     * @return Fixture_Update_Response
     */
    public function update_result_tie( Fixture $fixture ): Fixture_Update_Response;

    /**
     * Update leg linkage.
     *
     * @param Fixture $fixture
     * @param int $leg
     * @param int $linked_match_id
     *
     * @return Fixture_Update_Response
     */
    public function update_legs( Fixture $fixture, int $leg, int $linked_match_id ): Fixture_Update_Response;

    /**
     * Apply penalty points to fixture.
     *
     * @param Fixture $fixture
     * @param string $team_ref
     * @param int $penalty
     *
     * @return Fixture_Update_Response
     */
    public function apply_penalty( Fixture $fixture, string $team_ref, int $penalty ): Fixture_Update_Response;
}
