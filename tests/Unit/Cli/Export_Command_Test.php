<?php
declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../../wp-stubs.php';
    if ( ! class_exists( 'WP_CLI' ) ) {
        class WP_CLI {
            public static array $logs = [];
            public static array $errors = [];
            public static array $successes = [];

            public static function log( $msg ): void { self::$logs[] = $msg; }
            public static function error( $msg ): void { self::$errors[] = $msg; }
            public static function success( $msg ): void { self::$successes[] = $msg; }
        }
    }
}

namespace Racketmanager\Tests\Unit\Cli {

    use PHPUnit\Framework\MockObject\MockObject;
    use PHPUnit\Framework\TestCase;
    use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
    use Racketmanager\Cli\Export_Command;
    use Racketmanager\RacketManager;
    use Racketmanager\Services\Export\Export_Service;
    use Racketmanager\Services\Container\Simple_Container;
    use stdClass;
    use WP_CLI;

    #[AllowMockObjectsWithoutExpectations]
    final class Export_Command_Test extends TestCase {

        private Export_Service|MockObject $exporter;
        private mixed $original_racketmanager;

        protected function setUp(): void {
            parent::setUp();
            $this->original_racketmanager = $GLOBALS['racketmanager'] ?? null;
            $racketmanager                = $this->createMock( RacketManager::class );
            $container = $this->createMock( Simple_Container::class );
            $this->exporter = $this->createMock( Export_Service::class );

            $racketmanager->container = $container;
            $container->method( 'get' )->willReturnCallback( function( $id ) {
                if ( 'exporter' === $id ) return $this->exporter;
                return $this->createMock( stdClass::class );
            } );
            
            $GLOBALS['racketmanager'] = $racketmanager;
            
            WP_CLI::$logs = [];
            WP_CLI::$errors = [];
            WP_CLI::$successes = [];
        }

        protected function tearDown(): void {
            $GLOBALS['racketmanager'] = $this->original_racketmanager;
            parent::tearDown();
        }

        public function test_invoke_calls_exporter_and_logs_content(): void {
            $this->exporter->expects( self::once() )
                ->method( 'results' )
                ->willReturn( 'Match,Score' );

            $command = new Export_Command();
            $command( [], ['type' => 'results', 'league_id' => 1] );

            self::assertContains( 'Match,Score', WP_CLI::$logs );
        }

        public function test_invoke_handles_invalid_type(): void {
            $command = new Export_Command();
            $command( [], ['type' => 'invalid'] );

            self::assertNotEmpty( WP_CLI::$errors );
        }
    }
}
