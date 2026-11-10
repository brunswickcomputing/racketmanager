<?php

namespace Racketmanager\Tests\Unit\Repositories;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Recording_Wpdb;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Repository_Save_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private $wpdb;
    private Fixture_Repository $repository;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->wpdb         = $this->createMock( wpdb::class );
        $this->wpdb->prefix = 'wp_';
        $GLOBALS['wpdb']    = $this->wpdb;

        $this->repository = new Fixture_Repository();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_save_serializes_comments(): void {
        $fixture_data = (object)[
            'id' => 123,
            'comments' => ['note' => 'test comment']
        ];
        $fixture = Fixture_Factory::from_object($fixture_data);

        // Expect update to be called with serialized comments
        // The table name is constructed in constructor: wp_ + racketmanager_matches
        $expected_table = 'wp_racketmanager_matches';

        $this->wpdb->expects($this->once())
            ->method('update')
            ->with(
                $expected_table,
                $this->callback(function($data) {
                    return isset($data['comments']) && is_string($data['comments']) && str_contains($data['comments'], 'test comment');
                }),
                ['id' => 123],
                $this->anything(),
                ['%d']
            )
            ->willReturn(1);

        $this->repository->save($fixture);
    }

    public function test_save_canonicalizes_datetimes(): void {
        $fixture_data = (object)[
            'id'            => 123,
            'date'          => '2026-05-15T19:30:00+01:00',
            'date_original' => '2026-05-10',
        ];
        $fixture = Fixture_Factory::from_object( $fixture_data );

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->with(
                'wp_racketmanager_matches',
                $this->callback( function ( $data ) {
                    return '2026-05-15 19:30:00' === $data['date']
                        && '2026-05-10 00:00:00' === $data['date_original'];
                } ),
                [ 'id' => 123 ],
                $this->anything(),
                [ '%d' ]
            )
            ->willReturn( 1 );

        $this->repository->save( $fixture );
    }

    public function test_update_date_canonicalizes_datetime_formats(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->with(
                'wp_racketmanager_matches',
                [
                    'date'          => '2026-06-20 18:00:00',
                    'date_original' => '2026-06-15 00:00:00',
                ],
                [ 'id' => 456 ],
                [ '%s', '%s' ],
                [ '%d' ]
            )
            ->willReturn( 1 );

        $result = $this->repository->update_date( 456, '2026-06-20T18:00', '2026-06-15' );
        $this->assertTrue( $result );
    }

    public function test_find_by_id_hydrates_start_time_from_persisted_datetime(): void {
        $raw_row = (object) [
            'id'            => 789,
            'date'          => '2026-05-15 19:30:00',
            'date_original' => '2026-05-01 00:00:00',
            'home_team'     => '1',
            'away_team'     => '2',
            'league_id'     => 10,
            'season'        => '2026',
            'match_day'     => 1,
            'status'        => 0,
        ];

        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->willReturn( 'SELECT * FROM wp_racketmanager_matches WHERE `id` = 789 LIMIT 1' );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->willReturn( $raw_row );

        $fixture = $this->repository->find_by_id( 789 );

        $this->assertNotNull( $fixture );
        $this->assertSame( 789, $fixture->get_id() );
        $this->assertSame( '2026-05-15 19:30:00', $fixture->get_date() );
        $this->assertSame( '19:30', $fixture->get_start_time() );
    }

    public function test_save_and_find_by_id_persistence_round_trip(): void {
        $saved_payload = null;

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->with(
                'wp_racketmanager_matches',
                $this->callback( function ( $data ) use ( &$saved_payload ) {
                    $saved_payload = $data;
                    return true;
                } ),
                [ 'id' => 999 ],
                $this->anything(),
                [ '%d' ]
            )
            ->willReturn( 1 );

        $fixture_to_save = Fixture_Factory::from_object( (object) [
            'id'   => 999,
            'date' => '2026-07-20T18:45:00+01:00',
        ] );

        $this->repository->save( $fixture_to_save );

        $this->assertNotNull( $saved_payload );
        $this->assertSame( '2026-07-20 18:45:00', $saved_payload['date'] );

        // Mock DB returning row with the exact saved datetime payload
        $persisted_row = (object) [
            'id'            => 999,
            'date'          => $saved_payload['date'],
            'date_original' => $saved_payload['date_original'],
            'home_team'     => '10',
            'away_team'     => '20',
            'league_id'     => 5,
            'season'        => '2026',
            'match_day'     => 2,
            'status'        => 0,
        ];

        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->willReturn( 'SELECT * FROM wp_racketmanager_matches WHERE `id` = 999 LIMIT 1' );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->willReturn( $persisted_row );

        $hydrated_fixture = $this->repository->find_by_id( 999 );

        $this->assertNotNull( $hydrated_fixture );
        $this->assertSame( '2026-07-20 18:45:00', $hydrated_fixture->get_date() );
        $this->assertSame( '18:45', $hydrated_fixture->get_start_time() );
    }

    public function test_save_throws_on_invalid_datetime(): void {
        $fixture = Fixture_Factory::from_object( (object) [ 'id' => 123 ] );
        // Bypass entity setter check using reflection to simulate corrupt domain date reaching persistence
        $ref = new \ReflectionProperty( Fixture::class, 'date' );
        $ref->setValue( $fixture, '2026-13-45 25:99' );

        $this->expectException( InvalidArgumentException::class );
        $this->repository->save( $fixture );
    }

    public function test_update_date_throws_on_invalid_datetime(): void {
        $this->expectException( InvalidArgumentException::class );
        $this->repository->update_date( 123, 'garbage-date' );
    }

    public function test_find_by_post_queries_matches_and_hydrates_fixture(): void {
        $raw_row = (object) [
            'id'            => 789,
            'post_id'       => 42,
            'date'          => '2026-05-15 19:30:00',
            'date_original' => '2026-05-01 00:00:00',
            'home_team'     => '1',
            'away_team'     => '2',
            'league_id'     => 10,
            'season'        => '2026',
            'match_day'     => 1,
            'status'        => 0,
        ];

        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with(
                'SELECT * FROM wp_racketmanager_matches WHERE `post_id` = %d LIMIT 1',
                42
            )
            ->willReturn( 'SELECT * FROM wp_racketmanager_matches WHERE `post_id` = 42 LIMIT 1' );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->with( 'SELECT * FROM wp_racketmanager_matches WHERE `post_id` = 42 LIMIT 1' )
            ->willReturn( $raw_row );

        $fixture = $this->repository->find_by_post( 42 );

        $this->assertInstanceOf( Fixture::class, $fixture );
        $this->assertSame( 789, $fixture->get_id() );
        $this->assertSame( 10, $fixture->get_league_id() );
        $this->assertSame( '2026', $fixture->get_season() );
    }

    public function test_find_by_post_returns_null_when_row_not_found(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->willReturn( 'SELECT * FROM wp_racketmanager_matches WHERE `post_id` = 999 LIMIT 1' );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->willReturn( null );

        $fixture = $this->repository->find_by_post( 999 );

        $this->assertNull( $fixture );
    }

    public function test_find_by_post_returns_null_for_non_positive_post_id(): void {
        $this->wpdb->expects( $this->never() )
            ->method( 'prepare' );

        $this->assertNull( $this->repository->find_by_post( 0 ) );
        $this->assertNull( $this->repository->find_by_post( -5 ) );
    }

    public function test_update_post_id_updates_db_and_invalidates_cache(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache_deleted'] = array();

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->with(
                'wp_racketmanager_matches',
                array( 'post_id' => 789 ),
                array( 'id' => 123 ),
                array( '%d' ),
                array( '%d' )
            )
            ->willReturn( 1 );

        $result = $this->repository->update_post_id( 123, 789 );

        $this->assertTrue( $result );
        $deleted = $GLOBALS['wp_stubs_cache_deleted'];
        $this->assertContains( array( 123, 'fixtures' ), $deleted );
        $this->assertContains( array( '123_legacy', 'fixtures' ), $deleted );
    }

    public function test_update_post_id_allows_unlinking_with_zero_post_id(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache_deleted'] = array();

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->with(
                'wp_racketmanager_matches',
                array( 'post_id' => 0 ),
                array( 'id' => 123 ),
                array( '%d' ),
                array( '%d' )
            )
            ->willReturn( 1 );

        $result = $this->repository->update_post_id( 123, 0 );

        $this->assertTrue( $result );
        $deleted = $GLOBALS['wp_stubs_cache_deleted'];
        $this->assertContains( array( 123, 'fixtures' ), $deleted );
        $this->assertContains( array( '123_legacy', 'fixtures' ), $deleted );
    }

    public function test_update_post_id_returns_false_and_skips_cache_invalidation_on_db_failure(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache_deleted'] = array();

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->willReturn( false );

        $result = $this->repository->update_post_id( 123, 456 );

        $this->assertFalse( $result );
        $this->assertEmpty( $GLOBALS['wp_stubs_cache_deleted'] );
    }

    public function test_update_post_id_returns_false_for_non_positive_id(): void {
        $this->wpdb->expects( $this->never() )
            ->method( 'update' );

        $this->assertFalse( $this->repository->update_post_id( 0, 456 ) );
        $this->assertFalse( $this->repository->update_post_id( -1, 456 ) );
    }

    public function test_find_by_post_and_update_post_id_with_recording_wpdb(): void {
        $db = new class extends wpdb {
            public array $prepared = [];
            public array $executed = [];
            public $get_row_return = null;

            public function __construct() {
                $this->prefix = 'wp_';
                $this->racketmanager_matches = 'wp_racketmanager_matches';
            }

            public function prepare( $query, ...$args ) {
                if ( isset( $args[0] ) && is_array( $args[0] ) && 1 === count( $args ) ) {
                    $args = $args[0];
                }
                $this->prepared[] = [
                    'sql'  => (string) $query,
                    'args' => $args,
                ];
                $this->last_query = (string) $query;
                return (string) $query;
            }

            public function get_row( $query, $output = 'OBJECT' ) {
                $this->last_query = (string) $query;
                return $this->get_row_return;
            }

            public function update( $table, $data, $where, $format = null, $where_format = null ) {
                $this->executed[] = [
                    'method' => 'update',
                    'table'  => $table,
                    'data'   => $data,
                    'where'  => $where,
                ];
                return 1;
            }
        };

        $db->get_row_return = (object) [
            'id'        => 55,
            'post_id'   => 99,
            'home_team' => '1',
            'away_team' => '2',
            'league_id' => 3,
            'season'    => '2026',
        ];

        $repo = new Fixture_Repository( $db );

        $fixture = $repo->find_by_post( 99 );
        $this->assertInstanceOf( Fixture::class, $fixture );
        $this->assertSame( 55, $fixture->get_id() );
        $this->assertNotEmpty( $db->prepared );
        $last_prepared = $db->prepared[ array_key_last( $db->prepared ) ];
        $this->assertStringContainsString( 'WHERE `post_id` = %d LIMIT 1', $last_prepared['sql'] );
        $this->assertSame( array( 99 ), $last_prepared['args'] );

        $updated = $repo->update_post_id( 55, 101 );
        $this->assertTrue( $updated );
        $last_executed = $db->executed[ array_key_last( $db->executed ) ];
        $this->assertSame( 'update', $last_executed['method'] );
        $this->assertSame( 'wp_racketmanager_matches', $last_executed['table'] );
        $this->assertSame( array( 'post_id' => 101 ), $last_executed['data'] );
        $this->assertSame( array( 'id' => 55 ), $last_executed['where'] );
    }
}
