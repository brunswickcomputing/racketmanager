<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use Racketmanager\Tests\Helpers\League_Test_Factory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Repository_Delete_Cache_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private $wpdb;
    private Fixture_Repository $repository;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->wpdb         = $this->createMock( wpdb::class );
        $this->wpdb->prefix = 'wp_';
        $GLOBALS['wpdb']    = $this->wpdb;

        $this->repository = new Fixture_Repository( $this->wpdb );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_delete_evicts_cache_and_deletes_rubbers(): void {
        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'delete' )
            ->willReturn( 1 );

        $GLOBALS['wp_stubs_cache_deleted'] = array();
        $result = $this->repository->delete( 55 );
        $this->assertTrue( $result );
        $deleted = $GLOBALS['wp_stubs_cache_deleted'];
        $this->assertContains( array( 55, 'fixtures' ), $deleted );
        $this->assertContains( array( '55', 'matches' ), $deleted );
        $this->assertContains( array( '55', 'match' ), $deleted );
    }

    public function test_delete_removes_rubbers_of_the_fixture_before_the_fixture_row(): void {
        $deleted = array();
        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'delete' )
            ->willReturnCallback(
                static function ( string $table, array $where ) use ( &$deleted ): int {
                    $deleted[] = array( $table, $where );
                    return 1;
                }
            );

        $this->assertTrue( $this->repository->delete( 55 ) );
        $this->assertSame(
            array(
                array( 'wp_racketmanager_rubbers', array( 'match_id' => 55 ) ),
                array( 'wp_racketmanager_matches', array( 'id' => 55 ) ),
            ),
            $deleted
        );
    }

    public function test_delete_keeps_fixture_row_when_rubber_cleanup_fails(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'delete' )
            ->with( 'wp_racketmanager_rubbers', array( 'match_id' => 56 ) )
            ->willReturn( false );

        $this->assertFalse( $this->repository->delete( 56 ) );
    }

    public function test_find_one_by_slug_criteria_returns_fixture_for_numeric_league_id(): void {
        $row = (object) [
            'id'        => 88,
            'season'    => '2026',
            'home_team' => '1',
            'away_team' => '2',
        ];

        $league = League_Test_Factory::from_object( (object) [ 'id' => 10 ] );
        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 10 )
            ->willReturn( $league );
        $league_repo->expects( $this->once() )
            ->method( 'query_matches' )
            ->willReturn( [ $row ] );

        $repo = new Fixture_Repository( $this->wpdb, null, null, $league_repo );

        $result = $repo->find_one_by_slug_criteria( [
            'league_slug' => 10,
            'season'      => '2026',
        ] );

        $this->assertInstanceOf( Fixture::class, $result );
        $this->assertEquals( 88, $result->get_id() );
    }

    public function test_find_one_by_slug_criteria_resolves_league_title_via_league_repository(): void {
        $row = (object) [
            'id'        => 736,
            'season'    => '2017',
            'home_team' => '6',
            'away_team' => '3',
        ];

        $league = League_Test_Factory::from_object( (object) [ 'id' => 24, 'title' => 'Summer Mixed 1' ] );
        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( $this->once() )
            ->method( 'find_by_name' )
            ->with( 'Summer Mixed 1' )
            ->willReturn( $league );
        $league_repo->expects( $this->once() )
            ->method( 'query_matches' )
            ->willReturn( [ $row ] );

        $repo = new Fixture_Repository( $this->wpdb, null, null, $league_repo );

        $result = $repo->find_one_by_slug_criteria( [
            'league_slug' => 'Summer Mixed 1',
            'season'      => '2017',
        ] );

        $this->assertInstanceOf( Fixture::class, $result );
        $this->assertEquals( 736, $result->get_id() );
    }

    public function test_find_one_by_slug_criteria_returns_null_on_ambiguous_matches(): void {
        $row1 = (object) [ 'id' => 101, 'season' => '2017', 'away_team' => '3' ];
        $row2 = (object) [ 'id' => 102, 'season' => '2017', 'away_team' => '3' ];

        $league = League_Test_Factory::from_object( (object) [ 'id' => 24 ] );
        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( $this->once() )->method( 'find_by_id' )->with( 24 )->willReturn( $league );
        $league_repo->expects( $this->once() )
            ->method( 'query_matches' )
            ->willReturn( [ $row1, $row2 ] );

        $repo = new Fixture_Repository( $this->wpdb, null, null, $league_repo );

        $result = $repo->find_one_by_slug_criteria( [
            'league_slug' => 24,
            'season'      => '2017',
        ] );

        $this->assertNull( $result );
    }

    public function test_find_one_by_slug_criteria_returns_null_when_team_slug_not_found(): void {
        $league = League_Test_Factory::from_object( (object) [ 'id' => 10 ] );
        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( $this->once() )->method( 'find_by_id' )->with( 10 )->willReturn( $league );

        $this->wpdb->method( 'get_var' )->willReturn( null );

        $repo = new Fixture_Repository( $this->wpdb, null, null, $league_repo );

        $result = $repo->find_one_by_slug_criteria( [
            'league_slug'    => 10,
            'home_team_slug' => 'Unknown Nonexistent Team',
        ] );

        $this->assertNull( $result );
    }

    public function test_find_by_id_hydrates_fixture(): void {
        $row = (object) [
            'id'        => 42,
            'home_team' => '1',
            'away_team' => '2',
            'league_id' => 3,
            'season'    => '2026',
        ];

        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->willReturn( 'SELECT * FROM wp_racketmanager_matches WHERE `id` = 42 LIMIT 1' );
        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->willReturn( $row );

        $fixture = $this->repository->find_by_id( 42 );

        $this->assertInstanceOf( Fixture::class, $fixture );
        $this->assertSame( 42, $fixture->get_id() );
    }

    public function test_find_by_team_prepares_query_and_returns_fixtures(): void {
        $row = (object) [
            'id'        => 10,
            'home_team' => '5',
            'away_team' => '6',
            'league_id' => 1,
            'season'    => '2026',
        ];

        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with(
                'SELECT * FROM wp_racketmanager_matches WHERE `home_team` = %s OR `away_team` = %s',
                '5',
                '5'
            )
            ->willReturn( "SELECT * FROM wp_racketmanager_matches WHERE `home_team` = '5' OR `away_team` = '5'" );
        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturn( [ $row ] );

        $fixtures = $this->repository->find_by_team( 5 );

        $this->assertCount( 1, $fixtures );
        $this->assertInstanceOf( Fixture::class, $fixtures[0] );
        $this->assertSame( 10, $fixtures[0]->get_id() );
    }

    public function test_delete_by_team_finds_and_deletes_fixtures(): void {
        $row = (object) [
            'id'        => 20,
            'home_team' => '7',
            'away_team' => '8',
            'league_id' => 1,
            'season'    => '2026',
        ];

        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->willReturn( "SELECT * FROM wp_racketmanager_matches WHERE `home_team` = '7' OR `away_team` = '7'" );
        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturn( [ $row ] );

        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'delete' )
            ->willReturn( 1 );

        $this->assertTrue( $this->repository->delete_by_team( 7 ) );
    }

    public function test_find_all_count_queries_matches_table(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'get_var' )
            ->with( 'SELECT COUNT(*) FROM wp_racketmanager_matches' )
            ->willReturn( '4' );

        $count = $this->repository->find_all( array( 'count' => true ) );

        $this->assertSame( 4, $count );
    }

    public function test_find_all_count_queries_matches_table_with_filters(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'get_var' )
            ->with( "SELECT COUNT(*) FROM wp_racketmanager_matches WHERE `league_id`  = '3' AND `season`  = '2026'" )
            ->willReturn( '2' );

        $count = $this->repository->find_all(
            array(
                'count'    => true,
                'leagueId' => 3,
                'season'   => '2026',
            )
        );

        $this->assertSame( 2, $count );
    }

    public function test_find_all_builds_sql_with_unwrapped_tables(): void {
        $captured_sql = null;
        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturnCallback(
                function ( string $sql ) use ( &$captured_sql ): array {
                    $captured_sql = $sql;
                    return array();
                }
            );

        $this->repository->find_all(
            array(
                'competition_name' => 'Summer Cup',
                'club'             => 5,
            )
        );

        $this->assertNotNull( $captured_sql );
        $this->assertStringContainsString( 'FROM wp_racketmanager_matches AS m, wp_racketmanager_leagues AS l', $captured_sql );
        $this->assertStringContainsString( 'wp_racketmanager_events e, wp_racketmanager_competitions c', $captured_sql );
        $this->assertStringContainsString( 'SELECT `id` FROM wp_racketmanager_teams WHERE `club_id` = 5', $captured_sql );
        $this->assertStringNotContainsString( '{', $captured_sql );
        $this->assertStringNotContainsString( '}', $captured_sql );
    }
}
