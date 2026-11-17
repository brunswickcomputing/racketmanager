<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Upgrade;
use Racketmanager\Admin\Controllers\Upgrade_Admin_Controller;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Application\Admin\Ports\Admin_Redirector_Interface;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Security\Wp_Action_Guard;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Upgrade;
use Racketmanager\Application\Ports\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionProperty;
use wpdb;

require_once __DIR__ . '/../../wp-stubs.php';

if ( ! defined( 'RACKETMANAGER_DBVERSION' ) ) {
    define( 'RACKETMANAGER_DBVERSION', '10.0.19' );
}

final class Upgrade_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $_GET     = array();
        $_POST    = array();
        $_REQUEST = array();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    /**
     * @return RacketManager&MockObject partial double exposing options/container with update_plugin_options() intercepted.
     */
    private function make_plugin( string $dbversion ): RacketManager|MockObject {
        $plugin = $this->getMockBuilder( RacketManager::class )
            ->disableOriginalConstructor()
            ->onlyMethods( [ 'update_plugin_options' ] )
            ->getMock();
        $plugin->options = [ 'dbversion' => $dbversion, 'keep' => 'me' ];

        $container = new Simple_Container();
        $container->set( 'club_service', $this->createStub( Club_Service::class ) );
        $plugin->container = $container;

        return $plugin;
    }

    public function test_constructor_stores_the_injected_connection_without_touching_the_global(): void {
        $injected        = $this->createStub( wpdb::class );
        $GLOBALS['wpdb'] = $this->createStub( wpdb::class );

        $plugin = $this->make_plugin( RACKETMANAGER_DBVERSION );
        $plugin->expects( self::never() )->method( 'update_plugin_options' );

        $upgrade = new Upgrade( $plugin, $injected );

        $property = new ReflectionProperty( Upgrade::class, 'wpdb' );
        self::assertSame( $injected, $property->getValue( $upgrade ) );
        self::assertNotSame( $GLOBALS['wpdb'], $property->getValue( $upgrade ) );
    }

    public function test_run_at_current_dbversion_shows_errors_issues_no_ddl_and_persists_dbversion(): void {
        $db = $this->createMock( wpdb::class );
        $db->expects( self::once() )->method( 'show_errors' );
        $db->expects( self::never() )->method( 'query' );
        $db->expects( self::never() )->method( 'get_results' );

        $plugin = $this->make_plugin( RACKETMANAGER_DBVERSION );
        $plugin->expects( self::once() )
            ->method( 'update_plugin_options' )
            ->with( [ 'dbversion' => RACKETMANAGER_DBVERSION, 'keep' => 'me' ] );

        $GLOBALS['racketmanager'] = null;
        $this->expectOutputString( '' );

        ( new Upgrade( $plugin, $db ) )->run();
    }

    public function test_v10_0_19_adds_date_result_confirmed_column_when_missing(): void {
        $db         = $this->createMock( wpdb::class );
        $db->prefix = 'wp_';
        $db->expects( self::once() )
            ->method( 'get_var' )
            ->with( "SHOW COLUMNS FROM `wp_racketmanager_matches` LIKE 'date_result_confirmed'" )
            ->willReturn( null );
        $db->expects( self::once() )
            ->method( 'query' )
            ->with( self::stringContains( 'ALTER TABLE `wp_racketmanager_matches` ADD `date_result_confirmed` DATETIME NULL DEFAULT NULL AFTER `date_result_entered`' ) );

        $plugin = $this->make_plugin( '10.0.18' );
        $plugin->expects( self::once() )
            ->method( 'update_plugin_options' )
            ->with( [ 'dbversion' => RACKETMANAGER_DBVERSION, 'keep' => 'me' ] );

        $this->expectOutputString( '<p>starting 10.0.19 upgrade</p>' );

        ( new Upgrade( $plugin, $db ) )->run();
    }

    public function test_v10_0_19_skips_alter_when_column_already_exists(): void {
        $db         = $this->createMock( wpdb::class );
        $db->prefix = 'wp_';
        $db->expects( self::once() )->method( 'get_var' )->willReturn( 'date_result_confirmed' );
        $db->expects( self::never() )->method( 'query' );

        $plugin = $this->make_plugin( '10.0.18' );
        $plugin->expects( self::once() )->method( 'update_plugin_options' );

        $this->expectOutputString( '<p>starting 10.0.19 upgrade</p>' );

        ( new Upgrade( $plugin, $db ) )->run();
    }

    public function test_admin_upgrade_page_runs_the_upgrade_with_the_global_connection_and_flashes_its_output(): void {
        $_POST['doUpgrade']             = '1';
        $_REQUEST['racketmanager_nonce'] = 'nonce';
        $GLOBALS['wp_stubs_current_user_can']       = true;
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = true;

        $db = $this->createMock( wpdb::class );
        $db->expects( self::once() )->method( 'show_errors' );
        $db->postmeta    = 'wp_postmeta';
        $GLOBALS['wpdb'] = $db;

        $plugin = $this->make_plugin( '10.0.17' );
        $plugin->expects( self::once() )
            ->method( 'update_plugin_options' )
            ->with( self::callback( static fn( array $options ): bool => RACKETMANAGER_DBVERSION === $options['dbversion'] ) );

        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::stringContains( 'page=racketmanager' ) );

        $flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $flash_store->expects( self::once() )
            ->method( 'set' )
            ->with(
                self::logicalAnd(
                    self::stringStartsWith( 'Upgrade successful' ),
                    self::stringContains( '<p>starting 10.0.19 upgrade</p>' )
                ),
                false
            );

        $message_service = new Admin_Message_Service( $flash_store );
        $actions         = new Admin_Action_Adapter( $message_service, $redirector );
        $guard           = new Wp_Action_Guard();

        $controller = new Upgrade_Admin_Controller( $plugin, $guard );
        $plugin->container->set( Upgrade_Admin_Controller::class, fn() => $controller );

        $screens = new Admin_Screen_Presenter(
            $this->createStub( View_Renderer_Interface::class ),
            $message_service
        );

        $admin = new Admin_Upgrade(
            $plugin->container,
            $message_service,
            $actions,
            $screens
        );

        $admin->handle_display();
    }
}
