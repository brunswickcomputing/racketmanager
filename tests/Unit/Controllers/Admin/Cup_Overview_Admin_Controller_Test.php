<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use Racketmanager\Tests\Helpers\League_Test_Factory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Cup_Overview_Page_View_Model;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Resolves_Cup_Admin_Controllers;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Cup_Overview_Admin_Controller_Test extends TestCase {
    use Resolves_Cup_Admin_Controllers;

    public function test_capability_failure(): void {
        $controller = $this->resolve_controller( 'cup_overview_admin_controller' );
        $this->deny_capability( 'edit_leagues' );

        $this->expectException( Invalid_Status_Exception::class );
        $controller->overview_page( array(), array() );
    }

    public function test_missing_competition_id_throws(): void {
        $controller = $this->resolve_controller( 'cup_overview_admin_controller' );

        $this->expectException( Competition_Not_Found_Exception::class );
        $controller->overview_page( array( 'season' => '2026' ), array() );
    }

    public function test_missing_season_throws(): void {
        $controller = $this->resolve_controller( 'cup_overview_admin_controller' );

        $this->expectException( Season_Not_Found_Exception::class );
        $controller->overview_page( array( 'competition_id' => '1' ), array() );
    }

    public function test_unknown_season_throws(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )
            ->method( 'get_competition_by_season' )
            ->with( 1, 1999 )
            ->willThrowException( new Season_Not_Found_Exception( 'Season not found' ) );

        $controller = $this->resolve_controller( 'cup_overview_admin_controller', array( 'competition_service' => $competition_service ) );

        $this->expectException( Season_Not_Found_Exception::class );
        $controller->overview_page( array( 'competition_id' => '1', 'season' => '1999' ), array() );
    }

    public function test_get_builds_overview_view_model(): void {
        $competition = Competition_Test_Factory::create(
            id: 1,
            name: 'Senior Cup',
            type: Competition_Type::CUP,
            seasons: Competition_Test_Factory::season_map( '2026', array( 'date_closing' => '2999-12-31', 'venue' => 'wtc' ) )
        );

        $champ_ref = new \ReflectionClass( Championship::class );
        $champ_obj = $champ_ref->newInstanceWithoutConstructor();
        $prop      = $champ_ref->getProperty( 'num_teams_first_round' );
        $prop->setAccessible( true );
        $prop->setValue( $champ_obj, 8 );

        $league                  = League_Test_Factory::from_object( (object) array( 'id' => 10, 'title' => 'Men Draw' ) );
        $league->is_championship = true;
        $league->championship    = $champ_obj;
        $event                   = Event::from_object( (object) array( 'id' => 20, 'name' => 'Men Singles', 'type' => 'singles' ), $competition );

        $event_query_service = $this->createMock( Event_Query_Service::class );
        $event_query_service->expects( self::once() )
            ->method( 'get_leagues' )
            ->with( $event )
            ->willReturn( array( $league ) );

        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )
            ->method( 'get_competition_by_season' )
            ->with( 1, 2026 )
            ->willReturn( $competition );
        $competition_service->expects( self::once() )
            ->method( 'get_events_for_competition' )
            ->with( 1, 2026 )
            ->willReturn( array( $event ) );
        $competition_service->expects( self::once() )
            ->method( 'get_competition_overview' )
            ->with( 1, 2026 )
            ->willReturn(
                new \Racketmanager\Domain\DTO\Competition\Competition_Overview_DTO(
                    (object) array( 'id' => 1, 'name' => 'Senior Cup', 'settings' => '{}', 'num_events' => 1, 'num_teams' => 8, 'num_clubs' => 4, 'num_players' => 16 )
                )
            );
        $competition_service->expects( self::once() )
            ->method( 'get_clubs_for_competition' )
            ->with( 1, 2026 )
            ->willReturn(
                array( (object) array( 'id' => 5, 'shortcode' => 'WTC' ) )
            );

        $club_service = $this->createMock( Club_Service::class );
        $club_service->expects( self::once() )
            ->method( 'get_club' )
            ->with( 'wtc' )
            ->willReturn( Club::from_object( (object) array( 'id' => 1, 'shortcode' => 'Westcliff' ) ) );

        $controller = $this->resolve_controller( 'cup_overview_admin_controller', array(
            'competition_service' => $competition_service,
            'event_query_service' => $event_query_service,
            'club_service'        => $club_service,
        ) );

        $result = $controller->overview_page( array( 'competition_id' => '1', 'season' => '2026' ), array() );

        self::assertInstanceOf( Cup_Overview_Page_View_Model::class, $result['view_model'] );
        self::assertSame( '2026', $result['view_model']->season );
        self::assertSame( 'overview', $result['view_model']->tab );
        self::assertFalse( $result['view_model']->cup_season->is_active );
        self::assertSame( 'Westcliff', $result['view_model']->cup_season->venue_name );
        self::assertCount( 1, $result['view_model']->competition_events );
        self::assertArrayHasKey( '10', $result['view_model']->draw_sizes );
        self::assertSame( '8', $result['view_model']->draw_sizes['10'] );
    }

    public function test_get_builds_overview_view_model_when_venue_club_not_found(): void {
        $competition = Competition_Test_Factory::create(
            id: 1,
            name: 'Senior Cup',
            type: Competition_Type::CUP,
            seasons: Competition_Test_Factory::season_map( '2026', array( 'date_closing' => '2020-01-01', 'venue' => 'unknown' ) )
        );

        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )
            ->method( 'get_competition_by_season' )
            ->with( 1, 2026 )
            ->willReturn( $competition );
        $competition_service->expects( self::once() )
            ->method( 'get_events_for_competition' )
            ->with( 1, 2026 )
            ->willReturn( array() );
        $competition_service->expects( self::once() )
            ->method( 'get_competition_overview' )
            ->with( 1, 2026 )
            ->willReturn(
                new \Racketmanager\Domain\DTO\Competition\Competition_Overview_DTO(
                    (object) array( 'id' => 1, 'name' => 'Senior Cup', 'settings' => '{}', 'num_events' => 0, 'num_teams' => 0, 'num_clubs' => 0, 'num_players' => 0 )
                )
            );
        $competition_service->expects( self::once() )
            ->method( 'get_clubs_for_competition' )
            ->with( 1, 2026 )
            ->willReturn( array() );

        $club_service = $this->createMock( Club_Service::class );
        $club_service->expects( self::once() )
            ->method( 'get_club' )
            ->with( 'unknown' )
            ->willThrowException( new Club_Not_Found_Exception( 'Club not found' ) );

        $controller = $this->resolve_controller( 'cup_overview_admin_controller', array(
            'competition_service' => $competition_service,
            'club_service'        => $club_service,
        ) );

        $result = $controller->overview_page( array( 'competition_id' => '1', 'season' => '2026' ), array() );

        self::assertInstanceOf( Cup_Overview_Page_View_Model::class, $result['view_model'] );
        self::assertTrue( $result['view_model']->cup_season->is_active );
        self::assertNull( $result['view_model']->cup_season->venue_name );
    }
}
