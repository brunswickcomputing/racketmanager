<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use Racketmanager\Services\Competition\Event_Query_Service;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Team_Service;

require_once __DIR__ . '/../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Team_Service_Test extends TestCase {
    private $team_repository;
    private Team_Service $service;

    protected function setUp(): void {
        parent::setUp();
        $this->team_repository = $this->createMock( Team_Repository_Interface::class );

        $this->service = new Team_Service(
            $this->team_repository,
            $this->createMock( Club_Repository::class ),
            $this->createMock( Event_Repository::class ),
            $this->createMock( Player_Service::class ),
            $this->createMock( Fixture_Repository_Interface::class ),
            $this->createStub( Event_Query_Service::class )
        );
    }

    public function test_get_team_id_delegates_name_to_repository_find_id_by_name(): void {
        $this->team_repository->expects( self::once() )
            ->method( 'find_id_by_name' )
            ->with( 'Leigh Tennis Club 1' )
            ->willReturn( 42 );

        self::assertSame( 42, $this->service->get_team_id( 'Leigh Tennis Club 1' ) );
    }

    public function test_get_team_id_returns_null_when_repository_finds_no_team(): void {
        $this->team_repository->expects( self::once() )
            ->method( 'find_id_by_name' )
            ->with( 'Unknown Team' )
            ->willReturn( null );

        self::assertNull( $this->service->get_team_id( 'Unknown Team' ) );
    }
}
