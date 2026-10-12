<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Import;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Container\Simple_Container;
use ReflectionMethod;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Admin_Import_Fixtures_Test extends TestCase {
    private Simple_Container $container;
    private $fixture_service;
    private $original_racketmanager;

    protected function setUp(): void {
        parent::setUp();

        $this->fixture_service = $this->createMock( Fixture_Service::class );
        $this->container = new Simple_Container();
        $this->container->set( 'fixture_service', fn() => $this->fixture_service );

        global $racketmanager;
        $this->original_racketmanager = $racketmanager ?? null;
        $racketmanager = $this->getMockBuilder( RacketManager::class )
            ->disableOriginalConstructor()
            ->onlyMethods( [ 'get_team_id' ] )
            ->getMock();
        $racketmanager->container = $this->container;
        $racketmanager->method( 'get_team_id' )
            ->willReturn( 10 );
    }

    protected function tearDown(): void {
        global $racketmanager;
        $racketmanager = $this->original_racketmanager;
        unset( $GLOBALS['wp_stubs_leagues'][99] );
        parent::tearDown();
    }

    public function test_import_fixtures_creates_fixtures(): void {
        $admin_import = $this->getMockBuilder( Admin_Import::class )
            ->disableOriginalConstructor()
            ->onlyMethods( [ 'set_message' ] )
            ->getMock();

        $reflection = new \ReflectionProperty( \Racketmanager\Admin\Admin_Display::class, 'racketmanager' );
        $reflection->setValue( $admin_import, $GLOBALS['racketmanager'] );

        $mock_league = (new \ReflectionClass( League::class ))->newInstanceWithoutConstructor();
        $mock_league->id = 99;
        $mock_league->title = 'Test League';
        $mock_league->name = 'Test League';
        $GLOBALS['wp_stubs_leagues'][99] = $mock_league;

        $contents = [
            'Date,MatchDay,Home,Away,Location,Group,Time',
            '2026-06-01,1,Team A,Team B,Court 1,Group A,18:00',
        ];

        $fixture_mock = $this->createMock( Fixture::class );
        $fixture_mock->method( 'get_id' )->willReturn( 123 );

        $this->fixture_service->expects( $this->once() )
            ->method( 'create_fixture' )
            ->with( $this->callback( function ( $fixture ) {
                return $fixture instanceof Fixture
                    && 99 === (int) $fixture->get_league_id()
                    && '2026' === (string) $fixture->get_season()
                    && 10 === (int) $fixture->get_home_team()
                    && 10 === (int) $fixture->get_away_team()
                    && str_starts_with( (string) $fixture->get_date(), '2026-06-01 18:00' );
            } ), $mock_league )
            ->willReturn( $fixture_mock );

        $method = new ReflectionMethod( Admin_Import::class, 'import_fixtures' );
        $method->invoke( $admin_import, $contents, ',', 99, '2026' );
    }
}
