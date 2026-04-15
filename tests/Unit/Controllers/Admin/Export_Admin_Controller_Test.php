<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Admin\Controllers\Export_Admin_Controller;
use Racketmanager\RacketManager;
use Racketmanager\Services\Export\Export_Service;
use Racketmanager\Services\Container\Simple_Container;
use stdClass;
use WP_REST_Response;

require_once __DIR__ . '/../../../wp-stubs.php';

// Stub missing WordPress functions
if ( ! function_exists( 'check_admin_referer' ) ) {
    function check_admin_referer( $action = -1, $query_arg = '_wpnonce' ): true { return true; }
}
if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( 'esc_html_e' ) ) {
    function esc_html_e( $text, $domain = 'default' ): void { echo $text; }
}

#[AllowMockObjectsWithoutExpectations]
final class Export_Admin_Controller_Test extends TestCase {

    private RacketManager|MockObject $racketmanager;
    private Export_Service|MockObject $exporter;
    private mixed $original_racketmanager;

    protected function setUp(): void {
        parent::setUp();
        $this->original_racketmanager = $GLOBALS['racketmanager'] ?? null;
        $this->racketmanager = $this->createMock( RacketManager::class );
        $container = $this->createMock( Simple_Container::class );
        $this->exporter = $this->createMock( Export_Service::class );

        $this->racketmanager->container = $container;
        $container->method( 'get' )->willReturnCallback( function( $id ) {
            if ( 'exporter' === $id ) return $this->exporter;
            return $this->createMock( stdClass::class );
        } );

        $GLOBALS['racketmanager'] = $this->racketmanager;
        $_GET = [];
    }

    protected function tearDown(): void {
        $GLOBALS['racketmanager'] = $this->original_racketmanager;
        parent::tearDown();
    }

    public function test_handle_export_exits_early_if_no_export_param(): void {
        $controller = new Export_Admin_Controller( $this->racketmanager );
        $controller->handle_export();
        $this->assertTrue(true);
    }

    public function test_handle_export_calendar_delegates_to_rest(): void {
        $_GET['racketmanager_export'] = 'calendar';
        $GLOBALS['wp_stubs_current_user_can'] = true;

        $response = new WP_REST_Response( 'ICS FROM REST' );
        $response->header( 'Content-Type', 'text/calendar' );
        $response->header( 'Content-Disposition', 'attachment; filename="calendar.ics"' );
        $GLOBALS['wp_stubs_rest_do_request_return'] = $response;

        $controller = $this->getMockBuilder( Export_Admin_Controller::class )
            ->setConstructorArgs( [$this->racketmanager] )
            ->onlyMethods( ['terminate'] )
            ->getMock();
        
        $controller->expects( self::once() )->method( 'terminate' );

        ob_start();
        $controller->handle_export();
        $output = ob_get_clean();
        
        self::assertSame( 'ICS FROM REST', $output );
        unset($GLOBALS['wp_stubs_current_user_can']);
        unset($GLOBALS['wp_stubs_rest_do_request_return']);
    }

    public function test_handle_export_report_results_works_for_non_admins(): void {
        $_GET['racketmanager_export'] = 'report_results';
        $GLOBALS['wp_stubs_current_user_can'] = false; // Mock non-admin

        $response = new WP_REST_Response( 'CSV FROM REST' );
        $response->header( 'Content-Type', 'text/csv' );
        $GLOBALS['wp_stubs_rest_do_request_return'] = $response;

        $controller = $this->getMockBuilder( Export_Admin_Controller::class )
            ->setConstructorArgs( [$this->racketmanager] )
            ->onlyMethods( ['terminate'] )
            ->getMock();
        
        $controller->expects( self::once() )->method( 'terminate' );

        ob_start();
        $controller->handle_export();
        $output = ob_get_clean();
        
        self::assertSame( 'CSV FROM REST', $output );
        unset($GLOBALS['wp_stubs_current_user_can']);
        unset($GLOBALS['wp_stubs_rest_do_request_return']);
    }

    public function test_handle_export_results_delegates_to_rest(): void {
        $_GET['racketmanager_export'] = 'results';
        $GLOBALS['wp_stubs_current_user_can'] = true;

        $response = new WP_REST_Response( 'JSON FROM REST' );
        $response->header( 'Content-Type', 'application/json' );
        $GLOBALS['wp_stubs_rest_do_request_return'] = $response;

        $controller = $this->getMockBuilder( Export_Admin_Controller::class )
            ->setConstructorArgs( [$this->racketmanager] )
            ->onlyMethods( ['terminate'] )
            ->getMock();
        
        $controller->expects( self::once() )->method( 'terminate' );

        ob_start();
        $controller->handle_export();
        $output = ob_get_clean();
        
        self::assertSame( 'JSON FROM REST', $output );
        unset($GLOBALS['wp_stubs_current_user_can']);
        unset($GLOBALS['wp_stubs_rest_do_request_return']);
    }
}
