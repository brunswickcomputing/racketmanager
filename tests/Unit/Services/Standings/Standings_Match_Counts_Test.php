<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Standings;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Standings\Standings_Service;

/**
 * Characterisation of the per-team done/won/draw/lost counts that used to live on League_Team.
 */
#[AllowMockObjectsWithoutExpectations]
class Standings_Match_Counts_Test extends TestCase {
    use \Racketmanager\Tests\Helpers\Creates_League_Query_Service;

    private function service( League_Repository_Interface $league_repository ): Standings_Service {
        return new Standings_Service(
            league_repository: $league_repository,
            fixture_repository: $this->createStub( Fixture_Repository_Interface::class ),
            league_query_service: $this->league_query_service( $league_repository )
        );
    }

    private function league(): League {
        $league = League::from_object( (object) [ 'id' => 100, 'sport' => 'tennis', 'seasons' => [ '2026' => [ 'name' => '2026' ] ] ] );
        $league->current_season = [ 'name' => '2026' ];

        return $league;
    }

    public function test_refresh_match_counts_queries_each_count_with_league_defaults_and_team_args(): void {
        $captured = [];
        $repo     = $this->createMock( League_Repository_Interface::class );
        $repo->expects( $this->exactly( 4 ) )
            ->method( 'query_matches' )
            ->willReturnCallback( function ( int $league_id, array $args, bool $count ) use ( &$captured ) {
                $captured[] = [ $league_id, $args, $count ];
                return count( $captured );
            } );

        $league_team = League_Team::from_object( (object) [ 'id' => 50, 'league_id' => 100 ] );
        $this->service( $repo )->refresh_match_counts( $this->league(), $league_team );

        $this->assertSame( 1, $league_team->done_matches );
        $this->assertSame( 2, $league_team->won_matches );
        $this->assertSame( 3, $league_team->draw_matches );
        $this->assertSame( 4, $league_team->lost_matches );

        foreach ( $captured as [ $league_id, $args, $count ] ) {
            $this->assertSame( 100, $league_id );
            $this->assertTrue( $count );
            $this->assertTrue( $args['count'] );
            $this->assertSame( '2026', $args['current_season'] ?? null );
            $this->assertArrayHasKey( 'final', $args );
            $this->assertArrayHasKey( 'season', $args );
        }
        $this->assertSame( 50, $captured[0][1]['team_id'] );
        $this->assertSame( 'not_empty', $captured[0][1]['home_points'] );
        $this->assertSame( 50, $captured[1][1]['winner_id'] );
        $this->assertSame( -1, $captured[2][1]['winner_id'] );
        $this->assertSame( -1, $captured[2][1]['loser_id'] );
        $this->assertSame( 50, $captured[3][1]['loser_id'] );
    }
}
