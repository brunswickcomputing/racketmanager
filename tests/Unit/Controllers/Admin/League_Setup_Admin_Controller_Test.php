<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Setup_Page_View_Model;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Resolves_League_Admin_Controllers;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Setup_Admin_Controller_Test extends TestCase {
    use Resolves_League_Admin_Controllers;

    public function test_capability_failure(): void {
        $controller = $this->resolve_controller( 'league_setup_admin_controller' );
        $this->deny_capability( 'edit_matches' );

        $this->expectException( Invalid_Status_Exception::class );
        $controller->setup_page( array(), array() );
    }

    public function test_missing_competition_throws(): void {
        $controller = $this->resolve_controller( 'league_setup_admin_controller' );

        $this->expectException( Competition_Not_Found_Exception::class );
        $controller->setup_page( array( 'season' => '2026' ), array() );
    }

    public function test_missing_season_throws(): void {
        $service = $this->createMock( Competition_Service::class );
        $service->method( 'get_competition' )->willReturn( Competition_Test_Factory::create( id: 1, type: Competition_Type::LEAGUE ) );
        $controller = $this->resolve_controller( 'league_setup_admin_controller', array( 'competition_service' => $service ) );

        $this->expectException( Season_Not_Found_Exception::class );
        $controller->setup_page( array( 'competition_id' => '1' ), array() );
    }

    public function test_get_returns_setup_view_model(): void {
        $competition = Competition_Test_Factory::create(
            id: 1,
            type: Competition_Type::LEAGUE,
            seasons: Competition_Test_Factory::season_map( '2026', array( 'match_dates' => array( '2026-05-01' ) ) )
        );
        $service     = $this->createMock( Competition_Service::class );
        $service->method( 'get_competition' )->willReturn( $competition );

        $controller = $this->resolve_controller( 'league_setup_admin_controller', array( 'competition_service' => $service ) );
        $result     = $controller->setup_page( array( 'competition_id' => '1', 'season' => '2026' ), array() );

        self::assertInstanceOf( Setup_Page_View_Model::class, $result['view_model'] );
        self::assertSame( $competition, $result['view_model']->competition );
        self::assertSame( '2026', $result['view_model']->season );
        self::assertNull( $result['view_model']->event );
    }

    public function test_post_redirects_back_to_the_league_screen(): void {
        $controller = $this->resolve_controller( 'league_setup_admin_controller' );

        $result = $controller->setup_page(
            array( 'page' => 'racketmanager-leagues', 'view' => 'setup', 'competition_id' => '1', 'season' => '2026' ),
            array( 'rank' => '1' )
        );

        self::assertArrayNotHasKey( 'view_model', $result );
        self::assertStringContainsString( 'page=racketmanager-leagues', $result['redirect'] );
    }
}
