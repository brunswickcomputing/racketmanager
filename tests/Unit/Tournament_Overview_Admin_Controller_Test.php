<?php
declare(strict_types=1);

namespace Racketmanager {
    if ( ! function_exists( 'Racketmanager\seo_url' ) ) {
        function seo_url( string $string_field ): string {
            return strtolower( str_replace( ' ', '-', $string_field ) );
        }
    }
}

namespace Racketmanager\Tests\Unit {

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Tournament_Overview_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Overview_Page_View_Model;
use Racketmanager\Domain\Tournament;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Tournament_Service;
use stdClass;

require_once __DIR__ . '/../wp-stubs.php';

final class Tournament_Overview_Admin_Controller_Test extends TestCase {

    public function test_overview_page_returns_view_model(): void {
        $tournament_id = 123;
        $tournament_obj = new stdClass();
        $tournament_obj->id = $tournament_id;
        $tournament_obj->competition_id = 1;
        $tournament_obj->season = 2024;
        $tournament_obj->name = 'Test Tournament';
        $tournament_obj->competition_code = 'TTC';
        $tournament_obj->grade = 'A';
        $tournament_obj->date = '2024-01-01';
        $tournament_obj->date_closing = '2023-12-01';
        $tournament_obj->date_start = '2024-01-01';
        $tournament_obj->date_open = '2023-11-01';
        $tournament_obj->date_withdrawal = '2023-12-15';
        $tournament_obj->venue = 1;
        $tournament_obj->num_entries = 0;
        $tournament = new Tournament( $tournament_obj );
        
        $overview = new stdClass();
        $events = [];
        $categorized_entries = [
            'confirmed' => [],
            'unpaid'    => [],
            'pending'   => [],
            'withdrawn' => [],
        ];

        $tournament_service = $this->createMock( Tournament_Service::class );
        $action_guard = $this->createMock( Action_Guard_Interface::class );

        $tournament_service->expects( self::once() )
            ->method( 'get_tournament' )
            ->with( $tournament_id )
            ->willReturn( $tournament );

        $tournament_service->expects( self::once() )
            ->method( 'get_tournament_overview' )
            ->with( $tournament_id )
            ->willReturn( $overview );

        $tournament_service->expects( self::once() )
            ->method( 'get_leagues_by_event_for_tournament' )
            ->with( $tournament_id )
            ->willReturn( $events );

        $tournament_service->expects( self::once() )
            ->method( 'get_categorized_players_for_tournament' )
            ->with( $tournament_id )
            ->willReturn( $categorized_entries );

        $controller = new Tournament_Overview_Admin_Controller(
            $tournament_service,
            $action_guard
        );

        $result = $controller->overview_page( [ 'tournament' => (string) $tournament_id ], [] );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Overview_Page_View_Model::class, $result['view_model'] );
        self::assertSame( $tournament, $result['view_model']->tournament );
        self::assertSame( $overview, $result['view_model']->overview );
    }
}
}
