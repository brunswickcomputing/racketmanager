<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Services\Admin\Import\Import_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

require_once __DIR__ . '/../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
class Admin_Import_Fixtures_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Simple_Container $container;
    private Fixture_Service $fixture_service;
    private Player_Service $player_service;
    private Registration_Service $registration_service;
    private Action_Guard_Interface $action_guard;
    private League_Team_Repository_Interface $league_team_repository;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->fixture_service      = $this->createMock( Fixture_Service::class );
        $this->player_service       = $this->createMock( Player_Service::class );
        $this->registration_service = $this->createMock( Registration_Service::class );
        $this->action_guard         = $this->createMock( Action_Guard_Interface::class );
        $this->league_team_repository = $this->createMock( League_Team_Repository_Interface::class );

        $this->container = new Simple_Container();
        $this->container->set( 'fixture_service', fn() => $this->fixture_service );

        $racketmanager = $this->getMockBuilder( RacketManager::class )
            ->disableOriginalConstructor()
            ->onlyMethods( [ 'get_team_id' ] )
            ->getMock();
        $racketmanager->container = $this->container;
        $racketmanager->method( 'get_team_id' )
            ->willReturn( 10 );
        $GLOBALS['racketmanager'] = $racketmanager;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_import_fixtures_creates_fixtures(): void {
        $league_repository = $this->createMock( League_Repository_Interface::class );
        $dispatcher        = new Import_Action_Dispatcher(
            $this->action_guard,
            $this->fixture_service,
            $this->player_service,
            $this->registration_service,
            $this->league_team_repository,
            $this->createStub( \Racketmanager\Services\Standings\Standings_Service::class ),
            $this->createStub( \Racketmanager\Services\Competition\League_Query_Service::class ),
            $GLOBALS['racketmanager'],
            $league_repository
        );

        $mock_league = (new \ReflectionClass( League::class ))->newInstanceWithoutConstructor();
        $mock_league->id = 99;
        $mock_league->title = 'Test League';
        $mock_league->name = 'Test League';
        $league_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 99 )
            ->willReturn( $mock_league );

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

        $result = $dispatcher->import_fixtures( $contents, ',', 99, '2026' );

        $this->assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        $this->assertStringContainsString( '1 Fixtures imported', $result->message );
    }
}
