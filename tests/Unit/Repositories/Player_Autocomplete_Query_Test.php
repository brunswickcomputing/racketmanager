<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Repositories\Player_Autocomplete_Query;
use wpdb;

final class Player_Autocomplete_Query_Test extends TestCase {
    public function test_execute_short_term_returns_empty_without_db_query(): void {
        $query_executed = false;
        $wpdb           = new class( $query_executed ) extends wpdb {
            public function __construct( private bool &$executed ) {}
            public function get_results( $query, $output = 'OBJECT' ) {
                $this->executed = true;
                return [];
            }
        };

        $query = new Player_Autocomplete_Query( $wpdb );

        $this->assertSame( [], $query->execute( '' ) );
        $this->assertSame( [], $query->execute( ' ' ) );
        $this->assertSame( [], $query->execute( 'a' ) );
        $this->assertSame( [], $query->execute( ' x ' ) );
        $this->assertFalse( $query_executed );
    }

    public function test_execute_name_search_builds_expected_query_and_formats_results(): void {
        $captured_query  = '';
        $captured_params = [];
        $wpdb            = new class( $captured_query, $captured_params ) extends wpdb {
            public $prefix   = 'wp_';
            public $users    = 'wp_users';
            public $usermeta = 'wp_usermeta';

            public function __construct( private mixed &$captured_q, private array &$captured_p ) {}

            public function prepare( $query, ...$args ) {
                $this->captured_p = ( 1 === count( $args ) && is_array( $args[0] ) ) ? $args[0] : $args;
                return parent::prepare( $query, ...$args );
            }

            public function get_results( $query, $output = 'OBJECT' ) {
                $this->captured_q = $query;
                return [
                    [
                        'id'         => '101',
                        'playerId'   => '1',
                        'name'       => 'John Smith',
                        'user_email' => 'john@example.com',
                        'club_id'    => '15',
                        'club'       => 'Westcliff',
                        'btm'        => '12345',
                        'contactno'  => '01234 567890',
                    ],
                ];
            }
        };

        $query  = new Player_Autocomplete_Query( $wpdb );
        $result = $query->execute( 'Smith', 'name' );

        $this->assertCount( 1, $result );
        $this->assertSame( 101, $result[0]['id'] );
        $this->assertSame( 1, $result[0]['playerId'] );
        $this->assertSame( 'John Smith', $result[0]['name'] );
        $this->assertSame( 'john@example.com', $result[0]['user_email'] );
        $this->assertSame( 15, $result[0]['club_id'] );
        $this->assertSame( 'Westcliff', $result[0]['club'] );
        $this->assertSame( '12345', $result[0]['btm'] );
        $this->assertSame( '01234 567890', $result[0]['contactno'] );

        $this->assertSame( [ '%Smith%', 100 ], $captured_params );
        $this->assertStringContainsString( "u.display_name LIKE '%Smith%'", $captured_query );
        $this->assertStringContainsString( 'r.removed_date IS NULL', $captured_query );
        $this->assertStringContainsString( 'r.system_record IS NULL', $captured_query );
        $this->assertStringContainsString( 'LIMIT 100', $captured_query );
        $this->assertStringContainsString( 'LEFT JOIN wp_usermeta um_btm', $captured_query );
    }

    public function test_execute_btm_search_uses_inner_join_and_exact_match(): void {
        $captured_query = '';
        $wpdb           = new class( $captured_query ) extends wpdb {
            public $prefix   = 'wp_';
            public $users    = 'wp_users';
            public $usermeta = 'wp_usermeta';

            public function __construct( private mixed &$captured ) {}

            public function get_results( $query, $output = 'OBJECT' ) {
                $this->captured = $query;
                return [
                    [
                        'id'         => '202',
                        'playerId'   => '42',
                        'name'       => 'Andy Murray',
                        'user_email' => 'andy@example.com',
                        'club_id'    => '12',
                        'club'       => 'PTC',
                        'btm'        => '987654',
                        'contactno'  => '07700900000',
                    ],
                ];
            }
        };

        $query  = new Player_Autocomplete_Query( $wpdb );
        $result = $query->execute( '987654', 'btm' );

        $this->assertCount( 1, $result );
        $this->assertSame( 202, $result[0]['id'] );
        $this->assertSame( 42, $result[0]['playerId'] );
        $this->assertSame( '987654', $result[0]['btm'] );

        $this->assertStringContainsString( "um_btm.meta_value = '987654'", $captured_query );
        $this->assertStringContainsString( 'INNER JOIN wp_usermeta um_btm', $captured_query );
    }

