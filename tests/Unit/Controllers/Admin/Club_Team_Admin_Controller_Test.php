<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Club_Team_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Team_Page_View_Model;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Club_Team_Admin_Controller_Test extends TestCase {

    private Tournament_Service $tournament_service;
    private League_Service $league_service;
    private Team_Service $team_service;
    private Club_Service $club_service;
    private League_Query_Service $league_query_service;
    private Action_Guard_Interface $guard;
    private Club_Team_Admin_Controller $controller;

    protected function setUp(): void {
        $this->tournament_service   = $this->createMock( Tournament_Service::class );
        $this->league_service       = $this->createMock( League_Service::class );
        $this->team_service         = $this->createMock( Team_Service::class );
        $this->club_service         = $this->createMock( Club_Service::class );
        $this->league_query_service = $this->createMock( League_Query_Service::class );
        $this->guard                = $this->createMock( Action_Guard_Interface::class );

        $this->controller = new Club_Team_Admin_Controller(
            $this->tournament_service,
            $this->league_service,
            $this->team_service,
            $this->club_service,
            $this->league_query_service,
            $this->guard
        );
    }

    public function test_capability_check_fails_returns_error_message(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_teams' )
            ->willThrowException( new Exception( 'Permission denied' ) );

        $result = $this->controller->team_page( array(), array() );

        self::assertArrayHasKey( 'message', $result );
        self::assertSame( 'Permission denied', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }

    public function test_missing_team_id_returns_error_message(): void {
        $result = $this->controller->team_page( array(), array() );

        self::assertArrayHasKey( 'message', $result );
        self::assertSame( 'Team not specified', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }

    public function test_league_not_found_returns_error_message(): void {
        $this->league_service->expects( self::once() )
            ->method( 'get_league' )
            ->with( 10 )
            ->willReturn( null );

        $result = $this->controller->team_page(
            array( 'edit' => '5', 'league_id' => '10' ),
            array()
        );

        self::assertArrayHasKey( 'message', $result );
        self::assertSame( 'League not found', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }

    public function test_tournament_not_found_returns_error_message(): void {
        $this->tournament_service->expects( self::once() )
            ->method( 'get_tournament' )
            ->with( 20 )
            ->willThrowException( new Tournament_Not_Found_Exception( 'Tournament not found' ) );

        $result = $this->controller->team_page(
            array( 'edit' => '5', 'tournament' => '20' ),
            array()
        );

        self::assertArrayHasKey( 'message', $result );
        self::assertSame( 'Tournament not found', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }

    public function test_team_not_found_returns_error_message(): void {
        $this->team_service->expects( self::once() )
            ->method( 'get_team_by_id' )
            ->with( 5 )
            ->willThrowException( new Team_Not_Found_Exception( 'Team not found' ) );

        $result = $this->controller->team_page(
            array( 'edit' => '5' ),
            array()
        );

        self::assertArrayHasKey( 'message', $result );
        self::assertSame( 'Team not found', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }

    public function test_club_team_success_returns_view_model(): void {
        $team = $this->createStub( Team::class );
        $team->id = 5;
        $team->title = 'Team 1';
        $team->club_id = 1;
        $this->team_service->expects( self::once() )
            ->method( 'get_team_by_id' )
            ->with( 5 )
            ->willReturn( $team );
        $this->club_service->method( 'get_clubs' )->willReturn( array() );

        $result = $this->controller->team_page(
            array( 'edit' => '5', 'club_id' => '1' ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Team_Page_View_Model::class, $result['view_model'] );
        self::assertSame( $team, $result['view_model']->team );
        self::assertSame( 1, $result['view_model']->club_id );
        self::assertStringContainsString( 'team', $result['view_model']->file );
    }

    public function test_league_team_success_returns_view_model(): void {
        $league = $this->createStub( League::class );
        $team   = (object) array( 'id' => 5, 'title' => 'League Team', 'club_id' => 2 );

        $this->league_service->expects( self::once() )
            ->method( 'get_league' )
            ->with( 10 )
            ->willReturn( $league );
        $this->league_query_service->expects( self::once() )
            ->method( 'get_team_dtls' )
            ->with( $league, 5, '2025' )
            ->willReturn( $team );
        $this->club_service->method( 'get_clubs' )->willReturn( array() );

        $result = $this->controller->team_page(
            array( 'edit' => '5', 'league_id' => '10', 'season' => '2025' ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Team_Page_View_Model::class, $result['view_model'] );
        self::assertSame( $team, $result['view_model']->team );
        self::assertSame( $league, $result['view_model']->league );
        self::assertSame( '2025', $result['view_model']->season );
    }
}
