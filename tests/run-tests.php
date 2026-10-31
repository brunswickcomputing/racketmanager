#!/usr/bin/env php
<?php
/**
 * Reusable test runner script for RacketManager test suites.
 * Captures per-test failures/messages plus passed/failed/skipped stats.
 *
 * @package RacketManager
 */

declare( strict_types=1 );

/**
 * Write output directly to the terminal for this CLI-only script.
 *
 * @param string $message Text to write.
 */
function racketmanager_write_test_runner_output( string $message ): void {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Write CLI-only test diagnostics to STDOUT.
	fwrite( STDOUT, $message );
}

/**
 * Run configured PHPUnit suites and report summarized results.
 *
 * Usage:
 *   php tests/run-tests.php [Unit|Integration|Architecture|all] [extra phpunit args...]
 */
$suite_arg  = $argv[1] ?? 'Unit';
$extra_args = array_slice( $argv, 2 );

$suites_to_run = ( 'all' === strtolower( $suite_arg ) ) ? array( 'Architecture', 'Unit', 'Integration' ) : array( $suite_arg );

$phpunit_bin         = dirname( __DIR__ ) . '/vendor/bin/phpunit';
$has_overall_failure = false;

foreach ( $suites_to_run as $suite ) {
	$temp_junit = tempnam( sys_get_temp_dir(), 'phpunit_junit_' );
	if ( false === $temp_junit ) {
		racketmanager_write_test_runner_output( "Error: Unable to create a JUnit log file for suite $suite." . PHP_EOL );
		$has_overall_failure = true;
		continue;
	}

	$cmd = sprintf(
		'%s --testsuite %s --log-junit %s --enforce-time-limit --default-time-limit 20 --display-warnings --display-deprecations --display-phpunit-notices --fail-on-warning --fail-on-deprecation %s',
		escapeshellcmd( $phpunit_bin ),
		escapeshellarg( $suite ),
		escapeshellarg( $temp_junit ),
		implode( ' ', array_map( 'escapeshellarg', $extra_args ) )
	);

	$output          = array();
	$exit_code       = 0;
	$start           = microtime( true );
	$elapsed         = 0.0;
	$failed          = 0;
	$error_count     = 0;
	$passed          = 0;
	$skipped         = 0;
	$total_testcases = 0;
	$failures_list   = array();
	$suite_failed    = false;

	try {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- This CLI utility must invoke PHPUnit.
		exec( $cmd . ' 2>&1', $output, $exit_code );
		$elapsed      = microtime( true ) - $start;
		$suite_failed = 0 !== $exit_code;

		if ( ! is_file( $temp_junit ) || 0 === filesize( $temp_junit ) ) {
			racketmanager_write_test_runner_output( "Error: JUnit log file was not generated for suite $suite." . PHP_EOL );
			$suite_failed = true;
		} else {
			$previous_libxml_errors = libxml_use_internal_errors( true );
			$xml                    = simplexml_load_file( $temp_junit );
			$parse_errors           = libxml_get_errors();
			libxml_clear_errors();
			libxml_use_internal_errors( $previous_libxml_errors );

			if ( false === $xml ) {
				racketmanager_write_test_runner_output( "Error: Failed to parse JUnit XML log for suite $suite." . PHP_EOL );
				foreach ( $parse_errors as $parse_error ) {
					$output[] = trim( $parse_error->message );
				}
				$suite_failed = true;
			} else {
				$testcases = $xml->xpath( '//testcase' );
				if ( false === $testcases ) {
					$testcases    = array();
					$suite_failed = true;
				}
				$total_testcases = count( $testcases );

				foreach ( $testcases as $testcase ) {
					$class = (string) $testcase['class'];
					$name  = (string) $testcase['name'];
					$time  = (float) $testcase['time'];

					if ( isset( $testcase->failure ) ) {
						++$failed;
						$failures_list[] = array(
							'type'    => 'Failure',
							'test'    => "$class::$name",
							'time'    => $time,
							'message' => trim( (string) $testcase->failure ),
						);
					} elseif ( isset( $testcase->error ) ) {
						++$error_count;
						$failures_list[] = array(
							'type'    => 'Error',
							'test'    => "$class::$name",
							'time'    => $time,
							'message' => trim( (string) $testcase->error ),
						);
					} elseif ( isset( $testcase->skipped ) ) {
						++$skipped;
					} else {
						++$passed;
					}
				}

				if ( 0 === $total_testcases ) {
					racketmanager_write_test_runner_output( "Error: PHPUnit ran zero tests for suite $suite." . PHP_EOL );
					$suite_failed = true;
				}
			}
		}

		if ( 0 < $failed || 0 < $error_count ) {
			$suite_failed = true;
		}

		racketmanager_write_test_runner_output( '============================================================' . PHP_EOL );
		racketmanager_write_test_runner_output( " Test Suite:     $suite" . PHP_EOL );
		racketmanager_write_test_runner_output( ' Execution Time: ' . number_format( $elapsed, 3 ) . 's' . PHP_EOL );
		racketmanager_write_test_runner_output( " Total Tests:    $total_testcases" . PHP_EOL );
		racketmanager_write_test_runner_output( " Passed:         $passed" . PHP_EOL );
		racketmanager_write_test_runner_output( " Failed:         $failed" . PHP_EOL );
		racketmanager_write_test_runner_output( " Errors:         $error_count" . PHP_EOL );
		racketmanager_write_test_runner_output( " Skipped:        $skipped" . PHP_EOL );
		racketmanager_write_test_runner_output( '============================================================' . PHP_EOL );

		if ( ! empty( $failures_list ) ) {
			racketmanager_write_test_runner_output( PHP_EOL . "FAILURES / ERRORS in suite [$suite]:" . PHP_EOL );
			foreach ( $failures_list as $index => $failure ) {
				racketmanager_write_test_runner_output(
					sprintf(
						'[%d] %s in %s (%.3fs):%s%s%s',
						$index + 1,
						$failure['type'],
						$failure['test'],
						$failure['time'],
						PHP_EOL,
						$failure['message'],
						PHP_EOL . PHP_EOL
					)
				);
			}
		}

		if ( $suite_failed ) {
			$has_overall_failure = true;
			if ( ! empty( $output ) ) {
				racketmanager_write_test_runner_output( PHP_EOL . "PHPUnit diagnostics for suite [$suite]:" . PHP_EOL );
				racketmanager_write_test_runner_output( implode( PHP_EOL, $output ) . PHP_EOL );
			}
		}
	} finally {
		if ( is_file( $temp_junit ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove this runner's temporary JUnit file.
			unlink( $temp_junit );
		}
	}
}

exit( $has_overall_failure ? 1 : 0 );
