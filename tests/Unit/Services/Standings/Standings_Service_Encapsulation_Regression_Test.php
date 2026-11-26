<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Standings;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

final class Standings_Service_Encapsulation_Regression_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        unset( $GLOBALS['racketmanager'] );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    /**
     * Verifies that get_standings modifies standings status via domain setter on a real League instance
     * without triggering PHP 8.4 indirect modification errors on private(set) properties.
     */
    public function test_get_standings_updates_standings_status_via_setter_on_real_league(): void {
        $league = new League();
        $league->set_id( 12 );
        $league->set_seasons( array( '2026' => array( 'name' => '2026' ) ) );
        $league->set_current_season( array( 'name' => '2026' ) );
        $league->set_match_query_arg( 'season', '2026' );
        $league->set_standings( array( 'status' => 1, 'existing_key' => 'preserved' ) );
        $league->set_point_rule( 'three' );
        $league->set_num_rubbers( 0 );

        $team            = new League_Team();
        $team->id        = 10;
        $team->league_id = 12;
        $team->season    = '2026';
        $team->custom    = array();

        $league_query_service = $this->createStub( League_Query_Service::class );
        $league_query_service->method( 'get_matches' )->willReturn( array() );

        $ranking = $this->createMock( League_Service::class );
        $ranking->expects( $this->once() )->method( 'rank_teams_by_points' )->with( array( $team ) )->willReturn( array( $team ) );
        $ranking->expects( $this->once() )->method( 'get_ranking' )->with( $league, array( $team ) )->willReturn( array( $team ) );

        $service = new Standings_Service(
            league_repository: $this->createStub( League_Repository_Interface::class ),
            league_team_repository: $this->createStub( League_Team_Repository_Interface::class ),
            league_service: $ranking,
            fixture_repository: $this->createStub( Fixture_Repository_Interface::class ),
            rubber_repository: $this->createStub( Rubber_Repository_Interface::class ),
            league_query_service: $league_query_service
        );

        $result = $service->get_standings( $league, array( $team ), 0 );

        $this->assertSame( array( $team ), $result );
        $this->assertIsArray( $league->standings );
        $this->assertSame( 0, $league->standings['status'] );
        $this->assertSame( 'preserved', $league->standings['existing_key'] );
    }
}