    public function test_execute_gender_and_club_filtering(): void {
        $captured_query  = '';
        $captured_params = [];
        $wpdb            = new class( $captured_query, $captured_params ) extends wpdb {
            public $prefix   = 'wp_';
            public $users    = 'wp_users';
            public $usermeta = 'wp_usermeta';

            public function __construct( private mixed &$captured_q, private array &$captured_p ) {}

            public function prepare( $query, ...$args ) {
                $this->captured_p = ( 1 === count( $args ) && is_array( $args[0] ) ) ? $args[0] : $args;
                return parent::prepare( $query, ...$args );
            }

            public function get_results( $query, $output = 'OBJECT' ) {
                $this->captured_q = $query;
                return [];
            }
        };

        $query = new Player_Autocomplete_Query( $wpdb );
        $query->execute(
            term: '556677',
            type: 'btm',
            gender: 'M',
            club_id: 88,
            limit: 25
        );

        $this->assertSame( [ 'gender', 'M', '556677', 88, 25 ], $captured_params );
        $this->assertStringContainsString( "INNER JOIN wp_usermeta um_gender ON u.ID = um_gender.user_id AND um_gender.meta_key = 'gender' AND um_gender.meta_value = 'M'", $captured_query );
        $this->assertStringContainsString( 'r.club_id = 88', $captured_query );
        $this->assertStringContainsString( 'LIMIT 25', $captured_query );
    }

    public function test_execute_clamps_limits(): void {
        $captured_query  = '';
        $captured_params = [];
        $wpdb            = new class( $captured_query, $captured_params ) extends wpdb {
            public $prefix   = 'wp_';
            public $users    = 'wp_users';
            public $usermeta = 'wp_usermeta';

            public function __construct( private mixed &$captured_q, private array &$captured_p ) {}

            public function prepare( $query, ...$args ) {
                $this->captured_p = ( 1 === count( $args ) && is_array( $args[0] ) ) ? $args[0] : $args;
                return parent::prepare( $query, ...$args );
            }

            public function get_results( $query, $output = 'OBJECT' ) {
                $this->captured_q = $query;
                return [];
            }
        };

        $query = new Player_Autocomplete_Query( $wpdb );

        // Upper limit clamped to 100
        $query->execute( 'test', limit: 500 );
        $this->assertSame( 100, $captured_params[ count( $captured_params ) - 1 ] );
        $this->assertMatchesRegularExpression( '/LIMIT 100\s*$/', $captured_query );

        // Lower limit clamped to 1
        $query->execute( 'test', limit: 0 );
        $this->assertSame( 1, $captured_params[ count( $captured_params ) - 1 ] );
        $this->assertMatchesRegularExpression( '/LIMIT 1\s*$/', $captured_query );

        // Negative limit clamped to 1
        $query->execute( 'test', limit: -50 );
        $this->assertSame( 1, $captured_params[ count( $captured_params ) - 1 ] );
        $this->assertMatchesRegularExpression( '/LIMIT 1\s*$/', $captured_query );
    }

    public function test_execute_sql_injection_safety(): void {
        $captured_query = '';
        $wpdb           = new class( $captured_query ) extends wpdb {
            public $prefix   = 'wp_';
            public $users    = 'wp_users';
            public $usermeta = 'wp_usermeta';

            public function __construct( private mixed &$captured ) {}

            public function get_results( $query, $output = 'OBJECT' ) {
                $this->captured = $query;
                return [];
            }
        };

        $query = new Player_Autocomplete_Query( $wpdb );
        $query->execute( "test%' OR '1'='1" );

        $this->assertStringContainsString( "test\\\\%\\' OR \\'1\\'=\\'1", $captured_query );
    }

