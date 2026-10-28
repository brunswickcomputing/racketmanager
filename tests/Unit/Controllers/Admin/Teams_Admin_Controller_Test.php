<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Admin_Page_Context;
use Racketmanager\Admin\Controllers\Teams_Admin_Controller;
use Racketmanager\Admin\View_Models\Teams_List_Page_View_Model;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\League_Team_Admin_Service;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Tournament_Service;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Teams_Admin_Controller_Test extends TestCase {

    private Tournament_Service $tournament_service;
    private League_Service $league_service;
    private League_Team_Admin_Service $team_admin_service;
    private Action_Guard_Interface $guard;
    private Teams_Admin_Controller $controller;

    protected function setUp(): void {
        $this->tournament_service = $this->createMock( Tournament_Service::class );
        $this->league_service     = $this->createMock( League_Service::class );
        $this->team_admin_service = $this->createMock( League_Team_Admin_Service::class );
        $this->guard              = $this->createMock( Action_Guard_Interface::class );
        $this->controller         = new Teams_Admin_Controller(
            $this->tournament_service,
            $this->league_service,
            $this->team_admin_service,
            $this->guard
        );
    }

    protected function tearDown(): void {
        unset( $_SERVER['REQUEST_METHOD'] );
    }

    private function league(): League {
        $league        = $this->createMock( League::class );
        $event              = $this->createMock( Event::class );
        $event->competition = (object) array( 'id' => 55 );
        $league->event      = $event;
        return $league;
    }

    private function added( int $count ): array {
        return array( 'count' => $count, 'messages' => array(), 'errors' => array() );
    }

    public function test_get_returns_view_model_with_addable_teams_for_each_context(): void {
        $league = $this->league();
        $this->league_service->expects( self::exactly( 3 ) )->method( 'get_league' )->with( 1 )->willReturn( $league );
        $this->team_admin_service->expects( self::exactly( 3 ) )->method( 'get_addable_teams' )->with( $league, '2024' )->willReturn( array( 'team' ) );
        $this->guard->expects( self::exactly( 3 ) )->method( 'assert_capability' )->with( 'edit_teams' );

        foreach ( array( Admin_Page_Context::tournament(), Admin_Page_Context::league(), Admin_Page_Context::cup() ) as $context ) {
            $result = $this->controller->teams_page( array( 'league_id' => '1', 'season' => '2024', 'type' => 'constitution' ), array(), $context );

            self::assertInstanceOf( Teams_List_Page_View_Model::class, $result['view_model'] );
            self::assertSame( array( 'team' ), $result['view_model']->teams );
            self::assertSame( 'constitution', $result['view_model']->type );
            self::assertSame( '2024', $result['view_model']->season );
        }
    }

    public function test_post_redirects_to_tournament_draw(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->league_service->method( 'get_league' )->willReturn( $this->league() );
        $this->guard->expects( self::once() )->method( 'assert_allowed' )->with( 'racketmanager_nonce', 'racketmanager_add-teams-bulk', 'edit_teams' );
        $this->team_admin_service->expects( self::once() )->method( 'add_teams' )->with( self::anything(), array( '10', '11' ), 2024 )->willReturn( $this->added( 2 ) );

        $result = $this->controller->teams_page(
            array( 'league_id' => '1', 'tournament' => '9', 'page' => 'racketmanager-tournaments' ),
            array( 'team' => array( '10', '11' ), 'season' => '2024' ),
            Admin_Page_Context::tournament()
        );

        self::assertSame( '2 teams added', $result['message'] );
        self::assertFalse( $result['message_type'] );
        self::assertStringContainsString( 'page=racketmanager-tournaments', $result['redirect'] );
        self::assertStringContainsString( 'view=draw', $result['redirect'] );
        self::assertStringContainsString( 'tournament=9', $result['redirect'] );
        self::assertStringContainsString( 'league=1', $result['redirect'] );
    }

    public function test_post_redirects_to_league_screen(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->league_service->method( 'get_league' )->willReturn( $this->league() );
        $this->team_admin_service->method( 'add_teams' )->willReturn( $this->added( 1 ) );

        $result = $this->controller->teams_page(
            array( 'league_id' => '1', 'page' => 'racketmanager-leagues' ),
            array( 'team' => array( '10' ), 'season' => '2024' ),
            Admin_Page_Context::league()
        );

        self::assertSame( '1 team added', $result['message'] );
        self::assertStringContainsString( 'page=racketmanager-leagues', $result['redirect'] );
        self::assertStringContainsString( 'view=league', $result['redirect'] );
        self::assertStringContainsString( 'league_id=1', $result['redirect'] );
        self::assertStringContainsString( 'season=2024', $result['redirect'] );
    }

    public function test_post_redirects_to_constitution_when_type_is_constitution(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->league_service->method( 'get_league' )->willReturn( $this->league() );
        $this->team_admin_service->method( 'add_teams' )->willReturn( $this->added( 1 ) );

        $result = $this->controller->teams_page(
            array( 'league_id' => '1', 'type' => 'constitution', 'event_id' => '4' ),
            array( 'team' => array( '10' ), 'season' => '2024' ),
            Admin_Page_Context::league()
        );

        self::assertStringContainsString( 'view=constitution', $result['redirect'] );
        self::assertStringContainsString( 'event_id=4', $result['redirect'] );
    }

    public function test_post_redirects_to_cup_draw_with_competition(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->league_service->method( 'get_league' )->willReturn( $this->league() );
        $this->team_admin_service->method( 'add_teams' )->willReturn( $this->added( 1 ) );

        $result = $this->controller->teams_page(
            array( 'league_id' => '1' ),
            array( 'team' => array( '10' ), 'season' => '2024' ),
            Admin_Page_Context::cup()
        );

        self::assertStringContainsString( 'page=racketmanager-cups', $result['redirect'] );
        self::assertStringContainsString( 'view=draw', $result['redirect'] );
        self::assertStringContainsString( 'competition_id=55', $result['redirect'] );
        self::assertStringContainsString( 'league=1', $result['redirect'] );
        self::assertStringContainsString( 'tab=teams', $result['redirect'] );
    }

    public function test_post_reports_errors_as_error_message(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->league_service->method( 'get_league' )->willReturn( $this->league() );
        $this->team_admin_service->method( 'add_teams' )->willReturn( array( 'count' => 0, 'messages' => array(), 'errors' => array( 'Team not found' ) ) );

        $result = $this->controller->teams_page( array( 'league_id' => '1' ), array( 'team' => array( 'x' ), 'season' => '2024' ), Admin_Page_Context::league() );

        self::assertSame( 'Team not found', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }

    public function test_throws_when_league_id_missing(): void {
        $this->expectException( Invalid_Status_Exception::class );
        $this->expectExceptionMessage( 'League not found' );

        $this->controller->teams_page( array(), array(), Admin_Page_Context::league() );
    }

    public function test_throws_when_league_not_found(): void {
        $this->league_service->method( 'get_league' )->willReturn( null );

        $this->expectException( Invalid_Status_Exception::class );

        $this->controller->teams_page( array( 'league_id' => '1' ), array(), Admin_Page_Context::league() );
    }
}
