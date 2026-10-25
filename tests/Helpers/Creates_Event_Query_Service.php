<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Competition\Event_Query_Service;

trait Creates_Event_Query_Service {
    private function event_query_service(
        ?Event_Repository_Interface $event_repository = null,
        ?Fixture_Repository_Interface $fixture_repository = null,
        ?Rubber_Repository_Interface $rubber_repository = null,
        ?League_Repository_Interface $league_repository = null,
        ?Team_Repository_Interface $team_repository = null,
        ?Club_Repository_Interface $club_repository = null,
        ?Player_Repository_Interface $player_repository = null
    ): Event_Query_Service {
        return new Event_Query_Service(
            $event_repository ?? $this->createStub( Event_Repository_Interface::class ),
            $fixture_repository ?? $this->createStub( Fixture_Repository_Interface::class ),
            $rubber_repository ?? $this->createStub( Rubber_Repository_Interface::class ),
            $league_repository ?? $this->createStub( League_Repository_Interface::class ),
            $team_repository ?? $this->createStub( Team_Repository_Interface::class ),
            $club_repository ?? $this->createStub( Club_Repository_Interface::class ),
            $player_repository ?? $this->createStub( Player_Repository_Interface::class )
        );
    }

    /**
     * Create an Event repository stub with explicit query results.
     *
     * @param array<string, mixed> $results Repository method names mapped to return values.
     */
    private function event_repository_with_results( array $results ): Event_Repository_Interface {
        $repository = $this->createStub( Event_Repository_Interface::class );
        foreach ( $results as $method => $result ) {
            $repository->method( $method )->willReturn( $result );
        }

        return $repository;
    }
}