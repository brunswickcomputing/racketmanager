<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Season_Collection;
use Racketmanager\Domain\Competition\Competition_Settings;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Season_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Competition_Service_Test extends TestCase {
    private $service;
    private $competition_repository;
    private $app;

    protected function setUp(): void {
        parent::setUp();
        
        $this->app = $this->createMock( RacketManager::class );
        $this->competition_repository = $this->createMock( Competition_Repository_Interface::class );
        
        $this->service = new Competition_Service(
            $this->app,
            $this->competition_repository,
            $this->createMock( Club_Repository_Interface::class ),
            $this->createMock( Event_Repository_Interface::class ),
            $this->createMock( League_Repository_Interface::class ),
            $this->createMock( League_Team_Repository_Interface::class ),
            $this->createMock( Season_Repository_Interface::class ),
            $this->createMock( Team_Repository_Interface::class )
        );

        // Clear global competition before each test
        unset( $GLOBALS['competition'] );
    }

    public function test_get_competition_returns_object_if_passed() {
        $competition = $this->createStub( Competition::class );
        $result = $this->service->get_competition( $competition );
        $this->assertSame( $competition, $result );
    }

    public function test_get_competition_returns_global_if_no_id_passed() {
        $competition = $this->createStub( Competition::class );
        $GLOBALS['competition'] = $competition;
        
        $result = $this->service->get_competition();
        $this->assertSame( $competition, $result );
    }

    public function test_get_competition_hydrates_from_stdclass() {
        $data = new stdClass();
        $data->id = 123;
        $data->name = 'Test Comp';
        $data->type = 'league';
        $data->seasons = json_encode([]);
        $data->settings = json_encode([]);

        $result = $this->service->get_competition( $data );
        
        $this->assertInstanceOf( Competition::class, $result );
        $this->assertEquals( 123, $result->id );
        $this->assertEquals( 'Test Comp', $result->name );
    }

    public function test_get_competition_finds_by_id() {
        $competition = $this->createStub( Competition::class );
        $this->competition_repository->method( 'find_by_id' )
            ->with( 123 )
            ->willReturn( $competition );

        $result = $this->service->get_competition( 123 );
        $this->assertSame( $competition, $result );
    }

    public function test_get_competition_finds_by_name() {
        $competition = $this->createStub( Competition::class );
        $this->competition_repository->method( 'find_by_name' )
            ->with( 'Test Comp' )
            ->willReturn( $competition );

        $result = $this->service->get_competition( 'Test Comp', 'name' );
        $this->assertSame( $competition, $result );
    }

    public function test_get_competition_throws_exception_if_not_found() {
        $this->competition_repository->method( 'find_by_id' )->willReturn( null );
        
        $this->expectException( Competition_Not_Found_Exception::class );
        $this->service->get_competition( 999 );
    }
}
