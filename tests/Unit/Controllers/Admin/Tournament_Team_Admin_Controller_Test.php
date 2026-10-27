<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Admin\Controllers\Tournament_Team_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Team_Page_View_Model;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Domain\Tournament\Tournament;
use stdClass;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final
class Tournament_Team_Admin_Controller_Test extends TestCase {

    private $tournament_service;
    private $league_service;
    private $team_service;
    private $club_service;
    private $guard;
    private $league_query_service;
    private $controller;

    protected function setUp(): void {
        $this->tournament_service = $this->createMock( Tournament_Service::class );
        $this->league_service     = $this->createStub( League_Service::class );
        $this->team_service       = $this->createMock( Team_Service::class );
        $this->club_service       = $this->createStub( Club_Service::class );
        $this->guard              = $this->createStub( Action_Guard_Interface::class );
        $this->league_query_service = $this->createMock( \Racketmanager\Services\Competition\League_Query_Service::class );

        $this->controller = new Tournament_Team_Admin_Controller(
            $this->tournament_service,
            $this->league_service,
            $this->team_service,
            $this->club_service,
            $this->guard,
            $this->league_query_service
        );
    }

    public function test_handle_returns_error_if_no_capability(): void {
        $this->guard->method( 'assert_capability' )->willThrowException( new \Exception( 'No cap' ) );

        $result = $this->controller->handle( [] );

        self::assertEquals( 'No cap', $result['message'] );
    }

    public function test_handle_returns_view_model_for_display(): void {
        $query = [
            'edit'       => '123',
            'tournament' => '456',
        ];

        $tournament = unserialize( sprintf( 'O:%d:"Racketmanager\Domain\Tournament\Tournament":0:{}', strlen( 'Racketmanager\Domain\Tournament\Tournament' ) ) );
        $this->tournament_service->expects( self::once() )->method( 'get_tournament' )->with( 456 )->willReturn( $tournament );

        $team_obj = new stdClass();
        $team_obj->id = 123;
        $team_obj->title = 'Test Team';
        $team_obj->roster = [];
        $team_obj->team_type = 'C';
        $team = new \Racketmanager\Domain\Team\Team( $team_obj );

        $this->team_service->expects( self::once() )->method( 'get_team_by_id' )->with( 123 )->willReturn( $team );
        $this->club_service->method( 'get_clubs' )->willReturn( [] );

        $result = $this->controller->handle( $query );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Team_Page_View_Model::class, $result['view_model'] );
    }

    public function test_handle_uses_injected_league_query_service_when_league_given(): void {
        $competition                  = new stdClass();
        $competition->is_player_entry = false;
        $event                        = $this->createStub( \Racketmanager\Domain\Competition\Event::class );
        $event->competition           = $competition;
        $league                       = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        $league->event                = $event;

        $this->league_service->method( 'get_league' )->willReturn( $league );

        $team_obj            = new stdClass();
        $team_obj->id        = 123;
        $team_obj->title     = 'Test Team';
        $team_obj->roster    = [];
        $team_obj->team_type = 'C';
        $team                = new \Racketmanager\Domain\Team\Team( $team_obj );

        $this->league_query_service->expects( self::once() )->method( 'get_team_dtls' )->with( $league, 123 )->willReturn( $team );
        $this->team_service->expects( self::never() )->method( 'get_team_by_id' );
        $this->club_service->method( 'get_clubs' )->willReturn( [] );

        $result = $this->controller->handle( [ 'edit' => '123', 'league_id' => '7', 'season' => '2026' ] );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertSame( $team, $result['view_model']->team );
    }
}
