<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Standings;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Settings;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

final class Standings_Service_Test extends TestCase {
    use Manages_Wordpress_Globals;
    use \Racketmanager\Tests\Helpers\Creates_League_Query_Service;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        unset( $GLOBALS['racketmanager'] );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function event( string $ranking ): Event {
        $competition = $this->createStub( Competition::class );
        $competition->settings = new Competition_Settings( [ 'team_ranking' => $ranking ] );
        $competition->standings = [];
        $competition->is_player_entry = false;
        return new Event( (object) [ 'name' => 'League', 'seasons' => [] ], $competition );
    }

    public static function display_modes(): array {
        return [ 'all' => [ 'all', 'team_id' ], 'home' => [ 'home', 'home_team' ], 'away' => [ 'away', 'away_team' ] ];
    }

    #[DataProvider( 'display_modes' )]
    public function test_read_standings_filters_and_ranks_without_entity_standings_methods( string $mode, string $team_filter ): void {
        $league = new League( (object) [ 'title' => 'Division 1' ] );
        $league->id = 12;
        $league->seasons = [ '2026' => [ 'name' => '2026' ] ];
        $league->current_season = [ 'name' => '2026' ];
        $league->set_match_query_arg( 'season', '2026' );
        $league->standings = [ 'status' => 1 ];
        $league->point_rule = 'three';
        $fixture = Fixture_Factory::from_array( [
            'id' => 70, 'home_team' => '10', 'away_team' => '20',
            'winner_id' => 10, 'loser_id' => 20, 'home_points' => '3', 'away_points' => '1',
        ] );
        $league_repository = $this->createMock( League_Repository_Interface::class );
        $league_repository->expects( $this->once() )->method( 'query_matches' )->with(
            12,
            $this->callback( static fn( array $args ): bool =>
                10 === ( $args[ $team_filter ] ?? null )
                && 3 === $args['match_day']
                && true === $args['confirmed']
                && [ 'status_code' => 'Cancelled', 'compare' => 'not' ] === $args['status']
            ),
            false
        )->willReturn( [ (object) [ 'id' => 70 ] ] );
        $fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repository->expects( $this->once() )->method( 'find_by_id' )->with( 70 )->willReturn( $fixture );
        $team = new League_Team();
        $team->id = 10;
        $team->season = '2026';
        $team->custom = [];
        $ranking = $this->createMock( League_Service::class );
        $ranking->expects( $this->once() )->method( 'rank_teams_by_points' )->with( [ $team ] )->willReturn( [ $team ] );
        $ranking->expects( $this->once() )->method( 'get_ranking' )->with( $league, [ $team ] )->willReturn( [ $team ] );

        $rows = ( new Standings_Service( league_service: $ranking, league_query_service: $this->league_query_service( $league_repository, null, $fixture_repository ) ) )->get_standings( $league, [ $team ], 3, $mode );

        $this->assertSame( [ $team ], $rows );
        $this->assertSame( 1, $team->done_matches );
        $this->assertEquals( 3, $team->points_plus );
        $this->assertEquals( 1, $team->points_minus );
        $this->assertSame( 0, $league->standings['status'] );
        $this->assertSame( '0', $team->sets_shared );
        $this->assertSame( '0', $team->no_player );
        $this->assertSame( '0', $team->no_team );
    }

    public function test_manual_standings_persist_through_injected_repository(): void {
        $league = new League( (object) [ 'title' => 'Division 1' ], $this->event( 'manual' ) );
        $league->id = 12;
        $league->current_season = [ 'name' => '2026' ];
        $repository = $this->createMock( League_Repository_Interface::class );
        $repository->expects( $this->once() )->method( 'update_standings_manually' )->with(
            12, '2026', 41,
            [ 'points_plus' => 12, 'points_minus' => 3, 'add_points' => 0 ],
            [ 'num_done_matches' => 5, 'num_won_matches' => 4, 'num_draw_matches' => 0, 'num_lost_matches' => 1 ]
        );
        $ranking = $this->createMock( League_Service::class );
        $ranking->expects( $this->never() )->method( 'update_ranking' );

        ( new Standings_Service( league_repository: $repository, league_service: $ranking, league_query_service: $this->league_query_service( $repository ) ) )->save_standings_manually(
            $league, [ 41 => 10 ], [ 'points_plus' => [ 41 => 12 ], 'points_minus' => [ 41 => 3 ] ],
            [ 'num_done_matches' => [ 41 => 5 ], 'num_won_matches' => [ 41 => 4 ], 'num_lost_matches' => [ 41 => 1 ] ], []
        );
    }

