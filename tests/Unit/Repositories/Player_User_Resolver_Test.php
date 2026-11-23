<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Repositories\Player_User_Resolver;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use WP_User;

require_once dirname( __DIR__, 2 ) . '/wp-stubs.php';

final class Player_User_Resolver_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Player_User_Resolver $resolver;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->resolver = new Player_User_Resolver();
    }

    protected function tearDown(): void {
        unset(
            $GLOBALS['wp_stubs_userdata'],
            $GLOBALS['wp_stubs_users'],
            $GLOBALS['wp_stubs_users_not_found'],
            $GLOBALS['wp_stubs_get_users'],
        );
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_user_object( int $id, string $login, string $email, string $display_name ): WP_User {
        $user               = new WP_User( $id, $login );
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

    public function test_find_by_id_returns_userdata(): void {
        $expected = $this->create_user_object( 10, 'john.doe', 'john@example.com', 'John Doe' );
        $GLOBALS['wp_stubs_userdata'][10] = $expected;

        $result = $this->resolver->find_by_id( 10 );

        $this->assertSame( $expected, $result );
    }

    public function test_find_by_id_returns_false_or_null_when_not_found(): void {
        $GLOBALS['wp_stubs_users_not_found'][999] = true;

        $result = $this->resolver->find_by_id( 999 );

        $this->assertFalse( $result );
    }

    public function test_find_by_btm_queries_btm_meta_with_exact_match_and_returns_first_user(): void {
        $user1 = $this->create_user_object( 21, 'btm.one', 'btm1@example.com', 'BTM One' );
        $user2 = $this->create_user_object( 22, 'btm.two', 'btm2@example.com', 'BTM Two' );

        $captured_args                 = [];
        $GLOBALS['wp_stubs_get_users'] = function ( array $args ) use ( $user1, $user2, &$captured_args ): array {
            $captured_args = $args;
            return [ $user1, $user2 ];
        };

        $result = $this->resolver->find_by_btm( '12345678' );

        $this->assertSame( $user1, $result );
        $this->assertSame( 'btm', $captured_args['meta_key'] );
        $this->assertSame( '12345678', $captured_args['meta_value'] );
        $this->assertSame( '=', $captured_args['meta_compare'] );
    }

    public function test_find_by_btm_returns_false_when_no_users_found(): void {
        $captured_args                 = [];
        $GLOBALS['wp_stubs_get_users'] = function ( array $args ) use ( &$captured_args ): array {
            $captured_args = $args;
            return [];
        };

        $result = $this->resolver->find_by_btm( 'NONEXISTENT' );

        $this->assertFalse( $result );
        $this->assertSame( 'btm', $captured_args['meta_key'] );
        $this->assertSame( 'NONEXISTENT', $captured_args['meta_value'] );
        $this->assertSame( '=', $captured_args['meta_compare'] );
    }

    public function test_find_by_email_returns_user_from_get_user_by(): void {
        $user = $this->create_user_object( 30, 'email.user', 'user@example.com', 'Email User' );
        $GLOBALS['wp_stubs_users']['email']['user@example.com'] = $user;

        $result = $this->resolver->find_by_email( 'user@example.com' );

        $this->assertSame( $user, $result );
    }

    public function test_find_by_email_returns_false_when_not_found(): void {
        $result = $this->resolver->find_by_email( 'nonexistent@example.com' );

        $this->assertFalse( $result );
    }

    public function test_find_by_login_replaces_first_space_with_dot_when_no_dot_present(): void {
        $user = $this->create_user_object( 40, 'roger.federer', 'roger@example.com', 'Roger Federer' );
        $GLOBALS['wp_stubs_users']['login']['roger.federer'] = $user;

        $result = $this->resolver->find_by_login( 'Roger Federer' );

        $this->assertSame( $user, $result );
    }

    public function test_find_by_login_preserves_existing_dot_and_lowercases(): void {
        $user = $this->create_user_object( 41, 'novak.djokovic', 'novak@example.com', 'Novak Djokovic' );
        $GLOBALS['wp_stubs_users']['login']['novak.djokovic'] = $user;

        $result = $this->resolver->find_by_login( 'Novak.Djokovic' );

        $this->assertSame( $user, $result );
    }

    public function test_find_by_login_only_replaces_first_space(): void {
        $user = $this->create_user_object( 42, 'jane.mary smith', 'jane@example.com', 'Jane Mary Smith' );
        $GLOBALS['wp_stubs_users']['login']['jane.mary smith'] = $user;

        $result = $this->resolver->find_by_login( 'Jane Mary Smith' );

        $this->assertSame( $user, $result );
    }

    public function test_find_by_login_returns_false_when_not_found(): void {
        $result = $this->resolver->find_by_login( 'unknown.login' );

        $this->assertFalse( $result );
    }

    public function test_find_by_name_converts_spaces_to_hyphens_and_lowercases(): void {
        $user = $this->create_user_object( 50, 'rafael.nadal', 'rafa@example.com', 'Rafael Nadal' );
        $GLOBALS['wp_stubs_users']['slug']['rafael-nadal'] = $user;

        $result = $this->resolver->find_by_name( 'Rafael Nadal' );

        $this->assertSame( $user, $result );
    }

    public function test_find_by_name_converts_multiple_spaces_to_hyphens(): void {
        $user = $this->create_user_object( 51, 'mary.ann.jones', 'mary@example.com', 'Mary Ann Jones' );
        $GLOBALS['wp_stubs_users']['slug']['mary-ann-jones'] = $user;

        $result = $this->resolver->find_by_name( 'Mary Ann Jones' );

        $this->assertSame( $user, $result );
    }

    public function test_find_by_name_returns_false_when_not_found(): void {
        $result = $this->resolver->find_by_name( 'Nonexistent Name' );

        $this->assertFalse( $result );
    }

    public function test_resolve_delegates_to_find_by_id_by_default(): void {
        $user = $this->create_user_object( 60, 'user.sixty', 'sixty@example.com', 'User Sixty' );
        $GLOBALS['wp_stubs_userdata'][60] = $user;

        $this->assertSame( $user, $this->resolver->resolve( 60 ) );
        $this->assertSame( $user, $this->resolver->resolve( 60, 'id' ) );
        $this->assertSame( $user, $this->resolver->resolve( 60, 'unknown_type' ) );
    }

    public function test_resolve_delegates_to_find_by_btm(): void {
        $user          = $this->create_user_object( 61, 'user.sixtyone', 'sixtyone@example.com', 'User Sixty One' );
        $captured_args = [];
        $GLOBALS['wp_stubs_get_users'] = function ( array $args ) use ( $user, &$captured_args ): array {
            $captured_args = $args;
            return [ $user ];
        };

        $result = $this->resolver->resolve( '88888', 'btm' );

        $this->assertSame( $user, $result );
        $this->assertSame( 'btm', $captured_args['meta_key'] );
        $this->assertSame( '88888', $captured_args['meta_value'] );
        $this->assertSame( '=', $captured_args['meta_compare'] );
    }

    public function test_resolve_delegates_to_find_by_email(): void {
        $user = $this->create_user_object( 62, 'user.sixtytwo', 'sixtytwo@example.com', 'User Sixty Two' );
        $GLOBALS['wp_stubs_users']['email']['sixtytwo@example.com'] = $user;

        $result = $this->resolver->resolve( 'sixtytwo@example.com', 'email' );

        $this->assertSame( $user, $result );
    }

    public function test_resolve_delegates_to_find_by_login(): void {
        $user = $this->create_user_object( 63, 'user.sixtythree', 'sixtythree@example.com', 'User Sixty Three' );
        $GLOBALS['wp_stubs_users']['login']['user.sixtythree'] = $user;

        $result = $this->resolver->resolve( 'User Sixtythree', 'login' );

        $this->assertSame( $user, $result );
    }

    public function test_resolve_delegates_to_find_by_name(): void {
        $user = $this->create_user_object( 64, 'user.sixtyfour', 'sixtyfour@example.com', 'User Sixty Four' );
        $GLOBALS['wp_stubs_users']['slug']['user-sixtyfour'] = $user;

        $result = $this->resolver->resolve( 'User Sixtyfour', 'name' );

        $this->assertSame( $user, $result );
    }
}
