<?php
/**
 * Sydney child theme header cart integration tests.
 *
 * @package RacketManager
 * @subpackage Tests/Integration
 */

declare( strict_types=1 );

namespace Racketmanager\Tests\Integration;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Player\User;
use Racketmanager\Public_Api;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

#[AllowMockObjectsWithoutExpectations]
final class Sydney_Child_Header_Cart_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        Public_Api::reset();
    }

    protected function tearDown(): void {
        Public_Api::reset();
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_header_cart_logged_out(): void {
        $this->load_theme_functions();
        $GLOBALS['wp_stubs_is_user_logged_in'] = false;

        ob_start();
        \sydney_woocommerce_header_cart();
        $output = ob_get_clean();

        self::assertIsString( $output );
        self::assertStringContainsString( 'id="loginrow"', $output );
        self::assertStringContainsString( 'Login', $output );
    }

    public function test_header_cart_logged_in_with_encoded_btm_and_messages(): void {
        $this->load_theme_functions();

        $user               = new User();
        $user->id           = 7;
        $user->ID           = 7;
        $user->display_name = 'Roger Federer';
        $user->btm          = 'AB 12/3&x';

        $user_repo = $this->createMock( User_Repository_Interface::class );
        $user_repo->expects( self::once() )
            ->method( 'find' )
            ->with( 7 )
            ->willReturn( $user );

        $message_repo = $this->createMock( Message_Repository_Interface::class );
        $message_repo->expects( self::once() )
            ->method( 'find_by_user' )
            ->with( 7, [ 'count' => true, 'status' => 'unread' ] )
            ->willReturn( 5 );

        $container = new Simple_Container();
        $container->set( 'user_repository', $user_repo );
        $container->set( 'message_repository', $message_repo );
        Public_Api::init( $container );

        $GLOBALS['wp_stubs_is_user_logged_in']   = true;
        $GLOBALS['wp_stubs_get_current_user_id'] = 7;

        ob_start();
        \sydney_woocommerce_header_cart();
        $output = ob_get_clean();

        self::assertIsString( $output );
        self::assertStringContainsString( 'header-username', $output );
        self::assertStringContainsString( '/player/roger-federer/AB%2012%2F3%26x/', $output );
        self::assertStringNotContainsString( 'AB 12/3&x', $output );
        self::assertStringContainsString( 'Messages (5)</a>', $output );
    }

    public function test_header_cart_logged_in_without_btm(): void {
        $this->load_theme_functions();

        $user               = new User();
        $user->id           = 8;
        $user->ID           = 8;
        $user->display_name = 'Rafael Nadal';
        $user->btm          = '';

        $user_repo = $this->createMock( User_Repository_Interface::class );
        $user_repo->expects( self::once() )
            ->method( 'find' )
            ->with( 8 )
            ->willReturn( $user );

        $message_repo = $this->createMock( Message_Repository_Interface::class );
        $message_repo->expects( self::once() )
            ->method( 'find_by_user' )
            ->with( 8, [ 'count' => true, 'status' => 'unread' ] )
            ->willReturn( 0 );

        $container = new Simple_Container();
        $container->set( 'user_repository', $user_repo );
        $container->set( 'message_repository', $message_repo );
        Public_Api::init( $container );

        $GLOBALS['wp_stubs_is_user_logged_in']   = true;
        $GLOBALS['wp_stubs_get_current_user_id'] = 8;

        ob_start();
        \sydney_woocommerce_header_cart();
        $output = ob_get_clean();

        self::assertIsString( $output );
        self::assertStringContainsString( '/player/rafael-nadal/', $output );
        self::assertStringNotContainsString( '/player/rafael-nadal//', $output );
        self::assertStringContainsString( 'Messages</a>', $output );
        self::assertStringNotContainsString( 'Messages (', $output );
    }

    public function test_header_cart_logged_in_without_player_profile(): void {
        $this->load_theme_functions();

        $user_repo = $this->createMock( User_Repository_Interface::class );
        $user_repo->expects( self::once() )
            ->method( 'find' )
            ->with( 99 )
            ->willReturn( null );

        $container = new Simple_Container();
        $container->set( 'user_repository', $user_repo );
        Public_Api::init( $container );

        $GLOBALS['wp_stubs_is_user_logged_in']   = true;
        $GLOBALS['wp_stubs_get_current_user_id'] = 99;

        ob_start();
        \sydney_woocommerce_header_cart();
        $output = ob_get_clean();

        self::assertIsString( $output );
        self::assertStringContainsString( '/member-account/', $output );
        self::assertStringNotContainsString( '/player/', $output );
    }

    private function load_theme_functions(): void {
        $theme_functions = dirname( __DIR__, 4 ) . '/themes/sydney-child/functions.php';
        if ( ! is_file( $theme_functions ) ) {
            $this->markTestSkipped( 'The sydney-child theme checkout is not available.' );
        }
        if ( ! function_exists( 'sydney_woocommerce_header_cart' ) ) {
            require_once $theme_functions;
        }
    }
}