    public function test_execute_formats_rows_and_normalizes_nullable_fields(): void {
        $wpdb = new class( 'test', 'test', 'test', 'localhost' ) extends wpdb {
            public $prefix   = 'wp_';
            public $users    = 'wp_users';
            public $usermeta = 'wp_usermeta';

            public function get_results( $query, $output = 'OBJECT' ) {
                return [
                    [
                        'id'         => '10',
                        'playerId'   => '20',
                        'name'       => 'Player One',
                        'user_email' => '',
                        'club_id'    => '30',
                        'club'       => '',
                        'btm'        => '',
                    ],
                    [
                        'id'         => null,
                        'playerId'   => null,
                        'name'       => null,
                        'user_email' => null,
                        'club_id'    => null,
                        'club'       => null,
                        'btm'        => null,
                        'contactno'  => null,
                    ],
                    [
                        'id'         => 12,
                        'playerId'   => 22,
                        'name'       => 'Player Three',
                        'user_email' => '0',
                        'club_id'    => 32,
                        'club'       => '0',
                        'btm'        => '0',
                        'contactno'  => '0123456789',
                    ],
                ];
            }
        };

        $query   = new Player_Autocomplete_Query( $wpdb );
        $results = $query->execute( 'Players' );

        $this->assertCount( 3, $results );
        $this->assertSame( 10, $results[0]['id'] );
        $this->assertSame( 20, $results[0]['playerId'] );
        $this->assertSame( 'Player One', $results[0]['name'] );
        $this->assertNull( $results[0]['user_email'] );
        $this->assertSame( 30, $results[0]['club_id'] );
        $this->assertNull( $results[0]['club'] );
        $this->assertNull( $results[0]['btm'] );
        $this->assertSame( '', $results[0]['contactno'] );

        $this->assertSame( 0, $results[1]['id'] );
        $this->assertSame( 0, $results[1]['playerId'] );
        $this->assertSame( '', $results[1]['name'] );
        $this->assertNull( $results[1]['user_email'] );
        $this->assertSame( 0, $results[1]['club_id'] );
        $this->assertNull( $results[1]['club'] );
        $this->assertNull( $results[1]['btm'] );
        $this->assertSame( '', $results[1]['contactno'] );

        $this->assertSame( '0', $results[2]['user_email'] );
        $this->assertSame( '0', $results[2]['club'] );
        $this->assertSame( '0', $results[2]['btm'] );
        $this->assertSame( '0123456789', $results[2]['contactno'] );
    }

    public function test_execute_escapes_like_wildcards(): void {
        $captured_query = '';
        $wpdb           = new class( $captured_query ) extends wpdb {
            public $prefix   = 'wp_';
            public $users    = 'wp_users';
            public $usermeta = 'wp_usermeta';

            public function __construct( private string &$captured ) {}

            public function get_results( $query, $output = 'OBJECT' ) {
                $this->captured = $query;
                return [];
            }
        };

        $query = new Player_Autocomplete_Query( $wpdb );
        $query->execute( 'test%with_wild\\cards' );

        $this->assertStringContainsString( 'test\\\\%with\\\\_wild', $captured_query );
        $this->assertStringContainsString( 'cards', $captured_query );
    }

    public function test_execute_handles_null_or_empty_database_results(): void {
        $wpdb = new class( 'test', 'test', 'test', 'localhost' ) extends wpdb {
            public $prefix   = 'wp_';
            public $users    = 'wp_users';
            public $usermeta = 'wp_usermeta';

            public function get_results( $query, $output = 'OBJECT' ) {
                return null;
            }
        };

        $query = new Player_Autocomplete_Query( $wpdb );
        $this->assertSame( [], $query->execute( 'Player' ) );
    }
}
