<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Admin\Controllers\Tournament_Plan_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Plan_Page_View_Model;
use Racketmanager\Domain\Tournament;
use Racketmanager\Domain\DTO\Tournament\Tournament_Details_DTO;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Tournament_Service;
use stdClass;
use WP_Error;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final
class Tournament_Plan_Admin_Controller_Test extends TestCase {

    protected function tearDown(): void {
        unset( $_SERVER['REQUEST_METHOD'] );
        parent::tearDown();
    }

    public function test_plan_page_get_renders_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $tournament_id = 123;
        $tournament_details = $this->createMock( Tournament_Details_DTO::class );
        $tournament = (new \ReflectionClass( Tournament::class ))->newInstanceWithoutConstructor();
        $tournament->id = $tournament_id;
        $tournament_details->tournament = $tournament;

        $tournament_service->expects( self::once() )
            ->method( 'get_tournament_with_details' )
            ->with( $tournament_id )
            ->willReturn( $tournament_details );

        $tournament_service->expects( self::once() )
            ->method( 'get_finals_matches_for_tournament' )
            ->with( $tournament_id )
            ->willReturn( [] );

        $controller = new Tournament_Plan_Admin_Controller( $tournament_service, $guard );

        $result = $controller->plan_page( [ 'tournament' => (string) $tournament_id, 'tab' => 'fixtures' ], [] );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Plan_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'fixtures', $result['view_model']->tab );
    }

    public function test_plan_page_save_plan_redirects_on_success(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $tournament_id = 123;
        $post_data = [
            'saveTournamentPlan' => '1',
            'tournamentId' => (string) $tournament_id,
            'racketmanager_nonce' => 'valid_nonce'
        ];

        $tournament_service->expects( self::once() )
            ->method( 'save_finals_plan_for_tournament' )
            ->with( $tournament_id, self::isInstanceOf( \Racketmanager\Domain\DTO\Tournament\Tournament_Finals_Request_DTO::class ) )
            ->willReturn( true );

        $controller = new Tournament_Plan_Admin_Controller( $tournament_service, $guard );

        $result = $controller->plan_page( [ 'page' => 'racketmanager-tournaments' ], $post_data );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=plan', $result['redirect'] );
        self::assertStringContainsString( 'plan_saved=1', $result['redirect'] );
    }

    public function test_plan_page_save_plan_renders_errors_on_failure(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $tournament_id = 123;
        $post_data = [
            'saveTournamentPlan' => '1',
            'tournamentId' => (string) $tournament_id,
        ];

        $wp_error = new WP_Error( 'error_code', 'Error message' );
        $tournament_service->expects( self::once() )
            ->method( 'save_finals_plan_for_tournament' )
            ->willReturn( $wp_error );

        // Mocking render dependencies
        $tournament_details = $this->createMock( Tournament_Details_DTO::class );
        $tournament = (new \ReflectionClass( Tournament::class ))->newInstanceWithoutConstructor();
        $tournament_details->tournament = $tournament;
        $tournament_service->method( 'get_tournament_with_details' )->willReturn( $tournament_details );
        $tournament_service->method( 'get_finals_matches_for_tournament' )->willReturn( [] );

        $controller = new Tournament_Plan_Admin_Controller( $tournament_service, $guard );

        $result = $controller->plan_page( [], $post_data );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertTrue( $result['view_model']->validator->error );
        self::assertContains( 'Error message', $result['view_model']->validator->err_msgs );
    }

    public function test_plan_page_reset_plan_redirects(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $tournament_id = 123;
        $post_data = [
            'resetTournamentPlan' => '1',
            'tournamentId' => (string) $tournament_id,
        ];

        $tournament_service->expects( self::once() )
            ->method( 'reset_plan_for_tournament' )
            ->with( $tournament_id )
            ->willReturn( true );

        $controller = new Tournament_Plan_Admin_Controller( $tournament_service, $guard );

        $result = $controller->plan_page( [ 'page' => 'racketmanager-tournaments' ], $post_data );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'plan_reset=1', $result['redirect'] );
    }

    public function test_plan_page_save_config_redirects_on_success(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $tournament_id = 123;
        $post_data = [
            'saveTournamentFinalsConfig' => '1',
            'tournamentId' => (string) $tournament_id,
        ];

        $tournament_service->expects( self::once() )
            ->method( 'set_finals_config_for_tournament' )
            ->with( $tournament_id, self::isInstanceOf( \Racketmanager\Domain\DTO\Tournament\Tournament_Finals_Config_Request_DTO::class ) )
            ->willReturn( true );

        $controller = new Tournament_Plan_Admin_Controller( $tournament_service, $guard );

        $result = $controller->plan_page( [ 'page' => 'racketmanager-tournaments' ], $post_data );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'config_saved=1', $result['redirect'] );
    }

    public function test_plan_page_throws_exception_if_tournament_not_found(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $tournament_id = 999;
        $tournament_service->method( 'get_tournament_with_details' )
            ->with( $tournament_id )
            ->willThrowException( new Tournament_Not_Found_Exception( 'Not found' ) );

        $controller = new Tournament_Plan_Admin_Controller( $tournament_service, $guard );

        $this->expectException( Tournament_Not_Found_Exception::class );
        $controller->plan_page( [ 'tournament' => (string) $tournament_id ], [] );
    }
}
