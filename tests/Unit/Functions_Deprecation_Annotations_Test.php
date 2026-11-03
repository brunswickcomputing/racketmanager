<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit;

use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

/**
 * Guards the v11 sunset outcome of the DDD audit (§6.2 / §9): all deprecated procedural helpers
 * have been removed from functions.php, leaving strictly the 4 un-deprecated plugin plumbing functions.
 */
final class Functions_Deprecation_Annotations_Test extends TestCase {
    /** Helpers that are plugin plumbing rather than legacy accessors and stay un-deprecated. */
    private const EXEMPT = [ 'debug_to_console', 'log_trace', 'create_new_url_querystring', 'wp_get_current_url' ];

    /** Retired procedural helpers removed in v11.0. */
    private const RETIRED_HELPERS = [
        'seo_url',
        'un_seo_url',
        'get_club',
        'get_competition_repository_for_helpers',
        'get_competition',
        'get_event_query_service',
        'get_event_repository_for_helpers',
        'get_event',
        'get_league_repository_for_helpers',
        'get_league_query_service',
        'get_league',
        'get_league_team_repository_for_helpers',
        'get_league_team',
        'get_fixture_repository_for_helpers',
        'get_match',
        'match_add_players',
        'get_rubber_repository_for_helpers',
        'get_rubber',
        'get_player_repository_for_helpers',
        'get_player',
        'get_team_repository_for_helpers',
        'get_team',
        'get_season_repository_for_helpers',
        'get_season',
        'get_club_role_repository_for_helpers',
        'get_club_role',
        'get_message_repository_for_helpers',
        'get_message',
        'to_legacy_user',
        'get_user',
    ];

    /**
     * Parsed functions from functions.php indexed by function name.
     *
     * @var array<string, Function_>
     */
    private static array $functions = [];

    public static function setUpBeforeClass(): void {
        $path = dirname( __DIR__, 2 ) . '/functions.php';
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $code = file_get_contents( $path );
        self::assertNotFalse( $code, 'functions.php could not be read.' );

        $ast = ( new ParserFactory() )->createForNewestSupportedVersion()->parse( $code ) ?? [];
        /* @var array<int, Function_> $nodes */
        $nodes = ( new NodeFinder() )->findInstanceOf( $ast, Function_::class );
        foreach ( $nodes as $node ) {
            self::$functions[ $node->name->toString() ] = $node;
        }
    }

    public function test_functions_php_contains_strictly_exempt_plumbing_functions(): void {
        $actual_names = array_keys( self::$functions );
        sort( $actual_names );

        $expected_names = self::EXEMPT;
        sort( $expected_names );

        self::assertSame(
            $expected_names,
            $actual_names,
            'functions.php must contain strictly the 4 un-deprecated plugin plumbing functions.'
        );
    }

    public function test_exempt_helpers_are_not_deprecated(): void {
        foreach ( self::EXEMPT as $name ) {
            self::assertArrayHasKey( $name, self::$functions, sprintf( '%s() is expected to exist in functions.php.', $name ) );
            self::assertStringNotContainsString( '@deprecated', self::$functions[ $name ]->getDocComment()?->getText() ?? '' );
        }
    }

    public function test_deprecated_helpers_are_completely_removed(): void {
        foreach ( self::RETIRED_HELPERS as $name ) {
            self::assertArrayNotHasKey(
                $name,
                self::$functions,
                sprintf( 'Deprecated helper %s() should no longer exist in functions.php.', $name )
            );
        }
    }

    public function test_no_functions_in_functions_php_are_deprecated(): void {
        foreach ( self::$functions as $name => $function ) {
            $doc = $function->getDocComment()?->getText() ?? '';
            self::assertStringNotContainsString(
                '@deprecated',
                $doc,
                sprintf( 'functions.php: %s() must not carry an @deprecated tag; all deprecated helpers were sunset in v11.', $name )
            );
        }
    }

    public function test_runtime_sunset_in_isolated_process_loading_production_functions_php(): void {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Build literal data for the isolated PHP subprocess.
        $script_inputs = var_export( [ dirname( __DIR__, 2 ) . '/functions.php', self::RETIRED_HELPERS, self::EXEMPT ], true );
        $script         = sprintf(
            '$inputs = %s; ' .
            'function add_action( $hook, $callback ) {} ' .
            'require $inputs[0]; ' .
            '$retired = $inputs[1]; ' .
            '$exempt = $inputs[2]; ' .
            '$defined_retired = array_filter( $retired, static fn( $n ) => function_exists( "Racketmanager\\\\" . $n ) ); ' .
            '$missing_exempt = array_filter( $exempt, static fn( $n ) => ! function_exists( "Racketmanager\\\\" . $n ) ); ' .
            'echo json_encode( [ "defined_retired" => array_values( $defined_retired ), "missing_exempt" => array_values( $missing_exempt ) ] );',
            $script_inputs
        );

        $cmd = escapeshellarg( PHP_BINARY ) . ' -r ' . escapeshellarg( $script ) . ' 2>&1';
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
        exec( $cmd, $output, $exit_code );

        self::assertSame( 0, $exit_code, 'Subprocess loading functions.php failed to execute: ' . implode( "\n", $output ) );
        $result = json_decode( implode( '', $output ), true );
        self::assertIsArray( $result );
        self::assertSame( [], $result['defined_retired'], 'No retired helper functions should be defined when functions.php is loaded.' );
        self::assertSame( [], $result['missing_exempt'], 'All exempt plumbing functions must be defined when functions.php is loaded.' );
    }

    public function test_retired_helpers_are_not_defined_in_test_environment(): void {
        self::assertFalse( function_exists( 'Racketmanager\get_league' ) );
        self::assertFalse( function_exists( 'Racketmanager\get_event' ) );
        self::assertFalse( function_exists( 'Racketmanager\get_team' ) );
        self::assertFalse( function_exists( 'Racketmanager\get_player' ) );
        self::assertFalse( function_exists( 'Racketmanager\get_user' ) );
        self::assertFalse( function_exists( 'Racketmanager\seo_url' ) );
        self::assertFalse( function_exists( 'Racketmanager\un_seo_url' ) );
        self::assertFalse( function_exists( 'Racketmanager\get_competition' ) );
        self::assertFalse( function_exists( 'Racketmanager\get_rubber' ) );
        self::assertFalse( function_exists( 'Racketmanager\get_match' ) );
    }
}
