<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Tournament_Teams_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Teams_List_Page_View_Model;
use Racketmanager\Domain\League;
use Racketmanager\Domain\Tournament;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;

require_once __DIR__ . '/../wp-stubs.php';

use Racketmanager\Services\Championship;
use Racketmanager\Domain\League_Team;

use Racketmanager\Domain\Event;

final class Tournament_Teams_Admin_Controller_Test extends TestCase {

    public function test_teams_page_returns_view_model_on_get(): void {
        $tournament_service = $this->createMock( Tournament_Service::class );
        $league_service = $this->createMock( League_Service::class );
        $team_service = $this->createMock( Team_Service::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $league_id = 1;
        
        $league = $this->getMockBuilder( League::class )
            ->disableOriginalConstructor()
            ->getMock();
        $league->type = 'M';
        $league->championship = null; 
        
        $event = $this->getMockBuilder( Event::class )
            ->disableOriginalConstructor()
            ->getMock();
        $event->competition = (object)['is_player_entry' => false];
        $league->event = $event;

        $league_service->method( 'get_league' )->with( $league_id )->willReturn( $league );
        $team_service->method( 'get_club_teams' )->willReturn( [] );

        $controller = new Tournament_Teams_Admin_Controller(
            $tournament_service,
            $league_service,
            $team_service,
            $guard
        );

        $query = [ 'league_id' => (string)$league_id ];
        $result = $controller->teams_page( $query, [] );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Teams_List_Page_View_Model::class, $result['view_model'] );
    }

    public function test_teams_page_handles_post_request(): void {
        $tournament_service = $this->createMock( Tournament_Service::class );
        $league_service = $this->createMock( League_Service::class );
        $team_service = $this->createMock( Team_Service::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $league_id = 1;
        $league = $this->getMockBuilder( League::class )
            ->disableOriginalConstructor()
            ->getMock();
        $league_service->method( 'get_league' )->with( $league_id )->willReturn( $league );

        $controller = new Tournament_Teams_Admin_Controller(
            $tournament_service,
            $league_service,
            $team_service,
            $guard
        );

        $query = [ 'league_id' => (string)$league_id ];
        $post = [
            'team' => [ '10', '11' ],
            'season' => '2024'
        ];

        // Simulate POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';

        // We use a real League_Team instance if possible, or we need to find a way to mock the service method without returning a mock of a final class.
        // Actually, we can return a real League_Team instance by passing a stdClass to its constructor.
        $lt_data = new \stdClass();
        $lt_data->id = 999;
        $league_team = new League_Team($lt_data);

        $league_service->expects( self::exactly( 2 ) )
            ->method( 'add_team_to_league' )
            ->willReturn($league_team);

        $result = $controller->teams_page( $query, $post );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( '2 teams added', $result['message'] );

        // Clean up
        unset($_SERVER['REQUEST_METHOD']);
    }

    public function test_teams_page_throws_exception_if_league_missing(): void {
        $tournament_service = $this->createMock( Tournament_Service::class );
        $league_service = $this->createMock( League_Service::class );
        $team_service = $this->createMock( Team_Service::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $controller = new Tournament_Teams_Admin_Controller(
            $tournament_service,
            $league_service,
            $team_service,
            $guard
        );

        $this->expectException( \Racketmanager\Exceptions\Invalid_Status_Exception::class );
        $this->expectExceptionMessage( 'League not found' );

        $controller->teams_page( [], [] );
    }
}
