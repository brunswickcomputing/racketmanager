<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Tournament_Team_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Team_Page_View_Model;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Domain\Tournament;
use stdClass;

require_once __DIR__ . '/../wp-stubs.php';

final class Tournament_Team_Admin_Controller_Test extends TestCase {

    private $tournament_service;
    private $club_service;
    private $guard;
    private $controller;

    protected function setUp(): void {
        $this->tournament_service = $this->createMock( Tournament_Service::class );
        $this->club_service = $this->createMock( Club_Service::class );
        $this->guard = $this->createMock( Action_Guard_Interface::class );

        $this->controller = new Tournament_Team_Admin_Controller(
            $this->tournament_service,
            $this->club_service,
            $this->guard
        );
    }

    public function test_handle_returns_error_if_no_capability(): void {
        $this->guard->method( 'assert_capability' )->willThrowException( new \Exception( 'No cap' ) );

        $result = $this->controller->handle();

        self::assertEquals( 'No cap', $result['message'] );
    }

    public function test_handle_returns_view_model_for_display(): void {
        $_GET['edit'] = '123';
        $_GET['tournament'] = '456';

        $tournament = unserialize( sprintf( 'O:%d:"Racketmanager\Domain\Tournament":0:{}', strlen( 'Racketmanager\Domain\Tournament' ) ) );
        $this->tournament_service->method( 'get_tournament' )->with( 456 )->willReturn( $tournament );

        $team = new stdClass();
        $team->id = 123;
        $team->roster = [];

        // Define the global function get_team in Racketmanager namespace
        if ( ! function_exists( 'Racketmanager\\get_team' ) ) {
            eval( 'namespace Racketmanager; function get_team($id) { global $mock_team; return $mock_team; }' );
        }
        global $mock_team;
        $mock_team = $team;

        $this->club_service->method( 'get_clubs' )->willReturn( [] );

        $result = $this->controller->handle();

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Team_Page_View_Model::class, $result['view_model'] );
    }
}