    public function test_set_teams_rank_uses_current_season_and_ranks_each_group(): void {
        $league = $this->createStub( League::class );
        $league->event = $this->event( 'auto' );
        $league->current_season = [ 'name' => '2026' ];
        $league->groups = 'A;B';
        $league->id = 12;
        $team = new League_Team();
        $groups = [];
        $league_query_service = $this->createMock( League_Query_Service::class );
        $league_query_service->expects( $this->exactly( 2 ) )->method( 'get_league_teams' )->willReturnCallback(
            function ( object $query_league, array $args ) use ( &$groups, $team, $league ): array {
                $this->assertSame( $league, $query_league );
                $this->assertSame( '2026', $args['season'] );
                $groups[] = $args['group'];
                return [ $team ];
            }
        );
        $ranking = $this->createMock( League_Service::class );
        $ranking->expects( $this->exactly( 2 ) )->method( 'rank_teams_by_points' )->with( [ $team ] )->willReturn( [ $team ] );
        $ranking->expects( $this->exactly( 2 ) )->method( 'get_ranking' )->with( $league, [ $team ] )->willReturn( [ $team ] );
        $ranking->expects( $this->exactly( 2 ) )->method( 'update_ranking' )->with( [ $team ] );

        ( new Standings_Service( league_service: $ranking, league_query_service: $league_query_service ) )->set_teams_rank( $league );

        $this->assertSame( [ 'A', 'B' ], $groups );
    }

    public function test_update_standings_saves_calculated_stats_and_refreshes_ranking(): void {
        $league = $this->createStub( League::class );
        $league->id = 12;
        $league->event = $this->event( 'auto' );
        $league->point_rule = 'three';
        $league->method( 'get_season' )->willReturn( '2026' );
        $league->method( 'get_point_rule' )->willReturn( [] );
        $count_queries = [];
        $team = $this->createStub( League_Team::class );
        $team->id = 10;
        $team->league_id = 12;
        $team->season = '2026';
        $team->custom = [];
        $league_query_service = $this->createMock( League_Query_Service::class );
        $league_query_service->expects( $this->exactly( 2 ) )->method( 'get_league_teams' )->willReturnCallback(
            function ( object $query_league, array $args ) use ( $team, $league ): array {
                $this->assertSame( $league, $query_league );
                $this->assertSame( '2026', $args['season'] );
                return [ $team ];
            }
        );
        $league_query_service->method( 'get_matches' )->willReturnCallback( static function ( object $query_league, array $args ) use ( &$count_queries ): array|int {
            if ( $args['count'] ?? false ) {
                $count_queries[] = $args;
                return 0;
            }
            return [];
        } );
        $repository = $this->createMock( League_Repository_Interface::class );
        $repository->expects( $this->once() )->method( 'update_team_standings' )->with(
            12, '2026', 10, $this->callback( static fn( array $data ): bool =>
                0 === $data['points_plus'] && 0 === $data['points_minus'] && 0 === $data['custom']['sets_won']
            )
        );
        $ranking = $this->createMock( League_Service::class );
        $ranking->expects( $this->once() )->method( 'rank_teams_by_points' )->with( [ $team ] )->willReturn( [ $team ] );
        $ranking->expects( $this->once() )->method( 'get_ranking' )->with( $league, [ $team ] )->willReturn( [ $team ] );
        $ranking->expects( $this->once() )->method( 'update_ranking' )->with( [ $team ] );

        ( new Standings_Service( league_repository: $repository, league_service: $ranking, league_query_service: $league_query_service ) )->update_standings( $league, '2026' );
        $this->assertCount( 4, $count_queries );
        $this->assertSame( 10, $count_queries[0]['team_id'] );
        $this->assertSame( 10, $count_queries[1]['winner_id'] );
        $this->assertSame( -1, $count_queries[2]['winner_id'] );
        $this->assertSame( 10, $count_queries[3]['loser_id'] );
    }

    public function test_save_standings_does_not_overwrite_manual_points(): void {
        $league = new League( (object) [ 'title' => 'Division 1' ] );
        $league->point_rule = 'manual';
        $repository = $this->createMock( League_Repository_Interface::class );
        $repository->expects( $this->never() )->method( 'update_team_standings' );

        ( new Standings_Service( league_repository: $repository ) )->save_standings( $league, new League_Team() );
    }

    public function test_tie_requires_all_ranking_criteria_to_match(): void {
        $service = new Standings_Service();
        $team = (object) [ 'points' => [ 'plus' => 10 ], 'sets_won' => 5, 'sets_allowed' => 2, 'games_won' => 30, 'games_allowed' => 20 ];
        $this->assertTrue( $service->is_tie( $team, clone $team ) );
        foreach ( [ 'points' => [ 'plus' => 9 ], 'sets_won' => 4, 'sets_allowed' => 3, 'games_won' => 29, 'games_allowed' => 21 ] as $field => $value ) {
            $other = clone $team;
            $other->$field = $value;
            $this->assertFalse( $service->is_tie( $team, $other ), $field );
        }
    }
}