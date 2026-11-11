#!/usr/bin/env php
<?php
declare( strict_types=1 );

/**
 * Reusable parallel PHPUnit test runner with per-test isolation.
 *
 * Individual tests (including data-set variants) are discovered with PHPUnit's --list-tests-xml.
 * Each test runs in its own worker process, so the timeout applies to a single test and every
 * failure is reported with its test id and error message (taken from a JUnit log of the worker).
 *
 * Usage:
 *   php scripts/run-parallel-tests.php [options]
 *
 * Options:
 *   --concurrency=<int>  Number of parallel workers (default: 4)
 *   --timeout=<int>      Per-test timeout in seconds (default: 15, allowed: 5-20)
 *   --testsuite=<name>   PHPUnit testsuite to discover (Unit, Integration, Architecture, all; default: Unit)
 *   --path=<path>        Specific test directory or file (overrides testsuite)
 *   --filter=<pattern>   Only run tests whose id or file contains the pattern (or matches it as a regex)
 *   --help               Display this help message
 *
 * Every test costs one PHP/PHPUnit bootstrap, so use --path or --filter for samples.
 * For the whole suite, ./vendor/bin/phpunit -c phpunit.xml.dist is far faster (about 7s).
 */

const MAX_OUTPUT_CHARS = 4000;

$options = getopt( '', [
    'concurrency::',
    'timeout::',
    'testsuite::',
    'path::',
    'filter::',
    'help',
] );

if ( isset( $options['help'] ) ) {
    echo <<<HELP
Parallel PHPUnit Test Runner
Usage: php scripts/run-parallel-tests.php [options]

Options:
  --concurrency=<int>  Number of parallel workers (default: 4)
  --timeout=<int>      Per-test timeout in seconds (default: 15, allowed: 5-20)
  --testsuite=<name>   PHPUnit testsuite to discover (Unit, Integration, Architecture, all) (default: Unit)
  --path=<path>        Specific test directory or file (overrides testsuite)
  --filter=<pattern>   Only run tests whose id or file contains the pattern (or matches it as a regex)
  --help               Display this help message

HELP;
    exit( 0 );
}

$concurrency = isset( $options['concurrency'] ) ? max( 1, (int) $options['concurrency'] ) : 4;
$timeout     = isset( $options['timeout'] ) ? max( 5, min( 20, (int) $options['timeout'] ) ) : 15;
$testsuite   = $options['testsuite'] ?? 'Unit';
$customPath  = $options['path'] ?? null;
$filter      = $options['filter'] ?? null;

$baseDir   = dirname( __DIR__ );
$phpunit   = $baseDir . '/vendor/bin/phpunit';
$config    = $baseDir . '/phpunit.xml.dist';
$phpBinary = PHP_BINARY;

if ( ! file_exists( $phpunit ) ) {
    fwrite( STDERR, "Error: PHPUnit binary not found at $phpunit\n" );
    exit( 1 );
}

$selectors = [];
if ( $customPath ) {
    $discoveryPath = str_starts_with( $customPath, '/' ) ? $customPath : $baseDir . '/' . $customPath;
    if ( ! file_exists( $discoveryPath ) ) {
        fwrite( STDERR, "Error: test path not found at $discoveryPath\n" );
        exit( 1 );
    }
    $selectors[] = $discoveryPath;
    $scope       = $customPath;
} elseif ( 'all' !== strtolower( $testsuite ) ) {
    $selectors = [ '--testsuite', ucfirst( strtolower( $testsuite ) ) ];
    $scope     = ucfirst( strtolower( $testsuite ) );
} else {
    $scope = 'all';
}

/**
 * Run a command to completion and capture its output.
 *
 * @param array<int, string> $cmd Command and arguments.
 * @param string             $cwd Working directory.
 * @return array{exit: int, stdout: string, stderr: string}
 */
