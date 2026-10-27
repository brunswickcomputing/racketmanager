<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Repositories\Rubber_Repository;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class Rubber_Repository_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Rubber_Repository $repository;
    private $wpdb;
    private $registration_service;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->wpdb = $this->createMock( wpdb::class );
        $this->wpdb->prefix = 'wp_';
        $GLOBALS['wpdb'] = $this->wpdb;
        $this->registration_service = $this->createMock( Registration_Service::class );
        $this->repository = new Rubber_Repository( $this->registration_service );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_find_by_id_returns_null_for_empty_id(): void {
        $this->assertNull( $this->repository->find_by_id( null ) );
        $this->assertNull( $this->repository->find_by_id( 0 ) );
    }

    public function test_find_by_id_returns_cached_instance(): void {
        $data = (object) [
            'id'       => 10,
            'match_id' => 100,
        ];
        $rubber = Rubber::from_object( $data );

        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']['rubbers']['10'] = $rubber;

        $result = $this->repository->find_by_id( 10 );
        $this->assertEquals( $rubber, $result );
    }

    public function test_find_by_id_cache_miss_prepares_query_and_hydrates_rubber(): void {
        $row = (object) [
            'id'            => 10,
            'match_id'      => 100,
            'date'          => '2026-06-01 19:00:00',
            'day'           => '1',
            'month'         => '6',
            'year'          => '2026',
            'hour'          => '19',
            'minutes'       => '00',
            'home_points'   => 2.0,
            'away_points'   => 1.0,
            'winner_id'     => 1,
            'loser_id'      => 2,
            'post_id'       => 0,
            'type'          => 'MD',
            'custom'        => null,
            'rubber_number' => 1,
            'status'        => null,
            'group'         => '',
        ];

        $this->wpdb->expects( $this->atLeastOnce() )
            ->method( 'prepare' )
            ->willReturnCallback( function ( $query, ...$args ) {
                if ( str_contains( $query, 'LIMIT 1' ) ) {
                    $this->assertStringContainsString( "DATE_FORMAT(`date`, '%%Y-%%m-%%d %%H:%%i') AS date", $query );
                    $this->assertStringContainsString( "DATE_FORMAT(`date`, '%%e') AS day", $query );
                    $this->assertStringContainsString( "DATE_FORMAT(`date`, '%%c') AS month", $query );
                    $this->assertStringContainsString( "DATE_FORMAT(`date`, '%%i') AS `minutes`", $query );
                    $this->assertSame( [ 10 ], $args );
                }
                return vsprintf( str_replace( [ '%s', '%d', '%f' ], [ "'%s'", '%d', '%f' ], $query ), $args );
            } );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_row' )
            ->willReturn( $row );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->willReturn( [] );

        $GLOBALS['wp_stubs_cache_enabled'] = true;

        $rubber = $this->repository->find_by_id( 10 );
        $this->assertNotNull( $rubber );
        $this->assertSame( 10, $rubber->get_id() );
        $this->assertSame( 1, $rubber->day );
        $this->assertSame( 6, $rubber->month );
        $this->assertSame( 2026, $rubber->year );
        $this->assertSame( [], $rubber->players );
        $this->assertEquals( $rubber, $GLOBALS['wp_stubs_cache']['rubbers']['10'] ?? null );
    }

    public function test_save_inserts_new_rubber_and_sets_id(): void {
        $rubber = Rubber::from_object( (object) [
            'match_id'      => 100,
            'type'          => 'MD',
            'rubber_number' => 1,
            'date'          => '2026-06-01 19:00:00',
        ] );

        $this->wpdb->insert_id = 88;
        $this->wpdb->expects( $this->once() )
            ->method( 'insert' )
            ->with(
                'wp_racketmanager_rubbers',
                $this->callback( function ( $data ) {
                    return 100 === $data['match_id'] && 'MD' === $data['type'] && 1 === $data['rubber_number'];
                } ),
                $this->callback( 'is_array' )
            )
            ->willReturn( 1 );

        $insert_id = $this->repository->save( $rubber );
        $this->assertSame( 88, $insert_id );
        $this->assertSame( 88, $rubber->get_id() );
    }

    public function test_save_updates_existing_rubber(): void {
        $rubber = Rubber::from_object( (object) [
            'id'            => 88,
            'match_id'      => 100,
            'type'          => 'MD',
            'rubber_number' => 1,
            'date'          => '2026-06-01 19:00:00',
            'home_points'   => 2.0,
            'away_points'   => 0.0,
        ] );

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->with(
                'wp_racketmanager_rubbers',
                $this->callback( function ( $data ) {
                    return 88 !== ( $data['id'] ?? null ) && 2.0 === $data['home_points'];
                } ),
                [ 'id' => 88 ]
            )
            ->willReturn( 1 );

        $result = $this->repository->save( $rubber );
        $this->assertTrue( $result );
    }

    public function test_save_canonicalizes_rubber_datetime(): void {
        $rubber = Rubber::from_object( (object) [
            'id'            => 88,
            'match_id'      => 100,
            'type'          => 'MD',
            'rubber_number' => 1,
            'date'          => '2026-05-15T19:30:00+01:00',
        ] );

        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->with(
                'wp_racketmanager_rubbers',
                $this->callback( function ( $data ) {
                    return '2026-05-15 19:30:00' === $data['date'];
                } ),
                [ 'id' => 88 ]
            )
            ->willReturn( 1 );

        $result = $this->repository->save( $rubber );
        $this->assertTrue( $result );
    }

    public function test_save_throws_on_invalid_rubber_datetime(): void {
        $rubber = Rubber::from_object( (object) [
            'id'       => 88,
            'match_id' => 100,
            'date'     => '2026-13-45 25:99',
        ] );

        $this->expectException( InvalidArgumentException::class );
        $this->repository->save( $rubber );
    }

    public function test_find_by_fixture_id_prepares_query_and_returns_rubbers(): void {
        $row = (object) [
            'id'            => 10,
            'match_id'      => 100,
            'date'          => '2026-06-01 19:00:00',
            'day'           => '1',
            'month'         => '6',
            'year'          => '2026',
            'hour'          => '19',
            'minutes'       => '00',
            'home_points'   => 2.0,
            'away_points'   => 1.0,
            'winner_id'     => 1,
            'loser_id'      => 2,
            'post_id'       => 0,
            'type'          => 'MD',
            'custom'        => null,
            'rubber_number' => 1,
            'status'        => null,
            'group'         => '',
        ];

        $this->wpdb->expects( $this->atLeastOnce() )
            ->method( 'prepare' )
            ->willReturnCallback( function ( $query, ...$args ) {
                if ( str_contains( $query, 'ORDER BY' ) ) {
                    $this->assertStringContainsString( "DATE_FORMAT(r.`date`, '%%Y-%%m-%%d %%H:%%i') AS date", $query );
                    $this->assertStringContainsString( "DATE_FORMAT(r.`date`, '%%e') AS day", $query );
                    $this->assertStringContainsString( "DATE_FORMAT(r.`date`, '%%c') AS month", $query );
                    $this->assertStringContainsString( "DATE_FORMAT(r.`date`, '%%i') AS `minutes`", $query );
                    $this->assertSame( [ 100 ], $args );
                }
                return vsprintf( str_replace( [ '%s', '%d', '%f' ], [ "'%s'", '%d', '%f' ], $query ), $args );
            } );

        $this->wpdb->expects( $this->atLeastOnce() )
            ->method( 'get_results' )
            ->willReturnCallback( function ( $sql ) use ( $row ) {
                if ( str_contains( $sql, 'racketmanager_rubbers r' ) ) {
                    return [ $row ];
                }
                return [];
            } );

        $rubbers = $this->repository->find_by_fixture_id( 100 );
        $this->assertCount( 1, $rubbers );
        $this->assertInstanceOf( Rubber::class, $rubbers[0] );
        $this->assertSame( 10, $rubbers[0]->get_id() );
        $this->assertSame( 1, $rubbers[0]->day );
        $this->assertSame( 6, $rubbers[0]->month );
        $this->assertSame( 2026, $rubbers[0]->year );
    }

    public function test_find_players_by_rubber_id_hydrates_players_and_merges_ineligibility(): void {
        $player_row = (object) [
            'id'             => 1,
            'player_ref'     => 1,
            'player_team'    => 'home',
            'player_id'      => 301,
            'club_player_id' => 501,
        ];

        $checker_error1 = (object) [ 'description' => 'Unregistered player' ];
        $checker_error2 = (object) [ 'description' => 'Played above quota' ];

        $this->wpdb->expects( $this->atLeastOnce() )
            ->method( 'prepare' )
            ->willReturnCallback( function ( $query, ...$args ) {
                return vsprintf( str_replace( [ '%s', '%d', '%f' ], [ "'%s'", '%d', '%f' ], $query ), $args );
            } );

        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'get_results' )
            ->willReturnCallback( function ( $sql ) use ( $player_row, $checker_error1, $checker_error2 ) {
                if ( str_contains( $sql, 'racketmanager_rubber_players' ) ) {
                    return [ $player_row ];
                }
                if ( str_contains( $sql, 'racketmanager_results_checker' ) ) {
                    return [ $checker_error1, $checker_error2 ];
                }
                return [];
            } );

        $club_player_dto = $this->createMock( Club_Player_DTO::class );
        $club_player_dto->user_id = 301;
        $club_player_dto->registration_id = 501;

        $this->registration_service->expects( $this->once() )
            ->method( 'get_registration' )
            ->with( 501 )
            ->willReturn( $club_player_dto );

        $players = $this->repository->find_players_by_rubber_id( 10 );
        $this->assertArrayHasKey( 'home', $players );
        $this->assertArrayHasKey( 1, $players['home'] );

        $player = $players['home'][1];
        $this->assertSame( 'is-ineligible', $player->class );
        $this->assertSame( 'Unregistered player, Played above quota', $player->description );
        $this->assertSame( 301, $player->user_id );
    }

    public function test_find_players_by_rubber_id_throws_logic_exception_when_registration_service_missing(): void {
        $repo_without_service = new Rubber_Repository( null );

        $player_row = (object) [
            'id'             => 1,
            'player_ref'     => 1,
            'player_team'    => 'home',
            'player_id'      => 301,
            'club_player_id' => 501,
        ];

        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->willReturn( 'SELECT PLAYERS' );

        $this->wpdb->expects( $this->once() )
            ->method( 'get_results' )
            ->with( 'SELECT PLAYERS' )
            ->willReturn( [ $player_row ] );

        $this->expectException( \LogicException::class );
        $this->expectExceptionMessage( 'Registration_Service is required to hydrate rubber players.' );

        $repo_without_service->find_players_by_rubber_id( 10 );
    }

    public function test_save_players_throws_logic_exception_when_numeric_and_registration_service_missing(): void {
        $repo_without_service = new Rubber_Repository( null );

        $players = [
            'home' => [
                1 => 501,
            ],
        ];

        $this->expectException( \LogicException::class );
        $this->expectExceptionMessage( 'Registration_Service is required to save players by ID.' );

        $repo_without_service->save_players( 10, $players );
    }

    public function test_save_players_and_cache_invalidation(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']['rubbers']['10'] = 'cached_rubber';

        $club_player_dto = $this->createMock( Club_Player_DTO::class );
        $club_player_dto->user_id = 301;
        $club_player_dto->registration_id = 501;

        $this->registration_service->expects( $this->once() )
            ->method( 'get_registration' )
            ->with( 501 )
            ->willReturn( $club_player_dto );

        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with(
                $this->stringContains( 'REPLACE INTO wp_racketmanager_rubber_players' ),
                10,
                1,
                'home',
                301,
                501
            )
            ->willReturn( 'REPLACE QUERY' );

        $this->wpdb->expects( $this->once() )
            ->method( 'query' )
            ->with( 'REPLACE QUERY' )
            ->willReturn( 1 );

        $players = [
            'home' => [
                1 => (object) [ 'registration_id' => 501 ],
            ],
        ];

        $success = $this->repository->save_players( 10, $players );
        $this->assertTrue( $success );
        $this->assertArrayNotHasKey( '10', $GLOBALS['wp_stubs_cache']['rubbers'] ?? [] );
    }

    public function test_delete_cascades_to_players_and_deletes_rubber_row(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']['rubbers']['10'] = 'cached_rubber';

        $this->wpdb->expects( $this->exactly( 2 ) )
            ->method( 'delete' )
            ->willReturnCallback( function ( $table, $where ) {
                if ( 'wp_racketmanager_rubber_players' === $table ) {
                    $this->assertSame( [ 'rubber_id' => 10 ], $where );
                    return 1;
                }
                if ( 'wp_racketmanager_rubbers' === $table ) {
                    $this->assertSame( [ 'id' => 10 ], $where );
                    return 1;
                }
                return false;
            } );

        $success = $this->repository->delete( 10 );
        $this->assertTrue( $success );
        $this->assertArrayNotHasKey( '10', $GLOBALS['wp_stubs_cache']['rubbers'] ?? [] );
    }

    public function test_delete_by_fixture_id_deletes_players_and_fixture_rubbers(): void {
        $row = (object) [
            'id'            => 10,
            'match_id'      => 100,
            'date'          => '2026-06-01 19:00:00',
            'day'           => '1',
            'month'         => '6',
            'year'          => '2026',
            'hour'          => '19',
            'minutes'       => '00',
            'home_points'   => 2.0,
            'away_points'   => 1.0,
            'winner_id'     => 1,
            'loser_id'      => 2,
            'post_id'       => 0,
            'type'          => 'MD',
            'custom'        => null,
            'rubber_number' => 1,
            'status'        => null,
            'group'         => '',
        ];

        $this->wpdb->expects( $this->atLeastOnce() )
            ->method( 'prepare' )
            ->willReturn( 'PREPARED SQL' );

        $this->wpdb->expects( $this->atLeastOnce() )
            ->method( 'get_results' )
            ->willReturnCallback( function ( $sql ) use ( $row ) {
                if ( str_contains( $sql, 'racketmanager_rubbers' ) && ! str_contains( $sql, 'rubber_players' ) && ! str_contains( $sql, 'results_checker' ) ) {
                    return [ $row ];
                }
                return [];
            } );

        $this->wpdb->expects( $this->atLeastOnce() )
            ->method( 'delete' )
            ->willReturnCallback( function ( $table, $where ) {
                if ( 'wp_racketmanager_rubber_players' === $table ) {
                    $this->assertSame( [ 'rubber_id' => 10 ], $where );
                    return 1;
                }
                if ( 'wp_racketmanager_rubbers' === $table ) {
                    $this->assertSame( [ 'match_id' => 100 ], $where );
                    return 1;
                }
                return false;
            } );

        $success = $this->repository->delete_by_fixture_id( 100 );
        $this->assertTrue( $success );
    }

    public function test_update_date_by_fixture_id(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'update' )
            ->with(
                'wp_racketmanager_rubbers',
                [ 'date' => '2026-06-02 20:00:00' ],
                [ 'match_id' => 100 ],
                [ '%s' ],
                [ '%d' ]
            )
            ->willReturn( 1 );

        $success = $this->repository->update_date_by_fixture_id( 100, '2026-06-02T20:00' );
        $this->assertTrue( $success );
    }

    public function test_update_date_by_fixture_id_throws_on_invalid_datetime(): void {
        $this->expectException( InvalidArgumentException::class );
        $this->repository->update_date_by_fixture_id( 100, 'garbage-date' );
    }

    public function test_reset_result_deletes_players_and_updates_rubber(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $rubber = Rubber::from_object( (object) [ 'id' => 10, 'match_id' => 100, 'home_points' => 2.0 ] );
        $GLOBALS['wp_stubs_cache']['rubbers']['10'] = $rubber;

        $this->wpdb->expects( $this->once() )
            ->method( 'delete' )
            ->with(
                'wp_racketmanager_rubber_players',
                [ 'rubber_id' => 10 ],
                [ '%d' ]
            )
            ->willReturn( 1 );

        $this->wpdb->expects( $this->once() )
            ->method( 'prepare' )
            ->with(
                $this->stringContains( 'UPDATE wp_racketmanager_rubbers SET `home_points` = null' ),
                10
            )
            ->willReturn( 'RESET QUERY' );

        $this->wpdb->expects( $this->once() )
            ->method( 'query' )
            ->with( 'RESET QUERY' )
            ->willReturn( 1 );

        $success = $this->repository->reset_result( 10 );
        $this->assertTrue( $success );
        $cached_rubber = $GLOBALS['wp_stubs_cache']['rubbers']['10'] ?? null;
        $this->assertNotNull( $cached_rubber );
        $this->assertNull( $cached_rubber->get_home_points() );
    }
}
