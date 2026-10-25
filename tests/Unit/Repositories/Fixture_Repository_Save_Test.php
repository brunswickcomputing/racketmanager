<?php

namespace Racketmanager\Tests\Unit\Repositories;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
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
}
