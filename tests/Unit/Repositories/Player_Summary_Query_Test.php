<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Player\Players_List_DTO;
use Racketmanager\Repositories\Player_Summary_Query;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

final class Player_Summary_Query_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private const string SQL_ACTIVE_PLAYERS_SUBQUERY  = 'u.ID IN (SELECT DISTINCT player_id FROM wp_racketmanager_rubber_players UNION SELECT DISTINCT player_id FROM wp_racketmanager_tournament_entries)';
    private const string SQL_GENDER_FILTER            = "meta_key = 'gender' AND meta_value IN ('M', 'F')";
    private const string SQL_DISPLAY_NAME_LIKE        = 'u.display_name LIKE';
    private const string SQL_ORDER_BY_ID_ASC          = 'ORDER BY u.ID ASC';
    private const string SQL_RUBBER_PLAYERS_TABLE     = 'wp_racketmanager_rubber_players';
    private const string SQL_TOURNAMENT_ENTRIES_TABLE = 'wp_racketmanager_tournament_entries';

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
            public $base_prefix = 'wp_';
            public $queries = [];
            public array $prepares = [];
            public array $esc_likes = [];
            private array $return_rows;

            public function __construct( array $return_rows ) {
                $this->prefix      = 'wp_';
                $this->base_prefix = 'wp_';
                $this->return_rows = $return_rows;
            }

            public function esc_like( $text ): string {
                $this->esc_likes[] = (string) $text;
                return addcslashes( (string) $text, '_%\\' );
            }

            public function prepare( $query, ...$args ): string {
                $this->prepares[] = [ 'query' => $query, 'args' => $args ];
                $flat_args        = ( isset( $args[0] ) && is_array( $args[0] ) ) ? $args[0] : $args;
                return array_reduce(
                    $flat_args,
                    static fn( string $sql, $arg ): string => (string) preg_replace( '/%[sdf]/', "'" . addslashes( (string) $arg ) . "'", $sql, 1 ),
                    (string) $query
                );
            }

            public function get_results( $query = null, $output = 'OBJECT' ): array {
                $this->queries[] = $query;
                return $this->return_rows;
            }
        };
    }

    public function test_execute_text_search_sql_shape_and_escaping(): void {
        $wpdb = $this->create_recording_wpdb(
            [
                (object) [
                    'player_id'     => 101,
                    'display_name'  => 'John Smi_th',
                    'user_nicename' => 'johnsmith',
                    'email'         => 'john@example.com',
                    'firstName'     => 'John',
                    'surname'       => 'Smith',
                    'btm'           => '555',
                    'gender'        => 'M',
                ],
            ]
        );

        $query   = new Player_Summary_Query( $wpdb );
        $results = $query->execute( [ 'name' => 'Smi_th' ] );

        $this->assertCount( 1, $results );
        $this->assertInstanceOf( Players_List_DTO::class, $results[0] );
        $this->assertSame( 101, $results[0]->id );
        $this->assertSame( 'John Smi_th', $results[0]->display_name );
        $this->assertSame( '/player/john-smi_th/555/', $results[0]->link );

        $this->assertContains( 'Smi_th', $wpdb->esc_likes );
        $this->assertCount( 1, $wpdb->queries );
        $sql = $wpdb->queries[0];

        $this->assertStringContainsString( 'wp_users u', $sql );
        $this->assertStringContainsString( 'wp_usermeta m', $sql );
        $this->assertStringContainsString( "u.display_name LIKE '%Smi\\_th%'", $sql );
        $this->assertStringContainsString( self::SQL_GENDER_FILTER, $sql );
        $this->assertStringContainsString( 'GROUP BY u.ID, u.display_name, u.user_nicename, u.user_email', $sql );
        $this->assertStringContainsString( 'ORDER BY u.display_name ASC', $sql );
    }

    public function test_execute_numeric_search_filters_by_btm_meta_key_and_value(): void {
        $wpdb = $this->create_recording_wpdb(
            [
                (object) [
                    'player_id'     => 102,
                    'display_name'  => 'Jane Doe',
                    'user_nicename' => 'janedoe',
                    'email'         => 'jane@example.com',
                    'firstName'     => 'Jane',
                    'surname'       => 'Doe',
                    'btm'           => '123456',
                    'gender'        => 'F',
                ],
            ]
        );

        $query   = new Player_Summary_Query( $wpdb );
        $results = $query->execute( [ 'name' => '123456' ] );

        $this->assertCount( 1, $results );
        $this->assertInstanceOf( Players_List_DTO::class, $results[0] );
        $this->assertSame( 102, $results[0]->id );
        $this->assertEmpty( $wpdb->esc_likes );
        $this->assertCount( 1, $wpdb->queries );
        $sql = $wpdb->queries[0];

        $this->assertStringContainsString( "meta_key = 'btm' AND meta_value = '123456'", $sql );
        $this->assertStringNotContainsString( self::SQL_DISPLAY_NAME_LIKE, $sql );
        $this->assertStringNotContainsString( "meta_value IN ('M', 'F')", $sql );
    }

    public function test_execute_active_flag_adds_union_subquery_and_orders_by_id_asc(): void {
        $wpdb = $this->create_recording_wpdb();

        $query = new Player_Summary_Query( $wpdb );
        $query->execute( [ 'active' => true ] );

        $this->assertCount( 1, $wpdb->queries );
        $sql = $wpdb->queries[0];

        $this->assertStringContainsString( self::SQL_RUBBER_PLAYERS_TABLE, $sql );
        $this->assertStringContainsString( self::SQL_TOURNAMENT_ENTRIES_TABLE, $sql );
        $this->assertStringContainsString( self::SQL_ACTIVE_PLAYERS_SUBQUERY, $sql );
        $this->assertStringContainsString( self::SQL_ORDER_BY_ID_ASC, $sql );
    }

    public function test_execute_default_args_filters_by_gender_m_and_f(): void {
        $wpdb = $this->create_recording_wpdb();

        $query = new Player_Summary_Query( $wpdb );
        $query->execute();

        $this->assertCount( 1, $wpdb->queries );
        $sql = $wpdb->queries[0];

        $this->assertStringContainsString( self::SQL_GENDER_FILTER, $sql );
        $this->assertStringNotContainsString( self::SQL_ACTIVE_PLAYERS_SUBQUERY, $sql );
        $this->assertStringNotContainsString( self::SQL_DISPLAY_NAME_LIKE, $sql );
        $this->assertStringContainsString( 'ORDER BY u.display_name ASC', $sql );
    }

    public function test_execute_serves_from_cache_when_available(): void {
        $wpdb = $this->create_recording_wpdb();

        $cached_dto = new Players_List_DTO(
            (object) [
                'player_id'     => 103,
                'display_name'  => 'Cached Player',
                'user_nicename' => 'cached',
                'email'         => 'cached@example.com',
                'firstName'     => 'Cached',
                'surname'       => 'Player',
                'btm'           => '999',
                'gender'        => 'M',
            ]
        );

        $args      = [ 'name' => 'Cached' ];
        $cache_key = 'players_summary_' . md5(
            (string) wp_json_encode(
                array_merge(
                    [
                        'active' => false,
                        'name'   => false,
                    ],
                    $args
                )
            )
        );

        wp_cache_set( $cache_key, [ $cached_dto ], 'players' );

        $query   = new Player_Summary_Query( $wpdb );
        $results = $query->execute( $args );

        $this->assertCount( 1, $results );
        $this->assertSame( 103, $results[0]->id );
        $this->assertEmpty( $wpdb->queries, 'Database should not be queried when cache entry exists.' );
    }

    public function test_execute_populates_cache_on_cache_miss(): void {
        $wpdb = $this->create_recording_wpdb(
            [
                (object) [
                    'player_id'     => 104,
                    'display_name'  => 'Fresh Player',
                    'user_nicename' => 'fresh',
                    'email'         => 'fresh@example.com',
                    'firstName'     => 'Fresh',
                    'surname'       => 'Player',
                    'btm'           => '888',
                    'gender'        => 'M',
                ],
            ]
        );

        $args      = [ 'name' => 'Fresh' ];
        $cache_key = 'players_summary_' . md5(
            (string) wp_json_encode(
                array_merge(
                    [
                        'active' => false,
                        'name'   => false,
                    ],
                    $args
                )
            )
        );

        $query   = new Player_Summary_Query( $wpdb );
        $results = $query->execute( $args );

        $this->assertCount( 1, $results );
        $cached = wp_cache_get( $cache_key, 'players' );
        $this->assertIsArray( $cached );
        $this->assertCount( 1, $cached );
        $this->assertSame( 104, $cached[0]->id );
    }

    public function test_execute_returns_empty_array_when_no_rows_returned(): void {
        $wpdb = $this->create_recording_wpdb( [] );

        $query   = new Player_Summary_Query( $wpdb );
        $results = $query->execute( [ 'name' => 'NonExistent' ] );

        $this->assertIsArray( $results );
        $this->assertEmpty( $results );
    }
}
