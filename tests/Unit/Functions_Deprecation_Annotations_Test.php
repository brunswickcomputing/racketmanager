<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit;

use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

/**
 * Guards the P1-2 outcome of the DDD audit (§6.2 / §9): every procedural helper in
 * functions.php is annotated `@deprecated` with a `@see` replacement, and the five helpers
 * that already emitted a runtime notice keep doing so.
 */
final class Functions_Deprecation_Annotations_Test extends TestCase {
    /** Helpers that are plugin plumbing rather than legacy accessors and stay un-deprecated. */
    private const EXEMPT = [ 'debug_to_console', 'log_trace', 'create_new_url_querystring', 'wp_get_current_url' ];

    /** Helpers that must keep raising E_USER_DEPRECATED (observed by Template_Tags_Decoupling_Test). */
    private const RUNTIME_NOTICE = [ 'get_competition', 'get_match', 'get_rubber', 'match_add_players', 'get_club_role' ];

    /** @var array<string, Function_> */
    private static array $functions = [];

    public static function setUpBeforeClass(): void {
        $path = dirname( __DIR__, 2 ) . '/functions.php';
        $code = file_get_contents( $path );
        self::assertNotFalse( $code, 'functions.php could not be read.' );

        $ast = ( new ParserFactory() )->createForNewestSupportedVersion()->parse( $code ) ?? [];
        /** @var array<int, Function_> $nodes */
        $nodes = ( new NodeFinder() )->findInstanceOf( $ast, Function_::class );
        foreach ( $nodes as $node ) {
            self::$functions[ $node->name->toString() ] = $node;
        }
    }

    public function test_every_helper_is_either_exempt_or_deprecated_with_a_replacement(): void {
        self::assertNotEmpty( self::$functions );

        foreach ( self::$functions as $name => $function ) {
            if ( in_array( $name, self::EXEMPT, true ) ) {
                continue;
            }
            $doc = $function->getDocComment()?->getText() ?? '';

            self::assertMatchesRegularExpression(
                '/@deprecated\s+\d+\.\d+\.\d+\s+\S/',
                $doc,
                sprintf( 'functions.php: %s() must carry an `@deprecated <version> <replacement>` tag.', $name )
            );
            self::assertStringContainsString(
                '@see ',
                $doc,
                sprintf( 'functions.php: %s() must carry an `@see` tag naming the replacement.', $name )
            );
        }
    }

    public function test_exempt_helpers_are_not_deprecated(): void {
        foreach ( self::EXEMPT as $name ) {
            self::assertArrayHasKey( $name, self::$functions, sprintf( '%s() is expected to exist in functions.php.', $name ) );
            self::assertStringNotContainsString( '@deprecated', self::$functions[ $name ]->getDocComment()?->getText() ?? '' );
        }
    }

    public function test_runtime_notice_helpers_keep_trigger_error(): void {
        $finder = new NodeFinder();
        foreach ( self::RUNTIME_NOTICE as $name ) {
            self::assertArrayHasKey( $name, self::$functions );
            $calls = $finder->find(
                self::$functions[ $name ]->stmts ?? [],
                static fn( Node $node ): bool => $node instanceof Node\Expr\FuncCall
                    && $node->name instanceof Node\Name
                    && 'trigger_error' === $node->name->getLast()
            );
            self::assertNotEmpty( $calls, sprintf( '%s() must keep raising E_USER_DEPRECATED via trigger_error().', $name ) );
        }
    }

    public function test_annotation_only_helpers_do_not_emit_runtime_notices(): void {
        $finder = new NodeFinder();
        foreach ( self::$functions as $name => $function ) {
            if ( in_array( $name, self::RUNTIME_NOTICE, true ) ) {
                continue;
            }
            $calls = $finder->find(
                $function->stmts ?? [],
                static fn( Node $node ): bool => $node instanceof Node\Expr\FuncCall
                    && $node->name instanceof Node\Name
                    && in_array( $node->name->getLast(), [ 'trigger_error', '_deprecated_function' ], true )
            );
            self::assertSame( [], $calls, sprintf( '%s() must not add a runtime deprecation notice (257 internal callers).', $name ) );
        }
    }
}
