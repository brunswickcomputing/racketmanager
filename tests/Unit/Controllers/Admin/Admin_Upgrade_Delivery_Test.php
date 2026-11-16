<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Upgrade;
use Racketmanager\Admin\Controllers\Upgrade_Admin_Controller;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Upgrade_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Application\Admin\Ports\Admin_Redirector_Interface;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Application\Ports\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

require_once __DIR__ . '/../../../wp-stubs.php';

if ( ! defined( 'RACKETMANAGER_DBVERSION' ) ) {
    define( 'RACKETMANAGER_DBVERSION', '10.0.18' );
}

#[AllowMockObjectsWithoutExpectations]
final class Admin_Upgrade_Delivery_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Simple_Container $container;
    private Admin_Redirector_Interface $redirector;
    private View_Renderer_Interface $renderer;
    private Admin_Flash_Message_Store $flash_store;
    private Action_Guard_Interface $guard;
    private RacketManager $racketmanager;
    private wpdb $db;
    private Admin_Upgrade $screen;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_current_user_can'] = true;

        $this->redirector  = $this->createMock( Admin_Redirector_Interface::class );
        $this->renderer    = $this->createMock( View_Renderer_Interface::class );
        $this->flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $this->guard       = $this->createMock( Action_Guard_Interface::class );

        $this->db           = $this->createMock( wpdb::class );
        $this->db->postmeta = 'wp_postmeta';

        $this->racketmanager = $this->createMock( RacketManager::class );
        $this->racketmanager->options = array(
            'dbversion' => RACKETMANAGER_DBVERSION,
        );

        $this->container = new Simple_Container();
        $this->container->set( 'club_service', $this->createStub( Club_Service::class ) );

        $message_service = new Admin_Message_Service( $this->flash_store );
        $actions         = new Admin_Action_Adapter( $message_service, $this->redirector );
        $screens         = new Admin_Screen_Presenter( $this->renderer, $message_service );

        $controller = new Upgrade_Admin_Controller(
            $this->racketmanager,
            $this->guard,
            $this->db
        );

        $this->container->set( Upgrade_Admin_Controller::class, fn() => $controller );

        $this->racketmanager->container = $this->container;

        $this->screen = new Admin_Upgrade(
            $this->container,
            $message_service,
            $actions,
            $screens
        );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_get_rendering_presents_view_model_to_renderer(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/show-upgrade',
                self::callback(
                    static function ( $vm ): bool {
                        return $vm instanceof Upgrade_Page_View_Model
                            && RACKETMANAGER_DBVERSION === $vm->installed;
                    }
                )
            );

        $this->screen->display_upgrade_page();
    }

    public function test_get_capability_failure_does_not_render_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'manage_racketmanager' )
            ->willThrowException( new Invalid_Status_Exception( 'You do not have permission to perform this task' ) );
        $this->renderer->expects( self::never() )
            ->method( 'render' );

        ob_start();
        $this->screen->display_upgrade_page();
        $output = (string) ob_get_clean();

        self::assertStringContainsString( 'permission', $output );
    }

    public function test_post_mutation_do_upgrade_redirects_with_flash(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['doUpgrade']        = '1';

        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_upgrade', 'manage_racketmanager' );

        $this->db->expects( self::once() )->method( 'show_errors' );
        $this->racketmanager->expects( self::once() )
            ->method( 'update_plugin_options' )
            ->with( self::callback( static fn( array $opts ): bool => RACKETMANAGER_DBVERSION === $opts['dbversion'] ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( 'Upgrade successful', false );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::stringContains( 'page=racketmanager' ) );

        $this->screen->display_upgrade_page();
    }

    public function test_post_mutation_with_database_error_flashes_failure_with_output(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['doUpgrade']        = '1';

        $this->racketmanager->options = array( 'dbversion' => '10.0.17' );
        $this->db->last_error         = 'Table already exists';

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                self::logicalAnd(
                    self::stringContains( 'Upgrade failed' ),
                    self::stringContains( 'Table already exists' ),
                    self::stringContains( 'starting 10.0.18 upgrade' )
                ),
                true
            );

        $this->redirector->expects( self::once() )->method( 'redirect' );

        $this->screen->display_upgrade_page();
    }

    public function test_post_mutation_guard_failure_redirects_with_error_flash(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['doUpgrade']        = '1';

        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_upgrade', 'manage_racketmanager' )
            ->willThrowException( new Invalid_Status_Exception( 'Security token invalid' ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( 'Security token invalid', true );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::stringContains( 'page=racketmanager' ) );

        $this->screen->display_upgrade_page();
    }

    public function test_post_mutation_database_error_output_is_safe_for_shortcode_alert_rendering(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['doUpgrade']        = '1';

        $this->racketmanager->options = array( 'dbversion' => '10.0.17' );
        $this->db->last_error         = '[Unknown column \'x\' in \'field list\'] \path\to\file';

        $captured_flash_message = null;
        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->willReturnCallback(
                static function ( string $msg ) use ( &$captured_flash_message ): void {
                    $captured_flash_message = $msg;
                }
            );

        $this->redirector->expects( self::once() )->method( 'redirect' );

        $this->screen->display_upgrade_page();

        self::assertNotNull( $captured_flash_message );
        self::assertStringNotContainsString( '[', $captured_flash_message );
        self::assertStringNotContainsString( ']', $captured_flash_message );

        if ( function_exists( '\Racketmanager\show_alert' ) ) {
            $alert = \Racketmanager\show_alert( $captured_flash_message, 'danger' );
            self::assertTrue( str_contains( $alert, 'alert--danger' ) || str_contains( $alert, 'alert-danger' ) );
            self::assertStringNotContainsString( 'type=\'danger\'', $alert );
            self::assertStringContainsString( '&#91;Unknown column', $alert );
        }
    }
}
