<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\League_Standings_Admin_Controller;
use Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter;
use Racketmanager\Admin\View_Models\League_Standings_Page_View_Model;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Services\Admin\League\League_Contact_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Match_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Team_Action_Dispatcher;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Resolves_League_Admin_Controllers;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Standings_Admin_Controller_Test extends TestCase {
    use Resolves_League_Admin_Controllers;
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_current_user_can'] = true;
        $GLOBALS['wp_stubs_leagues']          = array();
        $_SERVER['REQUEST_METHOD']            = 'GET';
        $_GET                                 = array();
        $_POST                                = array();
    }

    protected function tearDown(): void {
        unset( $_SERVER['REQUEST_METHOD'] );
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_league( int $id = 42, bool $is_championship = false, array $seasons = array( '2026' => array( 'name' => '2026' ) ) ): League {
        $competition = Competition_Test_Factory::create(
            id: 1,
            name: 'Premier League',
            type: $is_championship ? Competition_Type::CUP : Competition_Type::LEAGUE
        );
        if ( $is_championship ) {
            $competition->settings['mode'] = 'championship';
        }

        $event = Event::from_object(
            (object) array(
                'id'             => 93,
                'name'           => 'Mixed Doubles',
                'seasons'        => $seasons,
                'current_season' => empty( $seasons ) ? null : array( 'name' => '2026' ),
            ),
            $competition
        );

        $league = League::from_object(
            (object) array(
                'id'             => $id,
                'title'          => 'Division 1',
                'current_season' => empty( $seasons ) ? null : array( 'name' => '2026' ),
            ),
            $event
        );

        if ( ! empty( $seasons ) ) {
            $league->set_season( '2026' );
        }
        $league->num_teams       = 2;
        $league->num_teams_total = 2;

        if ( $is_championship ) {
            $league->mode            = 'championship';
            $league->is_championship = true;
            $championship            = $this->createStub( Championship::class );
            $championship->method( 'get_finals' )->willReturn( array(
                array(
                    'key'         => 'semi',
                    'name'        => 'Semi-Final',
                    'num_matches' => 2,
                    'round'       => 2,
                ),
            ) );
            $championship->method( 'get_current_final_key' )->willReturn( 'semi' );
            $league->championship = $championship;
        }

        $GLOBALS['wp_stubs_leagues'][ $id ] = $league;

        return $league;
    }

    public function test_capability_failure(): void {
        $controller = $this->resolve_controller( 'league_standings_admin_controller' );
        $this->deny_capability( 'view_leagues' );

        $this->expectException( Invalid_Status_Exception::class );
        $controller->league_page( array(), array() );
    }

    public function test_missing_league_id_throws(): void {
        $controller = $this->resolve_controller( 'league_standings_admin_controller' );

        $this->expectException( League_Not_Found_Exception::class );
        $this->expectExceptionMessage( 'League id not found' );
        $controller->league_page( array(), array() );
    }

    public function test_unknown_league_throws(): void {
        $controller = $this->resolve_controller( 'league_standings_admin_controller' );

        $this->expectException( League_Not_Found_Exception::class );
        $this->expectExceptionMessage( 'League not found' );
        $controller->league_page( array( 'league_id' => '999' ), array() );
    }

    public function test_get_regular_league_returns_view_model_with_crosstable_and_matches(): void {
        $league = $this->create_league( 42, false );

        $team1 = League_Team::from_object( (object) array( 'id' => 11, 'title' => 'Westcliff 1', 'rank' => 1 ) );
        $team2 = League_Team::from_object( (object) array( 'id' => 22, 'title' => 'Leigh 2', 'rank' => 2 ) );

        $league_query_service = $this->createMock( League_Query_Service::class );
        $league_query_service->method( 'get_league_teams' )->willReturn( array( $team1, $team2 ) );
        $league_query_service->method( 'get_matches' )->willReturn( array() );
        $league_query_service->expects( self::exactly( 4 ) )
            ->method( 'get_crosstable_field' )
            ->with( $league, self::anything(), self::anything() )
            ->willReturnCallback( static fn( $l, int $team_id, int $opponent_id ): string => $team_id === $opponent_id ? '&nbsp;' : "{$team_id}v{$opponent_id}" );

        $controller = $this->resolve_controller( 'league_standings_admin_controller', array(
            'league_query_service' => $league_query_service,
        ) );

        $result = $controller->league_page( array( 'league_id' => '42', 'season' => '2026' ), array() );

        self::assertArrayHasKey( 'view_model', $result );
        $vm = $result['view_model'];
        self::assertInstanceOf( League_Standings_Page_View_Model::class, $vm );
        self::assertFalse( $vm->cup );
        self::assertSame( array(), $vm->finals );
        self::assertSame( 'standings', $vm->tab );
        self::assertCount( 2, $vm->crosstable_teams );
        self::assertSame( '&nbsp;', $vm->crosstable_cells[11][11] );
        self::assertSame( '11v22', $vm->crosstable_cells[11][22] );
        self::assertSame( '22v11', $vm->crosstable_cells[22][11] );
        self::assertSame( '&nbsp;', $vm->crosstable_cells[22][22] );
    }

    public function test_get_championship_league_uses_finals_presenter_and_empty_matches(): void {
        $league = $this->create_league( 42, true );

        $raw            = new Fixture();
        $raw->id        = 77;
        $raw->league_id = 42;
        $raw->final     = 'semi';
        $raw->season    = '2026';
        $raw->date      = '2026-05-09 11:00:00';
        $raw->location  = 'Club Court';

        $home = $this->createStub( Team::class );
        $home->method( 'get_name' )->willReturn( 'Cup Home' );
        $away = $this->createStub( Team::class );
        $away->method( 'get_name' )->willReturn( 'Cup Away' );

        $details = new Fixture_Details_DTO(
            $raw,
            $league,
            $league->event,
            $league->event->competition,
            new Team_Details_DTO( $home ),
            new Team_Details_DTO( $away ),
        );

        $league_query_service = $this->createMock( League_Query_Service::class );
        $league_query_service->method( 'get_matches' )->willReturn( array( $raw ) );

        $fixture_detail_service = $this->createMock( \Racketmanager\Services\Fixture\Fixture_Detail_Service::class );
        $fixture_detail_service->expects( self::once() )
            ->method( 'get_fixture_with_details' )
            ->with( $raw )
            ->willReturn( $details );

        $controller = $this->resolve_controller( 'league_standings_admin_controller', array(
            'league_query_service'   => $league_query_service,
            'fixture_detail_service' => $fixture_detail_service,
        ) );

        $result = $controller->league_page( array( 'league_id' => '42', 'season' => '2026' ), array() );

        self::assertArrayHasKey( 'view_model', $result );
        $vm = $result['view_model'];
        self::assertInstanceOf( League_Standings_Page_View_Model::class, $vm );
        self::assertTrue( $vm->cup );
        self::assertNotEmpty( $vm->finals );
        self::assertSame( array(), $vm->matches );
        self::assertSame( array(), $vm->crosstable_cells );
        self::assertSame( array(), $vm->crosstable_teams );
    }

    public function test_get_with_match_day_calls_set_match_day_and_sets_tab_to_matches(): void {
        $league = $this->create_league( 42, false );

        $league_query_service = $this->createMock( League_Query_Service::class );
        $league_query_service->expects( self::once() )
            ->method( 'set_match_day' )
            ->with( $league, -1 );
        $league_query_service->method( 'get_league_teams' )->willReturn( array() );
        $league_query_service->method( 'get_matches' )->willReturn( array() );

        $controller = $this->resolve_controller( 'league_standings_admin_controller', array(
            'league_query_service' => $league_query_service,
        ) );

        $result = $controller->league_page( array( 'league_id' => '42', 'match_day' => '-1' ), array() );

        self::assertArrayHasKey( 'view_model', $result );
        $vm = $result['view_model'];
        self::assertSame( 'matches', $vm->tab );
        self::assertSame( -1, $vm->match_day );
    }

    public function test_get_with_standingstable_calls_standings_service(): void {
        $league = $this->create_league( 42, false );

        $standings_service = $this->createMock( Standings_Service::class );
        $standings_service->expects( self::once() )
            ->method( 'get_standings' )
            ->with( $league, self::callback( 'is_array' ), 3, 'all' )
            ->willReturn( array() );

        $controller = $this->resolve_controller( 'league_standings_admin_controller', array(
            'standings_service' => $standings_service,
        ) );

        $result = $controller->league_page( array( 'league_id' => '42', 'standingstable' => 'match_day-3' ), array() );
        self::assertInstanceOf( League_Standings_Page_View_Model::class, $result['view_model'] );
    }

    public function test_get_with_standingstable_home_mode_calls_standings_service(): void {
        $league = $this->create_league( 42, false );

        $standings_service = $this->createMock( Standings_Service::class );
        $standings_service->expects( self::once() )
            ->method( 'get_standings' )
            ->with( $league, self::callback( 'is_array' ), 0, 'home' )
            ->willReturn( array() );

        $controller = $this->resolve_controller( 'league_standings_admin_controller', array(
            'standings_service' => $standings_service,
        ) );

        $result = $controller->league_page( array( 'league_id' => '42', 'standingstable' => 'home' ), array() );
        self::assertInstanceOf( League_Standings_Page_View_Model::class, $result['view_model'] );
    }

    public function test_get_without_event_seasons_returns_notice_message_and_view_model(): void {
        $league = $this->create_league( 42, false, seasons: array() );

        $controller = $this->resolve_controller( 'league_standings_admin_controller' );
        $result     = $controller->league_page( array( 'league_id' => '42' ), array() );

        self::assertArrayHasKey( 'message', $result );
        self::assertStringContainsString( 'You need to add at least one season', $result['message'] );
        self::assertTrue( $result['message_type'] );
        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( League_Standings_Page_View_Model::class, $result['view_model'] );
    }

    public function test_post_update_league_team_on_championship_sets_preliminary_tab_and_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $league                    = $this->create_league( 42, true );

        $controller = $this->resolve_controller( 'league_standings_admin_controller' );
        $result     = $controller->league_page(
            array( 'league_id' => '42', 'season' => '2026' ),
            array( 'updateLeague' => 'team' )
        );

        self::assertArrayNotHasKey( 'redirect', $result );
        self::assertArrayHasKey( 'view_model', $result );
        self::assertSame( 'preliminary', $result['view_model']->tab );
    }

    public function test_post_contact_team_delegates_to_dispatcher_and_redirects(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $league                    = $this->create_league( 42, false );

        $notification = $this->createMock( \Racketmanager\Services\Competition\Competition_Notification_Service::class );
        $notification->expects( self::once() )
            ->method( 'contact_league_teams' )
            ->with( $league, '2026', 'Hello captains' )
            ->willReturn( true );

        $controller = $this->resolve_controller( 'league_standings_admin_controller', array(
            'competition_notification_service' => $notification,
        ) );

        $result = $controller->league_page(
            array( 'page' => 'racketmanager-leagues', 'league_id' => '42', 'season' => '2026' ),
            array(
                'contactTeam'  => '1',
                'league_id'    => '42',
                'season'       => '2026',
                'emailMessage' => 'Hello captains',
            )
        );

        self::assertArrayNotHasKey( 'view_model', $result );
        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-leagues', $result['redirect'] );
        self::assertSame( 'Email sent to captains', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_team_actions_delegates_to_dispatcher_and_redirects_with_tab_and_flash(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $league                    = $this->create_league( 42, false );

        $league_service = $this->createMock( \Racketmanager\Services\League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );
        $league_service->expects( self::once() )
            ->method( 'rank_teams' )
            ->willReturn( true );

        $controller = $this->resolve_controller( 'league_standings_admin_controller', array(
            'league_service' => $league_service,
        ) );

        $result = $controller->league_page(
            array( 'page' => 'racketmanager-leagues', 'league_id' => '42' ),
            array(
                'saveRanking' => '1',
                'table_id'    => array( 11, 22 ),
            )
        );

        self::assertArrayNotHasKey( 'view_model', $result );
        self::assertArrayHasKey( 'redirect', $result );
        self::assertSame( 'Team ranking saved', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_add_teams_on_championship_redirects_with_preliminary_tab(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $league                    = $this->create_league( 42, true );

        $league_service = $this->createMock( \Racketmanager\Services\League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );
        $league_service->expects( self::once() )
            ->method( 'add_team_to_league' )
            ->with( 11, 42, 2026 );

        $controller = $this->resolve_controller( 'league_standings_admin_controller', array(
            'league_service' => $league_service,
        ) );

        $result = $controller->league_page(
            array( 'page' => 'racketmanager-leagues', 'league_id' => '42', 'season' => '2026' ),
            array(
                'action'   => 'addTeamsToLeague',
                'team'     => array( 11 ),
                'season'   => '2026',
                'event_id' => '93',
            )
        );

        self::assertArrayNotHasKey( 'view_model', $result );
        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'tab=preliminary', $result['redirect'] );
        self::assertStringContainsString( 'Team added', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_match_actions_delegates_to_dispatcher_and_redirects_with_tab_and_flash(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $league                    = $this->create_league( 42, false );

        $fixture_maintenance = $this->createMock( \Racketmanager\Services\Fixture\Fixture_Maintenance_Service::class );
        $fixture_maintenance->expects( self::once() )
            ->method( 'delete_fixture' )
            ->with( 101 );

        $controller = $this->resolve_controller( 'league_standings_admin_controller', array(
            'fixture_maintenance_service' => $fixture_maintenance,
        ) );

        $result = $controller->league_page(
            array( 'page' => 'racketmanager-leagues', 'league_id' => '42' ),
            array(
                'delMatches' => '1',
                'match'      => array( 101 ),
            )
        );

        self::assertArrayNotHasKey( 'view_model', $result );
        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'tab=matches', $result['redirect'] );
        self::assertStringContainsString( 'Match id 101 deleted', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }
}
