<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Admin\Controllers\Tournament_Competition_Config_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Competition_Config_Page_View_Model;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use stdClass;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final
class Tournament_Competition_Config_Admin_Controller_Test extends TestCase {

    private $tournament_service;
    private $competition_service;
    private $club_service;
    private $guard;
    private $controller;

    protected function setUp(): void {
        $this->tournament_service = $this->createMock( Tournament_Service::class );
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->club_service = $this->createMock( Club_Service::class );
        $this->guard = $this->createMock( Action_Guard_Interface::class );

        $this->controller = new Tournament_Competition_Config_Admin_Controller(
            $this->tournament_service,
            $this->competition_service,
            $this->club_service,
            $this->guard
        );
    }

    public function test_handle_returns_error_if_no_capability(): void {
        $this->guard->method( 'assert_capability' )->with( 'edit_leagues' )->willThrowException( new \Exception( 'No capability' ) );

        $result = $this->controller->handle();

        self::assertEquals( 'No capability', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }

    public function test_handle_returns_error_if_no_competition_id(): void {
        $_GET = [];

        $result = $this->controller->handle();

        self::assertEquals( 'Competition not specified', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }

    public function test_handle_returns_error_if_competition_not_found(): void {
        $_GET['competition_id'] = '123';

        $this->competition_service->method( 'get_by_id' )
            ->with( 123 )
            ->willThrowException( new Competition_Not_Found_Exception( 'Not found' ) );

        $result = $this->controller->handle();

        self::assertEquals( 'Not found', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }

    public function test_handle_returns_view_model_for_display(): void {
        $_GET['competition_id'] = '123';
        $_POST = [];

        $competition = $this->createMock( Competition::class );
        $competition->id = 123;
        $competition->type = 'type';
        $competition->age_group = 'age';
        $competition->method( 'get_settings' )->willReturn( [] );

        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );
        $this->competition_service->method( 'get_rules_options' )->willReturn( [] );
        $this->club_service->method( 'get_clubs' )->willReturn( [] );

        $result = $this->controller->handle();

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Competition_Config_Page_View_Model::class, $result['view_model'] );
    }

    public function test_handle_update_success(): void {
        $_GET['competition_id'] = '123';
        $_POST['updateCompetitionConfig'] = '1';
        $_POST['competition_id'] = '123';
        $_POST['competition_title'] = 'New Title';

        $competition = $this->createMock( Competition::class );
        $competition->id = 123;
        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );
        
        $this->competition_service->expects( self::once() )
            ->method( 'amend_details' )
            ->with( 123, self::callback( function( $config ) {
                return $config->name === 'New Title';
            } ) )
            ->willReturn( 1 );

        $result = $this->controller->handle();

        self::assertEquals( 'Competition updated', $result['message'] );
        self::assertEquals( 'success', $result['message_type'] );
        self::assertArrayHasKey( 'redirect', $result );
    }
}
