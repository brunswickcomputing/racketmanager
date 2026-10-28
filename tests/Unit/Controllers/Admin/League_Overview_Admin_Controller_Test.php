<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\League_Overview_Page_View_Model;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Resolves_League_Admin_Controllers;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Overview_Admin_Controller_Test extends TestCase {
    use Resolves_League_Admin_Controllers;

    private function service_for( ?object $competition ): Competition_Service {
        $service = $this->createMock( Competition_Service::class );
        $service->method( 'get_competition' )->willReturn( $competition );

        return $service;
    }

    public function test_capability_failure(): void {
        $controller = $this->resolve_controller( 'league_overview_admin_controller' );
        $this->deny_capability( 'edit_leagues' );

        $this->expectException( Invalid_Status_Exception::class );
        $controller->overview_page( array(), array() );
    }

    public function test_missing_competition_id_throws(): void {
        $controller = $this->resolve_controller( 'league_overview_admin_controller' );

        $this->expectException( Competition_Not_Found_Exception::class );
        $controller->overview_page( array( 'season' => '2026' ), array() );
    }

    public function test_unknown_season_throws(): void {
        $competition = Competition_Test_Factory::create( id: 1, type: Competition_Type::LEAGUE, seasons: Competition_Test_Factory::season_map( '2026' ) );
        $controller  = $this->resolve_controller( 'league_overview_admin_controller', array( 'competition_service' => $this->service_for( $competition ) ) );

        $this->expectException( Season_Not_Found_Exception::class );
        $controller->overview_page( array( 'competition_id' => '1', 'season' => '1999' ), array() );
    }

    public function test_get_builds_overview_view_model(): void {
        $competition = Competition_Test_Factory::create(
            id: 1,
            name: 'Mixed League',
            type: Competition_Type::LEAGUE,
            seasons: Competition_Test_Factory::season_map( '2026', array( 'date_closing' => '2999-12-31' ) )
        );
        $service     = $this->service_for( $competition );
        $service->method( 'get_competition_overview' )->willReturn(
            new \Racketmanager\Domain\DTO\Competition\Competition_Overview_DTO(
                (object) array( 'id' => 1, 'name' => 'Mixed League', 'settings' => '{}', 'num_events' => 0, 'num_teams' => 0, 'num_clubs' => 1, 'num_players' => 0 )
            )
        );
        $service->method( 'get_clubs_for_competition' )->willReturn( array( (object) array( 'id' => 1, 'shortcode' => 'WTC', 'team_count' => 2 ) ) );

        $controller = $this->resolve_controller( 'league_overview_admin_controller', array( 'competition_service' => $service ) );
        $result     = $controller->overview_page( array( 'competition_id' => '1', 'season' => '2026' ), array() );

        self::assertInstanceOf( League_Overview_Page_View_Model::class, $result['view_model'] );
        self::assertSame( '2026', $result['view_model']->season );
        self::assertSame( 'overview', $result['view_model']->tab );
        self::assertFalse( $result['view_model']->current_season->is_active );
        self::assertCount( 1, $result['view_model']->current_season->entries );
    }

    public function test_post_contact_team_redirects(): void {
        $controller = $this->resolve_controller( 'league_overview_admin_controller' );

        $result = $controller->overview_page( array( 'page' => 'racketmanager-leagues', 'competition_id' => '1' ), array( 'contactTeam' => '1' ) );

        self::assertArrayNotHasKey( 'view_model', $result );
        self::assertStringContainsString( 'page=racketmanager-leagues', $result['redirect'] );
    }
}
