#!/usr/bin/env php
<?php
/**
 * Reusable test runner script for RacketManager test suites.
 * Supports parallel worker execution across test suites and shards,
 * per-test timeout enforcement, and captures test counts, assertions,
 * and individual failed test names/messages.
 *
 * Usage: php tests/run-tests.php [suite] [options] [extra phpunit args...]
 *
 * Suite (first positional argument, case-insensitive, default: Unit):
 *   Unit | Integration | Architecture | all
 *
 * Options:
 *   --no-parallel          Run shards one at a time with a single worker.
 *   --parallel[=<n>]       Enable parallel workers, optionally setting the worker count.
 *   --workers=<n>          Set the number of parallel workers (default: CPU cores, max 16).
 *   --timeout=<seconds>    Per-test time limit enforced by PHPUnit (default: 15).
 *                          Alias: --per-test-timeout=<seconds>.
 *   --filter <pattern>     Restrict tests; runs each suite in a single process.
 *   -c, --configuration, --group, --exclude-group, --test-suffix <value>
 *                          Passed through to PHPUnit.
 *
 * Timed-out tests ("aborted after Ns") are reported by PHPUnit as risky, and --fail-on-risky
 * makes them fail the run. Risky tests are counted separately in the summary, not as passed.
 *
 * Exit status: 0 when every shard passes; 1 for failures, errors, warnings, deprecations,
 * risky or timed-out tests, unknown suites, or when no tests were executed.
 *
 * Examples:
 *   php tests/run-tests.php
 *   php tests/run-tests.php all --timeout=20
 *   php tests/run-tests.php Architecture --filter Layer_Rules_Test
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
 * Detect the number of available CPU cores.
 *
 * @return int Number of CPU cores, defaulting to 4.
 */
function racketmanager_detect_cpu_cores(): int {
	$cores = 4;
	if ( 'WIN' === strtoupper( substr( PHP_OS, 0, 3 ) ) ) {
		$env_cores = getenv( 'NUMBER_OF_PROCESSORS' );
		if ( false !== $env_cores && is_numeric( $env_cores ) ) {
			$cores = (int) $env_cores;
		}
	} else {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec -- Detect hardware cores on Unix/macOS.
		$nproc = shell_exec( 'nproc 2>/dev/null || sysctl -n hw.ncpu 2>/dev/null' );
		if ( null !== $nproc && is_numeric( trim( (string) $nproc ) ) ) {
			$cores = (int) trim( (string) $nproc );
		}
	}

	return max( 1, min( 16, $cores ) );
}

/**
 * Check if the extra PHPUnit arguments contain a test filter.
 *
 * @param array<int, string> $args Command-line arguments.
 * @return bool True if a filter argument is present.
 */
