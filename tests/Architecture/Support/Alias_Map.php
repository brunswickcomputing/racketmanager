<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use RuntimeException;

/**
 * Reads the deprecated `class_alias` map from `src/php/aliases.php` without executing it, so rules
 * can forbid references to alias names and the list stays in sync with the production file.
 *
 * The file declares `$aliases = [ \Fully\Qualified\Target::class => 'Racketmanager\Legacy\Name', … ]`.
 */
final class Alias_Map {
    /** @var array<string, array<string, string>> alias => target, memoised per file path. */
    private static array $cache = [];

    public static function default_path(): string {
        return Source_Scanner::plugin_root() . '/src/php/aliases.php';
    }

    /**
     * @return array<string, string> alias name => target class, both without a leading backslash.
     */
    public static function load( ?string $path = null ): array {
        $path = $path ?? self::default_path();
        if ( isset( self::$cache[ $path ] ) ) {
            return self::$cache[ $path ];
        }

        $code = is_file( $path ) ? file_get_contents( $path ) : false;
        if ( false === $code ) {
            throw new RuntimeException( sprintf( 'Alias map %s could not be read.', $path ) );
        }

        $ast = new ParserFactory()->createForNewestSupportedVersion()->parse( $code ) ?? [];

        /** @var array<int, Expr\Assign> $assignments */
        $assignments = new NodeFinder()->find(
            $ast,
            static fn( Node $node ): bool => $node instanceof Expr\Assign
                && $node->var instanceof Expr\Variable
                && 'aliases' === $node->var->name
                && $node->expr instanceof Expr\Array_
        );
        if ( [] === $assignments ) {
            throw new RuntimeException( sprintf( 'Alias map %s does not declare an `$aliases = [ … ]` array.', $path ) );
        }

        $map = [];
        /** @var Expr\Array_ $array */
        $array = $assignments[0]->expr;
        foreach ( $array->items as $item ) {
            if ( ! $item->value instanceof Scalar\String_ ) {
                continue;
            }
            $alias  = ltrim( $item->value->value, '\\' );
            $target = '';
            if ( $item->key instanceof Expr\ClassConstFetch && $item->key->class instanceof Node\Name ) {
                $target = ltrim( $item->key->class->toString(), '\\' );
            } elseif ( $item->key instanceof Scalar\String_ ) {
                $target = ltrim( $item->key->value, '\\' );
            }
            $map[ $alias ] = $target;
        }
        ksort( $map, SORT_STRING );

        return self::$cache[ $path ] = $map;
    }

    /**
     * @return array<int,string> Deprecated alias class names.
     */
    public static function alias_names( ?string $path = null ): array {
        return array_keys( self::load( $path ) );
    }

    public static function reset_cache(): void {
        self::$cache = [];
    }
}
