<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Ajax\Admin_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Admin_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\RacketManager;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

require_once __DIR__ . '/../../../../wp-stubs.php';

final class Admin_Ajax_Controller_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private RacketManager $racketmanager;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->racketmanager = $this->createStub( RacketManager::class );
        $_GET  = [];
        $_POST = [];
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_get_request_merges_get_and_post_with_post_precedence(): void {
        $_GET  = [
            'action' => 'racketmanager_get_event_dropdown',
            'shared' => 'from_get',
        ];
        $_POST = [
            'shared'        => 'from_post',
            'competitionId' => '10',
        ];

        $controller = new Admin_Ajax_Controller( $this->racketmanager );
        $request    = $controller->get_request();

        $this->assertSame( [
            'action'        => 'racketmanager_get_event_dropdown',
            'shared'        => 'from_post',
            'competitionId' => '10',
        ], $request );
    }

    public function test_save_add_points_passes_request_to_adapter(): void {
        $_POST = [ 'racketmanager_nonce' => 'valid', 'matchId' => '5' ];

        $adapter = $this->createMock( Admin_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'save_add_points' )
            ->with( [ 'racketmanager_nonce' => 'valid', 'matchId' => '5' ] )
            ->willReturn( new Response( [ 'msg' => 'saved' ], 200 ) );

        $this->expectOutputString( '{"msg":"saved"}' );

        $controller = new Admin_Ajax_Controller( $this->racketmanager, $adapter );
        $controller->save_add_points();
    }

    public function test_get_event_dropdown_passes_request_to_adapter(): void {
        $_POST = [ 'security' => 'sec_nonce', 'competitionId' => '2' ];

        $adapter = $this->createMock( Admin_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'get_event_dropdown' )
            ->with( [ 'security' => 'sec_nonce', 'competitionId' => '2' ] )
            ->willReturn( new Response( '<select></select>', 200 ) );

        $this->expectOutputString( '<select></select>' );

        $controller = new Admin_Ajax_Controller( $this->racketmanager, $adapter );
        $controller->get_event_dropdown();
    }

    public function test_get_league_dropdown_passes_request_to_adapter(): void {
        $_POST = [ 'security' => 'sec_nonce', 'eventId' => '3' ];

        $adapter = $this->createMock( Admin_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'get_league_dropdown' )
            ->with( [ 'security' => 'sec_nonce', 'eventId' => '3' ] )
            ->willReturn( new Response( '<select></select>', 200 ) );

        $this->expectOutputString( '<select></select>' );

        $controller = new Admin_Ajax_Controller( $this->racketmanager, $adapter );
        $controller->get_league_dropdown();
    }

    public function test_set_season_dropdown_passes_request_to_adapter(): void {
        $_POST = [ 'security' => 'sec_nonce', 'leagueId' => '4' ];

        $adapter = $this->createMock( Admin_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'set_season_dropdown' )
            ->with( [ 'security' => 'sec_nonce', 'leagueId' => '4' ] )
            ->willReturn( new Response( '<select></select>', 200 ) );

        $this->expectOutputString( '<select></select>' );

        $controller = new Admin_Ajax_Controller( $this->racketmanager, $adapter );
        $controller->set_season_dropdown();
    }

    public function test_set_match_dropdown_passes_request_to_adapter(): void {
        $_POST = [ 'security' => 'sec_nonce', 'leagueId' => '4' ];

        $adapter = $this->createMock( Admin_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'set_match_dropdown' )
            ->with( [ 'security' => 'sec_nonce', 'leagueId' => '4' ] )
            ->willReturn( new Response( '<select></select>', 200 ) );

        $this->expectOutputString( '<select></select>' );

        $controller = new Admin_Ajax_Controller( $this->racketmanager, $adapter );
        $controller->set_match_dropdown();
    }

    public function test_check_team_exists_passes_request_to_adapter(): void {
        $_POST = [ 'security' => 'sec_nonce', 'title' => 'Team A' ];

        $adapter = $this->createMock( Admin_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'check_team_exists' )
            ->with( [ 'security' => 'sec_nonce', 'title' => 'Team A' ] )
            ->willReturn( new Response( [ 'exists' => false ], 200 ) );

        $this->expectOutputString( '{"exists":false}' );

        $controller = new Admin_Ajax_Controller( $this->racketmanager, $adapter );
        $controller->check_team_exists();
    }

    public function test_notify_competition_entries_open_passes_request_to_adapter(): void {
        $_POST = [ 'security' => 'sec_nonce', 'competitionId' => '1' ];

        $adapter = $this->createMock( Admin_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'notify_competition_entries_open' )
            ->with( [ 'security' => 'sec_nonce', 'competitionId' => '1' ] )
            ->willReturn( new Response( [ 'msg' => 'sent' ], 200 ) );

        $this->expectOutputString( '{"msg":"sent"}' );

        $controller = new Admin_Ajax_Controller( $this->racketmanager, $adapter );
        $controller->notify_competition_entries_open();
    }

    public function test_send_fixtures_passes_request_to_adapter(): void {
        $_POST = [ 'security' => 'sec_nonce', 'eventId' => '2' ];

        $adapter = $this->createMock( Admin_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'send_fixtures' )
            ->with( [ 'security' => 'sec_nonce', 'eventId' => '2' ] )
            ->willReturn( new Response( [ 'msg' => 'sent' ], 200 ) );

        $this->expectOutputString( '{"msg":"sent"}' );

        $controller = new Admin_Ajax_Controller( $this->racketmanager, $adapter );
        $controller->send_fixtures();
    }

    public function test_get_adapter_resolves_from_container_when_adapter_not_injected(): void {
        $adapter   = $this->createStub( Admin_Ajax_Adapter::class );
        $container = $this->createStub( \Racketmanager\Services\Container\Simple_Container::class );
        $container->method( 'has' )->willReturnCallback( fn( string $id ) => 'admin_ajax_adapter' === $id );
        $container->method( 'get' )->willReturnCallback( fn( string $id ) => 'admin_ajax_adapter' === $id ? $adapter : null );

        $this->racketmanager->container = $container;

        $controller = new Admin_Ajax_Controller( $this->racketmanager );
        $reflection = new \ReflectionClass( $controller );
        $method     = $reflection->getMethod( 'get_adapter' );

        $this->assertSame( $adapter, $method->invoke( $controller ) );
    }

    public function test_get_adapter_falls_back_to_new_instance_when_container_lacks_adapter(): void {
        $container = $this->createStub( \Racketmanager\Services\Container\Simple_Container::class );
        $container->method( 'has' )->willReturn( false );
        $container->method( 'get' )->willReturnCallback( function( string $id ) {
            return match ( $id ) {
                'competition_service'              => $this->createStub( \Racketmanager\Services\Competition_Service::class ),
                'competition_entry_service'        => $this->createStub( \Racketmanager\Services\Competition_Entry_Service::class ),
                'notification_service'             => $this->createStub( \Racketmanager\Services\Notification\Notification_Service::class ),
                'fixture_repository'               => $this->createStub( \Racketmanager\Repositories\Fixture_Repository::class ),
                'competition_notification_service' => $this->createStub( \Racketmanager\Services\Competition\Competition_Notification_Service::class ),
                'standings_service'                 => $this->createStub( \Racketmanager\Services\Standings\Standings_Service::class ),
                'league_repository'                => $this->createStub( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class ),
                'event_repository'                 => $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class ),
                'team_repository'                  => $this->createStub( \Racketmanager\Repositories\Interfaces\Team_Repository_Interface::class ),
                default                            => null,
            };
        } );

        $this->racketmanager->container = $container;

        $controller = new Admin_Ajax_Controller( $this->racketmanager );
        $reflection = new \ReflectionClass( $controller );
        $method     = $reflection->getMethod( 'get_adapter' );

        $result = $method->invoke( $controller );
        $this->assertInstanceOf( Admin_Ajax_Adapter::class, $result );
    }
}
