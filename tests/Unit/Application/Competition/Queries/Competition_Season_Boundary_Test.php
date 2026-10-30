<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Competition\Queries;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Competition\Queries\Get_Competition_Events_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Events_Query;
use Racketmanager\Application\Competition\Queries\Get_Competition_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Overview_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Overview_Query;
use Racketmanager\Application\Competition\Queries\Get_Competition_Query;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\DTO\Competition\Competition_Overview_DTO;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Competition_Season_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

/**
 * The [competition] and [competition-overview] handlers resolve the request-selected season and the
 * venue name through Competition_Season_Service, on top of the request-neutral repository entity.
 */
class Competition_Season_Boundary_Test extends TestCase {
    use Creates_Competition_Season_Service;
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $_GET = [];
        unset( $GLOBALS['wp'] );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_competition_handler_keeps_request_selected_season_with_its_venue(): void {
        $_GET['season'] = '2016';

        $vm = $this->competition_handler( $this->competition() )->handle( new Get_Competition_Query( competition_id: 7 ) );

        $this->assertNull( $vm->error_message );
        $this->assertSame( '2016', $vm->competition_season['name'] );
        $this->assertSame( 'Hadleigh', $vm->competition_season['venue_name'] );
        $this->assertSame( '2016', $vm->competition->current_season['name'] );
        $this->assertSame( 'end', $vm->competition->current_phase );
    }

    public function test_competition_handler_request_season_beats_shortcode_season(): void {
        $_GET['season'] = '2016';

        $vm = $this->competition_handler( $this->competition() )->handle( new Get_Competition_Query( competition_id: 7, season: '2026' ) );

        $this->assertSame( '2016', $vm->competition_season['name'] );
    }

    public function test_competition_handler_without_request_uses_shortcode_season_or_default(): void {
        $handler = $this->competition_handler( $this->competition() );

        $default = $handler->handle( new Get_Competition_Query( competition_id: 7 ) );
        $this->assertSame( '2026', $default->competition_season['name'] );
        $this->assertSame( 'Thorpe Bay', $default->competition_season['venue_name'] );

        $selected = $handler->handle( new Get_Competition_Query( competition_id: 7, season: '2016' ) );
        $this->assertSame( '2016', $selected->competition_season['name'] );
        $this->assertSame( 'Hadleigh', $selected->competition_season['venue_name'] );
    }

    public function test_competition_handler_ignores_unknown_request_season(): void {
        $_GET['season'] = '1999';

        $vm = $this->competition_handler( $this->competition() )->handle( new Get_Competition_Query( competition_id: 7 ) );

        $this->assertSame( '2026', $vm->competition_season['name'] );
    }

    public function test_overview_handler_exposes_venue_name_to_view_model(): void {
        $competition  = $this->competition();
        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $comp_service->expects( $this->once() )
            ->method( 'get_competition_overview' )
            ->with( 7, 2026 )
            ->willReturn( new Competition_Overview_DTO( $this->overview_row( [ 'num_events' => 3 ] ) ) );

        $handler = new Get_Competition_Overview_Handler(
            $comp_service,
            $this->competition_season_service( [ 12 => 'Hadleigh', 130 => 'Thorpe Bay' ] )
        );

        $result = $handler->handle( new Get_Competition_Overview_Query( competition_id: 7 ) );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( 'Thorpe Bay', $result->competition_season['venue_name'] );
    }

    public function test_overview_handler_honours_request_season(): void {
        $_GET['season_7'] = '2016';
        $competition      = $this->competition();
        $comp_service     = $this->createMock( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $comp_service->expects( $this->once() )
            ->method( 'get_competition_overview' )
            ->with( 7, 2016 )
            ->willReturn( new Competition_Overview_DTO( $this->overview_row() ) );

        $handler = new Get_Competition_Overview_Handler(
            $comp_service,
            $this->competition_season_service( [ 12 => 'Hadleigh', 130 => 'Thorpe Bay' ] )
        );

        $result = $handler->handle( new Get_Competition_Overview_Query( competition_id: 7 ) );

        $this->assertSame( '2016', $result->competition_season['name'] );
        $this->assertSame( 'Hadleigh', $result->competition_season['venue_name'] );
    }

    private function competition(): Competition {
        return Competition_Test_Factory::create(
            id: 7,
            name: 'Summer Cups',
            type: Competition_Type::CUP,
            seasons: [
                '2016' => [
                    'name'       => '2016',
                    'venue'      => 12,
                    'date_start' => '2016-06-01',
                    'date_end'   => '2016-08-01',
                ],
                '2026' => [
                    'name'       => '2026',
                    'venue'      => 130,
                    'date_start' => '2026-06-01',
                    'date_end'   => '2026-08-01',
                ],
            ]
        );
    }

    public function test_standalone_events_handler_follows_request_season(): void {
        $_GET['season'] = '2016';

        $this->assertSame( 2016, $this->events_handler_season( new Get_Competition_Events_Query( competition_id: 7 ) ) );
    }

    public function test_standalone_events_handler_defaults_to_active_season_without_request(): void {
        $this->assertSame( 2026, $this->events_handler_season( new Get_Competition_Events_Query( competition_id: 7 ) ) );
    }

    public function test_standalone_events_handler_explicit_query_season_is_used(): void {
        $this->assertSame( 2016, $this->events_handler_season( new Get_Competition_Events_Query( competition_id: 7, season: 2016 ) ) );
    }

    private function events_handler_season( Get_Competition_Events_Query $query ): int {
        $captured     = null;
        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $this->competition() );
        $comp_service->method( 'get_events_with_details_for_competition' )->willReturnCallback(
            function ( int $id, ?int $season ) use ( &$captured ): array {
                $captured = $season;
                return [];
            }
        );
        $handler = new Get_Competition_Events_Handler(
            $comp_service,
            $this->competition_season_service( [ 12 => 'Hadleigh', 130 => 'Thorpe Bay' ] )
        );
        $handler->handle( $query );

        return (int) $captured;
    }

    private function overview_row( array $overrides = [] ): object {
        return (object) array_merge(
            [ 'id' => 7, 'name' => 'Summer Cups', 'settings' => '{}', 'num_events' => 0, 'num_teams' => 0, 'num_clubs' => 0, 'num_players' => 0 ],
            $overrides
        );
    }

    private function competition_handler( Competition $competition ): Get_Competition_Handler {
        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

        $club_service = $this->createMock( Club_Service::class );
        $club_service->expects( $this->never() )->method( 'get_club' );

        return new Get_Competition_Handler(
            $comp_service,
            $club_service,
            $this->createStub( Player_Service::class ),
            $this->createStub( Security_Service_Interface::class ),
            new Settings_Service( [] ),
            $this->competition_season_service( [ 12 => 'Hadleigh', 130 => 'Thorpe Bay' ] )
        );
    }
}
