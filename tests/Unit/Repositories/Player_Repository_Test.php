<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

require_once dirname( __DIR__, 2 ) . '/wp-stubs.php';

final class Player_Repository_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_find_by_id_resolves_locked_user_name_from_get_userdata(): void {
        $wpdb = new class extends wpdb {
            public $prefix = 'wp_';
            public $base_prefix = 'wp_';
        };

        $GLOBALS['wp_stubs_userdata'][50] = (object) [
            'data'         => (object) [
                'ID'           => 50,
                'user_login'   => 'john.doe',
                'user_email'   => 'john@example.com',
                'display_name' => 'John Doe',
            ],
            'ID'           => 50,
            'user_login'   => 'john.doe',
            'user_email'   => 'john@example.com',
            'display_name' => 'John Doe',
        ];

        $GLOBALS['wp_stubs_user_meta'][50] = [
            'locked'      => [ '1' ],
            'locked_user' => [ '17' ],
            'locked_date' => [ '2026-01-01' ],
        ];

        $GLOBALS['wp_stubs_userdata'][17] = (object) [
            'ID'           => 17,
            'display_name' => 'Admin User 17',
        ];

        $repo   = new Player_Repository( $wpdb );
        $player = $repo->find_by_id( 50 );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertSame( 50, $player->get_id() );
        $this->assertSame( 'John Doe', $player->get_name() );
        $this->assertSame( 'Admin User 17', $player->locked_user_name );
    }

    public function test_find_registered_player_autocomplete_short_term_returns_empty(): void {
        $wpdb = new class extends wpdb {
            public $prefix = 'wp_';
            public $base_prefix = 'wp_';
        };

        $repo = new Player_Repository( $wpdb );

        $this->assertSame( [], $repo->find_registered_player_autocomplete( '' ) );
        $this->assertSame( [], $repo->find_registered_player_autocomplete( ' ' ) );
        $this->assertSame( [], $repo->find_registered_player_autocomplete( 'a' ) );
    }

    public function test_find_registered_player_autocomplete_name_search(): void {
        $captured_query = '';
        $wpdb = new class( $captured_query ) extends wpdb {
            public $prefix = 'wp_';
            public $base_prefix = 'wp_';
            public $users = 'wp_users';
            public $usermeta = 'wp_usermeta';
            public function __construct( private mixed &$captured ) {}
            public function get_results( $query, $output = 'OBJECT' ) {
                $this->captured = $query;
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

        $repo   = new Player_Repository( $wpdb );
        $result = $repo->find_registered_player_autocomplete( 'Smith', 'name' );

        $this->assertCount( 1, $result );
        $this->assertSame( 101, $result[0]['id'] );
        $this->assertSame( 1, $result[0]['playerId'] );
        $this->assertSame( 'John Smith', $result[0]['name'] );
        $this->assertSame( 'john@example.com', $result[0]['user_email'] );
        $this->assertSame( 15, $result[0]['club_id'] );
        $this->assertSame( 'Westcliff', $result[0]['club'] );
        $this->assertSame( '12345', $result[0]['btm'] );
        $this->assertSame( '01234 567890', $result[0]['contactno'] );

        $this->assertStringContainsString( "u.display_name LIKE '%Smith%'", $captured_query );
        $this->assertStringContainsString( 'r.removed_date IS NULL', $captured_query );
        $this->assertStringContainsString( 'r.system_record IS NULL', $captured_query );
        $this->assertStringContainsString( 'LIMIT 100', $captured_query );
    }

    public function test_find_registered_player_autocomplete_btm_search(): void {
        $captured_query = '';
        $wpdb = new class( $captured_query ) extends wpdb {
            public $prefix = 'wp_';
            public $base_prefix = 'wp_';
            public $users = 'wp_users';
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

        $repo   = new Player_Repository( $wpdb );
        $result = $repo->find_registered_player_autocomplete( '987654', 'btm' );

        $this->assertCount( 1, $result );
        $this->assertSame( 202, $result[0]['id'] );
        $this->assertSame( 42, $result[0]['playerId'] );
        $this->assertSame( '987654', $result[0]['btm'] );

        $this->assertStringContainsString( "um_btm.meta_value = '987654'", $captured_query );
        $this->assertStringContainsString( "INNER JOIN wp_usermeta um_btm", $captured_query );
    }

    public function test_find_registered_player_autocomplete_gender_and_club_filtering(): void {
        $captured_query = '';
        $wpdb = new class( $captured_query ) extends wpdb {
            public $prefix = 'wp_';
            public $base_prefix = 'wp_';
            public $users = 'wp_users';
            public $usermeta = 'wp_usermeta';
            public function __construct( private mixed &$captured ) {}
            public function get_results( $query, $output = 'OBJECT' ) {
                $this->captured = $query;
                return [];
            }
        };

        $repo = new Player_Repository( $wpdb );
        $repo->find_registered_player_autocomplete(
            term: 'jo',
            type: 'name',
            gender: 'F',
            club_id: 130,
            limit: 50
        );

        $this->assertStringContainsString( "INNER JOIN wp_usermeta um_gender ON u.ID = um_gender.user_id AND um_gender.meta_key = 'gender' AND um_gender.meta_value = 'F'", $captured_query );
        $this->assertStringContainsString( 'r.club_id = 130', $captured_query );
        $this->assertStringContainsString( 'LIMIT 50', $captured_query );
    }

    public function test_find_registered_player_autocomplete_sql_injection_safety(): void {
        $captured_query = '';
        $wpdb = new class( $captured_query ) extends wpdb {
            public $prefix = 'wp_';
            public $base_prefix = 'wp_';
            public $users = 'wp_users';
            public $usermeta = 'wp_usermeta';
            public function __construct( private mixed &$captured ) {}
            public function get_results( $query, $output = 'OBJECT' ) {
                $this->captured = $query;
                return [];
            }
        };

        $repo = new Player_Repository( $wpdb );
        $repo->find_registered_player_autocomplete( "test%' OR '1'='1" );

        // Verifies esc_like and prepare escaped wildcards and quotes
        $this->assertStringContainsString( "test\\\\%\\' OR \\'1\\'=\\'1", $captured_query );
    }

    public function test_find_registered_player_autocomplete_caps_limit_at_100(): void {
        $captured_query = '';
        $wpdb = new class( $captured_query ) extends wpdb {
            public $prefix = 'wp_';
            public $base_prefix = 'wp_';
            public $users = 'wp_users';
            public $usermeta = 'wp_usermeta';
            public function __construct( private mixed &$captured ) {}
            public function get_results( $query, $output = 'OBJECT' ) {
                $this->captured = $query;
                return [];
            }
        };

        $repo = new Player_Repository( $wpdb );
        $repo->find_registered_player_autocomplete( 'test', limit: 500 );

        $this->assertStringContainsString( 'LIMIT 100', $captured_query );
    }
}
