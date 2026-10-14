<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Export;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Export\Export_Delivery_Adapter;
use Racketmanager\RacketManager;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

require_once __DIR__ . '/../../../../wp-stubs.php';

final class Export_Delivery_Adapter_Test extends TestCase {
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

    private function make_send_response_spy(): Export_Delivery_Adapter&MockObject {
        return $this->getMockBuilder( Export_Delivery_Adapter::class )
            ->setConstructorArgs( [ $this->racketmanager ] )
            ->onlyMethods( [ 'send_response' ] )
            ->getMock();
    }

    public function test_handle_export_passes_content_disposition_filename_and_content_type_to_send_response(): void {
        $_GET['racketmanager_export'] = 'report_results';

        $response = new WP_REST_Response( 'Tournament,Code,Organiser' );
        $response->header( 'Content-Type', 'text/csv' );
        $response->header( 'Content-Disposition', 'attachment; filename="custom_report.csv"' );
        $GLOBALS['wp_stubs_rest_do_request_return'] = $response;

        $adapter = $this->make_send_response_spy();
        $adapter->expects( self::once() )
            ->method( 'send_response' )
            ->with( 'Tournament,Code,Organiser', 'custom_report.csv', 'text/csv' );

        $adapter->handle_export();
    }

    public function test_handle_export_uses_default_filename_and_content_type_when_headers_missing(): void {
        $_GET['racketmanager_export'] = 'results';

        $GLOBALS['wp_stubs_rest_do_request_return'] = new WP_REST_Response( 'RAW BODY' );

        $adapter = $this->make_send_response_spy();
        $adapter->expects( self::once() )
            ->method( 'send_response' )
            ->with( 'RAW BODY', 'export', 'text/plain' );

        $adapter->handle_export();
    }

    public function test_handle_export_uses_default_filename_when_disposition_has_no_filename(): void {
        $_GET['racketmanager_export'] = 'calendar';

        $response = new WP_REST_Response( 'ICS' );
        $response->header( 'Content-Type', 'text/calendar' );
        $response->header( 'Content-Disposition', 'attachment' );
        $GLOBALS['wp_stubs_rest_do_request_return'] = $response;

        $adapter = $this->make_send_response_spy();
        $adapter->expects( self::once() )
            ->method( 'send_response' )
            ->with( 'ICS', 'export', 'text/calendar' );

        $adapter->handle_export();
    }

    public function test_handle_export_skips_nonce_check_outside_admin(): void {
        $_GET['racketmanager_export'] = 'calendar';
        $GLOBALS['wp_stubs_rest_do_request_return'] = new WP_REST_Response( 'ICS' );

        $adapter = $this->make_send_response_spy();
        $adapter->expects( self::once() )->method( 'send_response' );

        $adapter->handle_export();

        self::assertNull( $GLOBALS['wp_stubs_last_check_admin_referer'] ?? null );
    }

    public function test_handle_export_exits_early_if_no_export_param(): void {
        $adapter = new Export_Delivery_Adapter( $this->racketmanager );
        $adapter->handle_export();
        $this->assertNull( $GLOBALS['wp_stubs_last_rest_request'] ?? null );
    }

    public function test_handle_export_calendar_delegates_to_rest_endpoint(): void {
        $_GET['racketmanager_export'] = 'calendar';
        $_GET['competition_id']       = '12';
        $GLOBALS['wp_stubs_current_user_can'] = true;

        $response = new WP_REST_Response( 'BEGIN:VCALENDAR...END:VCALENDAR' );
        $response->header( 'Content-Type', 'text/calendar' );
        $response->header( 'Content-Disposition', 'attachment; filename="calendar.ics"' );
        $GLOBALS['wp_stubs_rest_do_request_return'] = $response;

        $adapter = $this->getMockBuilder( Export_Delivery_Adapter::class )
            ->setConstructorArgs( [ $this->racketmanager ] )
            ->onlyMethods( [ 'terminate' ] )
            ->getMock();

        $adapter->expects( self::once() )->method( 'terminate' );

        ob_start();
        $adapter->handle_export();
        $output = ob_get_clean();

        self::assertSame( 'BEGIN:VCALENDAR...END:VCALENDAR', $output );
        /** @var WP_REST_Request $request */
        $request = $GLOBALS['wp_stubs_last_rest_request'];
        self::assertInstanceOf( WP_REST_Request::class, $request );
        self::assertSame( '/racketmanager/v1/export/calendar', $request->get_route() );
        self::assertSame( '12', $request->get_param( 'competition_id' ) );
    }

    public function test_handle_export_fixtures_delegates_to_rest_endpoint(): void {
        $_GET['racketmanager_export'] = 'fixtures';
        $_GET['league_id']            = '5';
        $GLOBALS['wp_stubs_current_user_can'] = true;

        $response = new WP_REST_Response( '[{"home":"Team A","away":"Team B"}]' );
        $response->header( 'Content-Type', 'application/json' );
        $response->header( 'Content-Disposition', 'attachment; filename="fixtures.json"' );
        $GLOBALS['wp_stubs_rest_do_request_return'] = $response;

        $adapter = $this->getMockBuilder( Export_Delivery_Adapter::class )
            ->setConstructorArgs( [ $this->racketmanager ] )
            ->onlyMethods( [ 'terminate' ] )
            ->getMock();

        $adapter->expects( self::once() )->method( 'terminate' );

        ob_start();
        $adapter->handle_export();
        $output = ob_get_clean();

        self::assertSame( '[{"home":"Team A","away":"Team B"}]', $output );
        /** @var WP_REST_Request $request */
        $request = $GLOBALS['wp_stubs_last_rest_request'];
        self::assertInstanceOf( WP_REST_Request::class, $request );
        self::assertSame( '/racketmanager/v1/export/fixtures', $request->get_route() );
        self::assertSame( '5', $request->get_param( 'league_id' ) );
    }

