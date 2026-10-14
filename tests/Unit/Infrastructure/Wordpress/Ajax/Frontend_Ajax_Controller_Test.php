<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Ajax\Ajax_Registry;
use Racketmanager\Infrastructure\Wordpress\Ajax\Frontend_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Frontend_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\RacketManager;

require_once __DIR__ . '/../../../../wp-stubs.php';

final class Frontend_Ajax_Controller_Test extends TestCase {
    private RacketManager $racketmanager;

    protected function setUp(): void {
        parent::setUp();
        $this->racketmanager = $this->createStub( RacketManager::class );
        $_GET  = [];
        $_POST = [];
    }

    public function test_get_request_merges_get_and_post_with_post_precedence(): void {
        $_GET  = [
            'action' => 'racketmanager_tab_data',
            'tab'    => 'standings',
            'shared' => 'from_get',
        ];
        $_POST = [
            'shared'   => 'from_post',
            'targetId' => '42',
        ];

        $controller = new Frontend_Ajax_Controller( $this->racketmanager );
        $request    = $controller->get_request();

        $this->assertSame( [
            'action'   => 'racketmanager_tab_data',
            'tab'      => 'standings',
            'shared'   => 'from_post',
            'targetId' => '42',
        ], $request );
    }

    public function test_update_team_passes_request_snapshot_to_adapter(): void {
        $_GET  = [ 'get_param' => '1' ];
        $_POST = [ 'racketmanager_nonce' => 'nonce123', 'team_id' => '10' ];

        $expected_request = [
            'get_param'           => '1',
            'racketmanager_nonce' => 'nonce123',
            'team_id'             => '10',
        ];

        $adapter = $this->createMock( Frontend_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'update_team' )
            ->with( $expected_request )
            ->willReturn( new Response( [ 'msg' => 'ok' ], 200 ) );

        $this->expectOutputString( '{"msg":"ok"}' );

        $controller = new Frontend_Ajax_Controller( $this->racketmanager, $adapter );
        $controller->update_team();
    }

    public function test_update_player_passes_request_snapshot_to_adapter(): void {
        $_POST = [ 'racketmanager_nonce' => 'nonce123', 'playerId' => '5' ];

        $adapter = $this->createMock( Frontend_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'update_player' )
            ->with( [ 'racketmanager_nonce' => 'nonce123', 'playerId' => '5' ] )
            ->willReturn( new Response( [ 'msg' => 'ok' ], 200 ) );

        $this->expectOutputString( '{"msg":"ok"}' );

        $controller = new Frontend_Ajax_Controller( $this->racketmanager, $adapter );
        $controller->update_player();
    }

    public function test_cup_entry_passes_request_snapshot_to_adapter(): void {
        $_POST = [ 'racketmanager_nonce' => 'cup_nonce', 'competitionId' => '2' ];

        $adapter = $this->createMock( Frontend_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'cup_entry' )
            ->with( [ 'racketmanager_nonce' => 'cup_nonce', 'competitionId' => '2' ] )
            ->willReturn( new Response( [ 'msg' => 'ok' ], 200 ) );

        $this->expectOutputString( '{"msg":"ok"}' );

        $controller = new Frontend_Ajax_Controller( $this->racketmanager, $adapter );
        $controller->cup_entry();
    }

    public function test_league_entry_passes_request_snapshot_to_adapter(): void {
        $_POST = [ 'racketmanager_nonce' => 'league_nonce', 'competitionId' => '3' ];

        $adapter = $this->createMock( Frontend_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'league_entry' )
            ->with( [ 'racketmanager_nonce' => 'league_nonce', 'competitionId' => '3' ] )
            ->willReturn( new Response( [ 'msg' => 'ok' ], 200 ) );

        $this->expectOutputString( '{"msg":"ok"}' );

        $controller = new Frontend_Ajax_Controller( $this->racketmanager, $adapter );
        $controller->league_entry();
    }

    public function test_search_players_passes_request_snapshot_to_adapter(): void {
        $_POST = [ 'security' => 'sec_nonce', 'search_string' => 'Smith' ];

        $adapter = $this->createMock( Frontend_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'search_players' )
            ->with( [ 'security' => 'sec_nonce', 'search_string' => 'Smith' ] )
            ->willReturn( new Response( 'HTML', 200 ) );

        $this->expectOutputString( 'HTML' );

        $controller = new Frontend_Ajax_Controller( $this->racketmanager, $adapter );
        $controller->search_players();
    }

    public function test_tab_data_passes_request_snapshot_to_adapter(): void {
        $_POST = [ 'security' => 'sec_nonce', 'target' => 'competition', 'targetId' => '1' ];

        $adapter = $this->createMock( Frontend_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'tab_data' )
            ->with( [ 'security' => 'sec_nonce', 'target' => 'competition', 'targetId' => '1' ] )
            ->willReturn( new Response( 'HTML', 200 ) );

        $this->expectOutputString( 'HTML' );

        $controller = new Frontend_Ajax_Controller( $this->racketmanager, $adapter );
        $controller->tab_data();
    }

    public function test_get_player_details_passes_request_snapshot_to_adapter(): void {
        $_POST = [ 'security' => 'sec_nonce', 'name' => 'Smith', 'partnerGender' => 'F' ];

        $adapter = $this->createMock( Frontend_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'get_player_details' )
            ->with( [ 'security' => 'sec_nonce', 'name' => 'Smith', 'partnerGender' => 'F' ] )
            ->willReturn( new Response( [], 200 ) );

        $this->expectOutputString( '[]' );

        $controller = new Frontend_Ajax_Controller( $this->racketmanager, $adapter );
        $controller->get_player_details();
    }

    public function test_logged_out_handlers_delegate_to_adapter(): void {
        $adapter = $this->createMock( Frontend_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'logged_out' )
            ->willReturn( new Response( [ 'msg' => 'unauth' ], 401 ) );

        $this->expectOutputString( '{"msg":"unauth"}' );

        $controller = new Frontend_Ajax_Controller( $this->racketmanager, $adapter );
        $controller->logged_out();
    }

    public function test_logged_out_modal_handlers_delegate_to_adapter(): void {
        $adapter = $this->createMock( Frontend_Ajax_Adapter::class );
        $adapter->expects( $this->once() )
            ->method( 'logged_out_modal' )
            ->willReturn( new Response( [ 'msg' => 'unauth' ], 401 ) );

        $this->expectOutputString( '{"msg":"unauth"}' );

        $controller = new Frontend_Ajax_Controller( $this->racketmanager, $adapter );
        $controller->logged_out_modal();
    }
}