function racketmanager_has_filter_argument( array $args ): bool {
	foreach ( $args as $arg ) {
		if ( '--filter' === $arg || str_starts_with( $arg, '--filter=' ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Collect all test targets (suites or directory-level shards) for a given suite argument.
 *
 * @param string $suite_lower Lowercase suite argument (all, unit, architecture, integration).
 * @param string $tests_dir   Absolute path to the tests directory.
 * @param bool   $is_filtered Whether filter arguments are present.
 * @return array<int, array{name: string, target?: string, targets?: array<int, string>, is_suite: bool}> List of shard definitions.
 */
function racketmanager_resolve_shards( string $suite_lower, string $tests_dir, bool $is_filtered = false ): array {
	$valid_suites = array( 'all', 'unit', 'architecture', 'integration' );
	if ( ! in_array( $suite_lower, $valid_suites, true ) ) {
		return array();
	}

	$suite_canonical = match ( $suite_lower ) {
		'all'          => 'all',
		'unit'         => 'Unit',
		'architecture' => 'Architecture',
		'integration'  => 'Integration',
	};

	// When filtering tests, fall back to a single process target for maximum speed and simplicity.
	if ( $is_filtered ) {
		return array(
			array(
				'name'     => $suite_canonical,
				'target'   => ( 'all' === $suite_lower ) ? '' : $suite_canonical,
				'is_suite' => true,
			),
		);
	}

	if ( 'architecture' === $suite_lower ) {
		return array(
			array(
				'name'     => 'Architecture',
				'target'   => 'Architecture',
				'is_suite' => true,
			),
		);
	}

	if ( 'integration' === $suite_lower ) {
		return array(
			array(
				'name'     => 'Integration',
				'target'   => 'Integration',
				'is_suite' => true,
			),
		);
	}

	$shards = array();

	if ( 'all' === $suite_lower ) {
		$shards[] = array(
			'name'     => 'Architecture',
			'target'   => 'Architecture',
			'is_suite' => true,
		);
		$shards[] = array(
			'name'     => 'Integration',
			'target'   => 'Integration',
			'is_suite' => true,
		);
	}

	// For 'all' or 'unit', shard the Unit test directories.
	$unit_dir = $tests_dir . '/Unit';
	if ( is_dir( $unit_dir ) ) {
		$entries = scandir( $unit_dir );
		if ( false !== $entries ) {
			$root_files = array();
			foreach ( $entries as $entry ) {
				if ( '.' === $entry || '..' === $entry ) {
					continue;
				}
				$path = $unit_dir . '/' . $entry;
				if ( is_dir( $path ) ) {
					$shards[] = array(
						'name'     => 'Unit/' . $entry,
						'targets'  => array( 'tests/Unit/' . $entry ),
						'is_suite' => false,
					);
				} elseif ( str_ends_with( $entry, 'Test.php' ) ) {
					$root_files[] = 'tests/Unit/' . $entry;
				}
			}

			if ( ! empty( $root_files ) ) {
				$shards[] = array(
					'name'     => 'Unit/RootTests',
					'targets'  => $root_files,
					'is_suite' => false,
				);
			}
		}
	}

	return $shards;
}

/**
 * Extract the risky test count from the PHPUnit text summary ("Risky: N").
 *
 * @param string $output Combined PHPUnit stdout and stderr.
 * @return int Number of risky tests reported, or 0 when none are reported.
 */
function racketmanager_parse_risky_count( string $output ): int {
	if ( 1 === preg_match( '/\bRisky: (\d+)/', $output, $matches ) ) {
		return (int) $matches[1];
	}

	return 0;
}

/**
 * Parse a JUnit XML file and return statistics and failures.
 *
 * @param string $junit_file Path to JUnit XML file.
 * @return array{
 *     total: int,
 *     assertions: int,
 *     passed: int,
 *     failed: int,
 *     errors: int,
 *     skipped: int,
 *     failures: array<int, array{type: string, test: string, time: float, message: string}>,
 *     parse_error: ?string
 * }
 */
function racketmanager_parse_junit_log( string $junit_file ): array {
	$result = array(
		'total'       => 0,
		'assertions'  => 0,
		'passed'      => 0,
		'failed'      => 0,
		'errors'      => 0,
		'skipped'     => 0,
		'failures'    => array(),
		'parse_error' => null,
	);

	if ( ! is_file( $junit_file ) || 0 === filesize( $junit_file ) ) {
		$result['parse_error'] = 'JUnit log file was empty or missing.';
		return $result;
	}

	$prev_errors = libxml_use_internal_errors( true );
	$xml         = simplexml_load_file( $junit_file );
	$errors      = libxml_get_errors();
	libxml_clear_errors();
	libxml_use_internal_errors( $prev_errors );

	if ( false === $xml ) {
		$messages = array();
		foreach ( $errors as $error ) {
			$messages[] = trim( $error->message );
		}
		$result['parse_error'] = 'XML parse error: ' . implode( '; ', $messages );
		return $result;
	}

	$testcases = $xml->xpath( '//testcase' );
	if ( false === $testcases ) {
		$result['parse_error'] = 'Failed to evaluate testcase xpath query.';
		return $result;
	}

	$result['total'] = count( $testcases );

	foreach ( $testcases as $testcase ) {
		$class       = (string) $testcase['class'];
		$name        = (string) $testcase['name'];
		$time        = (float) $testcase['time'];
		$assertions  = isset( $testcase['assertions'] ) ? (int) $testcase['assertions'] : 0;
		$result['assertions'] += $assertions;

		if ( isset( $testcase->failure ) ) {
			++$result['failed'];
			$result['failures'][] = array(
				'type'    => 'Failure',
				'test'    => "$class::$name",
				'time'    => $time,
				'message' => trim( (string) $testcase->failure ),
			);
		} elseif ( isset( $testcase->error ) ) {
			++$result['errors'];
			$result['failures'][] = array(
				'type'    => 'Error',
				'test'    => "$class::$name",
				'time'    => $time,
				'message' => trim( (string) $testcase->error ),
			);
		} elseif ( isset( $testcase->skipped ) ) {
			++$result['skipped'];
		} else {
			++$result['passed'];
		}
	}

	return $result;
}

// -----------------------------------------------------------------------------
// CLI Argument Parsing & Setup
// -----------------------------------------------------------------------------
$raw_args               = array_slice( $argv, 1 );
$suite_arg              = null;
$parallel_enabled       = true;
$worker_count           = racketmanager_detect_cpu_cores();
$per_test_timeout       = 15;
$extra_args             = array();
$expecting_option_value = false;

foreach ( $raw_args as $arg ) {
	if ( $expecting_option_value ) {
		$extra_args[]           = $arg;
		$expecting_option_value = false;
		continue;
	}

	if ( '--no-parallel' === $arg ) {
		$parallel_enabled = false;
	} elseif ( str_starts_with( $arg, '--parallel' ) ) {
		$parallel_enabled = true;
		if ( str_contains( $arg, '=' ) ) {
			$val = (int) substr( $arg, strpos( $arg, '=' ) + 1 );
			if ( $val > 0 ) {
				$worker_count = $val;
			}
		}
	} elseif ( str_starts_with( $arg, '--workers=' ) ) {
		$val = (int) substr( $arg, 10 );
		if ( $val > 0 ) {
			$worker_count = $val;
		}
	} elseif ( str_starts_with( $arg, '--per-test-timeout=' ) || str_starts_with( $arg, '--timeout=' ) ) {
		$val = (int) substr( $arg, strpos( $arg, '=' ) + 1 );
		if ( $val > 0 ) {
			$per_test_timeout = $val;
		}
	} elseif ( in_array( $arg, array( '--filter', '-c', '--configuration', '--test-suffix', '--group', '--exclude-group' ), true ) ) {
		$expecting_option_value = true;
		$extra_args[]           = $arg;
	} elseif ( ! str_starts_with( $arg, '-' ) && null === $suite_arg ) {
		$suite_arg = $arg;
	} else {
		$extra_args[] = $arg;
	}
}

if ( null === $suite_arg ) {
	$suite_arg = 'Unit';
}

$suite_lower  = strtolower( $suite_arg );
$valid_suites = array( 'all', 'unit', 'architecture', 'integration' );

if ( ! in_array( $suite_lower, $valid_suites, true ) ) {
	racketmanager_write_test_runner_output(
		sprintf(
			"Error: Unknown test suite '%s'. Valid suites are: Unit, Integration, Architecture, all.%s",
			$suite_arg,
			PHP_EOL
		)
	);
	exit( 1 );
}

$suite_canonical = match ( $suite_lower ) {
	'all'          => 'all',
	'unit'         => 'Unit',
	'architecture' => 'Architecture',
	'integration'  => 'Integration',
};

$is_filtered = racketmanager_has_filter_argument( $extra_args );
$root_dir    = dirname( __DIR__ );
$phpunit_bin = $root_dir . '/vendor/bin/phpunit';
$tests_dir   = $root_dir . '/tests';
$shards      = racketmanager_resolve_shards( $suite_lower, $tests_dir, $is_filtered );

if ( empty( $shards ) ) {
	racketmanager_write_test_runner_output( "Error: No matching test shards found for suite '$suite_canonical'." . PHP_EOL );
	exit( 1 );
}

$total_shards   = count( $shards );
$active_workers = min( $worker_count, $total_shards );
if ( ! $parallel_enabled || $total_shards <= 1 ) {
	$active_workers = 1;
}

racketmanager_write_test_runner_output(
	sprintf(
		'Starting RacketManager test runner [Suite: %s, Shards: %d, Workers: %d, Per-Test Timeout: %ds]...%s',
		$suite_canonical,
		$total_shards,
		$active_workers,
		$per_test_timeout,
		PHP_EOL
	)
);

// -----------------------------------------------------------------------------
// Parallel Worker Execution Loop
// -----------------------------------------------------------------------------
$overall_start   = microtime( true );
$shard_queue     = $shards;
$running_workers = array();
$completed_data  = array();
$has_failures    = false;

while ( ! empty( $shard_queue ) || ! empty( $running_workers ) ) {
	// Spawn workers up to maximum concurrency limit.
	$active_count = count( $running_workers );
	while ( $active_count < $active_workers && ! empty( $shard_queue ) ) {
		$shard      = array_shift( $shard_queue );
		$temp_junit = tempnam( sys_get_temp_dir(), 'rm_junit_' );

		if ( false === $temp_junit ) {
			racketmanager_write_test_runner_output( "Error creating temporary file for shard {$shard['name']}." . PHP_EOL );
			$has_failures = true;
			continue;
		}

		$cmd_target = '';
		if ( $shard['is_suite'] ) {
			$target = $shard['target'] ?? '';
			if ( '' !== $target ) {
				$cmd_target = '--testsuite ' . escapeshellarg( $target );
			}
		} else {
			$shard_targets = $shard['targets'] ?? array();
			$cmd_target    = implode( ' ', array_map( 'escapeshellarg', $shard_targets ) );
		}

		$cmd_parts = array(
			escapeshellcmd( $phpunit_bin ),
		);
		if ( '' !== $cmd_target ) {
			$cmd_parts[] = $cmd_target;
		}
		$cmd_parts[] = '--log-junit ' . escapeshellarg( $temp_junit );
		$cmd_parts[] = '--enforce-time-limit';
		$cmd_parts[] = '--default-time-limit ' . (int) $per_test_timeout;
		$cmd_parts[] = '--display-warnings';
		$cmd_parts[] = '--display-deprecations';
		$cmd_parts[] = '--display-phpunit-notices';
		$cmd_parts[] = '--fail-on-warning';
		$cmd_parts[] = '--fail-on-deprecation';
		$cmd_parts[] = '--fail-on-risky';
		foreach ( $extra_args as $extra_arg ) {
			$cmd_parts[] = escapeshellarg( $extra_arg );
		}
		$cmd = implode( ' ', $cmd_parts );

		$descriptors = array(
			0 => array( 'pipe', 'r' ),
			1 => array( 'pipe', 'w' ),
			2 => array( 'pipe', 'w' ),
		);

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Execute parallel PHPUnit processes.
		$process = proc_open( $cmd, $descriptors, $pipes, $root_dir );

		if ( is_resource( $process ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close unused STDIN pipe.
			fclose( $pipes[0] );
			stream_set_blocking( $pipes[1], false );
			stream_set_blocking( $pipes[2], false );

			$running_workers[] = array(
				'shard'      => $shard,
				'temp_junit' => $temp_junit,
				'process'    => $process,
				'pipes'      => $pipes,
				'stdout'     => '',
				'stderr'     => '',
				'start'      => microtime( true ),
			);
			$active_count      = count( $running_workers );
		} else {
			racketmanager_write_test_runner_output( "Error: Failed to spawn process for shard {$shard['name']}." . PHP_EOL );
			$has_failures = true;
			if ( is_file( $temp_junit ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Clean up temporary JUnit log.
				unlink( $temp_junit );
			}
		}
	}

	// Check running workers for completion.
	foreach ( $running_workers as $index => &$worker ) {
		// Read available output.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Read non-blocking process output.
		$stdout_chunk = fread( $worker['pipes'][1], 8192 );
		if ( false !== $stdout_chunk && '' !== $stdout_chunk ) {
			$worker['stdout'] .= $stdout_chunk;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Read non-blocking process output.
		$stderr_chunk = fread( $worker['pipes'][2], 8192 );
		if ( false !== $stderr_chunk && '' !== $stderr_chunk ) {
			$worker['stderr'] .= $stderr_chunk;
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_get_status -- Check if child process has exited.
		$proc_status = proc_get_status( $worker['process'] );

		if ( ! $proc_status['running'] ) {
			// Drain remaining buffer.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_stream_get_contents -- Read remaining buffer.
			$worker['stdout'] .= stream_get_contents( $worker['pipes'][1] );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_stream_get_contents -- Read remaining buffer.
			$worker['stderr'] .= stream_get_contents( $worker['pipes'][2] );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close pipes.
			fclose( $worker['pipes'][1] );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close pipes.
			fclose( $worker['pipes'][2] );

			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_close -- Close child process.
			$exit_code = proc_close( $worker['process'] );
			$elapsed   = microtime( true ) - $worker['start'];

			$parsed = racketmanager_parse_junit_log( $worker['temp_junit'] );

			// PHPUnit's JUnit log has no risky element, so risky tests are taken from the text summary and moved out of passed.
			$parsed['risky']  = min( racketmanager_parse_risky_count( $worker['stdout'] . $worker['stderr'] ), $parsed['passed'] );
			$parsed['passed'] -= $parsed['risky'];
			if ( is_file( $worker['temp_junit'] ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Clean up temporary JUnit log.
				unlink( $worker['temp_junit'] );
			}

			$is_empty_shard = (
				0 === $parsed['total']
				&& 0 === $parsed['failed']
				&& 0 === $parsed['errors']
				&& null === $parsed['parse_error']
				&& (
					str_contains( $worker['stdout'], 'No tests executed!' )
					|| str_contains( $worker['stderr'], 'No tests executed!' )
				)
			);

			if ( $is_empty_shard && $is_filtered ) {
				$shard_failed = false;
				$status_label = 'EMPTY';
			} else {
				$shard_failed = (
					0 !== $exit_code
					|| 0 < $parsed['failed']
					|| 0 < $parsed['errors']
					|| 0 < $parsed['risky']
					|| null !== $parsed['parse_error']
				);
				$status_label = $shard_failed ? 'FAILED' : 'PASS';
			}

			if ( $shard_failed ) {
				$has_failures = true;
			}

			$worker_result = array(
				'shard'        => $worker['shard'],
				'elapsed'      => $elapsed,
				'exit_code'    => $exit_code,
				'parsed'       => $parsed,
				'shard_failed' => $shard_failed,
				'stdout'       => $worker['stdout'],
				'stderr'       => $worker['stderr'],
			);

			$completed_data[] = $worker_result;

			// Report shard completion indicator.
			racketmanager_write_test_runner_output(
				sprintf(
					'  [%s] %-24s (%4d tests, %5d assertions, %6.2fs)%s',
					$status_label,
					$worker['shard']['name'],
					$parsed['total'],
					$parsed['assertions'],
					$elapsed,
					PHP_EOL
				)
			);

			unset( $running_workers[ $index ] );
		}
	}
	unset( $worker );

	if ( ! empty( $running_workers ) ) {
		usleep( 10000 ); // Sleep 10ms to prevent high CPU busy-wait.
	}
}

$overall_elapsed = microtime( true ) - $overall_start;

// -----------------------------------------------------------------------------
// Aggregate Results & Summary Display
// -----------------------------------------------------------------------------
$total_tests      = 0;
$total_assertions = 0;
$total_passed     = 0;
$total_failed     = 0;
$total_errors     = 0;
$total_skipped    = 0;
$total_risky      = 0;
$all_failures     = array();

foreach ( $completed_data as $data ) {
	$parsed            = $data['parsed'];
	$total_tests      += $parsed['total'];
	$total_assertions += $parsed['assertions'];
	$total_passed     += $parsed['passed'];
	$total_failed     += $parsed['failed'];
	$total_errors     += $parsed['errors'];
	$total_skipped    += $parsed['skipped'];
	$total_risky      += $parsed['risky'];

	foreach ( $parsed['failures'] as $failure ) {
		$all_failures[] = array(
			'shard'   => $data['shard']['name'],
			'type'    => $failure['type'],
			'test'    => $failure['test'],
			'time'    => $failure['time'],
			'message' => $failure['message'],
		);
	}
}

racketmanager_write_test_runner_output( PHP_EOL );
racketmanager_write_test_runner_output( '============================================================' . PHP_EOL );
racketmanager_write_test_runner_output( '                  TEST EXECUTION SUMMARY                    ' . PHP_EOL );
racketmanager_write_test_runner_output( '============================================================' . PHP_EOL );
racketmanager_write_test_runner_output( sprintf( ' Target:           %s%s', $suite_canonical, PHP_EOL ) );
racketmanager_write_test_runner_output( sprintf( ' Wall Clock Time:  %.3fs%s', $overall_elapsed, PHP_EOL ) );
racketmanager_write_test_runner_output( sprintf( ' Shards / Workers: %d / %d%s', $total_shards, $active_workers, PHP_EOL ) );
racketmanager_write_test_runner_output( sprintf( ' Per-Test Cap:     %ds%s', $per_test_timeout, PHP_EOL ) );
racketmanager_write_test_runner_output( sprintf( ' Total Tests:      %d%s', $total_tests, PHP_EOL ) );
racketmanager_write_test_runner_output( sprintf( ' Assertions:       %d%s', $total_assertions, PHP_EOL ) );
racketmanager_write_test_runner_output( sprintf( ' Passed:           %d%s', $total_passed, PHP_EOL ) );
racketmanager_write_test_runner_output( sprintf( ' Failed:           %d%s', $total_failed, PHP_EOL ) );
racketmanager_write_test_runner_output( sprintf( ' Errors:           %d%s', $total_errors, PHP_EOL ) );
racketmanager_write_test_runner_output( sprintf( ' Skipped:          %d%s', $total_skipped, PHP_EOL ) );
racketmanager_write_test_runner_output( sprintf( ' Risky:            %d%s', $total_risky, PHP_EOL ) );
racketmanager_write_test_runner_output( '============================================================' . PHP_EOL );

if ( 0 === $total_tests ) {
	$has_failures = true;
	racketmanager_write_test_runner_output( PHP_EOL . 'Error: No tests were executed.' . PHP_EOL );
}

if ( ! empty( $all_failures ) ) {
	racketmanager_write_test_runner_output( PHP_EOL . 'FAILURES / ERRORS:' . PHP_EOL );
	foreach ( $all_failures as $index => $failure ) {
		racketmanager_write_test_runner_output(
			sprintf(
				'[%d] %s in [%s] %s (%.3fs):%s%s%s',
				$index + 1,
				$failure['type'],
				$failure['shard'],
				$failure['test'],
				$failure['time'],
				PHP_EOL,
				$failure['message'],
				PHP_EOL . PHP_EOL
			)
		);
	}
}

// Print diagnostics if any shard failed or has risky tests.
foreach ( $completed_data as $data ) {
	if ( ( $data['shard_failed'] && empty( $data['parsed']['failures'] ) ) || $data['parsed']['risky'] > 0 ) {
		racketmanager_write_test_runner_output(
			sprintf(
				'%sDiagnostics for shard [%s] (exit code %d):%s%s%s',
				PHP_EOL,
				$data['shard']['name'],
				$data['exit_code'],
				PHP_EOL,
				$data['stdout'] . $data['stderr'],
				PHP_EOL
			)
		);
	}
}

exit( $has_failures ? 1 : 0 );
