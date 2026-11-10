<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Cli;

use LogicException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Cli\Export_Command;
use Racketmanager\Infrastructure\Wordpress\Bootstrap\Plugin_Bootstrap;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Export\Export_Service;
use WP_CLI;

#[AllowMockObjectsWithoutExpectations]
final class Export_Command_Test extends TestCase {

    private Export_Service&MockObject $exporter;

    protected function setUp(): void {
        parent::setUp();
        $this->exporter = $this->createMock( Export_Service::class );
        WP_CLI::reset();
    }

    protected function tearDown(): void {
        Plugin_Bootstrap::reset();
        WP_CLI::reset();
        parent::tearDown();
    }

    public function test_construct_resolves_exporter_from_bootstrapped_container(): void {
        $container = $this->createMock( Simple_Container::class );
        $container->expects( self::once() )
            ->method( 'get' )
            ->with( Export_Service::class )
            ->willReturn( $this->exporter );

        Plugin_Bootstrap::set_container( $container );

        $command = new Export_Command();
        self::assertInstanceOf( Export_Command::class, $command );
    }

    public function test_construct_resolves_exporter_from_explicit_container(): void {
        $container = $this->createMock( Simple_Container::class );
        $container->expects( self::once() )
            ->method( 'get' )
            ->with( Export_Service::class )
            ->willReturn( $this->exporter );

        $command = new Export_Command( $container );
        self::assertInstanceOf( Export_Command::class, $command );
    }

    public function test_construct_uses_injected_exporter_without_touching_container(): void {
        $command = new Export_Command( null, $this->exporter );
        self::assertInstanceOf( Export_Command::class, $command );
    }

    public function test_construct_throws_when_unbootstrapped_and_no_dependencies_provided(): void {
        Plugin_Bootstrap::reset();

        $this->expectException( LogicException::class );
        $this->expectExceptionMessage( 'Export_Command requires an active container or an injected Export_Service.' );

        new Export_Command();
    }

    public function test_invoke_calls_exporter_and_logs_content(): void {
        $this->exporter->expects( self::once() )
            ->method( 'results' )
            ->willReturn( 'Match,Score' );

        $command = new Export_Command( null, $this->exporter );
        $command( [], [ 'type' => 'results', 'league_id' => 1 ] );

        self::assertContains( 'Match,Score', WP_CLI::$logs );
        self::assertEmpty( WP_CLI::$errors );
        self::assertEmpty( WP_CLI::$successes );
    }

    public function test_invoke_supports_calendar_fixtures_and_report_results(): void {
        $this->exporter->expects( self::once() )
            ->method( 'calendar' )
            ->willReturn( 'CalendarData' );
        $this->exporter->expects( self::once() )
            ->method( 'fixtures' )
            ->willReturn( 'FixturesData' );
        $this->exporter->expects( self::once() )
            ->method( 'report_results' )
            ->willReturn( 'ReportResultsData' );

        $command = new Export_Command( null, $this->exporter );

        $command( [], [ 'type' => 'calendar' ] );
        self::assertContains( 'CalendarData', WP_CLI::$logs );

        $command( [], [ 'type' => 'fixtures' ] );
        self::assertContains( 'FixturesData', WP_CLI::$logs );

        $command( [], [ 'type' => 'report_results' ] );
        self::assertContains( 'ReportResultsData', WP_CLI::$logs );

        self::assertEmpty( WP_CLI::$errors );
        self::assertEmpty( WP_CLI::$successes );
    }

    public function test_invoke_writes_to_file_and_logs_success_when_output_specified(): void {
        $this->exporter->expects( self::once() )
            ->method( 'results' )
            ->willReturn( 'TeamA,TeamB,6-0' );

        $temp_file = tempnam( sys_get_temp_dir(), 'rm_export_' );
        self::assertIsString( $temp_file );

        try {
            $command = new Export_Command( null, $this->exporter );
            $command( [], [ 'type' => 'results', 'output' => $temp_file ] );

            self::assertFileExists( $temp_file );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            self::assertSame( 'TeamA,TeamB,6-0', file_get_contents( $temp_file ) );
            self::assertContains( 'Export saved to ' . $temp_file, WP_CLI::$successes );
            self::assertEmpty( WP_CLI::$errors );
            self::assertEmpty( WP_CLI::$logs );
        } finally {
            if ( file_exists( $temp_file ) ) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
                unlink( $temp_file );
            }
        }
    }

    public function test_invoke_handles_file_write_failure(): void {
        $this->exporter->expects( self::once() )
            ->method( 'results' )
            ->willReturn( 'ResultsData' );

        $invalid_path = sys_get_temp_dir() . '/non_existent_dir_' . uniqid() . '/export.csv';

        $command = new Export_Command( null, $this->exporter );
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        @$command( [], [ 'type' => 'results', 'output' => $invalid_path ] );

        self::assertContains( 'Failed to write to file: ' . $invalid_path, WP_CLI::$errors );
        self::assertEmpty( WP_CLI::$successes );
        self::assertEmpty( WP_CLI::$logs );
    }

    public function test_invoke_handles_invalid_type(): void {
        $command = new Export_Command( null, $this->exporter );
        $command( [], [ 'type' => 'invalid' ] );

        self::assertNotEmpty( WP_CLI::$errors );
        self::assertContains(
            'Invalid export type: invalid. Available types: calendar, results, fixtures, report_results.',
            WP_CLI::$errors
        );
        self::assertEmpty( WP_CLI::$logs );
        self::assertEmpty( WP_CLI::$successes );
    }
}
