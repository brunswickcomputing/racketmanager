<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Admin\Controllers\Tournament_Plan_Admin_Controller;
use Racketmanager\Admin\Presenters\Admin_Plan_Presenter;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Racketmanager\Admin\View_Models\Tournament_Plan_Page_View_Model;
use Racketmanager\Domain\Tournament\Tournament;
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

    private function make_controller( Tournament_Service $tournament_service, Action_Guard_Interface $guard ): Tournament_Plan_Admin_Controller {
        return new Tournament_Plan_Admin_Controller(
            $tournament_service,
            $guard,
            new Admin_Plan_Presenter(
                $this->createStub( Fixture_Detail_Service::class ),
                $this->createStub( Player_Repository_Interface::class ),
                new Fixture_Relation_Loader()
            )
        );
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

        $controller = $this->make_controller( $tournament_service, $guard );

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

        $controller = $this->make_controller( $tournament_service, $guard );

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
            'startTime' => '14:30',
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

        $controller = $this->make_controller( $tournament_service, $guard );

        $result = $controller->plan_page( [], $post_data );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertTrue( $result['view_model']->errors->has( 'error_code' ) );
        self::assertSame( 'Error message', $result['view_model']->errors->message( 'error_code' ) );
        self::assertSame( '14:30', $result['view_model']->config_values['start_time'] );
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

        $controller = $this->make_controller( $tournament_service, $guard );

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

        $controller = $this->make_controller( $tournament_service, $guard );

        $result = $controller->plan_page( [ 'page' => 'racketmanager-tournaments' ], $post_data );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'config_saved=1', $result['redirect'] );
    }

    public function test_plan_page_throws_exception_if_tournament_not_found(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $tournament_id = 999;
        $tournament_service->expects( self::once() )
            ->method( 'get_tournament_with_details' )
            ->with( $tournament_id )
            ->willThrowException( new Tournament_Not_Found_Exception( 'Not found' ) );

        $controller = $this->make_controller( $tournament_service, $guard );

        $this->expectException( Tournament_Not_Found_Exception::class );
        $controller->plan_page( [ 'tournament' => (string) $tournament_id ], [] );
    }
}