function run_blocking( array $cmd, string $cwd ): array {
    $proc = proc_open( $cmd, [ 0 => [ 'pipe', 'r' ], 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes, $cwd );
    if ( ! is_resource( $proc ) ) {
        return [ 'exit' => 1, 'stdout' => '', 'stderr' => 'proc_open failed' ];
    }
    fclose( $pipes[0] );
    $stdout = stream_get_contents( $pipes[1] );
    $stderr = stream_get_contents( $pipes[2] );
    fclose( $pipes[1] );
    fclose( $pipes[2] );

    return [ 'exit' => proc_close( $proc ), 'stdout' => $stdout, 'stderr' => $stderr ];
}

/**
 * Send a signal to a specific process ID.
 *
 * @param int $pid    Target process ID.
 * @param int $signal Signal number.
 */
function kill_pid( int $pid, int $signal = 9 ): void {
    if ( $pid <= 1 ) {
        return;
    }

    if ( function_exists( 'posix_kill' ) ) {
        @posix_kill( $pid, $signal );
    } else {
        @exec( sprintf( 'kill -%d %d 2>/dev/null', $signal, $pid ) );
    }
}

/**
 * Find all descendant process IDs of a given root PID (recursive).
 *
 * @param int $rootPid Root process ID.
 * @return list<int> List of descendant PIDs.
 */
function get_descendant_pids( int $rootPid ): array {
    if ( $rootPid <= 1 ) {
        return [];
    }

    static $hasPgrep = null;
    if ( null === $hasPgrep ) {
        $whichOutput = [];
        $whichCode   = 1;
        @exec( 'which pgrep 2>/dev/null', $whichOutput, $whichCode );
        $hasPgrep = ( 0 === $whichCode );
    }

    $descendants = [];
    $queue       = [ $rootPid ];

    if ( $hasPgrep ) {
        while ( ! empty( $queue ) ) {
            $parent   = array_shift( $queue );
            $children = [];
            @exec( sprintf( 'pgrep -P %d 2>/dev/null', $parent ), $children );
            foreach ( $children as $line ) {
                $childPid = (int) trim( $line );
                if ( $childPid > 1 && ! in_array( $childPid, $descendants, true ) ) {
                    $descendants[] = $childPid;
                    $queue[]       = $childPid;
                }
            }
        }

        return $descendants;
    }

    // Fallback: parse ps -A -o pid,ppid
    $psOutput = [];
    @exec( 'ps -A -o pid,ppid 2>/dev/null', $psOutput );
    $childrenByPpid = [];
    foreach ( $psOutput as $line ) {
        $line = trim( $line );
        if ( '' === $line || ! preg_match( '/^(\d+)\s+(\d+)$/', $line, $matches ) ) {
            continue;
        }
        $p  = (int) $matches[1];
        $pp = (int) $matches[2];
        if ( $p > 1 && $pp > 0 ) {
            $childrenByPpid[ $pp ][] = $p;
        }
    }

    while ( ! empty( $queue ) ) {
        $parent = array_shift( $queue );
        if ( isset( $childrenByPpid[ $parent ] ) ) {
            foreach ( $childrenByPpid[ $parent ] as $childPid ) {
                if ( ! in_array( $childPid, $descendants, true ) ) {
                    $descendants[] = $childPid;
                    $queue[]       = $childPid;
                }
            }
        }
    }

    return $descendants;
}

/**
 * Safely terminate a worker process and any descendant processes (e.g. RunInSeparateProcess children).
 *
 * @param resource $proc    Process resource returned by proc_open.
 * @param int|null $rootPid Process ID of the root worker process.
 * @param int      $signal  Signal to send (default SIGKILL / 9).
 */
function terminate_process_tree( $proc, ?int $rootPid = null, int $signal = 9 ): void {
    if ( ! is_resource( $proc ) ) {
        return;
    }

    if ( null === $rootPid || $rootPid <= 1 ) {
        $status  = proc_get_status( $proc );
        $rootPid = $status['pid'] ?? 0;
    }

    if ( $rootPid > 1 ) {
        $descendants = get_descendant_pids( $rootPid );
        // Terminate children deepest-first before terminating parent,
        // so children are not reparented to PID 1.
        foreach ( array_reverse( $descendants ) as $childPid ) {
            kill_pid( $childPid, $signal );
        }
    }

    proc_terminate( $proc, $signal );

    // Ensure any descendants spawned during termination are also killed.
    if ( $rootPid > 1 ) {
        $remaining = get_descendant_pids( $rootPid );
        foreach ( array_reverse( $remaining ) as $childPid ) {
            kill_pid( $childPid, $signal );
        }
    }
}

/**
 * Discover individual tests using PHPUnit's --list-tests-xml.
 *
 * @param array<int, string> $selectors Testsuite or path selectors.
 * @return array<int, array{id: string, class: string, method: string, dataset: ?string, file: string}>|null
 */
function discover_tests( string $phpBinary, string $phpunit, string $config, string $baseDir, array $selectors ): ?array {
    $listFile = tempnam( sys_get_temp_dir(), 'rm_list_' );
    if ( false === $listFile ) {
        fwrite( STDERR, "Error: could not create temporary file for test discovery\n" );
        return null;
    }

    $cmd = array_merge( [ $phpBinary, $phpunit, '-c', $config ], $selectors, [ '--list-tests-xml', $listFile ] );
    $run = run_blocking( $cmd, $baseDir );

    if ( 0 !== $run['exit'] || ! is_file( $listFile ) || 0 === filesize( $listFile ) ) {
        @unlink( $listFile );
        fwrite( STDERR, "Error: test discovery failed\n" . $run['stdout'] . $run['stderr'] );
        return null;
    }

    libxml_use_internal_errors( true );
    $dom    = new DOMDocument();
    $loaded = $dom->load( $listFile );
    @unlink( $listFile );
    if ( ! $loaded ) {
        fwrite( STDERR, "Error: could not parse PHPUnit test listing\n" );
        return null;
    }

    $tests = [];
    foreach ( $dom->getElementsByTagName( 'testClass' ) as $testClass ) {
        $class = $testClass->getAttribute( 'name' );
        $file  = $testClass->getAttribute( 'file' );
        foreach ( $testClass->getElementsByTagName( 'testMethod' ) as $testMethod ) {
            $id    = $testMethod->getAttribute( 'id' );
            $hash  = strpos( $id, '#' );
            $tests[] = [
                'id'      => $id,
                'class'   => $class,
                'method'  => $testMethod->getAttribute( 'name' ),
                'dataset' => false === $hash ? null : substr( $id, $hash + 1 ),
                'file'    => $file,
            ];
        }
    }

    return $tests;
}

/**
 * Build a --filter value that selects exactly one test (and data set) by its full name.
 *
 * A filter starting with "/" that compiles as a delimited regex is used verbatim by PHPUnit,
 * so the class, method and data set names are escaped here rather than relying on PHPUnit's
 * "#dataset" shorthand, which does not escape regex metacharacters.
 *
 * @param array{id: string, class: string, method: string, dataset: ?string, file: string} $test Test definition.
 */
function build_filter( array $test ): string {
    $name = preg_quote( $test['class'] . '::' . $test['method'], '/' );
    if ( null === $test['dataset'] ) {
        return '/^' . $name . '$/';
    }

    $dataset = preg_quote( $test['dataset'], '/' );

    return '/^' . $name . ' with data set (?:#' . $dataset . '|"' . $dataset . '")$/';
}

/**
 * Read failure, error and skip records from a worker's JUnit log.
 *
 * @return array{problems: array<int, array{kind: string, message: string}>, skipped: bool}
 */
function read_junit_result( string $junitFile ): array {
    $result = [ 'problems' => [], 'skipped' => false ];
    if ( ! is_file( $junitFile ) || 0 === filesize( $junitFile ) ) {
        return $result;
    }

    libxml_use_internal_errors( true );
    $dom = new DOMDocument();
    if ( ! $dom->load( $junitFile ) ) {
        return $result;
    }

    $xpath = new DOMXPath( $dom );
    foreach ( $xpath->query( '//testcase/*[self::failure or self::error]' ) as $node ) {
        $message = trim( $node->getAttribute( 'message' ) );
        $details = trim( $node->textContent );
        if ( '' !== $details && $details !== $message ) {
            $message = trim( $message . "\n" . $details );
        }
        $result['problems'][] = [
            'kind'    => $node->nodeName,
            'message' => $message,
        ];
    }
    $result['skipped'] = $xpath->query( '//testcase/skipped' )->length > 0;

    return $result;
}

/**
 * @param array{id: string, class: string, method: string, dataset: ?string, file: string} $test    Test definition.
 * @return array{kind: string, id: string, file: string, message: string, output: string}
 */
function failure_entry( string $kind, array $test, string $baseDir, string $message, string $output = '' ): array {
    return [
        'kind'    => $kind,
        'id'      => $test['id'],
        'file'    => str_replace( $baseDir . '/', '', $test['file'] ),
        'message' => $message,
        'output'  => $output,
    ];
}

function cap_output( string $text ): string {
    $text = trim( $text );
    return strlen( $text ) > MAX_OUTPUT_CHARS ? '...' . substr( $text, -MAX_OUTPUT_CHARS ) : $text;
}

function indent( string $text, int $spaces = 4 ): string {
    $pad = str_repeat( ' ', $spaces );
    return implode( "\n", array_map( static fn ( string $line ): string => $pad . $line, explode( "\n", $text ) ) ) . "\n";
}

$tests = discover_tests( $phpBinary, $phpunit, $config, $baseDir, $selectors );
if ( null === $tests ) {
    exit( 1 );
}

if ( $filter ) {
    $tests = array_values( array_filter( $tests, static function ( array $test ) use ( $filter ): bool {
        return str_contains( $test['id'], $filter )
            || str_contains( $test['file'], $filter )
            || (bool) @preg_match( $filter, $test['id'] );
    } ) );
}

$total = count( $tests );

if ( 0 === $total ) {
    echo "No tests matched criteria.\n";
    exit( 0 );
}

echo sprintf(
    "Running %d tests (scope: %s, concurrency: %d, per-test timeout: %ds)\n\n",
    $total,
    $scope,
    $concurrency,
    $timeout
);

$startTime = microtime( true );

$queue     = $tests;
$running   = [];
$results   = [
    'passed'    => 0,
    'skipped'   => 0,
    'failed'    => 0,
    'errors'    => 0,
    'timed_out' => 0,
];
$failures  = [];
$completed = 0;

$descriptors = [
    0 => [ 'pipe', 'r' ],
    1 => [ 'pipe', 'w' ],
    2 => [ 'pipe', 'w' ],
];

register_shutdown_function( static function () use ( &$running ): void {
    foreach ( $running as $worker ) {
        if ( isset( $worker['proc'] ) && is_resource( $worker['proc'] ) ) {
            terminate_process_tree( $worker['proc'], $worker['pid'] ?? null, 9 );
            if ( isset( $worker['pipes'] ) && is_array( $worker['pipes'] ) ) {
                if ( isset( $worker['pipes'][1] ) && is_resource( $worker['pipes'][1] ) ) {
                    @fclose( $worker['pipes'][1] );
                }
                if ( isset( $worker['pipes'][2] ) && is_resource( $worker['pipes'][2] ) ) {
                    @fclose( $worker['pipes'][2] );
                }
            }
            @proc_close( $worker['proc'] );
        }
        if ( isset( $worker['junit'] ) && is_string( $worker['junit'] ) && is_file( $worker['junit'] ) ) {
            @unlink( $worker['junit'] );
        }
    }
} );

while ( ! empty( $queue ) || ! empty( $running ) ) {
    // Fill available worker slots with one test per process.
    while ( count( $running ) < $concurrency && ! empty( $queue ) ) {
        $test      = array_shift( $queue );
        $junitFile = tempnam( sys_get_temp_dir(), 'rm_junit_' );
        if ( false === $junitFile ) {
            $completed++;
            $results['errors']++;
            $failures[] = failure_entry( 'ERROR', $test, $baseDir, 'Failed to create temporary JUnit file' );
            echo 'E';
            continue;
        }

        $cmd       = [
            $phpBinary,
            $phpunit,
            '-c',
            $config,
            '--no-coverage',
            '--filter',
            build_filter( $test ),
            '--log-junit',
            $junitFile,
            $test['file'],
        ];

        $proc = proc_open( $cmd, $descriptors, $pipes, $baseDir );
        if ( ! is_resource( $proc ) ) {
            $completed++;
            $results['errors']++;
            $failures[] = failure_entry( 'ERROR', $test, $baseDir, 'Failed to spawn worker process' );
            @unlink( $junitFile );
            echo 'E';
            continue;
        }

        fclose( $pipes[0] );
        stream_set_blocking( $pipes[1], false );
        stream_set_blocking( $pipes[2], false );

        $procStatus = proc_get_status( $proc );

        $running[] = [
            'test'   => $test,
            'proc'   => $proc,
            'pid'    => $procStatus['pid'] ?? null,
            'pipes'  => $pipes,
            'start'  => microtime( true ),
            'junit'  => $junitFile,
            'stdout' => '',
            'stderr' => '',
        ];
    }

    // Check workers: collect output, enforce per-test timeout, record outcomes.
    foreach ( array_keys( $running ) as $key ) {
        $running[ $key ]['stdout'] .= stream_get_contents( $running[ $key ]['pipes'][1] );
        $running[ $key ]['stderr'] .= stream_get_contents( $running[ $key ]['pipes'][2] );

        $status  = proc_get_status( $running[ $key ]['proc'] );
        $elapsed = microtime( true ) - $running[ $key ]['start'];

        if ( $status['running'] && $elapsed <= $timeout ) {
            continue;
        }

        $worker   = $running[ $key ];
        $timedOut = $status['running'];
        unset( $running[ $key ] );

        if ( $timedOut ) {
            terminate_process_tree( $worker['proc'], $worker['pid'] ?? null, 9 );
        }

        // Drain buffered output and close pipes before proc_close(), which invalidates them.
        $worker['stdout'] .= stream_get_contents( $worker['pipes'][1] );
        $worker['stderr'] .= stream_get_contents( $worker['pipes'][2] );
        fclose( $worker['pipes'][1] );
        fclose( $worker['pipes'][2] );

        $closeCode = proc_close( $worker['proc'] );
        $exitCode  = ( ! $timedOut && -1 !== $status['exitcode'] ) ? $status['exitcode'] : $closeCode;

        $junit  = $timedOut ? [ 'problems' => [], 'skipped' => false ] : read_junit_result( $worker['junit'] );
        @unlink( $worker['junit'] );
        $output = cap_output( $worker['stdout'] . "\n" . $worker['stderr'] );
        $completed++;

        if ( $timedOut ) {
            $results['timed_out']++;
            $failures[] = failure_entry(
                'TIMEOUT',
                $worker['test'],
                $baseDir,
                sprintf( 'Timed out after %ds and was killed', $timeout ),
                $output
            );
            echo 'T';
        } elseif ( 0 === $exitCode && empty( $junit['problems'] ) ) {
            if ( $junit['skipped'] ) {
                $results['skipped']++;
                echo 'S';
            } else {
                $results['passed']++;
                echo '.';
            }
        } elseif ( empty( $junit['problems'] ) ) {
            $results['errors']++;
            $failures[] = failure_entry(
                'ERROR',
                $worker['test'],
                $baseDir,
                sprintf( 'Worker exited with code %d without a JUnit failure record', $exitCode ),
                $output
            );
            echo 'E';
        } else {
            $hasError = false;
            foreach ( $junit['problems'] as $problem ) {
                $hasError = $hasError || 'error' === $problem['kind'];
                $failures[] = failure_entry(
                    strtoupper( $problem['kind'] ),
                    $worker['test'],
                    $baseDir,
                    $problem['message']
                );
            }
            if ( $hasError ) {
                $results['errors']++;
                echo 'E';
            } else {
                $results['failed']++;
                echo 'F';
            }
        }

        if ( 0 === $completed % 50 || $completed === $total ) {
            echo sprintf( "  [%d/%d] (%d%%)\n", $completed, $total, (int) round( ( $completed / $total ) * 100 ) );
        }
    }

    if ( ! empty( $running ) ) {
        usleep( 10000 );
    }
}

$elapsed = microtime( true ) - $startTime;

echo "\n\n";

if ( ! empty( $failures ) ) {
    echo "FAILURES:\n";
    foreach ( $failures as $index => $failure ) {
        echo sprintf( "%d) [%s] %s\n", $index + 1, $failure['kind'], $failure['id'] );
        echo sprintf( "    file: %s\n", $failure['file'] );
        if ( '' !== $failure['message'] ) {
            echo indent( $failure['message'] );
        }
        if ( '' !== $failure['output'] ) {
            echo "    output:\n";
            echo indent( $failure['output'], 6 );
        }
        echo "\n";
    }
}

echo sprintf(
    "SUMMARY: %d tests | %d passed | %d failed | %d timed out | %d errors in %.2fs",
    $total,
    $results['passed'],
    $results['failed'],
    $results['timed_out'],
    $results['errors'],
    $elapsed
);
if ( $results['skipped'] > 0 ) {
    echo sprintf( " (%d skipped)", $results['skipped'] );
}
echo "\n";

exit( ( $results['failed'] + $results['errors'] + $results['timed_out'] ) > 0 ? 1 : 0 );
