<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Player\Players_List_DTO;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

final class Player_Repository_Summary_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private const string SQL_ACTIVE_PLAYERS_SUBQUERY  = 'u.ID IN (SELECT DISTINCT player_id FROM wp_racketmanager_rubber_players UNION SELECT DISTINCT player_id FROM wp_racketmanager_tournament_entries)';
    private const string SQL_GENDER_FILTER            = "meta_key = '" . Player_Repository::META_KEY_GENDER . "' AND meta_value IN ('M', 'F')";
    private const string SQL_GENDER_VALUE_FILTER      = "meta_value IN ('M', 'F')";
    private const string SQL_DISPLAY_NAME_LIKE        = 'u.display_name LIKE';
    private const string SQL_ORDER_BY_ID_ASC          = 'ORDER BY u.ID ASC';
    private const string SQL_RUBBER_PLAYERS_TABLE     = 'wp_racketmanager_rubber_players';
    private const string SQL_TOURNAMENT_ENTRIES_TABLE = 'wp_racketmanager_tournament_entries';

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
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
                $this->prefix = 'wp_';
                $this->base_prefix = 'wp_';
                $this->return_rows = $return_rows;
            }

            public function esc_like( $text ): string {
                $this->esc_likes[] = (string) $text;
                return addcslashes( (string) $text, '_%\\' );
            }

            public function prepare( $query, ...$args ): string {
                $this->prepares[] = [ 'query' => $query, 'args' => $args ];
                $flat_args = ( isset( $args[0] ) && is_array( $args[0] ) ) ? $args[0] : $args;
                foreach ( $flat_args as $arg ) {
                    $query = preg_replace( '/%[sdf]/', "'" . addslashes( (string) $arg ) . "'", $query, 1 );
                }
                return $query;
            }

            public function get_results( $query = null, $output = 'OBJECT' ): array {
                $this->queries[] = $query;
                return $this->return_rows;
            }
        };
    }

    private function assert_active_players_filter_applied( string $sql ): void {
        $this->assertStringContainsString( self::SQL_RUBBER_PLAYERS_TABLE, $sql );
        $this->assertStringContainsString( self::SQL_TOURNAMENT_ENTRIES_TABLE, $sql );
        $this->assertStringContainsString( self::SQL_ACTIVE_PLAYERS_SUBQUERY, $sql );
        $this->assertStringNotContainsString( self::SQL_GENDER_VALUE_FILTER, $sql );
        $this->assertStringNotContainsString( self::SQL_DISPLAY_NAME_LIKE, $sql );
        $this->assertStringContainsString( self::SQL_ORDER_BY_ID_ASC, $sql );
    }

    public function test_find_players_summary_text_search_sql_shape_and_escaping(): void {
        $wpdb = $this->create_recording_wpdb( [
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
        ] );

        $repo = new Player_Repository( $wpdb );
        $results = $repo->find_players_summary( [ 'name' => 'Smi_th' ] );

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

        $this->assertNotEmpty( $wpdb->prepares );
        $flat_args = ( isset( $wpdb->prepares[0]['args'][0] ) && is_array( $wpdb->prepares[0]['args'][0] ) )
            ? $wpdb->prepares[0]['args'][0]
            : $wpdb->prepares[0]['args'];
        $this->assertContains( '%Smi\_th%', $flat_args );
    }

    public function test_find_players_summary_numeric_search_filters_btm_without_gender(): void {
        $wpdb = $this->create_recording_wpdb( [
            (object) [
                'player_id'     => 202,
                'display_name'  => 'Numeric Player',
                'user_nicename' => 'numplayer',
                'email'         => 'num@example.com',
                'firstName'     => 'Num',
                'surname'       => 'Player',
                'btm'           => '88888',
                'gender'        => null,
            ],
        ] );

        $repo = new Player_Repository( $wpdb );
        $results = $repo->find_players_summary( [ 'name' => '88888' ] );

        $this->assertCount( 1, $results );
        $this->assertSame( 202, $results[0]->id );
        $this->assertSame( '88888', $results[0]->btm );
        $this->assertSame( '/player/numeric-player/88888/', $results[0]->link );

        $sql = $wpdb->queries[0];
        $this->assertStringContainsString( "meta_key = '" . Player_Repository::META_KEY_BTM . "' AND meta_value = '88888'", $sql );
        $this->assertStringNotContainsString( self::SQL_GENDER_FILTER, $sql );
    }

    public function test_find_players_summary_empty_search_applies_gender_filter_only(): void {
        $wpdb = $this->create_recording_wpdb();
        $repo = new Player_Repository( $wpdb );
        $results = $repo->find_players_summary();

        $this->assertSame( [], $results );
        $this->assertCount( 1, $wpdb->queries );
        $sql = $wpdb->queries[0];
        $this->assertStringContainsString( self::SQL_GENDER_FILTER, $sql );
        $this->assertStringNotContainsString( self::SQL_DISPLAY_NAME_LIKE, $sql );
    }

    public function test_find_players_summary_active_flag_adds_subquery(): void {
        $wpdb = $this->create_recording_wpdb();
        $repo = new Player_Repository( $wpdb );
        $results = $repo->find_players_summary( [ 'active' => true ] );

        $this->assertSame( [], $results );
        $this->assert_active_players_filter_applied( $wpdb->queries[0] );
    }

    public function test_find_players_summary_active_flag_overrides_name_and_gender_filters(): void {
        $wpdb = $this->create_recording_wpdb();
        $repo = new Player_Repository( $wpdb );
        $results = $repo->find_players_summary( [ 'active' => true, 'name' => 'John' ] );

        $this->assertSame( [], $results );
        $this->assert_active_players_filter_applied( $wpdb->queries[0] );
    }

    public function test_find_players_summary_group_by_maps_distinct_dto_records(): void {
        $wpdb = $this->create_recording_wpdb( [
            (object) [
                'player_id'     => 1,
                'display_name'  => 'Player One',
                'user_nicename' => 'player-one',
                'email'         => 'one@example.com',
                'firstName'     => 'Player',
                'surname'       => 'One',
                'btm'           => '11111',
                'gender'        => 'M',
            ],
            (object) [
                'player_id'     => 2,
                'display_name'  => 'Player Two',
                'user_nicename' => 'player-two',
                'email'         => 'two@example.com',
                'firstName'     => 'Player',
                'surname'       => 'Two',
                'btm'           => '22222',
                'gender'        => 'F',
            ],
        ] );

        $repo = new Player_Repository( $wpdb );
        $results = $repo->find_players_summary();

        $this->assertCount( 2, $results );
        $this->assertSame( 1, $results[0]->id );
        $this->assertSame( 2, $results[1]->id );
        $this->assertSame( 'Player One', $results[0]->display_name );
        $this->assertSame( 'Player Two', $results[1]->display_name );
    }

    public function test_find_players_summary_uses_cache(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;

        $wpdb = $this->create_recording_wpdb( [
            (object) [ 'player_id' => 1, 'display_name' => 'Cached Player' ],
        ] );

        $repo = new Player_Repository( $wpdb );
        $res1 = $repo->find_players_summary( [ 'name' => 'Cached' ] );
        $this->assertCount( 1, $res1 );
        $this->assertCount( 1, $wpdb->queries );

        // Second call should hit wp_cache
        $res2 = $repo->find_players_summary( [ 'name' => 'Cached' ] );
        $this->assertCount( 1, $res2 );
        $this->assertCount( 1, $wpdb->queries ); // Query count unchanged
    }
}
