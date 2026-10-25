<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Competition\League_Query_Service;

trait Creates_League_Query_Service {
    private function league_query_service(
        ?League_Repository_Interface $league_repository = null,
        ?League_Team_Repository_Interface $league_team_repository = null,
        ?Fixture_Repository_Interface $fixture_repository = null,
        ?Rubber_Repository_Interface $rubber_repository = null,
        ?Team_Repository_Interface $team_repository = null,
        ?Club_Repository_Interface $club_repository = null,
        ?Player_Repository_Interface $player_repository = null
    ): League_Query_Service {
        return new League_Query_Service(
            $league_repository ?? $this->createStub( League_Repository_Interface::class ),
            $fixture_repository ?? $this->createStub( Fixture_Repository_Interface::class ),
            $rubber_repository ?? $this->createStub( Rubber_Repository_Interface::class ),
            $team_repository ?? $this->createStub( Team_Repository_Interface::class ),
            $league_team_repository ?? $this->createStub( League_Team_Repository_Interface::class ),
            $club_repository ?? $this->createStub( Club_Repository_Interface::class ),
            $player_repository ?? $this->createStub( Player_Repository_Interface::class )
        );
    }
}
