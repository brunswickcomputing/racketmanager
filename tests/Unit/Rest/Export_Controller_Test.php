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
    use Racketmanager\Services\Exporter;
    use Racketmanager\Services\Export\DTO\Export_Criteria;
    use Racketmanager\Services\Container\Simple_Container;
    use Racketmanager\Services\Export\Formatters\Export_Formatter_Interface;
    use stdClass;
    use WP_REST_Request;
    use WP_REST_Response;

    #[AllowMockObjectsWithoutExpectations]
    final class Export_Controller_Test extends TestCase {

        private RacketManager|MockObject $racketmanager;
        private MockObject|Exporter $exporter;
        private mixed $original_racketmanager;

        protected function setUp(): void {
            parent::setUp();
            $this->original_racketmanager = $GLOBALS['racketmanager'] ?? null;
            $this->racketmanager = $this->createMock( RacketManager::class );
            $container = $this->createMock( Simple_Container::class );
            $this->exporter = $this->createMock( Exporter::class );

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
    }
}
