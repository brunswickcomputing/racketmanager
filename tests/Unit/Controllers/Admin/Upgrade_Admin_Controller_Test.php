<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use LogicException;
use RuntimeException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Upgrade_Admin_Controller;
use Racketmanager\Admin\View_Models\Upgrade_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

require_once __DIR__ . '/../../../wp-stubs.php';

if ( ! defined( 'RACKETMANAGER_DBVERSION' ) ) {
    define( 'RACKETMANAGER_DBVERSION', '10.0.18' );
}

#[AllowMockObjectsWithoutExpectations]
final class Upgrade_Admin_Controller_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Action_Guard_Interface $guard;
    private RacketManager $racketmanager;
    private wpdb $db;
    private Upgrade_Admin_Controller $controller;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->guard         = $this->createMock( Action_Guard_Interface::class );
        $this->db           = $this->createMock( wpdb::class );
        $this->db->postmeta = 'wp_postmeta';
        $this->racketmanager = $this->createMock( RacketManager::class );
        $this->racketmanager->options = array(
            'dbversion' => '10.0.17',
        );
        $container = new Simple_Container();
        $container->set( 'club_service', $this->createStub( Club_Service::class ) );
        $this->racketmanager->container = $container;

        $this->controller = new Upgrade_Admin_Controller(
            $this->racketmanager,
            $this->guard,
            $this->db
        );
    }

    protected function tearDown(): void {
        unset( $GLOBALS['EZSQL_ERROR'] );
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_capability_check_fails_on_get(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'manage_racketmanager' )
            ->willThrowException( new Invalid_Status_Exception( 'Permission denied' ) );

        $this->expectException( Invalid_Status_Exception::class );

        $this->controller->upgrade_page( array(), array() );
    }

    public function test_get_upgrade_page_returns_view_model(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'manage_racketmanager' );

        $result = $this->controller->upgrade_page( array(), array() );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Upgrade_Page_View_Model::class, $result['view_model'] );
        self::assertSame( '10.0.17', $result['view_model']->installed );
        self::assertSame( RACKETMANAGER_DBVERSION, $result['view_model']->latest_version );
    }

    public function test_post_upgrade_executes_and_redirects(): void {
        $this->racketmanager->options = array(
            'dbversion' => RACKETMANAGER_DBVERSION,
        );

        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_upgrade', 'manage_racketmanager' );

        $this->db->expects( self::once() )->method( 'show_errors' );
        $this->racketmanager->expects( self::once() )
            ->method( 'update_plugin_options' )
            ->with( self::callback( static fn( array $opts ): bool => RACKETMANAGER_DBVERSION === $opts['dbversion'] ) );

        $result = $this->controller->upgrade_page(
            array( 'page' => 'racketmanager' ),
            array( 'doUpgrade' => '1' )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager', $result['redirect'] );
        self::assertSame( 'Upgrade successful', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_upgrade_keeps_step_output_in_the_flash_message(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $level  = ob_get_level();
        $result = $this->controller->upgrade_page( array(), array( 'doUpgrade' => '1' ) );

        self::assertSame( $level, ob_get_level() );
        self::assertFalse( $result['message_type'] );
        self::assertStringStartsWith( 'Upgrade successful', $result['message'] );
        self::assertStringContainsString( '<p>starting 10.0.18 upgrade</p>', $result['message'] );
    }

    public function test_post_upgrade_reports_database_errors_instead_of_success(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->db->last_error = 'Duplicate column name \'x\'';

        $result = $this->controller->upgrade_page( array(), array( 'doUpgrade' => '1' ) );

        self::assertTrue( $result['message_type'] );
        self::assertStringContainsString( 'Upgrade failed', $result['message'] );
        self::assertStringContainsString( 'Duplicate column name', $result['message'] );
        self::assertStringNotContainsString( 'Upgrade successful', $result['message'] );
        self::assertStringContainsString( '<p>starting 10.0.18 upgrade</p>', $result['message'] );
    }

    public function test_post_upgrade_reports_exceptions_and_restores_output_buffer(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->db->method( 'query' )->willThrowException( new RuntimeException( 'boom' ) );

        $level  = ob_get_level();
        $result = $this->controller->upgrade_page( array(), array( 'doUpgrade' => '1' ) );

        self::assertSame( $level, ob_get_level() );
        self::assertTrue( $result['message_type'] );
        self::assertStringContainsString( 'Upgrade failed', $result['message'] );
        self::assertStringContainsString( '<p>boom</p>', $result['message'] );
    }

    public function test_post_mutation_guard_failure_redirects_with_error_flash(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_upgrade', 'manage_racketmanager' )
            ->willThrowException( new Invalid_Status_Exception( 'Security token invalid' ) );

        $result = $this->controller->upgrade_page(
            array( 'page' => 'racketmanager' ),
            array( 'doUpgrade' => '1' )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager', $result['redirect'] );
        self::assertSame( 'Security token invalid', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }

    public function test_post_upgrade_encodes_brackets_and_backslashes_for_shortcode_safety(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->db->last_error = '[Unknown column \'x\' in \'field list\'] \path\to\file';

        $result = $this->controller->upgrade_page( array(), array( 'doUpgrade' => '1' ) );

        self::assertTrue( $result['message_type'] );
        self::assertStringContainsString( '&#91;Unknown column', $result['message'] );
        self::assertStringContainsString( '&#93;', $result['message'] );
        self::assertStringContainsString( '&#92;path&#92;to&#92;file', $result['message'] );
        self::assertStringNotContainsString( '[', $result['message'] );
        self::assertStringNotContainsString( ']', $result['message'] );
    }

    public function test_post_upgrade_detects_printed_wpdb_error_when_last_error_is_reset(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );

        $this->db->method( 'query' )->willReturnCallback(
            function () {
                static $called = 0;
                ++$called;
                if ( 1 === $called ) {
                    echo '<div id="error"><p class="wpdberror"><strong>WordPress database error:</strong> [Unknown column \'x\' in \'field list\']<br /><code>SELECT x</code></p></div>';
                    $this->db->last_error = 'Unknown column \'x\' in \'field list\'';
                } else {
                    $this->db->last_error = '';
                }
                return true;
            }
        );

        $result = $this->controller->upgrade_page( array(), array( 'doUpgrade' => '1' ) );

        self::assertTrue( $result['message_type'] );
        self::assertStringContainsString( 'Upgrade failed', $result['message'] );
        self::assertStringContainsString( 'Unknown column', $result['message'] );
        self::assertStringNotContainsString( 'Upgrade successful', $result['message'] );
    }

    public function test_post_upgrade_reports_ezsql_error_when_present(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->db->last_error = '';
        $this->db->method( 'query' )->willReturnCallback(
            static function (): bool {
                // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                $GLOBALS['EZSQL_ERROR'][] = array( 'error_str' => 'EZSQL syntax error' );
                return true;
            }
        );

        $result = $this->controller->upgrade_page( array(), array( 'doUpgrade' => '1' ) );

        self::assertTrue( $result['message_type'] );
        self::assertStringContainsString( 'Upgrade failed', $result['message'] );
        self::assertStringContainsString( 'EZSQL syntax error', $result['message'] );
    }

    public function test_post_upgrade_reports_generic_database_error_when_output_contains_unbracketed_error(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->db->last_error = '';
        $this->db->method( 'query' )->willReturnCallback(
            static function (): bool {
                echo '<p class="wpdberror">Custom query failed unbracketed</p>';
                return true;
            }
        );

        $result = $this->controller->upgrade_page( array(), array( 'doUpgrade' => '1' ) );

        self::assertTrue( $result['message_type'] );
        self::assertStringContainsString( '<p>Upgrade failed</p>', $result['message'] );
        self::assertStringContainsString( '<p>Custom query failed unbracketed</p>', $result['message'] );
        self::assertStringEndsWith( '<p>WordPress database error</p>', $result['message'] );
    }

    public function test_post_upgrade_throws_logic_exception_when_wpdb_unavailable(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' );

        unset( $GLOBALS['wpdb'] );
        $controller = new Upgrade_Admin_Controller(
            $this->racketmanager,
            $this->guard,
            null
        );

        $this->expectException( LogicException::class );
        $controller->upgrade_page( array(), array( 'doUpgrade' => '1' ) );
    }
}
