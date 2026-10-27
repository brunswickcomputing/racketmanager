<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Standings;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Standings_Rubbers_Test extends TestCase {
    use Manages_Wordpress_Globals;
    use \Racketmanager\Tests\Helpers\Creates_League_Query_Service;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_standings_loads_rubbers_for_plain_fixtures_and_calculates_totals(): void {
        $fixture = Fixture_Factory::from_array( [
            'id' => 70, 'home_team' => '10', 'away_team' => '20',
            'winner_id' => 10, 'loser_id' => 20, 'home_points' => '2', 'away_points' => '0',
        ] );
        $rubber = ( new \ReflectionClass( Rubber::class ) )->newInstanceWithoutConstructor();
        $rubber->id = 71;
        $rubber->winner_id = 10;
        $rubber->loser_id = 20;
        $rubber->home_points = 2;
        $rubber->away_points = 0;
        $rubber->sets = [ 1 => [ 'player1' => 6, 'player2' => 2 ], 2 => [ 'player1' => 6, 'player2' => 3 ] ];

        $league = new League( (object) [ 'title' => 'Division 1' ] );
        $league->id = 12;
        $league->seasons = [ '2026' => [ 'name' => '2026' ] ];
        $league->current_season = [ 'name' => '2026' ];
        $league->set_match_query_arg( 'season', '2026' );
        $league->point_rule = 'three';
        $league->standings = [];
        $league->num_rubbers = 1;
        $league->num_sets = 2;
        $league->scoring = '';
        $league_repository = $this->createStub( League_Repository_Interface::class );
        $league_repository->method( 'query_matches' )->willReturn( [ (object) [ 'id' => 70 ] ] );
        $fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
        $fixture_repository->method( 'find_by_id' )->willReturn( $fixture );

        $team         = new League_Team();
        $team->id     = 10;
        $team->season = '2026';
        $team->custom = [];
        $league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
        $league_team_repository->expects( $this->once() )->method( 'find_by_id' )->with( 10 )->willReturn( $team );

        $repository = $this->createMock( Rubber_Repository_Interface::class );
        $repository->expects( $this->once() )->method( 'find_by_fixture_id' )->with( 70 )->willReturn( [ $rubber ] );

        $standings = ( new Standings_Service(
            league_team_repository: $league_team_repository,
            rubber_repository: $repository,
            league_query_service: $this->league_query_service( $league_repository, null, $fixture_repository )
        ) )->get_standings( $league, [ 10 ], 0 );

        $this->assertSame( [ $rubber ], $fixture->get_rubbers() );
        $this->assertSame( 1, $standings[0]->rubbers_won );
        $this->assertSame( 2, $standings[0]->sets_won );
        $this->assertSame( 12, $standings[0]->games_won );
    }
}