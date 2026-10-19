<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Repository_Player_Matches_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private $wpdb;
    private array $captured_sql = [];

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->captured_sql = [];
        $this->wpdb         = $this->createMock( wpdb::class );
        $this->wpdb->prefix = 'wp_';
        $this->wpdb->method( 'prepare' )->willReturnCallback(
            static function ( string $query, ...$args ): string {
                $args = isset( $args[0] ) && is_array( $args[0] ) ? $args[0] : $args;
                $i    = 0;
                return (string) preg_replace_callback(
                    '/%[ds]/',
                    static function ( array $m ) use ( &$i, $args ) {
                        $value = $args[ $i++ ];
                        return '%d' === $m[0] ? (string) (int) $value : "'" . $value . "'";
                    },
                    $query
                );
            }
        );
        $GLOBALS['wpdb'] = $this->wpdb;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function capture_results( array|null $rows ): void {
        $this->wpdb->method( 'get_results' )->willReturnCallback(
            function ( $sql ) use ( $rows ) {
                $this->captured_sql[] = $sql;
                return $rows;
            }
        );
    }

    public function test_builds_sql_with_all_filters(): void {
        $this->capture_results( [] );
        $repository = new Fixture_Repository( $this->wpdb, null, $this->createStub( Rubber_Repository_Interface::class ) );

        $result = $repository->find_player_matches( 7, [ 'season' => '2026', 'status' => 'Y', 'time' => 30, 'type' => 'M_D%' ] );

        $this->assertSame( [], $result );
        $sql = $this->captured_sql[0];
        $this->assertStringStartsWith( 'SELECT DISTINCT m.*', $sql );
        $this->assertStringContainsString( 'rp.`player_id` = 7', $sql );
        $this->assertStringContainsString( "m.`season` = '2026'", $sql );
        $this->assertStringContainsString( "m.`confirmed` = 'Y'", $sql );
        $this->assertStringContainsString( 'm.`date` > NOW() - INTERVAL 30 DAY', $sql );
        $this->assertStringContainsString( "e.`type` LIKE '%M\\_D\\%%'", $sql );
        $this->assertStringEndsWith( 'ORDER BY m.`date` ASC', $sql );
    }

    public function test_latest_time_filter_requires_points(): void {
        $this->capture_results( [] );
        $repository = new Fixture_Repository( $this->wpdb, null, $this->createStub( Rubber_Repository_Interface::class ) );

        $repository->find_player_matches( 7, [ 'time' => 'latest' ] );

        $this->assertStringContainsString( "(m.`home_points` != '' OR m.`away_points` != '')", $this->captured_sql[0] );
        $this->assertStringNotContainsString( 'INTERVAL', $this->captured_sql[0] );
        $this->assertStringNotContainsString( 'm.`season`', $this->captured_sql[0] );
    }

    public function test_non_array_results_return_empty(): void {
        $this->capture_results( null );
        $repository = new Fixture_Repository( $this->wpdb, null, $this->createStub( Rubber_Repository_Interface::class ) );

        $this->assertSame( [], $repository->find_player_matches( 7 ) );
    }

    public function test_attaches_player_filtered_rubbers(): void {
        $this->capture_results( [ (object) [ 'id' => 55, 'season' => '2026' ] ] );
        $rubber_repository = $this->createMock( Rubber_Repository_Interface::class );
        $rubber_repository->expects( $this->once() )
            ->method( 'find_by_fixture_id' )
            ->with( 55, 7 )
            ->willReturn( [] );
        $repository = new Fixture_Repository( $this->wpdb, null, $rubber_repository );

        $result = $repository->find_player_matches( 7, [ 'season' => '2026' ] );

        $this->assertCount( 1, $result );
        $this->assertInstanceOf( Fixture::class, $result[0] );
        $this->assertSame( [], $result[0]->rubbers );
    }

    public function test_throws_without_rubber_repository(): void {
        $this->capture_results( [] );
        $repository = new Fixture_Repository( $this->wpdb );

        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( 'requires a Rubber_Repository_Interface' );
        $repository->find_player_matches( 7 );
    }
}
