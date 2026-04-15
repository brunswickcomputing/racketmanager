<?php
declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../../wp-stubs.php';
}

namespace Racketmanager\Tests\Unit\Rest {

    use PHPUnit\Framework\MockObject\MockObject;
    use PHPUnit\Framework\TestCase;
    use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
    use Racketmanager\RacketManager;
    use Racketmanager\Rest\Export_Controller;
    use Racketmanager\Services\Export\Export_Service;
    use Racketmanager\Services\Export\DTO\Export_Criteria;
    use Racketmanager\Services\Container\Simple_Container;
    use Racketmanager\Services\Export\Formatters\Export_Formatter_Interface;
    use stdClass;
    use WP_REST_Request;
    use WP_REST_Response;
    use WP_REST_Server;

    #[AllowMockObjectsWithoutExpectations]
    final class Export_Controller_Test extends TestCase {

        private RacketManager|MockObject $racketmanager;
        private MockObject|Export_Service $exporter;
        private mixed $original_racketmanager;

        protected function setUp(): void {
            parent::setUp();
            $this->original_racketmanager = $GLOBALS['racketmanager'] ?? null;
            $this->racketmanager = $this->createMock( RacketManager::class );
            $container = $this->createMock( Simple_Container::class );
            $this->exporter = $this->createMock( Export_Service::class );

            $this->racketmanager->container = $container;
        
            // Mock container get('exporter')
            $container->method( 'get' )->willReturnCallback( function( $id ) {
                if ( 'exporter' === $id ) return $this->exporter;
                return $this->createMock( stdClass::class );
            } );

            $GLOBALS['racketmanager'] = $this->racketmanager;
        }

        protected function tearDown(): void {
            $GLOBALS['racketmanager'] = $this->original_racketmanager;
            parent::tearDown();
        }

        public function test_get_calendar_calls_exporter_and_returns_response(): void {
            $request = new WP_REST_Request( 'GET', '/racketmanager/v1/export/calendar' );
            $request->set_param( 'league_id', 123 );

            $this->exporter->expects( self::once() )
                ->method( 'calendar' )
                ->with( self::callback( function( $criteria ) {
                    return $criteria instanceof Export_Criteria && $criteria->league_id === 123;
                } ) )
                ->willReturn( 'BEGIN:VCALENDAR...' );

            $controller = new Export_Controller( $this->racketmanager );
            $response = $controller->get_calendar( $request );

            self::assertInstanceOf( WP_REST_Response::class, $response );
            self::assertSame( 200, $response->get_status() );
            self::assertSame( 'BEGIN:VCALENDAR...', $response->get_data() );
            
            $headers = $response->get_headers();
            self::assertSame( 'text/calendar', $headers['Content-Type'] );
            self::assertSame( 'attachment; filename="calendar.ics"', $headers['Content-Disposition'] );
        }

        public function test_get_results_calls_exporter_and_returns_response(): void {
            $request = new WP_REST_Request( 'GET', '/racketmanager/v1/export/results' );
            $request->set_param( 'format', 'csv' );

            $this->exporter->expects( self::once() )
                ->method( 'results' )
                ->with( self::callback( function( $criteria ) {
                    return $criteria->format === 'csv';
                } ) )
                ->willReturn( 'Match,Score' );

            $controller = new Export_Controller( $this->racketmanager );
            $response = $controller->get_results( $request );

            self::assertInstanceOf( WP_REST_Response::class, $response );
            self::assertSame( 'Match,Score', $response->get_data() );
            
            $headers = $response->get_headers();
            self::assertSame( Export_Formatter_Interface::CONTENT_TYPE_CSV, $headers['Content-Type'] );
        }

        public function test_get_fixtures_calls_exporter_and_returns_response(): void {
            $request = new WP_REST_Request( 'GET', '/racketmanager/v1/export/fixtures' );
            
            $this->exporter->expects( self::once() )
                ->method( 'fixtures' )
                ->willReturn( '[]' );

            $controller = new Export_Controller( $this->racketmanager );
            $response = $controller->get_fixtures( $request );

            self::assertInstanceOf( WP_REST_Response::class, $response );
            self::assertSame( '[]', $response->get_data() );
            
            $headers = $response->get_headers();
            self::assertSame( 'application/json', $headers['Content-Type'] );
        }

        public function test_get_report_results_calls_exporter_and_returns_csv_response(): void {
            $request = new WP_REST_Request( 'GET', '/racketmanager/v1/export/report-results' );
            
            $this->exporter->expects( self::once() )
                ->method( 'report_results' )
                ->willReturn( 'Match,Score,Player1' );

            $controller = new Export_Controller( $this->racketmanager );
            $response = $controller->get_report_results( $request );

            self::assertInstanceOf( WP_REST_Response::class, $response );
            self::assertSame( 'Match,Score,Player1', $response->get_data() );
            
            $headers = $response->get_headers();
            self::assertSame( Export_Formatter_Interface::CONTENT_TYPE_CSV, $headers['Content-Type'] );
            self::assertSame( 'attachment; filename="report_results.csv"', $headers['Content-Disposition'] );
        }

        public function test_get_item_permissions_check_allows_public_exports(): void {
            $controller = new Export_Controller( $this->racketmanager );

            $request_calendar = new WP_REST_Request( 'GET', '/racketmanager/v1/export/calendar' );
            self::assertTrue( $controller->get_item_permissions_check( $request_calendar ) );

            $request_results = new WP_REST_Request( 'GET', '/racketmanager/v1/export/results' );
            self::assertTrue( $controller->get_item_permissions_check( $request_results ) );

            $request_fixtures = new WP_REST_Request( 'GET', '/racketmanager/v1/export/fixtures' );
            self::assertTrue( $controller->get_item_permissions_check( $request_fixtures ) );

            $request_report = new WP_REST_Request( 'GET', '/racketmanager/v1/export/report-results' );
            self::assertTrue( $controller->get_item_permissions_check( $request_report ) );
        }

        public function test_serve_raw_export_sends_headers_and_echos_data_for_csv(): void {
            $controller = new Export_Controller( $this->racketmanager );
            $request = new WP_REST_Request( 'GET', '/racketmanager/v1/export/report-results' );
            $request->set_route( '/racketmanager/v1/export/report-results' );

            $response = new WP_REST_Response( 'Match,Score', 200, [ 'Content-Type' => Export_Formatter_Interface::CONTENT_TYPE_CSV ] );
            
            $server = $this->createMock( WP_REST_Server::class );
            $server->expects( self::once() )
                ->method( 'send_headers' )
                ->with( self::callback( function( $headers ) {
                    return is_array( $headers ) && $headers['Content-Type'] === Export_Formatter_Interface::CONTENT_TYPE_CSV;
                } ) );

            ob_start();
            $result = $controller->serve_raw_export( false, $response, $request, $server );
            $output = ob_get_clean();

            self::assertTrue( $result );
            self::assertSame( 'Match,Score', $output );
        }

        public function test_serve_raw_export_skips_non_export_routes(): void {
            $controller = new Export_Controller( $this->racketmanager );
            $request = new WP_REST_Request( 'GET', '/other/v1/route' );
            $request->set_route( '/other/v1/route' );

            $response = new WP_REST_Response( '[]' );
            $server = $this->createMock( WP_REST_Server::class );
            $server->expects( self::never() )->method( 'send_headers' );

            $result = $controller->serve_raw_export( false, $response, $request, $server );

            self::assertFalse( $result );
        }
    }
}
