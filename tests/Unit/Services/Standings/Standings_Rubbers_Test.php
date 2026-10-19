<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Standings;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Standings_Rubbers_Test extends TestCase {
    use Manages_Wordpress_Globals;

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

        $league = $this->createStub( League::class );
        $league->standings = [];
        $league->num_rubbers = 1;
        $league->num_sets = 2;
        $league->scoring = '';
        $league->method( 'get_matches' )->willReturn( [ $fixture ] );
        $league->method( 'get_season' )->willReturn( '2026' );
        $league->method( 'get_point_rule' )->willReturn( [] );

        $team = new class extends \stdClass {
            public int $id = 10;
            public string $season = '2026';
            public array $custom = [];

            public function win_percent(): void {}
        };
        $GLOBALS['wp_stubs_league_teams'] = [ 10 => $team ];

        $repository = $this->createMock( Rubber_Repository_Interface::class );
        $repository->expects( $this->once() )->method( 'find_by_fixture_id' )->with( 70 )->willReturn( [ $rubber ] );

        $standings = ( new Standings_Service( rubber_repository: $repository ) )->get_standings( $league, [ 10 ], 0 );

        $this->assertSame( [ $rubber ], $fixture->get_rubbers() );
        $this->assertSame( 1, $standings[0]->rubbers_won );
        $this->assertSame( 2, $standings[0]->sets_won );
        $this->assertSame( 12, $standings[0]->games_won );
    }
}