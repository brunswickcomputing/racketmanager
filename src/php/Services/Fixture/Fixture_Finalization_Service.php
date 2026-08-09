<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Fixture;

use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;
use Racketmanager\Domain\Result\Result;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition\Knockout_Progression_Service;
use Racketmanager\Services\Result\Result_Reporting_Service;
use Racketmanager\Services\Standings\Standings_Service;

/**
 * Service to handle the finalization of a fixture result.
 * This includes updating league standings, tournament progression, and external reporting.
 */
class Fixture_Finalization_Service {
    public function __construct(
        private readonly ?Standings_Service $standings_service = null,
        private readonly ?Knockout_Progression_Service $progression_service = null,
        private readonly ?Result_Reporting_Service $reporting_service = null
    ) {
    }

    /**
     * Finalize the fixture result.
     *
     * @param Fixture $fixture
     * @param League $league
     * @param Result $result
     * @param bool $update_standings Whether to update league standings.
     * @param League_Repository_Interface|null $league_repository
     */
    public function finalize( Fixture $fixture, League $league, Result $result, bool $update_standings = true, ?League_Repository_Interface $league_repository = null ): array {
        $outcomes = [];
        // 1. Update League Standings
        if ( $update_standings ) {
            $league->update_standings( (string) $fixture->get_season() );
            $outcomes[] = Fixture_Update_Status::TABLE_UPDATED;
        }

        // 2. Tournament Progression
        if ( $this->progression_service && $fixture->get_league_id() ) {
            // Use Stage domain model
            $stage = Stage::from_league( $league );
            if ( 'draw' === $stage->get_type() ) {
                $this->progression_service->progress_winner( $stage, $fixture, $league );
                $outcomes[] = Fixture_Update_Status::PROGRESSED;
                // Also handle consolation if needed
                if ( method_exists( $this->progression_service, 'handle_consolation' ) ) {
                    $this->progression_service->handle_consolation( $stage, $fixture, $league );
                }
            }
        }

        // 3. External Reporting
        if ( $this->reporting_service ) {
            // Queue for background processing to avoid blocking user
            wp_schedule_single_event( time(), 'racketmanager_report_fixture_result', [ (int) $fixture->get_id(), $league->get_competition_type() ] );
        }

        return $outcomes;
    }
}
