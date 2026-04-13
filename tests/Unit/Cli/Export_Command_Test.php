<?php
declare(strict_types=1);

namespace {
    require_once __DIR__ . '/../../wp-stubs.php';
    if ( ! class_exists( 'WP_CLI' ) ) {
        class WP_CLI {
            public static $logs = [];
            public static $errors = [];
            public static $successes = [];

            public static function log( $msg ) { self::$logs[] = $msg; }
            public static function error( $msg ) { self::$errors[] = $msg; }
            public static function success( $msg ) { self::$successes[] = $msg; }
        }
    }
}

namespace Racketmanager\Tests\Unit\Cli {
    use PHPUnit\Framework\TestCase;
    use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
    use Racketmanager\Cli\Export_Command;
    use Racketmanager\RacketManager;
    use Racketmanager\Services\Exporter;
    use Racketmanager\Services\Container\Simple_Container;
    use WP_CLI;

    #[AllowMockObjectsWithoutExpectations]
    final class Export_Command_Test extends TestCase {

        private $racketmanager;
        private $exporter;
        private $container;
        private $original_racketmanager;

        protected function setUp(): void {
            parent::setUp();
            $this->original_racketmanager = $GLOBALS['racketmanager'] ?? null;
            $this->racketmanager = $this->createMock( RacketManager::class );
            $this->container = $this->createMock( Simple_Container::class );
            $this->exporter = $this->createMock( Exporter::class );

            $this->racketmanager->container = $this->container;
            $this->container->method( 'get' )->willReturnCallback( function( $id ) {
                if ( 'exporter' === $id ) return $this->exporter;
                return $this->createMock( \stdClass::class );
            } );
            
            $GLOBALS['racketmanager'] = $this->racketmanager;
            
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
