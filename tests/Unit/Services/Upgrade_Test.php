<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Display;
use Racketmanager\Admin\Admin_Upgrade;
use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Upgrade;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionClass;
use ReflectionProperty;
use wpdb;

require_once __DIR__ . '/../../wp-stubs.php';

if ( ! defined( 'RACKETMANAGER_DBVERSION' ) ) {
    define( 'RACKETMANAGER_DBVERSION', '10.0.18' );
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

    public function test_admin_upgrade_page_runs_the_upgrade_through_the_template_with_the_global_connection(): void {
        $_POST['doUpgrade']             = '1';
        $_REQUEST['racketmanager_nonce'] = 'nonce';
        $GLOBALS['wp_stubs_current_user_can']       = true;
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = true;

        $db = $this->createMock( wpdb::class );
        $db->expects( self::once() )->method( 'show_errors' );
        $db->expects( self::never() )->method( 'query' );
        $GLOBALS['wpdb'] = $db;

        $plugin = $this->make_plugin( RACKETMANAGER_DBVERSION );
        $plugin->expects( self::once() )
            ->method( 'update_plugin_options' )
            ->with( self::callback( static fn( array $options ): bool => RACKETMANAGER_DBVERSION === $options['dbversion'] ) );

        $admin = ( new ReflectionClass( Admin_Upgrade::class ) )->newInstanceWithoutConstructor();
        ( new ReflectionProperty( Admin_Display::class, 'racketmanager' ) )->setValue( $admin, $plugin );

        ob_start();
        try {
            $admin->handle_display();
            $output = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertStringContainsString( 'Upgrade database structure...', $output );
        self::assertStringContainsString( 'Upgrade successful', $output );
    }
}
