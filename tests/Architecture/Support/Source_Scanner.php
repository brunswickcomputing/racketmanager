<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support;

use FilesystemIterator;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Parses PHP files with nikic/php-parser and extracts Source_File facts. Results are memoised per
 * process so every architecture test shares one parse of the plugin.
 */
final class Source_Scanner {
    /** Plugin-root-relative locations scanned by scan_plugin(). */
    public const array PLUGIN_SCAN_PATHS = [
        'src/php',
        'templates',
        'functions.php',
        'template-tags.php',
    ];

    /** @var array<string, array<string, Source_File>> Memoised results keyed by absolute root path. */
    private static array $cache = [];

    private static ?Parser $parser = null;

    /**
     * Absolute path of the plugin root (the directory containing `src/php` and `tests`).
     */
    public static function plugin_root(): string {
        return dirname( __DIR__, 3 );
    }

    /**
     * Scan every PHP file in the plugin that architecture rules may target: `src/php/**`,
     * `templates/**` and the root helper files `functions.php` / `template-tags.php`.
     *
     * @return array<string, Source_File> Keyed by plugin-root-relative path, sorted.
     */
    public static function scan_plugin(): array {
        $root = self::plugin_root();
        $key  = $root . ':plugin';
        if ( isset( self::$cache[ $key ] ) ) {
            return self::$cache[ $key ];
        }

        $files = [];
        foreach ( self::PLUGIN_SCAN_PATHS as $relative ) {
            $files += self::scan( $root . '/' . $relative );
        }
        ksort( $files, SORT_STRING );

        self::$cache[ $key ] = $files;

        return $files;
    }

    /**
     * Scan a directory (recursively) or a single file.
     *
     * @return array<string, Source_File> Keyed by plugin-root-relative path (or absolute path when
     *                                    the target is outside the plugin), sorted.
     */
    public static function scan( string $target ): array {
        $real = realpath( $target );
        if ( false === $real ) {
            return [];
        }
        if ( isset( self::$cache[ $real ] ) ) {
            return self::$cache[ $real ];
        }

        $files = [];
        foreach ( self::php_files( $real ) as $absolute ) {
            $relative           = self::relative_path( $absolute );
            $files[ $relative ] = self::scan_file( $absolute, $relative );
        }
        ksort( $files, SORT_STRING );

        self::$cache[ $real ] = $files;

        return $files;
    }

    /**
     * Scan one file from disk.
     */
    public static function scan_file( string $absolute, ?string $relative = null ): Source_File {
        $code = file_get_contents( $absolute );
        if ( false === $code ) {
            return new Source_File( $relative ?? $absolute, parse_error: 'unreadable file' );
        }

        return self::scan_source( $code, $relative ?? self::relative_path( $absolute ) );
    }

    /**
     * Scan PHP source held in memory. `$path` is only used to label the resulting facts.
     */
    public static function scan_source( string $code, string $path ): Source_File {
        // A UTF-8 BOM or leading whitespace would otherwise be parsed as inline HTML before `<?php`,
        // which makes php-parser reject a following `declare`/`namespace` as "not the first statement".
        // Whitespace lines are replaced rather than removed so reported line numbers stay accurate.
        if ( str_starts_with( $code, "\xEF\xBB\xBF" ) ) {
            $code = substr( $code, 3 );
        }
        if ( 1 === preg_match( '/^(\s+)<\?php\b/', $code, $matches ) ) {
            $newlines = str_repeat( "\n", substr_count( $matches[1], "\n" ) );
            $code     = '<?php' . $newlines . substr( $code, strlen( $matches[1] ) + 5 );
        }

        try {
            $ast = self::parser()->parse( $code );
        } catch ( Error $error ) {
            return new Source_File( $path, parse_error: $error->getMessage() );
        }

        $collector = new Fact_Collector_Visitor();
        $traverser = new NodeTraverser();
        $traverser->addVisitor( new NameResolver( null, [ 'preserveOriginalNames' => true ] ) );
        $traverser->addVisitor( $collector );
        $traverser->traverse( $ast ?? [] );

        return new Source_File(
            path: $path,
            namespace: $collector->namespace,
            strict_types: $collector->strict_types,
            parse_error: null,
            imports: $collector->imports,
            function_calls: $collector->function_calls,
            globals: $collector->globals,
            variables: $collector->variables,
            new_targets: $collector->new_targets,
            static_calls: $collector->static_calls,
            class_references: $collector->class_references,
            constructor_params: $collector->constructor_params,
            includes: $collector->includes,
            declared_functions: $collector->declared_functions,
            declared_classes: $collector->declared_classes,
        );
    }

    /**
     * Drop memoised results (tests that mutate fixtures on disk may need this).
     */
    public static function reset_cache(): void {
        self::$cache = [];
    }

    /**
     * Path relative to the plugin root with forward slashes; absolute paths outside the plugin
     * are returned unchanged.
     */
    public static function relative_path( string $absolute ): string {
        $root       = self::plugin_root() . '/';
        $normalised = str_replace( '\\', '/', $absolute );

        return str_starts_with( $normalised, $root ) ? substr( $normalised, strlen( $root ) ) : $normalised;
    }

    /**
     * @return array<int,string> Absolute paths of `.php` files under (or equal to) `$real`.
     */
    private static function php_files( string $real ): array {
        if ( is_file( $real ) ) {
            return [ $real ];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $real, FilesystemIterator::SKIP_DOTS ),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $paths = [];
        /** @var SplFileInfo $info */
        foreach ( $iterator as $info ) {
            if ( $info->isFile() && 'php' === strtolower( $info->getExtension() ) ) {
                $paths[] = $info->getPathname();
            }
        }
        sort( $paths, SORT_STRING );

        return $paths;
    }

    private static function parser(): Parser {
        return self::$parser ??= new ParserFactory()->createForNewestSupportedVersion();
    }
}
