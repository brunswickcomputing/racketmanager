<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Repositories\Tournament_Repository;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

require_once __DIR__ . '/../../wp-stubs.php';

final class Tournament_Repository_Find_By_Id_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']         = [];
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_find_by_id_returns_null_when_id_is_empty(): void {
        $wpdb = $this->createMock( wpdb::class );
        $wpdb->prefix = 'wp_';
        $wpdb->expects( $this->never() )->method( 'prepare' );
        $wpdb->expects( $this->never() )->method( 'get_row' );

        $repository = new Tournament_Repository( db: $wpdb );

        $this->assertNull( $repository->find_by_id( null ) );
        $this->assertNull( $repository->find_by_id( 0 ) );
        $this->assertNull( $repository->find_by_id( '' ) );
    }

    public function test_find_by_id_returns_cached_tournament_and_wires_collaborators_without_query(): void {
        $wpdb = $this->createMock( wpdb::class );
        $wpdb->prefix = 'wp_';
        $wpdb->expects( $this->never() )->method( 'prepare' );
        $wpdb->expects( $this->never() )->method( 'get_row' );

        $cached_tournament = Tournament::from_object(
            (object) [
                'id'             => 42,
                'name'           => 'Cached Tournament',
                'competition_id' => 12,
                'season'         => '2026',
            ]
        );

        wp_cache_set( 42, $cached_tournament, 'tournaments' );

        $competition  = Competition_Test_Factory::create( 12, 'Tournament', Competition_Type::TOURNAMENT );
        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 12 )
            ->willReturn( $competition );

        $repository = new Tournament_Repository(
            db: $wpdb,
            competition_service: static fn() => $comp_service
        );

        $result = $repository->find_by_id( 42 );

        $this->assertInstanceOf( Tournament::class, $result );
        $this->assertSame( 42, $result->id );
        $this->assertSame( $competition, $result->get_competition() );
    }

    public function test_find_by_id_normalizes_numeric_string_cache_key_to_int(): void {
        $wpdb = $this->createMock( wpdb::class );
        $wpdb->prefix = 'wp_';
        $wpdb->expects( $this->never() )->method( 'prepare' );
        $wpdb->expects( $this->never() )->method( 'get_row' );

        $cached_tournament = Tournament::from_object(
            (object) [
                'id'             => 42,
                'name'           => 'Cached Tournament',
                'competition_id' => 0,
                'season'         => '2026',
            ]
        );

        wp_cache_set( 42, $cached_tournament, 'tournaments' );

        $repository = new Tournament_Repository( db: $wpdb );

        $result = $repository->find_by_id( '42' );

        $this->assertInstanceOf( Tournament::class, $result );
        $this->assertSame( 42, $result->id );
    }

    public function test_find_by_id_queries_by_id_for_numeric_int_and_numeric_string(): void {
        $wpdb = $this->createMock( wpdb::class );
        $wpdb->prefix = 'wp_';

        $prepared_queries = [];
        $wpdb->expects( $this->exactly( 2 ) )
            ->method( 'prepare' )
            ->willReturnCallback(
                function ( string $query, ...$args ) use ( &$prepared_queries ) {
                    $prepared_queries[] = [
                        'query' => $query,
                        'args'  => $args,
                    ];
                    return '`id` = ' . (int) $args[0];
                }
            );

        $wpdb->expects( $this->exactly( 2 ) )
            ->method( 'get_row' )
            ->willReturn(
                (object) [
                    'id'             => 42,
                    'name'           => 'Summer Open',
                    'competition_id' => 5,
                    'season'         => '2026',
                ]
            );

        $repository = new Tournament_Repository( db: $wpdb );

        $result_int = $repository->find_by_id( 42 );
        $this->assertInstanceOf( Tournament::class, $result_int );
        $this->assertSame( 42, $result_int->id );
        $this->assertSame( '`id` = %d', $prepared_queries[0]['query'] );
        $this->assertSame( [ 42 ], $prepared_queries[0]['args'] );
        $this->assertInstanceOf( Tournament::class, wp_cache_get( 42, 'tournaments' ) );

        $GLOBALS['wp_stubs_cache'] = [];

        $result_str = $repository->find_by_id( '42' );
        $this->assertInstanceOf( Tournament::class, $result_str );
        $this->assertSame( 42, $result_str->id );
        $this->assertSame( '`id` = %d', $prepared_queries[1]['query'] );
        $this->assertSame( [ 42 ], $prepared_queries[1]['args'] );
        $this->assertInstanceOf( Tournament::class, wp_cache_get( 42, 'tournaments' ) );
    }

    public function test_find_by_id_queries_by_name_when_search_term_is_name(): void {
        $wpdb = $this->createMock( wpdb::class );
        $wpdb->prefix = 'wp_';

        $wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with( '`name` = %s', 'Summer Open' )
            ->willReturn( "`name` = 'Summer Open'" );

        $wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->with( "SELECT * FROM `wp_racketmanager_tournaments` WHERE `name` = 'Summer Open'" )
            ->willReturn(
                (object) [
                    'id'             => 88,
                    'name'           => 'Summer Open',
                    'competition_id' => 5,
                    'season'         => '2026',
                ]
            );

        $repository = new Tournament_Repository( db: $wpdb );

        $result = $repository->find_by_id( 'Summer Open', 'name' );

        $this->assertInstanceOf( Tournament::class, $result );
        $this->assertSame( 88, $result->id );
        $this->assertSame( 'Summer Open', $result->name );

        $cached = wp_cache_get( 'Summer Open', 'tournaments' );
        $this->assertInstanceOf( Tournament::class, $cached );
        $this->assertSame( 88, $cached->id );
    }

    public function test_find_by_id_falls_back_to_name_query_when_id_is_non_numeric_and_search_term_is_id(): void {
        $wpdb = $this->createMock( wpdb::class );
        $wpdb->prefix = 'wp_';

        $wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with( '`name` = %s', 'Club Championship' )
            ->willReturn( "`name` = 'Club Championship'" );

        $wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->with( "SELECT * FROM `wp_racketmanager_tournaments` WHERE `name` = 'Club Championship'" )
            ->willReturn(
                (object) [
                    'id'             => 99,
                    'name'           => 'Club Championship',
                    'competition_id' => 7,
                    'season'         => '2026',
                ]
            );

        $repository = new Tournament_Repository( db: $wpdb );

        $result = $repository->find_by_id( 'Club Championship' );

        $this->assertInstanceOf( Tournament::class, $result );
        $this->assertSame( 99, $result->id );
        $this->assertSame( 'Club Championship', $result->name );

        $cached = wp_cache_get( 'Club Championship', 'tournaments' );
        $this->assertInstanceOf( Tournament::class, $cached );
    }

    public function test_find_by_id_queries_by_competition_and_season_for_valid_shortcode(): void {
        $wpdb = $this->createMock( wpdb::class );
        $wpdb->prefix = 'wp_';

        $wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with( '`competition_id` = %d AND `season` = %s', 12, '2026' )
            ->willReturn( "`competition_id` = 12 AND `season` = '2026'" );

        $wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->with( "SELECT * FROM `wp_racketmanager_tournaments` WHERE `competition_id` = 12 AND `season` = '2026'" )
            ->willReturn(
                (object) [
                    'id'             => 105,
                    'name'           => 'Shortcode Cup',
                    'competition_id' => 12,
                    'season'         => '2026',
                ]
            );

        $repository = new Tournament_Repository( db: $wpdb );

        $result = $repository->find_by_id( '12,2026', 'shortcode' );

        $this->assertInstanceOf( Tournament::class, $result );
        $this->assertSame( 105, $result->id );
        $this->assertSame( 'Shortcode Cup', $result->name );

        $cached = wp_cache_get( '12,2026', 'tournaments' );
        $this->assertInstanceOf( Tournament::class, $cached );
    }

    public function test_find_by_id_returns_null_safely_when_shortcode_is_malformed(): void {
        $wpdb = $this->createMock( wpdb::class );
        $wpdb->prefix = 'wp_';
        $wpdb->expects( $this->never() )->method( 'prepare' );
        $wpdb->expects( $this->never() )->method( 'get_row' );

        $repository = new Tournament_Repository( db: $wpdb );

        $this->assertNull( $repository->find_by_id( 'invalid_shortcode_without_comma', 'shortcode' ) );
    }

    public function test_find_by_id_returns_null_and_does_not_cache_when_db_returns_no_row(): void {
        $wpdb = $this->createMock( wpdb::class );
        $wpdb->prefix = 'wp_';

        $wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with( '`id` = %d', 999 )
            ->willReturn( '`id` = 999' );

        $wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->with( 'SELECT * FROM `wp_racketmanager_tournaments` WHERE `id` = 999' )
            ->willReturn( null );

        $repository = new Tournament_Repository( db: $wpdb );

        $result = $repository->find_by_id( 999 );

        $this->assertNull( $result );
        $this->assertFalse( wp_cache_get( 999, 'tournaments' ) );
    }
}
