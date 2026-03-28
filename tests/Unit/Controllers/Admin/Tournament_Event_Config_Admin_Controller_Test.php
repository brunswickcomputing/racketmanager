<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Tournament_Event_Config_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Event_Config_Page_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use stdClass;

require_once __DIR__ . '/../../../wp-stubs.php';

final class Tournament_Event_Config_Admin_Controller_Test extends TestCase {

    private $tournament_service;
    private $competition_service;
    private $guard;
    private $controller;

    protected function setUp(): void {
        $this->tournament_service = $this->createMock( Tournament_Service::class );
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->guard = $this->createMock( Action_Guard_Interface::class );

        $this->controller = new Tournament_Event_Config_Admin_Controller(
            $this->tournament_service,
            $this->competition_service,
            $this->guard
        );
    }

    public function test_handle_returns_error_if_no_capability(): void {
        $this->guard->method( 'assert_capability' )->willThrowException( new \Exception( 'No cap' ) );

        $result = $this->controller->handle();

        self::assertEquals( 'No cap', $result['message'] );
    }

    public function test_handle_returns_view_model_for_display_new_event(): void {
        $_GET['competition_id'] = '123';
        $_GET['event_id'] = '999';

        $competition = $this->createMock( Competition::class );
        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );
        $this->competition_service->method( 'get_event_by_id' )->willThrowException( new Event_Not_Found_Exception() );

        $result = $this->controller->handle();

        self::assertArrayHasKey( 'view_model', $result );
        self::assertTrue( $result['view_model']->new_event );
    }

    public function test_handle_returns_view_model_for_display_existing_event(): void {
        $_GET['competition_id'] = '123';
        $_GET['event_id'] = '456';

        $competition = $this->createMock( Competition::class );
        $event = $this->createMock( Event::class );
        $event->settings = [];
        $event->num_sets = 3;
        $event->num_rubbers = 6;

        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );
        $this->competition_service->method( 'get_event_by_id' )->with( 456 )->willReturn( $event );

        $result = $this->controller->handle();

        self::assertArrayHasKey( 'view_model', $result );
        self::assertFalse( $result['view_model']->new_event );
    }
}
