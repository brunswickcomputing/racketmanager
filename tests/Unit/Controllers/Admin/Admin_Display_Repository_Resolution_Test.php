<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Display;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Team\Team;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\League_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionMethod;

/**
 * Admin_Display must resolve league/club/team repositories from the container
 * (or constructor overrides) instead of the legacy functions.php helpers.
 */
#[AllowMockObjectsWithoutExpectations]
final class Admin_Display_Repository_Resolution_Test extends TestCase {
    use Creates_Admin_Delivery_Container;
    use Manages_Wordpress_Globals;

    private Simple_Container $container;
    private League_Repository_Interface $league_repository;
    private Team_Repository_Interface $team_repository;
    private League_Service $league_service;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_current_user_can'] = true;
        $_GET                                 = array();
        $_POST                                = array();
        $_REQUEST                             = array();

        $this->league_repository = $this->createMock( League_Repository_Interface::class );
        $this->team_repository   = $this->createMock( Team_Repository_Interface::class );
        $this->league_service    = $this->createMock( League_Service::class );
        $this->container         = $this->create_admin_delivery_container(
            services: array(
                'league_repository' => $this->league_repository,
                'team_repository'   => $this->team_repository,
                'league_service'    => $this->league_service,
            )
        );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_screen( ?League_Repository_Interface $league_repository = null, ?Club_Repository_Interface $club_repository = null, ?Team_Repository_Interface $team_repository = null ): Admin_Display {
        $plugin            = $this->createStub( RacketManager::class );
        $plugin->container = $this->container;
        return new Admin_Display( $plugin, null, $league_repository, $club_repository, $team_repository );
    }

    private function create_league( int $id ): League {
        $competition = Competition_Test_Factory::create( id: 1, type: Competition_Type::LEAGUE );
        $event       = Event::from_object( (object) array( 'id' => 9, 'name' => 'Event', 'seasons' => array( '2026' => array( 'name' => '2026' ) ) ), $competition );
        return League::from_object( (object) array( 'id' => $id, 'title' => 'League ' . $id ), $event );
    }

    private function resolve( Admin_Display $screen, mixed $league ): ?League {
        $method = new ReflectionMethod( Admin_Display::class, 'resolve_league' );
        return $method->invoke( $screen, $league );
    }

    public function test_resolve_league_with_id_uses_container_league_repository(): void {
        $league = $this->create_league( 42 );
        $this->league_repository->expects( self::once() )->method( 'find_by_id' )->with( 42 )->willReturn( $league );
        $this->league_repository->expects( self::never() )->method( 'find_by_name' );

        self::assertSame( $league, $this->resolve( $this->create_screen(), 42 ) );
    }

    public function test_resolve_league_with_name_uses_find_by_name(): void {
        $league = $this->create_league( 7 );
        $this->league_repository->expects( self::once() )->method( 'find_by_name' )->with( 'Division 1' )->willReturn( $league );

        self::assertSame( $league, $this->resolve( $this->create_screen(), 'Division 1' ) );
    }

    public function test_resolve_league_with_league_instance_returns_it_unchanged(): void {
        $league = $this->create_league( 3 );
        $this->league_repository->expects( self::never() )->method( 'find_by_id' );

        self::assertSame( $league, $this->resolve( $this->create_screen(), $league ) );
    }

    public function test_resolve_league_with_raw_row_hydrates_league_without_repository_lookup(): void {
        $this->league_repository->expects( self::never() )->method( 'find_by_id' );
        $competition = Competition_Test_Factory::create( id: 1, type: Competition_Type::LEAGUE );
        $event       = Event::from_object( (object) array( 'id' => 9, 'name' => 'Event', 'seasons' => array( '2026' => array( 'name' => '2026' ) ) ), $competition );
        $row         = (object) array( 'id' => 55, 'title' => 'Raw League', 'event' => $event, 'event_id' => 9 );

        $resolved = $this->resolve( $this->create_screen(), $row );

        self::assertInstanceOf( League::class, $resolved );
        self::assertSame( 55, $resolved->id );
        self::assertSame( 'Raw League', $resolved->title );
    }

    public function test_resolve_league_with_empty_value_returns_null_without_global_fallback(): void {
        $GLOBALS['league'] = $this->create_league( 1 );
        $this->league_repository->expects( self::never() )->method( 'find_by_id' );

        self::assertNull( $this->resolve( $this->create_screen(), null ) );
        self::assertNull( $this->resolve( $this->create_screen(), 0 ) );
    }

    public function test_constructor_overrides_take_precedence_over_container_repositories(): void {
        $league   = $this->create_league( 11 );
        $override = $this->createMock( League_Repository_Interface::class );
        $override->expects( self::once() )->method( 'find_by_id' )->with( 11 )->willReturn( $league );
        $this->league_repository->expects( self::never() )->method( 'find_by_id' );

        self::assertSame( $league, $this->resolve( $this->create_screen( $override ), 11 ) );
    }

    public function test_withdraw_teams_action_accepts_league_object_and_uses_team_repository(): void {
        $league  = $this->create_league( 42 );
        $team    = Team::from_object( (object) array( 'id' => 101, 'title' => 'Westcliff 1', 'club_id' => 5 ) );
        $_POST   = array(
            'action'              => 'withdraw',
            'racketmanager_nonce' => 'nonce',
            'season'              => '2026',
            'team'                => array( '101' ),
        );
        $_REQUEST = $_POST;

        $this->league_repository->expects( self::never() )->method( 'find_by_id' );
        $this->team_repository->expects( self::once() )->method( 'find_by_id' )->with( 101 )->willReturn( $team );
        $this->league_service->expects( self::once() )->method( 'withdraw_team' )->with( $league, 101, '2026' );

        $screen = $this->create_screen();
        $method = new ReflectionMethod( Admin_Display::class, 'handle_league_teams_action' );

        self::assertTrue( $method->invoke( $screen, $league ) );
        self::assertStringContainsString( 'Westcliff 1', $screen->message );
    }

    public function test_delete_teams_action_with_raw_row_hydrates_league_via_event_repository(): void {
        $competition                 = Competition_Test_Factory::create( id: 1, type: Competition_Type::LEAGUE );
        $event                       = Event::from_object( (object) array( 'id' => 9, 'name' => 'Event', 'seasons' => array( '2026' => array( 'name' => '2026' ) ) ), $competition );
        $GLOBALS['wp_stubs_events'][9] = $event;
        $_POST                       = array(
            'action'              => 'delete',
            'racketmanager_nonce' => 'nonce',
            'season'              => '2026',
            'team'                => array( '101' ),
        );
        $_REQUEST                    = $_POST;

        $this->league_repository->expects( self::never() )->method( 'find_by_id' );
        $this->league_service->expects( self::once() )->method( 'delete_team' )
            ->with(
                self::callback( static fn( $league ): bool => $league instanceof League && 42 === $league->id && 9 === $league->event->id ),
                101,
                '2026'
            );

        $screen = $this->create_screen();
        $method = new ReflectionMethod( Admin_Display::class, 'handle_league_teams_action' );

        self::assertTrue( $method->invoke( $screen, (object) array( 'id' => 42, 'title' => 'Row', 'event_id' => 9 ) ) );
    }
}
