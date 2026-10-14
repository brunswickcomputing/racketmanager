<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Ajax\Admin_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Admin_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\RacketManager;

require_once __DIR__ . '/../../../../wp-stubs.php';

final class Admin_Ajax_Controller_Test extends TestCase {
    private RacketManager $racketmanager;

    protected function setUp(): void {
        parent::setUp();
        $this->racketmanager = $this->createStub( RacketManager::class );
        $_GET  = [];
        $_POST = [];
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
}
