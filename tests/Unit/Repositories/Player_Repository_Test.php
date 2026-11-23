<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Player\Player_Factory;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Update_Exception;
use Racketmanager\Repositories\Player_Autocomplete_Query;
use Racketmanager\Repositories\Player_Metadata_Hydrator;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Repositories\Player_Summary_Query;
use Racketmanager\Repositories\Player_Titles_Query;
use Racketmanager\Repositories\Player_User_Resolver;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Player_Test_Factory;
use wpdb;

require_once dirname( __DIR__, 2 ) . '/wp-stubs.php';

final class Player_Repository_Test extends TestCase {
    use Manages_Wordpress_Globals;

    public function test_repository_method_count_and_visibility_limits(): void {
        $repository = new \ReflectionClass( Player_Repository::class );
        $methods    = array_filter(
            $repository->getMethods(),
            static fn( \ReflectionMethod $method ): bool => Player_Repository::class === $method->getDeclaringClass()->getName()
        );

        $this->assertLessThanOrEqual( 20, count( $methods ) );

        $private_methods = array_filter(
            $repository->getMethods( \ReflectionMethod::IS_PRIVATE ),
            static fn( \ReflectionMethod $method ): bool => Player_Repository::class === $method->getDeclaringClass()->getName()
        );
        $this->assertSame( [], array_values( $private_methods ) );

        $constructor = $repository->getConstructor();
        $this->assertNotNull( $constructor );
        foreach ( $constructor->getParameters() as $parameter ) {
            $this->assertTrue( $parameter->isOptional(), sprintf( 'Constructor parameter $%s must be optional.', $parameter->getName() ) );
        }
    }

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        unset(
            $GLOBALS['wp_stubs_userdata'],
            $GLOBALS['wp_stubs_users'],
            $GLOBALS['wp_stubs_users_not_found'],
            $GLOBALS['wp_stubs_get_users'],
            $GLOBALS['wp_stubs_update_user_meta'],
            $GLOBALS['wp_stubs_wp_update_user'],
            $GLOBALS['wp_stubs_wp_update_user_error'],
        );
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_find_by_id_resolves_locked_user_name_from_get_userdata(): void {
        $wpdb = new class( 'test', 'test', 'test', 'localhost' ) extends wpdb {
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
        $wpdb = new class( 'test', 'test', 'test', 'localhost' ) extends wpdb {
            public $prefix = 'wp_';
            public $base_prefix = 'wp_';
        };

        $repo = new Player_Repository( $wpdb );

        $this->assertSame( [], $repo->find_registered_player_autocomplete( '' ) );
        $this->assertSame( [], $repo->find_registered_player_autocomplete( ' ' ) );
        $this->assertSame( [], $repo->find_registered_player_autocomplete( 'a' ) );
    }

    public function test_find_registered_player_autocomplete_name_search(): void {
        $captured_query  = '';
        $captured_params = [];
        $wpdb            = new class( $captured_query, $captured_params ) extends wpdb {
            public $prefix      = 'wp_';
            public $base_prefix = 'wp_';
            public $users       = 'wp_users';
            public $usermeta    = 'wp_usermeta';
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

        $this->assertSame( [ '%Smith%', 100 ], $captured_params );
        $this->assertStringContainsString( "u.display_name LIKE '%Smith%'", $captured_query );
        $this->assertStringContainsString( 'r.removed_date IS NULL', $captured_query );
        $this->assertStringContainsString( 'r.system_record IS NULL', $captured_query );
        $this->assertStringContainsString( 'LIMIT 100', $captured_query );
        $this->assertStringContainsString( 'LEFT JOIN wp_usermeta um_btm', $captured_query );
        $this->assertStringNotContainsString( 'um_gender', $captured_query );
        $this->assertStringNotContainsString( 'AND r.club_id =', $captured_query );
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
        $this->assertStringContainsString( 'INNER JOIN wp_usermeta um_btm', $captured_query );
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

    public function test_find_registered_player_autocomplete_clamps_limit_at_minimum_one(): void {
        $captured_query  = '';
        $captured_params = [];
        $wpdb            = new class( $captured_query, $captured_params ) extends wpdb {
            public $prefix      = 'wp_';
            public $base_prefix = 'wp_';
            public $users       = 'wp_users';
            public $usermeta    = 'wp_usermeta';
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

        $repo = new Player_Repository( $wpdb );
        $repo->find_registered_player_autocomplete( 'test', limit: 0 );
        $this->assertSame( 1, $captured_params[ count( $captured_params ) - 1 ] );
        $this->assertMatchesRegularExpression( '/LIMIT 1\s*$/', $captured_query );

        $repo->find_registered_player_autocomplete( 'test', limit: -10 );
        $this->assertSame( 1, $captured_params[ count( $captured_params ) - 1 ] );
        $this->assertMatchesRegularExpression( '/LIMIT 1\s*$/', $captured_query );
    }

    public function test_find_registered_player_autocomplete_query_and_parameter_order_with_all_filters(): void {
        $captured_query  = '';
        $captured_params = [];
        $wpdb            = new class( $captured_query, $captured_params ) extends wpdb {
            public $prefix      = 'wp_';
            public $base_prefix = 'wp_';
            public $users       = 'wp_users';
            public $usermeta    = 'wp_usermeta';
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

        $repo = new Player_Repository( $wpdb );
        $repo->find_registered_player_autocomplete(
            term: '556677',
            type: 'btm',
            gender: 'M',
            club_id: 88,
            limit: 25
        );

        $this->assertSame( [ 'gender', 'M', '556677', 88, 25 ], $captured_params );
        $this->assertStringContainsString( "INNER JOIN wp_usermeta um_gender ON u.ID = um_gender.user_id AND um_gender.meta_key = 'gender' AND um_gender.meta_value = 'M'", $captured_query );
        $this->assertStringContainsString( "INNER JOIN wp_usermeta um_btm ON u.ID = um_btm.user_id AND um_btm.meta_key = 'btm'", $captured_query );
        $this->assertStringContainsString( "um_btm.meta_value = '556677'", $captured_query );
        $this->assertStringContainsString( 'r.club_id = 88', $captured_query );
        $this->assertStringContainsString( 'LIMIT 25', $captured_query );
    }

    public function test_find_registered_player_autocomplete_row_normalization_with_nullable_fields(): void {
        $wpdb = new class( 'test', 'test', 'test', 'localhost' ) extends wpdb {
            public $prefix = 'wp_';
            public $base_prefix = 'wp_';
            public $users = 'wp_users';
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
                        'id'         => '11',
                        'playerId'   => '21',
                        'name'       => 'Player Two',
                        'user_email' => null,
                        'club_id'    => '31',
                        'club'       => null,
                        'btm'        => null,
                        'contactno'  => null,
                    ],
                    [
                        'id'         => '12',
                        'playerId'   => '22',
                        'name'       => 'Player Three',
                        'user_email' => '0',
                        'club_id'    => '32',
                        'club'       => '0',
                        'btm'        => '0',
                        'contactno'  => '0123456789',
                    ],
                ];
            }
        };

        $repo   = new Player_Repository( $wpdb );
        $result = $repo->find_registered_player_autocomplete( 'Player' );

        $this->assertCount( 3, $result );

        // Row 1: empty strings normalized to null, missing contactno defaults to empty string.
        $this->assertSame( 10, $result[0]['id'] );
        $this->assertSame( 20, $result[0]['playerId'] );
        $this->assertSame( 'Player One', $result[0]['name'] );
        $this->assertNull( $result[0]['user_email'] );
        $this->assertSame( 30, $result[0]['club_id'] );
        $this->assertNull( $result[0]['club'] );
        $this->assertNull( $result[0]['btm'] );
        $this->assertSame( '', $result[0]['contactno'] );

        // Row 2: null values stay null, null contactno defaults to empty string.
        $this->assertSame( 11, $result[1]['id'] );
        $this->assertSame( 21, $result[1]['playerId'] );
        $this->assertSame( 'Player Two', $result[1]['name'] );
        $this->assertNull( $result[1]['user_email'] );
        $this->assertSame( 31, $result[1]['club_id'] );
        $this->assertNull( $result[1]['club'] );
        $this->assertNull( $result[1]['btm'] );
        $this->assertSame( '', $result[1]['contactno'] );

        // Row 3: '0' string preserved (strict comparison check).
        $this->assertSame( 12, $result[2]['id'] );
        $this->assertSame( 22, $result[2]['playerId'] );
        $this->assertSame( 'Player Three', $result[2]['name'] );
        $this->assertSame( '0', $result[2]['user_email'] );
        $this->assertSame( 32, $result[2]['club_id'] );
        $this->assertSame( '0', $result[2]['club'] );
        $this->assertSame( '0', $result[2]['btm'] );
        $this->assertSame( '0123456789', $result[2]['contactno'] );
    }

    public function test_find_registered_player_autocomplete_handles_null_results(): void {
        $wpdb = new class( 'test', 'test', 'test', 'localhost' ) extends wpdb {
            public $prefix = 'wp_';
            public $base_prefix = 'wp_';
            public $users = 'wp_users';
            public $usermeta = 'wp_usermeta';
            public function get_results( $query, $output = 'OBJECT' ) {
                return null;
            }
        };

        $repo   = new Player_Repository( $wpdb );
        $result = $repo->find_registered_player_autocomplete( 'test' );

        $this->assertSame( [], $result );
    }

    private function create_repository(): Player_Repository {
        $wpdb = new class( 'test', 'test', 'test', 'localhost' ) extends wpdb {
            public $prefix      = 'wp_';
            public $base_prefix = 'wp_';
        };

        return new Player_Repository( $wpdb );
    }

    private function create_user_object( int $id, string $login = 'john.doe', string $email = 'john@example.com', string $display_name = 'John Doe' ): \WP_User {
        $user               = new \WP_User( $id, $login );
        $user->user_email   = $email;
        $user->display_name = $display_name;
        $user->data         = (object) [
            'ID'           => $id,
            'user_login'   => $login,
            'user_email'   => $email,
            'display_name' => $display_name,
        ];

        return $user;
    }

    public function test_find_throws_exception_when_id_is_empty_string(): void {
        $repo = $this->create_repository();

        $this->expectException( Player_Not_Found_Exception::class );
        $this->expectExceptionMessage( 'Player ID cannot be empty.' );

        $repo->find( '' );
    }

    public function test_find_throws_exception_when_id_is_zero(): void {
        $repo = $this->create_repository();

        $this->expectException( Player_Not_Found_Exception::class );
        $this->expectExceptionMessage( 'Player ID cannot be empty.' );

        $repo->find( 0 );
    }

    public function test_find_returns_cached_player_without_querying_user(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']         = [];

        $cached_player = Player_Factory::from_object(
            (object) [
                'ID'           => 42,
                'display_name' => 'Cached Player',
                'user_email'   => 'cached@example.com',
            ]
        );
        $GLOBALS['wp_stubs_cache']['players']['42'] = $cached_player;

        $GLOBALS['wp_stubs_users_not_found'][42] = true;

        $repo   = $this->create_repository();
        $player = $repo->find( 42 );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertSame( 42, $player->get_id() );
        $this->assertSame( 'Cached Player', $player->get_name() );
    }

    public function test_find_caches_hydrated_player_on_cache_miss(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']         = [];

        $GLOBALS['wp_stubs_userdata'][42]  = $this->create_user_object( 42, 'alex.smith', 'alex@example.com', 'Alex Smith' );
        $GLOBALS['wp_stubs_user_meta'][42] = [
            'first_name' => [ 'Alex' ],
            'last_name'  => [ 'Smith' ],
        ];

        $repo   = $this->create_repository();
        $player = $repo->find( 42 );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertSame( 42, $player->get_id() );
        $this->assertSame( 'Alex', $player->firstname );
        $this->assertSame( 'Smith', $player->surname );

        $this->assertArrayHasKey( 'players', $GLOBALS['wp_stubs_cache'] );
        $this->assertArrayHasKey( '42', $GLOBALS['wp_stubs_cache']['players'] );
        $cached = $GLOBALS['wp_stubs_cache']['players']['42'];
        $this->assertInstanceOf( Player::class, $cached );
        $this->assertSame( 42, $cached->get_id() );
    }

    public function test_find_returns_null_and_does_not_cache_when_user_not_found(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']         = [];

        $GLOBALS['wp_stubs_users_not_found'][999] = true;

        $repo   = $this->create_repository();
        $player = $repo->find( 999 );

        $this->assertNull( $player );
        $this->assertArrayNotHasKey( '999', $GLOBALS['wp_stubs_cache']['players'] ?? [] );
    }

    public function test_find_by_search_type_default_id(): void {
        $GLOBALS['wp_stubs_userdata'][51] = $this->create_user_object( 51, 'carlos.alcaraz', 'carlos@example.com', 'Carlos Alcaraz' );

        $repo   = $this->create_repository();
        $player = $repo->find( 51, 'id' );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertSame( 51, $player->get_id() );
        $this->assertSame( 'Carlos Alcaraz', $player->get_name() );
    }

    public function test_find_by_search_type_btm(): void {
        $user = $this->create_user_object( 60, 'btm.user', 'btm@example.com', 'BTM User' );
        $GLOBALS['wp_stubs_get_users'] = static function ( array $args ) use ( $user ): array {
            if ( isset( $args['meta_key'], $args['meta_value'] ) && 'btm' === $args['meta_key'] && 'BTM7788' === $args['meta_value'] ) {
                return [ $user ];
            }
            return [];
        };

        $repo   = $this->create_repository();
        $player = $repo->find( 'BTM7788', 'btm' );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertSame( 60, $player->get_id() );
        $this->assertSame( 'BTM User', $player->get_name() );
    }

    public function test_find_by_search_type_email(): void {
        $user = $this->create_user_object( 61, 'email.user', 'member@example.com', 'Member User' );
        $GLOBALS['wp_stubs_users']['email']['member@example.com'] = $user;

        $repo   = $this->create_repository();
        $player = $repo->find( 'member@example.com', 'email' );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertSame( 61, $player->get_id() );
        $this->assertSame( 'Member User', $player->get_name() );
    }

    public function test_find_by_search_type_login_with_space_normalized(): void {
        $user = $this->create_user_object( 62, 'roger.federer', 'roger@example.com', 'Roger Federer' );
        $GLOBALS['wp_stubs_users']['login']['roger.federer'] = $user;

        $repo   = $this->create_repository();
        $player = $repo->find( 'Roger Federer', 'login' );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertSame( 62, $player->get_id() );
        $this->assertSame( 'Roger Federer', $player->get_name() );
    }

    public function test_find_by_search_type_name_with_slug_conversion(): void {
        $user = $this->create_user_object( 63, 'rafael.nadal', 'rafa@example.com', 'Rafael Nadal' );
        $GLOBALS['wp_stubs_users']['slug']['rafael-nadal'] = $user;

        $repo   = $this->create_repository();
        $player = $repo->find( 'Rafael Nadal', 'name' );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertSame( 63, $player->get_id() );
        $this->assertSame( 'Rafael Nadal', $player->get_name() );
    }

    public function test_find_hydrates_removal_metadata_when_unremoved(): void {
        $GLOBALS['wp_stubs_userdata'][80] = $this->create_user_object( 80, 'active.player', 'active@example.com', 'Active Player' );
        $GLOBALS['wp_stubs_user_meta'][80] = [];

        $repo   = $this->create_repository();
        $player = $repo->find( 80 );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertNull( $player->removed_date );
        $this->assertNull( $player->removed_user );
    }

    public function test_find_hydrates_removal_metadata_without_user_id(): void {
        $GLOBALS['wp_stubs_userdata'][81] = $this->create_user_object( 81, 'removed.player', 'removed@example.com', 'Removed Player' );
        $GLOBALS['wp_stubs_user_meta'][81] = [
            'remove_date' => [ '2026-03-01' ],
        ];

        $repo   = $this->create_repository();
        $player = $repo->find( 81 );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertSame( '2026-03-01', $player->removed_date );
        $this->assertNull( $player->removed_user );
    }

    public function test_find_hydrates_removal_metadata_with_user_id(): void {
        $GLOBALS['wp_stubs_userdata'][82] = $this->create_user_object( 82, 'removed.player2', 'removed2@example.com', 'Removed Player 2' );
        $GLOBALS['wp_stubs_user_meta'][82] = [
            'remove_date' => [ '2026-03-01' ],
            'remove_user' => [ '15' ],
        ];

        $repo   = $this->create_repository();
        $player = $repo->find( 82 );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertSame( '2026-03-01', $player->removed_date );
        $this->assertSame( 15, $player->removed_user );
    }

    public function test_find_hydrates_locked_metadata_when_unlocked(): void {
        $GLOBALS['wp_stubs_userdata'][90] = $this->create_user_object( 90, 'unlocked.player', 'unlocked@example.com', 'Unlocked Player' );
        $GLOBALS['wp_stubs_user_meta'][90] = [];

        $repo   = $this->create_repository();
        $player = $repo->find( 90 );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertFalse( (bool) $player->locked );
        $this->assertNull( $player->locked_date );
        $this->assertNull( $player->locked_user );
        $this->assertSame( '', $player->locked_user_name );
    }

    public function test_find_hydrates_locked_metadata_when_locked_without_user(): void {
        $GLOBALS['wp_stubs_userdata'][91] = $this->create_user_object( 91, 'locked.player1', 'locked1@example.com', 'Locked Player 1' );
        $GLOBALS['wp_stubs_user_meta'][91] = [
            'locked'      => [ '1' ],
            'locked_date' => [ '2026-04-10' ],
        ];

        $repo   = $this->create_repository();
        $player = $repo->find( 91 );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertTrue( (bool) $player->locked );
        $this->assertSame( '2026-04-10', $player->locked_date );
        $this->assertSame( '', $player->locked_user );
        $this->assertSame( '', $player->locked_user_name );
    }

    public function test_find_hydrates_locked_metadata_when_locked_with_valid_user(): void {
        $GLOBALS['wp_stubs_userdata'][92] = $this->create_user_object( 92, 'locked.player2', 'locked2@example.com', 'Locked Player 2' );
        $GLOBALS['wp_stubs_user_meta'][92] = [
            'locked'      => [ '1' ],
            'locked_date' => [ '2026-04-10' ],
            'locked_user' => [ '20' ],
        ];

        $GLOBALS['wp_stubs_userdata'][20] = (object) [
            'ID'           => 20,
            'display_name' => 'Admin Boss',
        ];

        $repo   = $this->create_repository();
        $player = $repo->find( 92 );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertTrue( (bool) $player->locked );
        $this->assertSame( '2026-04-10', $player->locked_date );
        $this->assertSame( '20', $player->locked_user );
        $this->assertSame( 'Admin Boss', $player->locked_user_name );
    }

    public function test_find_hydrates_locked_metadata_when_locked_with_missing_user(): void {
        $GLOBALS['wp_stubs_userdata'][93] = $this->create_user_object( 93, 'locked.player3', 'locked3@example.com', 'Locked Player 3' );
        $GLOBALS['wp_stubs_user_meta'][93] = [
            'locked'      => [ '1' ],
            'locked_date' => [ '2026-04-10' ],
            'locked_user' => [ '999' ],
        ];
        $GLOBALS['wp_stubs_users_not_found'][999] = true;

        $repo   = $this->create_repository();
        $player = $repo->find( 93 );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertTrue( (bool) $player->locked );
        $this->assertSame( '2026-04-10', $player->locked_date );
        $this->assertSame( '999', $player->locked_user );
        $this->assertSame( '', $player->locked_user_name );
    }

    public function test_find_hydrates_basic_metadata_wtn_and_opt_ins(): void {
        $GLOBALS['wp_stubs_userdata'][70] = $this->create_user_object( 70, 'serena.williams', 'serena@example.com', 'Serena Williams' );
        $GLOBALS['wp_stubs_user_meta'][70] = [
            'first_name'           => [ 'Serena' ],
            'last_name'            => [ 'Williams' ],
            'gender'               => [ 'F' ],
            'racketmanager_type'   => [ 'senior' ],
            'btm'                  => [ '112233' ],
            'year_of_birth'        => [ '1981' ],
            'contactno'            => [ '07123456789' ],
            'wtn_S'                => [ '15.4' ],
            'wtn_D'                => [ '14.2' ],
            'racketmanager_opt_in' => [ [ 'email', 'sms' ] ],
        ];

        $repo   = $this->create_repository();
        $player = $repo->find( 70 );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertSame( 70, $player->get_id() );
        $this->assertSame( 'Serena', $player->firstname );
        $this->assertSame( 'Williams', $player->surname );
        $this->assertSame( 'F', $player->gender );
        $this->assertSame( 'senior', $player->type );
        $this->assertSame( '112233', $player->btm );
        $this->assertSame( 1981, $player->year_of_birth );
        $this->assertSame( '07123456789', $player->contactno );
        $this->assertSame( 'senior', $player->system_record );
        $this->assertSame( [ 'S' => '15.4', 'D' => '14.2' ], $player->wtn );
        $this->assertSame( [ 'email', 'sms' ], $player->opt_ins );
    }

    public function test_find_falls_back_to_null_for_empty_optional_fields(): void {
        $GLOBALS['wp_stubs_userdata'][71] = $this->create_user_object( 71, 'venus.williams', 'venus@example.com', 'Venus Williams' );
        $GLOBALS['wp_stubs_user_meta'][71] = [
            'first_name'    => [ 'Venus' ],
            'last_name'     => [ 'Williams' ],
            'year_of_birth' => [ '' ],
            'contactno'     => [ '' ],
        ];

        $repo   = $this->create_repository();
        $player = $repo->find( 71 );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertNull( $player->year_of_birth );
        $this->assertNull( $player->contactno );
    }

    public function test_find_unremoved_player_ignores_stale_remove_user_meta(): void {
        $GLOBALS['wp_stubs_userdata'][83]  = $this->create_user_object( 83, 'unremoved.player', 'unremoved@example.com', 'Unremoved Player' );
        $GLOBALS['wp_stubs_user_meta'][83] = [
            'remove_date' => [ '' ],
            'remove_user' => [ '99' ],
        ];

        $repo   = $this->create_repository();
        $player = $repo->find( 83 );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertNull( $player->removed_date );
        $this->assertNull( $player->removed_user );
    }

    public function test_find_unlocked_player_ignores_stale_lock_metadata(): void {
        $GLOBALS['wp_stubs_userdata'][94]  = $this->create_user_object( 94, 'unlocked.stale', 'stale@example.com', 'Unlocked Stale' );
        $GLOBALS['wp_stubs_user_meta'][94] = [
            'locked'      => [ '0' ],
            'locked_date' => [ '2025-01-01' ],
            'locked_user' => [ '20' ],
        ];

        $repo   = $this->create_repository();
        $player = $repo->find( 94 );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertFalse( (bool) $player->locked );
        $this->assertNull( $player->locked_date );
        $this->assertNull( $player->locked_user );
        $this->assertSame( '', $player->locked_user_name );
    }

    public function test_find_by_search_type_fallback_to_get_userdata_for_unknown_type(): void {
        $GLOBALS['wp_stubs_userdata'][52] = $this->create_user_object( 52, 'novak.djokovic', 'novak@example.com', 'Novak Djokovic' );

        $repo   = $this->create_repository();
        $player = $repo->find( 52, 'custom_unknown_type' );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertSame( 52, $player->get_id() );
        $this->assertSame( 'Novak Djokovic', $player->get_name() );
    }

    public function test_find_by_search_types_return_null_when_user_not_found(): void {
        $repo = $this->create_repository();

        $this->assertNull( $repo->find( 'NONEXISTENT_BTM', 'btm' ) );
        $this->assertNull( $repo->find( 'nonexistent@example.com', 'email' ) );
        $this->assertNull( $repo->find( 'nonexistent.login', 'login' ) );
        $this->assertNull( $repo->find( 'Nonexistent Name', 'name' ) );
    }

    public function test_find_preserves_search_type_cache_key_behavior(): void {
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']         = [];

        $user = $this->create_user_object( 65, 'cached.email', 'cached_email@example.com', 'Cached By Email' );
        $GLOBALS['wp_stubs_users']['email']['cached_email@example.com'] = $user;

        $repo   = $this->create_repository();
        $player = $repo->find( 'cached_email@example.com', 'email' );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertArrayHasKey( 'cached_email@example.com', $GLOBALS['wp_stubs_cache']['players'] );

        // Remove user from lookup map; subsequent find should still succeed via cache
        unset( $GLOBALS['wp_stubs_users']['email']['cached_email@example.com'] );
        $cached_retrieval = $repo->find( 'cached_email@example.com', 'email' );

        $this->assertInstanceOf( Player::class, $cached_retrieval );
        $this->assertSame( 65, $cached_retrieval->get_id() );
    }

    public function test_find_delegates_to_injected_metadata_hydrator(): void {
        $GLOBALS['wp_stubs_userdata'][88] = $this->create_user_object( 88, 'custom.hydrator', 'hydrator@example.com', 'Custom Hydrator Player' );

        $hydrator_mock = $this->createMock( Player_Metadata_Hydrator::class );
        $hydrator_mock->expects( $this->once() )
            ->method( 'hydrate' )
            ->with(
                $this->callback(
                    function ( $data ) {
                        $data->firstname = 'CustomFirst';
                        $data->surname   = 'CustomLast';
                        return 88 === $data->ID;
                    }
                )
            );

        $wpdb = new class( 'test', 'test', 'test', 'localhost' ) extends wpdb {
            public $prefix = 'wp_';
            public $base_prefix = 'wp_';
        };

        $repo   = new Player_Repository( $wpdb, $hydrator_mock );
        $player = $repo->find( 88 );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertSame( 88, $player->get_id() );
        $this->assertSame( 'CustomFirst', $player->firstname );
        $this->assertSame( 'CustomLast', $player->surname );
    }

    public function test_find_registered_player_autocomplete_delegates_to_injected_query(): void {
        $expected_result = [
            [
                'id'         => 999,
                'playerId'   => 888,
                'name'       => 'Mock Autocomplete Player',
                'user_email' => 'mock@example.com',
                'club_id'    => 77,
                'club'       => 'Mock Club',
                'btm'        => '112233',
                'contactno'  => '01234 000000',
            ],
        ];

        $autocomplete_mock = $this->createMock( Player_Autocomplete_Query::class );
        $autocomplete_mock->expects( $this->once() )
            ->method( 'execute' )
            ->with( 'Mock', 'name', 'M', 77, 10 )
            ->willReturn( $expected_result );

        $wpdb = new class( 'test', 'test', 'test', 'localhost' ) extends wpdb {
            public $prefix      = 'wp_';
            public $base_prefix = 'wp_';
        };

        $repo   = new Player_Repository( db: $wpdb, autocomplete_query: $autocomplete_mock );
        $result = $repo->find_registered_player_autocomplete( 'Mock', 'name', 'M', 77, 10 );

        $this->assertSame( $expected_result, $result );
    }

    public function test_find_delegates_to_injected_user_resolver(): void {
        $user_resolver_mock = $this->createMock( Player_User_Resolver::class );
        $user_resolver_mock->expects( $this->once() )
            ->method( 'resolve' )
            ->with( 'custom_btm_val', 'btm' )
            ->willReturn( $this->create_user_object( 77, 'btm.player', 'btm@example.com', 'BTM Player' ) );

        $wpdb = new class( 'test', 'test', 'test', 'localhost' ) extends wpdb {
            public $prefix      = 'wp_';
            public $base_prefix = 'wp_';
        };

        $repo   = new Player_Repository( db: $wpdb, user_resolver: $user_resolver_mock );
        $player = $repo->find( 'custom_btm_val', 'btm' );

        $this->assertInstanceOf( Player::class, $player );
        $this->assertSame( 77, $player->get_id() );
        $this->assertSame( 'BTM Player', $player->get_name() );
    }

    public function test_find_by_btm_delegates_to_injected_user_resolver(): void {
        $expected_user = $this->create_user_object( 12, 'btm.user', 'btm12@example.com', 'BTM User' );

        $user_resolver_mock = $this->createMock( Player_User_Resolver::class );
        $user_resolver_mock->expects( $this->once() )
            ->method( 'find_by_btm' )
            ->with( '998877' )
            ->willReturn( $expected_user );

        $wpdb = new class( 'test', 'test', 'test', 'localhost' ) extends wpdb {
            public $prefix      = 'wp_';
            public $base_prefix = 'wp_';
        };

        $repo = new Player_Repository( db: $wpdb, user_resolver: $user_resolver_mock );

        $this->assertSame( $expected_user, $repo->find_by_btm( '998877' ) );
    }

    public function test_find_by_email_delegates_to_injected_user_resolver(): void {
        $expected_user = $this->create_user_object( 14, 'email.user', 'user14@example.com', 'Email User' );

        $user_resolver_mock = $this->createMock( Player_User_Resolver::class );
        $user_resolver_mock->expects( $this->once() )
            ->method( 'find_by_email' )
            ->with( 'user14@example.com' )
            ->willReturn( $expected_user );

        $wpdb = new class( 'test', 'test', 'test', 'localhost' ) extends wpdb {
            public $prefix      = 'wp_';
            public $base_prefix = 'wp_';
        };

        $repo = new Player_Repository( db: $wpdb, user_resolver: $user_resolver_mock );

        $this->assertSame( $expected_user, $repo->find_by_email( 'user14@example.com' ) );
    }

    public function test_find_players_summary_delegates_to_injected_summary_query(): void {
        $expected_results = [
            new \Racketmanager\Domain\DTO\Player\Players_List_DTO(
                (object) [
                    'player_id'    => 10,
                    'display_name' => 'Test Player',
                ]
            ),
        ];

        $summary_query_mock = $this->createMock( Player_Summary_Query::class );
        $summary_query_mock->expects( $this->once() )
            ->method( 'execute' )
            ->with( [ 'active' => true ] )
            ->willReturn( $expected_results );

        $wpdb = new class( 'test', 'test', 'test', 'localhost' ) extends wpdb {
            public $prefix      = 'wp_';
            public $base_prefix = 'wp_';
        };

        $repo = new Player_Repository( db: $wpdb, summary_query: $summary_query_mock );

        $this->assertSame( $expected_results, $repo->find_players_summary( [ 'active' => true ] ) );
    }

    public function test_get_titles_delegates_to_injected_titles_query(): void {
        $expected_titles = [
            '2024' => [
                'Championship' => [ (object) [ 'final' => 'final' ] ],
            ],
        ];

        $titles_query_mock = $this->createMock( Player_Titles_Query::class );
        $titles_query_mock->expects( $this->once() )
            ->method( 'execute' )
            ->with( 42 )
            ->willReturn( $expected_titles );

        $wpdb = new class( 'test', 'test', 'test', 'localhost' ) extends wpdb {
            public $prefix      = 'wp_';
            public $base_prefix = 'wp_';
        };

        $repo = new Player_Repository( db: $wpdb, titles_query: $titles_query_mock );

        $this->assertSame( $expected_titles, $repo->get_titles( 42 ) );
    }

    public function test_add_creates_user_and_saves_gender_btm_contactno_and_year_of_birth(): void {
        $GLOBALS['wp_stubs_update_user_meta']   = [];
        $GLOBALS['wp_stubs_insert_user_return'] = 77;
        $player                                 = Player_Test_Factory::create(
            btm: '12345678',
            overrides: [ 'contactno' => '01234567890', 'year_of_birth' => 1990 ]
        );

        $repo = $this->create_repository();

        $this->assertSame( 77, $repo->add( $player ) );
        $this->assertSame( 77, $player->get_id() );

        $writes = $this->recorded_user_meta_writes();
        $this->assertSame( [ 'user_id' => 77, 'value' => 'M' ], $writes['gender'] );
        $this->assertSame( [ 'user_id' => 77, 'value' => '12345678' ], $writes['btm'] );
        $this->assertSame( [ 'user_id' => 77, 'value' => '01234567890' ], $writes['contactno'] );
        $this->assertSame( [ 'user_id' => 77, 'value' => '1990' ], $writes['year_of_birth'] );
    }

    public function test_add_skips_btm_and_contactno_meta_when_empty(): void {
        $GLOBALS['wp_stubs_update_user_meta']   = [];
        $GLOBALS['wp_stubs_insert_user_return'] = 78;
        $player                                 = Player_Test_Factory::create(
            btm: '',
            overrides: [ 'contactno' => '' ]
        );

        $repo = $this->create_repository();

        $this->assertSame( 78, $repo->add( $player ) );

        $writes = $this->recorded_user_meta_writes();
        $this->assertArrayHasKey( 'gender', $writes );
        $this->assertArrayNotHasKey( 'btm', $writes );
        $this->assertArrayNotHasKey( 'contactno', $writes );
    }

    public function test_add_returns_false_and_writes_no_meta_when_wp_insert_user_fails(): void {
        $GLOBALS['wp_stubs_update_user_meta']  = [];
        $GLOBALS['wp_stubs_insert_user_error'] = new \WP_Error( 'existing_user_login', 'Login already exists' );

        $repo = $this->create_repository();

        $this->assertFalse( $repo->add( Player_Test_Factory::create() ) );
        $this->assertSame( [], $GLOBALS['wp_stubs_update_user_meta'] );
    }

    public function test_update_writes_only_requested_btm_and_contactno_meta_and_clears_players_cache(): void {
        $GLOBALS['wp_stubs_update_user_meta']  = [];
        $GLOBALS['wp_stubs_wp_update_user']    = [];
        $GLOBALS['wp_stubs_cache_deleted']     = [];
        $GLOBALS['wp_stubs_cache_enabled']     = true;
        $GLOBALS['wp_stubs_cache']             = [ 'players' => [ '42' => 'stale' ] ];
        $player                                = Player_Test_Factory::create(
            id: 42,
            btm: '87654321',
            overrides: [ 'contactno' => '07700900123' ]
        );

        $repo = $this->create_repository();

        $this->assertTrue( $repo->update( $player, [ 'btm' => true, 'contactno' => true ] ) );

        $writes = $this->recorded_user_meta_writes();
        $this->assertSame( [ 'btm', 'contactno' ], array_keys( $writes ) );
        $this->assertSame( [ 'user_id' => 42, 'value' => '87654321' ], $writes['btm'] );
        $this->assertSame( [ 'user_id' => 42, 'value' => '07700900123' ], $writes['contactno'] );
        $this->assertSame( [], $GLOBALS['wp_stubs_wp_update_user'] );
        $this->assertContains( [ 42, 'players' ], $GLOBALS['wp_stubs_cache_deleted'] );
        $this->assertArrayNotHasKey( '42', $GLOBALS['wp_stubs_cache']['players'] );
    }

    public function test_update_core_success_updates_wp_user_and_clears_players_cache(): void {
        $GLOBALS['wp_stubs_update_user_meta'] = [];
        $GLOBALS['wp_stubs_wp_update_user']   = [];
        $GLOBALS['wp_stubs_cache_enabled']    = true;
        $GLOBALS['wp_stubs_cache']            = [ 'players' => [ '42' => 'stale' ] ];
        $player                               = Player_Test_Factory::create(
            id: 42,
            display_name: 'Alex Smith',
            firstname: 'Alex',
            surname: 'Smith',
            email: 'alex@example.test'
        );

        $repo = $this->create_repository();

        $this->assertTrue( $repo->update( $player, [ 'core' => true ] ) );

        $this->assertCount( 1, $GLOBALS['wp_stubs_wp_update_user'] );
        $this->assertSame( 42, $GLOBALS['wp_stubs_wp_update_user'][0]['ID'] );
        $this->assertSame( 'alex@example.test', $GLOBALS['wp_stubs_wp_update_user'][0]['user_email'] );
        $this->assertArrayNotHasKey( '42', $GLOBALS['wp_stubs_cache']['players'] );
    }

    public function test_update_throws_player_update_exception_when_core_user_update_fails(): void {
        $GLOBALS['wp_stubs_wp_update_user_error'] = 'Email address is invalid';
        $player                                   = Player_Test_Factory::create( id: 42 );

        $repo = $this->create_repository();

        $this->expectException( Player_Update_Exception::class );
        $this->expectExceptionMessage( 'Email address is invalid' );

        $repo->update( $player, [ 'core' => true ] );
    }

    /**
     * Collect the last recorded update_user_meta write per meta key.
     *
     * @return array<string, array{user_id: int, value: mixed}>
     */
    private function recorded_user_meta_writes(): array {
        $writes = [];
        foreach ( $GLOBALS['wp_stubs_update_user_meta'] ?? [] as [ $user_id, $meta_key, $meta_value ] ) {
            $writes[ $meta_key ] = [
                'user_id' => $user_id,
                'value'   => $meta_value,
            ];
        }

        return $writes;
    }
}
