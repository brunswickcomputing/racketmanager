<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Player\Player_Factory;
use Racketmanager\Domain\Team\Team_Factory;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class League_Team_Repository_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private League_Team_Repository $repository;
    private $wpdb;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->wpdb = $this->createMock( wpdb::class );
        $this->wpdb->prefix = 'wp_';
        $GLOBALS['wpdb'] = $this->wpdb;
        $this->repository = new League_Team_Repository();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_hydrate_enriches_league_team(): void {
        $club_repo = $this->createMock( Club_Repository_Interface::class );
        $team_repo = $this->createMock( Team_Repository_Interface::class );

        $club = new Club( (object) [ 'id' => 10, 'name' => 'Tennis Club' ] );
        $club_repo->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 10 )
            ->willReturn( $club );

        $player = Player_Factory::from_object( (object) [ 'id' => 77, 'display_name' => 'John Smith' ] );
        $team   = Team_Factory::from_object( (object) [ 'id' => 25 ] );
        $team->players = [ $player ];
        $team_repo->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 25 )
            ->willReturn( $team );

        $repository = new League_Team_Repository( $this->wpdb, $club_repo, $team_repo );

        $row = (object) [
            'id'        => 25,
            'table_id'  => 500,
            'club_id'   => 10,
            'team_type' => 'P',
            'roster'    => serialize( [ 77 ] ),
        ];

        $league_team = $repository->hydrate( $row );

        $this->assertSame( $club, $league_team->club );
        $this->assertCount( 1, $league_team->players );
        $this->assertSame( $player, $league_team->players[0] );
    }

    public function test_find_by_id_returns_null_for_empty_id(): void {
        $this->assertNull( $this->repository->find_by_id( null ) );
        $this->assertNull( $this->repository->find_by_id( 0 ) );
    }

    public function test_find_by_id_returns_cached_instance(): void {
        $data = (object) [
            'id'       => 1532,
            'table_id' => 7929,
            'team_id'  => 1532,
            'title'    => 'Test Team',
            'season'   => 2026,
        ];
        $league_team = new League_Team( $data );

        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']['league-teams']['7929'] = $league_team;

        $result = $this->repository->find_by_id( 7929 );
        $this->assertEquals( $league_team, $result );
        $this->assertSame( 7929, $result->table_id );
    }

    public function test_find_by_id_queries_db_and_hydrates_league_team(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;

        $row = (object) [
            'id'             => 1532,
            'table_id'       => 7929,
            'team_id'        => 1532,
            'title'          => 'First Team',
            'club_id'        => 50,
            'stadium'        => 'Centre Court',
            'home'           => 'Home Venue',
            'group'          => 'A',
            'roster'         => '',
            'profile'        => '1',
            'captain'        => 2245,
            'match_day'      => 'Saturday',
            'match_time'     => '14:00',
            'points_plus'    => 12.0,
            'points_minus'   => 4.0,
            'points_2_plus'  => 8,
            'points_2_minus' => 2,
            'add_points'     => 1.5,
            'done_matches'   => 6,
            'won_matches'    => 5,
            'draw_matches'   => 0,
            'lost_matches'   => 1,
            'diff'           => 8.0,
            'league_id'      => 2347,
            'season'         => 2026,
            'rank'           => 1,
            'status'         => 'W1',
            'custom'         => '',
            'team_type'      => 'standard',
            'rating'         => 1500.0,
        ];

        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->willReturn( 'PREPARED_QUERY' );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->with( 'PREPARED_QUERY' )
            ->willReturn( $row );

        $result = $this->repository->find_by_id( 7929 );

        $this->assertInstanceOf( League_Team::class, $result );
        $this->assertSame( 1532, $result->id );
        $this->assertSame( 7929, $result->table_id );
        $this->assertSame( 1532, $result->get_team_id() );
        $this->assertSame( 2245, $result->captain );
        $this->assertSame( 1, $result->get_profile() );
        $this->assertSame( 'Saturday', $result->match_day );
        $this->assertSame( '14:00', $result->match_time );
        $this->assertSame( 1.5, $result->add_points );
        $this->assertSame( 2347, $result->league_id );
    }

    public function test_find_by_id_fallback_query_when_join_returns_null(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;

        $row = (object) [
            'id'        => 7929,
            'team_id'   => 1532,
            'league_id' => 2347,
            'season'    => 2026,
            'rank'      => 2,
            'status'    => '',
            'profile'   => '0',
            'captain'   => 100,
        ];

        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'prepare' )
            ->willReturnOnConsecutiveCalls( 'QUERY_JOIN', 'QUERY_FALLBACK' );

        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'get_row' )
            ->willReturnOnConsecutiveCalls( null, $row );

        $result = $this->repository->find_by_id( 7929 );

        $this->assertInstanceOf( League_Team::class, $result );
        $this->assertSame( 7929, $result->table_id );
        $this->assertSame( 1532, $result->get_team_id() );
    }

    public function test_update_constitution_updates_table_and_clears_cache(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']['league-teams']['101'] = 'dummy';

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->with(
                'wp_racketmanager_league_teams',
                [
                    'league_id' => 500,
                    'status'    => 'P1',
                ],
                [ 'id' => 101 ],
                [ '%d', '%s' ],
                [ '%d' ]
            )
            ->willReturn( 1 );

        $this->assertTrue( $this->repository->update_constitution( 101, 500, 'P1' ) );
        $this->assertArrayNotHasKey( '101', $GLOBALS['wp_stubs_cache']['league-teams'] ?? [] );
    }

    public function test_set_constitution_rank_updates_table_and_clears_cache(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']['league-teams']['102'] = 'dummy';

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->with(
                'wp_racketmanager_league_teams',
                [
                    'league_id' => 600,
                    'rank'      => 3,
                    'status'    => 'W2',
                    'profile'   => 1,
                ],
                [ 'id' => 102 ],
                [ '%d', '%d', '%s', '%d' ],
                [ '%d' ]
            )
            ->willReturn( 1 );

        $this->assertTrue( $this->repository->set_constitution_rank( 102, 600, 3, 'W2', '1' ) );
        $this->assertArrayNotHasKey( '102', $GLOBALS['wp_stubs_cache']['league-teams'] ?? [] );
    }

    public function test_amend_points_updates_table_with_float_and_clears_cache(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']['league-teams']['103'] = 'dummy';

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->with(
                'wp_racketmanager_league_teams',
                [ 'add_points' => 2.5 ],
                [ 'id' => 103 ],
                [ '%f' ],
                [ '%d' ]
            )
            ->willReturn( 1 );

        $this->assertTrue( $this->repository->amend_points( 103, 2.5 ) );
        $this->assertArrayNotHasKey( '103', $GLOBALS['wp_stubs_cache']['league-teams'] ?? [] );
    }

    public function test_update_details_updates_table_and_clears_cache(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']['league-teams']['104'] = 'dummy';

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->with(
                'wp_racketmanager_league_teams',
                [
                    'captain'    => 200,
                    'match_day'  => 'Sunday',
                    'match_time' => '10:00',
                ],
                [ 'id' => 104 ],
                [ '%d', '%s', '%s' ],
                [ '%d' ]
            )
            ->willReturn( 1 );

        $this->assertTrue( $this->repository->update_details( 104, 200, 'Sunday', '10:00' ) );
        $this->assertArrayNotHasKey( '104', $GLOBALS['wp_stubs_cache']['league-teams'] ?? [] );
    }

    public function test_update_stats_updates_all_statistics_and_clears_cache(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']['league-teams']['105'] = 'dummy';

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->with(
                'wp_racketmanager_league_teams',
                [
                    'done_matches'   => 10,
                    'won_matches'    => 8,
                    'draw_matches'   => 1,
                    'lost_matches'   => 1,
                    'points_plus'    => 24.0,
                    'points_minus'   => 6.0,
                    'points_2_plus'  => 16,
                    'points_2_minus' => 4,
                    'add_points'     => 3.0,
                    'custom'         => serialize( [ 'bonus' => true ] ),
                ],
                [ 'id' => 105 ],
                [ '%d', '%d', '%d', '%d', '%f', '%f', '%d', '%d', '%f', '%s' ],
                [ '%d' ]
            )
            ->willReturn( 1 );

        $this->assertTrue( $this->repository->update_stats(
            105,
            10,
            8,
            1,
            1,
            24.0,
            6.0,
            16,
            4,
            3.0,
            [ 'bonus' => true ]
        ) );
        $this->assertArrayNotHasKey( '105', $GLOBALS['wp_stubs_cache']['league-teams'] ?? [] );
    }

    public function test_construct_uses_injected_wpdb_instance(): void {
        $custom_wpdb = $this->createMock( wpdb::class );
        $custom_wpdb->prefix = 'custom_';
        $repo = new League_Team_Repository( $custom_wpdb );

        $custom_wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with( $this->stringContains( 'custom_racketmanager_teams' ) )
            ->willReturn( 'PREPARED' );
        $custom_wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->with( 'PREPARED' )
            ->willReturn( (object) [ 'id' => 999, 'table_id' => 999 ] );

        $result = $repo->find_by_id( 999 );
        $this->assertNotNull( $result );
    }

    private function make_repo( ?int $club_calls = null, ?int $team_calls = null, ?object $club = null, ?object $team = null, ?int $team_arg = null ): League_Team_Repository {
        $club_repo = $this->createMock( Club_Repository_Interface::class );
        $team_repo = $this->createMock( Team_Repository_Interface::class );
        $club_repo->expects( $this->exactly( $club_calls ?? 0 ) )->method( 'find_by_id' )->willReturn( $club );
        $m = $team_repo->expects( $this->exactly( $team_calls ?? 0 ) )->method( 'find_by_id' );
        if ( null !== $team_arg ) {
            $m->with( $team_arg );
        }
        $m->willReturn( $team );
        return new League_Team_Repository( $this->wpdb, $club_repo, $team_repo );
    }

    public function test_hydrate_without_injected_repositories_does_not_enrich(): void {
        $lt = ( new League_Team_Repository( $this->wpdb ) )->hydrate( (object) [ 'id' => 1, 'table_id' => 2, 'club_id' => 3, 'team_type' => 'P', 'roster' => serialize( [ 1 ] ) ] );
        $this->assertNull( $lt->club );
        $this->assertSame( [], $lt->players );
    }

    public function test_hydrate_non_player_team_skips_team_repository(): void {
        $club = new Club( (object) [ 'id' => 3, 'name' => 'C' ] );
        $lt   = $this->make_repo( 1, 0, $club )->hydrate( (object) [ 'id' => 1, 'table_id' => 2, 'club_id' => 3, 'team_type' => 'D', 'roster' => serialize( [ 1 ] ) ] );
        $this->assertSame( $club, $lt->club );
        $this->assertSame( [], $lt->players );
    }

    public function test_hydrate_null_roster_skips_team_lookup(): void {
        $lt = $this->make_repo( 0, 0 )->hydrate( (object) [ 'id' => 1, 'table_id' => 2, 'team_type' => 'P', 'roster' => null ] );
        $this->assertSame( [], $lt->players );
    }

    public function test_hydrate_empty_roster_still_loads_players(): void {
        $player = Player_Factory::from_object( (object) [ 'id' => 5, 'display_name' => 'A B' ] );
        $team   = Team_Factory::from_object( (object) [ 'id' => 9 ] );
        $team->players = [ $player ];
        $lt = $this->make_repo( 0, 1, null, $team, 9 )->hydrate( (object) [ 'id' => 9, 'table_id' => 2, 'team_type' => 'P', 'roster' => serialize( [] ) ] );
        $this->assertSame( [ $player ], $lt->players );
    }

    public function test_hydrate_lowercase_p_is_not_a_player_team(): void {
        $lt = $this->make_repo( 0, 0 )->hydrate( (object) [ 'id' => 9, 'table_id' => 2, 'team_type' => 'p', 'roster' => serialize( [ 1 ] ) ] );
        $this->assertSame( [], $lt->players );
    }

    public function test_hydrate_empty_club_id_skips_club_lookup(): void {
        $lt = $this->make_repo( 0, 0 )->hydrate( (object) [ 'id' => 1, 'table_id' => 2, 'club_id' => 0, 'team_type' => 'D' ] );
        $this->assertNull( $lt->club );
    }

    public function test_hydrate_missing_team_yields_empty_players(): void {
        $lt = $this->make_repo( 0, 1, null, null, 4 )->hydrate( (object) [ 'id' => 4, 'table_id' => 2, 'team_type' => 'P', 'roster' => serialize( [ 1 ] ) ] );
        $this->assertSame( [], $lt->players );
    }

    public function test_hydrate_prefers_team_id_over_id(): void {
        $team = Team_Factory::from_object( (object) [ 'id' => 77 ] );
        $this->make_repo( 0, 1, null, $team, 77 )->hydrate( (object) [ 'id' => 4, 'team_id' => 77, 'table_id' => 2, 'team_type' => 'P', 'roster' => serialize( [ 1 ] ) ] );
    }
}
