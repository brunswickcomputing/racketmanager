<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Tournament_Tournaments_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Tournaments_Page_View_Model;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Tournament_Service;

require_once __DIR__ . '/../../../wp-stubs.php';

final class Tournament_Tournaments_Admin_Controller_Test extends TestCase {

    public function test_tournaments_page_returns_view_model(): void {
        $tournament_service = $this->createMock( Tournament_Service::class );
        $competition_service = $this->createMock( Competition_Service::class );
        $season_service = $this->createMock( Season_Service::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $tournament_service->method( 'get_tournaments_with_details' )->willReturn( [] );
        $competition_service->method( 'get_tournament_competitions' )->willReturn( [] );
        $season_service->method( 'get_all_seasons' )->willReturn( [] );

        $guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_leagues' );

        $controller = new Tournament_Tournaments_Admin_Controller(
            $tournament_service,
            $competition_service,
            $season_service,
            $guard
        );

        $result = $controller->tournaments_page( [], [] );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Tournaments_Page_View_Model::class, $result['view_model'] );
    }

    public function test_handle_bulk_delete_calls_service(): void {
        $tournament_service = $this->createMock( Tournament_Service::class );
        $competition_service = $this->createMock( Competition_Service::class );
        $season_service = $this->createMock( Season_Service::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $tournament_service->method( 'get_tournaments_with_details' )->willReturn( [] );

        $tournament_ids = [ 123, 456 ];
        $tournament_service->expects( self::once() )
            ->method( 'bulk_remove_tournaments' )
            ->with( $tournament_ids )
            ->willReturn( [ 'message' => 'Deleted', 'message_type' => false ] );

        $controller = new Tournament_Tournaments_Admin_Controller(
            $tournament_service,
            $competition_service,
            $season_service,
            $guard
        );

        $post = [
            'doTournamentDel' => '1',
            'action' => 'delete',
            'tournament' => [ '123', '456' ]
        ];

        $result = $controller->tournaments_page( [], $post );

        self::assertEquals( 'Deleted', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }
}
