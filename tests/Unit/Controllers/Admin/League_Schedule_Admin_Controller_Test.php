<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Admin_League_Schedule_Page_View_Model;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Resolves_League_Admin_Controllers;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Schedule_Admin_Controller_Test extends TestCase {
    use Resolves_League_Admin_Controllers;
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void {
        unset( $_SERVER['REQUEST_METHOD'] );
        $this->restore_globals();
        parent::tearDown();
    }

    private function service(): Competition_Service {
        $competition = Competition_Test_Factory::create(
            id: 55,
            name: 'Premier Leagues',
            type: Competition_Type::LEAGUE,
            seasons: Competition_Test_Factory::season_map( '2026' ),
            current_season: array( 'name' => '2026' )
        );
        $service     = $this->createMock( Competition_Service::class );
        $service->method( 'get_competition' )->willReturn( $competition );
        $service->method( 'get_events_for_competition' )->willReturn( array() );

        return $service;
    }

    public function test_capability_failure(): void {
        $controller = $this->resolve_controller( 'league_schedule_admin_controller' );
        $this->deny_capability( 'edit_leagues' );

        $this->expectException( Invalid_Status_Exception::class );
        $controller->schedule_page( array(), array() );
    }

    public function test_missing_competition_throws(): void {
        $controller = $this->resolve_controller( 'league_schedule_admin_controller' );

        $this->expectException( Competition_Not_Found_Exception::class );
        $controller->schedule_page( array(), array() );
    }

    public function test_get_presents_the_schedule_using_the_flash_data_handed_over_by_the_bridge(): void {
        $controller = $this->resolve_controller( 'league_schedule_admin_controller', array( 'competition_service' => $this->service() ) );
        $report     = array( 'retry' => 2, 'scheduled' => 7, 'conflicts' => array(), 'alerts' => array(), 'resource_pairings' => array() );

        $result = $controller->schedule_page( array( 'competition_id' => '55' ), array(), $report );

        self::assertInstanceOf( Admin_League_Schedule_Page_View_Model::class, $result['view_model'] );
        self::assertSame( '2026', $result['view_model']->season );
        self::assertSame( 55, $result['view_model']->competition_id );
        self::assertSame( 7, $result['view_model']->schedule_report['scheduled'] );
        self::assertSame( 2, $result['view_model']->schedule_report['retry'] );
    }

    public function test_post_uses_the_form_nonce_and_redirects_with_flash_data(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $controller                = $this->resolve_controller( 'league_schedule_admin_controller', array( 'competition_service' => $this->service() ) );
        $this->guard->expects( self::atLeastOnce() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_schedule-matches', 'edit_leagues' );

        $result = $controller->schedule_page(
            array( 'page' => 'racketmanager-leagues', 'view' => 'plan', 'competition_id' => '55', 'season' => '2026' ),
            array( 'scheduleAction' => '1', 'actionSchedule' => 'schedule' )
        );

        self::assertArrayNotHasKey( 'view_model', $result );
        self::assertStringContainsString( 'page=racketmanager-leagues', $result['redirect'] );
        self::assertStringContainsString( 'view=plan', $result['redirect'] );
        self::assertArrayHasKey( 'flash_data', $result );
    }
}
