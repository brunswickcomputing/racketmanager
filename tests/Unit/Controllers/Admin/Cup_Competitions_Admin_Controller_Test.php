<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Cup_Competitions_Admin_Controller;
use Racketmanager\Admin\View_Models\Competitions_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Resolves_Cup_Admin_Controllers;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Cup_Competitions_Admin_Controller_Test extends TestCase {
    use Resolves_Cup_Admin_Controllers;

    public function test_capability_failure_stops_before_any_work(): void {
        $controller = $this->resolve_controller( 'cup_competitions_admin_controller' );
        $this->deny_capability( 'edit_leagues' );

        $this->expectException( Invalid_Status_Exception::class );
        $controller->competitions_page( array(), array() );
    }

    public function test_get_returns_competitions_view_model(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )
            ->method( 'find_competitions_with_summary' )
            ->with( 'open', 'cup' )
            ->willReturn( array( (object) array( 'id' => 1 ) ) );

        $controller = $this->resolve_controller( 'cup_competitions_admin_controller', array( 'competition_service' => $competition_service ) );
        $this->guard->expects( self::once() )->method( 'assert_capability' )->with( 'edit_leagues' );

        $result = $controller->competitions_page( array( 'age_group' => 'open' ), array() );

        self::assertInstanceOf( Cup_Competitions_Admin_Controller::class, $controller );
        self::assertInstanceOf( Competitions_Page_View_Model::class, $result['view_model'] );
        self::assertCount( 1, $result['view_model']->competitions );
        self::assertSame( 'open', $result['view_model']->age_group_select );
        self::assertSame( 'racketmanager-cups', $result['view_model']->page_name );
        self::assertSame( 'Cup Competitions', $result['view_model']->page_title );
        self::assertArrayNotHasKey( 'redirect', $result );
    }

    public function test_post_bulk_delete_redirects_back_to_the_cup_screen(): void {
        $controller = $this->resolve_controller( 'cup_competitions_admin_controller' );

        $result = $controller->competitions_page( array( 'page' => 'racketmanager-cups' ), array( 'doCompDel' => '1' ) );

        self::assertArrayNotHasKey( 'view_model', $result );
        self::assertStringContainsString( 'page=racketmanager-cups', $result['redirect'] );
    }
}
