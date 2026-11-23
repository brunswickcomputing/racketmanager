<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Repositories\Player_Titles_Query;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

final class Player_Titles_Query_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_cache_enabled'] = true;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_recording_wpdb( array $return_rows = [] ): object {
        return new class( $return_rows ) extends wpdb {
            public $prefix = 'wp_';
            public array $queries = [];
            public array $prepares = [];
            private array $return_rows;

            public function __construct( array $return_rows ) {
                $this->prefix      = 'wp_';
                $this->return_rows = $return_rows;
            }

            public function prepare( $query, ...$args ): string {
                $this->prepares[] = [ 'query' => $query, 'args' => $args ];
                $flat_args        = ( isset( $args[0] ) && is_array( $args[0] ) ) ? $args[0] : $args;
                return array_reduce(
                    $flat_args,
                    static fn( string $sql, $arg ): string => (string) preg_replace( '/%[sdf]/', (string) $arg, $sql, 1 ),
                    (string) $query
                );
            }

            public function get_results( $query = null, $output = 'OBJECT' ): array {
                $this->queries[] = $query;
                return $this->return_rows;
            }
        };
    }

    public function test_execute_queries_database_and_groups_by_season_and_tournament(): void {
        $match1 = (object) [
            'season'      => '2024',
            'tournament'  => 'Club Championship',
            'draw'        => 'Men Singles',
            'league_name' => 'Men Singles Final',
            'title'       => 'Men Singles Final',
            'team_id'     => 10,
            'winner_id'   => 10,
            'loser_id'    => 20,
        ];
        $match2 = (object) [
            'season'      => '2024',
            'tournament'  => 'Club Championship',
            'draw'        => 'Men Doubles',
            'league_name' => 'Men Doubles Final',
            'title'       => 'Men Doubles Final',
            'team_id'     => 11,
            'winner_id'   => 11,
            'loser_id'    => 21,
        ];
        $match3 = (object) [
            'season'      => '2023',
            'tournament'  => 'Summer Open',
            'draw'        => 'Open Singles',
            'league_name' => 'Open Singles Final',
            'title'       => 'Open Singles Final',
            'team_id'     => 12,
            'winner_id'   => 12,
            'loser_id'    => 22,
        ];

        $wpdb  = $this->create_recording_wpdb( [ $match1, $match2, $match3 ] );
        $query = new Player_Titles_Query( $wpdb );

        $titles = $query->execute( 42 );

        $this->assertCount( 1, $wpdb->queries );
        $sql = $wpdb->queries[0];

        $this->assertStringContainsString( 'wp_racketmanager_team_players', $sql );
        $this->assertStringContainsString( 'wp_racketmanager_matches', $sql );
        $this->assertStringContainsString( 'tp.`player_id` = 42', $sql );
        $this->assertStringContainsString( "m.`final` = 'final'", $sql );
        $this->assertStringContainsString( 'ORDER BY m.`season` DESC, t.`name`', $sql );

        $this->assertArrayHasKey( '2024', $titles );
        $this->assertArrayHasKey( '2023', $titles );
        $this->assertArrayHasKey( 'Club Championship', $titles['2024'] );
        $this->assertCount( 2, $titles['2024']['Club Championship'] );
        $this->assertSame( $match1, $titles['2024']['Club Championship'][0] );
        $this->assertSame( $match2, $titles['2024']['Club Championship'][1] );
        $this->assertArrayHasKey( 'Summer Open', $titles['2023'] );
        $this->assertCount( 1, $titles['2023']['Summer Open'] );
        $this->assertSame( $match3, $titles['2023']['Summer Open'][0] );

        $cache_key = md5( $sql );
        $cached    = wp_cache_get( $cache_key, 'player_finals' );
        $this->assertSame( [ $match1, $match2, $match3 ], $cached );
    }

    public function test_execute_serves_from_cache_when_available(): void {
        $cached_match = (object) [
            'season'      => '2024',
            'tournament'  => 'Winter Cup',
            'draw'        => 'Final',
            'league_name' => 'Winter Cup Final',
            'title'       => 'Winter Cup Final',
            'team_id'     => 5,
            'winner_id'   => 5,
            'loser_id'    => 6,
        ];

        $wpdb  = $this->create_recording_wpdb();
        $query = new Player_Titles_Query( $wpdb );

        // Generate the expected SQL to populate cache beforehand.
        $expected_sql = "SELECT m.`season`, t.`name` as `tournament`, e.`name` as `draw`, l.`title` as `league_name`, l.`title` as `title`, tp.`team_id`, m.`winner_id`, m.`loser_id` FROM wp_racketmanager_team_players tp, wp_racketmanager_matches m, wp_racketmanager_leagues l, wp_racketmanager_events e, wp_racketmanager_competitions c, wp_racketmanager_tournaments t WHERE tp.`player_id` = 99 AND (tp.`team_id` = m.`winner_id` OR tp.`team_id` = m.`loser_id`) AND m.`final` = 'final' AND m.`league_id` = l.`id` AND l.`event_id` = e.`id` AND e.competition_id = c.`id` AND t.`competition_id` = c.`id` AND t.`season` = m.`season` ORDER BY m.`season` DESC, t.`name`";
        wp_cache_set( md5( $expected_sql ), [ $cached_match ], 'player_finals' );

        $titles = $query->execute( 99 );

        $this->assertEmpty( $wpdb->queries, 'Database should not be queried when cache entry exists.' );
        $this->assertArrayHasKey( '2024', $titles );
        $this->assertArrayHasKey( 'Winter Cup', $titles['2024'] );
        $this->assertSame( $cached_match, $titles['2024']['Winter Cup'][0] );
    }

    public function test_execute_returns_empty_array_when_no_finals_found(): void {
        $wpdb  = $this->create_recording_wpdb( [] );
        $query = new Player_Titles_Query( $wpdb );

        $titles = $query->execute( 77 );

        $this->assertIsArray( $titles );
        $this->assertEmpty( $titles );
    }
}