    public function test_handle_export_results_delegates_to_rest_endpoint(): void {
        $_GET['racketmanager_export'] = 'results';
        $GLOBALS['wp_stubs_current_user_can'] = true;

        $response = new WP_REST_Response( 'JSON RESULTS' );
        $response->header( 'Content-Type', 'application/json' );
        $GLOBALS['wp_stubs_rest_do_request_return'] = $response;

        $adapter = $this->getMockBuilder( Export_Delivery_Adapter::class )
            ->setConstructorArgs( [ $this->racketmanager ] )
            ->onlyMethods( [ 'terminate' ] )
            ->getMock();

        $adapter->expects( self::once() )->method( 'terminate' );

        ob_start();
        $adapter->handle_export();
        $output = ob_get_clean();

        self::assertSame( 'JSON RESULTS', $output );
        /** @var WP_REST_Request $request */
        $request = $GLOBALS['wp_stubs_last_rest_request'];
        self::assertInstanceOf( WP_REST_Request::class, $request );
        self::assertSame( '/racketmanager/v1/export/results', $request->get_route() );
    }

    public function test_handle_export_report_results_delegates_to_rest_endpoint_with_custom_query_params(): void {
        $custom_params = [
            'racketmanager_export' => 'report_results',
            'competition_id'       => 10,
            'season'               => 2026,
        ];
        $GLOBALS['wp_stubs_current_user_can'] = true;

        $response = new WP_REST_Response( 'Tournament,Code,Organiser' );
        $response->header( 'Content-Type', 'text/csv' );
        $response->header( 'Content-Disposition', 'attachment; filename="custom_report.csv"' );
        $GLOBALS['wp_stubs_rest_do_request_return'] = $response;

        $adapter = $this->getMockBuilder( Export_Delivery_Adapter::class )
            ->setConstructorArgs( [ $this->racketmanager ] )
            ->onlyMethods( [ 'terminate' ] )
            ->getMock();

        $adapter->expects( self::once() )->method( 'terminate' );

        ob_start();
        $adapter->handle_export( $custom_params );
        $output = ob_get_clean();

        self::assertSame( 'Tournament,Code,Organiser', $output );
        /** @var WP_REST_Request $request */
        $request = $GLOBALS['wp_stubs_last_rest_request'];
        self::assertInstanceOf( WP_REST_Request::class, $request );
        self::assertSame( '/racketmanager/v1/export/report-results', $request->get_route() );
        self::assertSame( 10, $request->get_param( 'competition_id' ) );
        self::assertSame( 2026, $request->get_param( 'season' ) );
    }

    public function test_handle_export_enforces_nonce_in_admin(): void {
        $GLOBALS['wp_stubs_is_admin'] = true;
        $_GET['racketmanager_export'] = 'calendar';

        $response = new WP_REST_Response( 'CALENDAR CONTENT' );
        $GLOBALS['wp_stubs_rest_do_request_return'] = $response;

        $adapter = $this->getMockBuilder( Export_Delivery_Adapter::class )
            ->setConstructorArgs( [ $this->racketmanager ] )
            ->onlyMethods( [ 'terminate' ] )
            ->getMock();

        $adapter->expects( self::once() )->method( 'terminate' );

        ob_start();
        $adapter->handle_export();
        ob_end_clean();

        self::assertNotNull( $GLOBALS['wp_stubs_last_check_admin_referer'] ?? null );
        self::assertSame( 'racketmanager_export_nonce', $GLOBALS['wp_stubs_last_check_admin_referer']['action'] );
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

        $adapter = $this->getMockBuilder( Export_Delivery_Adapter::class )
            ->setConstructorArgs( [ $this->racketmanager ] )
            ->onlyMethods( [ 'terminate' ] )
            ->getMock();

        $adapter->expects( self::never() )->method( 'terminate' );

        $adapter->handle_export();

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

        $adapter = $this->getMockBuilder( Export_Delivery_Adapter::class )
            ->setConstructorArgs( [ $this->racketmanager ] )
            ->onlyMethods( [ 'terminate' ] )
            ->getMock();

        $adapter->expects( self::never() )->method( 'terminate' );

        $adapter->handle_export();

        self::assertNotNull( $GLOBALS['wp_stubs_last_wp_die'] ?? null );
        self::assertSame( 'Authentication required', $GLOBALS['wp_stubs_last_wp_die']['message'] );
        self::assertSame( 401, $GLOBALS['wp_stubs_last_wp_die']['args']['response'] ?? null );
    }

    public function test_handle_export_unknown_type_calls_wp_die(): void {
        $_GET['racketmanager_export'] = 'unknown_type';
        unset( $GLOBALS['wp_stubs_last_wp_die'] );

        $adapter = $this->getMockBuilder( Export_Delivery_Adapter::class )
            ->setConstructorArgs( [ $this->racketmanager ] )
            ->onlyMethods( [ 'terminate' ] )
            ->getMock();

        $adapter->expects( self::never() )->method( 'terminate' );

        $adapter->handle_export();

        self::assertNotNull( $GLOBALS['wp_stubs_last_wp_die'] ?? null );
        self::assertSame( 'Export function not found', $GLOBALS['wp_stubs_last_wp_die']['message'] );
    }
}
