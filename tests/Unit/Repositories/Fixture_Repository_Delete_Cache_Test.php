<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Repositories\Fixture_Repository;
use stdClass;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Repository_Delete_Cache_Test extends TestCase {
    private $wpdb;
    private $original_wpdb;
    private Fixture_Repository $repository;

    protected function setUp(): void {
        parent::setUp();
        $this->wpdb = $this->createMock( wpdb::class );
        $this->wpdb->prefix = 'wp_';

        global $wpdb;
        $this->original_wpdb = $wpdb ?? null;
        $wpdb = $this->wpdb;

        $this->repository = new Fixture_Repository( $this->wpdb );
    }

    protected function tearDown(): void {
        global $wpdb;
        $wpdb = $this->original_wpdb;
        unset( $GLOBALS['wp_stubs_leagues'][10], $GLOBALS['wp_stubs_cache_deleted'] );
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

    public function test_find_one_by_slug_criteria_with_fixture_array(): void {
        $fixture = Fixture_Factory::from_object( (object) [ 'id' => 77, 'season' => '2026' ] );

        $league = $this->createMock( League::class );
        $league->method( 'get_matches' )
            ->willReturn( [ $fixture ] );

        $GLOBALS['wp_stubs_leagues'][10] = $league;

        $result = $this->repository->find_one_by_slug_criteria( [
            'league_slug' => 10,
            'season'      => '2026',
        ] );

        $this->assertSame( $fixture, $result );
    }

    public function test_find_one_by_slug_criteria_with_row_object_array(): void {
        $row = (object) [
            'id'        => 88,
            'season'    => '2026',
            'home_team' => '1',
            'away_team' => '2',
        ];

        $league = $this->createMock( League::class );
        $league->method( 'get_matches' )
            ->willReturn( [ (object) [ 'id' => 88 ] ] );

        $GLOBALS['wp_stubs_leagues'][10] = $league;

        $this->wpdb->method( 'prepare' )->willReturn( 'SELECT * FROM wp_racketmanager_matches WHERE id = 88 LIMIT 1' );
        $this->wpdb->method( 'get_row' )->willReturn( $row );

        $result = $this->repository->find_one_by_slug_criteria( [
            'league_slug' => 10,
            'season'      => '2026',
        ] );

        $this->assertInstanceOf( Fixture::class, $result );
        $this->assertEquals( 88, $result->get_id() );
    }

    public function test_find_one_by_slug_criteria_with_single_object(): void {
        $row = (object) [
            'id'        => 99,
            'season'    => '2026',
            'home_team' => '1',
            'away_team' => '2',
        ];

        $league = $this->createMock( League::class );
        $league->method( 'get_matches' )
            ->willReturn( (object) [ 'id' => 99 ] );

        $GLOBALS['wp_stubs_leagues'][10] = $league;

        $this->wpdb->method( 'prepare' )->willReturn( 'SELECT * FROM wp_racketmanager_matches WHERE id = 99 LIMIT 1' );
        $this->wpdb->method( 'get_row' )->willReturn( $row );

        $result = $this->repository->find_one_by_slug_criteria( [
            'league_slug' => 10,
            'season'      => '2026',
        ] );

        $this->assertInstanceOf( Fixture::class, $result );
        $this->assertEquals( 99, $result->get_id() );
    }

    public function test_find_one_by_slug_criteria_with_direct_fixture(): void {
        $fixture = Fixture_Factory::from_object( (object) [ 'id' => 101, 'season' => '2026' ] );

        $league = $this->createMock( League::class );
        $league->method( 'get_matches' )
            ->willReturn( $fixture );

        $GLOBALS['wp_stubs_leagues'][10] = $league;

        $result = $this->repository->find_one_by_slug_criteria( [
            'league_slug' => 10,
            'season'      => '2026',
        ] );

        $this->assertSame( $fixture, $result );
    }
}
