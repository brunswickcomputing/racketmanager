<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Export_Admin_Controller;
use Racketmanager\RacketManager;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use WP_Error;
use WP_REST_Response;

require_once __DIR__ . '/../../../wp-stubs.php';

final class Export_Admin_Controller_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private RacketManager|Stub $racketmanager;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->racketmanager      = $this->createStub( RacketManager::class );
        $GLOBALS['racketmanager'] = $this->racketmanager;
        $_GET                     = [];
        wp_stubs_reset_export_rest_globals();
    }

    protected function tearDown(): void {
        wp_stubs_reset_export_rest_globals();
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_handle_export_exits_early_if_no_export_param(): void {
        $controller = new Export_Admin_Controller( $this->racketmanager );
        $controller->handle_export();
        $this->assertNull( $GLOBALS['wp_stubs_last_rest_request'] ?? null );
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
    }

    public function test_handle_export_passes_parsed_filename_and_content_type_to_send_response(): void {
        $_GET['racketmanager_export'] = 'report_results';

        $response = new WP_REST_Response( 'CSV FROM REST' );
        $response->header( 'Content-Type', 'text/csv' );
        $response->header( 'Content-Disposition', 'attachment; filename="custom_report.csv"' );
        $GLOBALS['wp_stubs_rest_do_request_return'] = $response;

        $controller = $this->getMockBuilder( Export_Admin_Controller::class )
            ->setConstructorArgs( [$this->racketmanager] )
            ->onlyMethods( ['send_response'] )
            ->getMock();

        $controller->expects( self::once() )
            ->method( 'send_response' )
            ->with( 'CSV FROM REST', 'custom_report.csv', 'text/csv' );

        $controller->handle_export();
    }

    public function test_handle_export_report_results_delegates_to_rest(): void {
        $_GET['racketmanager_export'] = 'report_results';
        $GLOBALS['wp_stubs_current_user_can'] = true;

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
    }

    public function test_handle_export_denied_rest_response_calls_wp_die_with_status(): void {
        $_GET['racketmanager_export'] = 'report_results';
        unset( $GLOBALS['wp_stubs_last_wp_die'] );

        $response = new WP_REST_Response( [
            'code'    => 'rest_forbidden',
            'message' => 'Sorry, you are not allowed to export match reports.',
            'data'    => [ 'status' => 403 ],
        ], 403 );
        $GLOBALS['wp_stubs_rest_do_request_return'] = $response;

        $controller = $this->getMockBuilder( Export_Admin_Controller::class )
            ->setConstructorArgs( [$this->racketmanager] )
            ->onlyMethods( ['terminate'] )
            ->getMock();

        $controller->expects( self::never() )->method( 'terminate' );

        $controller->handle_export();

        self::assertNotNull( $GLOBALS['wp_stubs_last_wp_die'] ?? null );
        self::assertSame( 'Sorry, you are not allowed to export match reports.', $GLOBALS['wp_stubs_last_wp_die']['message'] );
        self::assertSame( 403, $GLOBALS['wp_stubs_last_wp_die']['args']['response'] ?? null );
    }

    public function test_handle_export_wp_error_calls_wp_die_with_status(): void {
        $_GET['racketmanager_export'] = 'report_results';
        unset( $GLOBALS['wp_stubs_last_wp_die'] );

        $GLOBALS['wp_stubs_rest_do_request_return'] = new WP_Error(
            'rest_forbidden',
            'Authentication required',
            [ 'status' => 401 ]
        );

        $controller = $this->getMockBuilder( Export_Admin_Controller::class )
            ->setConstructorArgs( [$this->racketmanager] )
            ->onlyMethods( ['terminate'] )
            ->getMock();

        $controller->expects( self::never() )->method( 'terminate' );

        $controller->handle_export();

        self::assertNotNull( $GLOBALS['wp_stubs_last_wp_die'] ?? null );
        self::assertSame( 'Authentication required', $GLOBALS['wp_stubs_last_wp_die']['message'] );
        self::assertSame( 401, $GLOBALS['wp_stubs_last_wp_die']['args']['response'] ?? null );
    }
}
