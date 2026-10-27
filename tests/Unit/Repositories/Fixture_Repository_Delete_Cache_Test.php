<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

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

        $league = League::from_object( (object) [ 'id' => 10 ] );
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

        $league = League::from_object( (object) [ 'id' => 24, 'title' => 'Summer Mixed 1' ] );
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

        $league = League::from_object( (object) [ 'id' => 24 ] );
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
        $league = League::from_object( (object) [ 'id' => 10 ] );
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
}
