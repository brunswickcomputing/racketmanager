<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support;

/**
 * Matching of rule arguments against symbol names.
 *
 * A pattern is either
 *  - a PCRE regex when it starts and ends with `/` (e.g. `/^Racketmanager\\Repositories\\(?!Interfaces\\)/`), or
 *  - a case-insensitive glob where `*` matches any run of characters including namespace separators
 *    (e.g. `wp_cache_*`, `Racketmanager\get_*_query_service`). A glob without `*` is an exact match.
 *
 * Leading backslashes on either side are ignored so `\Foo\Bar` and `Foo\Bar` are equivalent.
 */
final class Name_Pattern {
    /** @var array<string, string> Compiled regex per pattern. */
    private static array $compiled = [];

    public static function matches( string $pattern, string $subject ): bool {
        return 1 === preg_match( self::compile( $pattern ), ltrim( $subject, '\\' ) );
    }

    /**
     * @param array<int,string> $patterns
     */
    public static function matches_any( array $patterns, string $subject ): bool {
        foreach ( $patterns as $pattern ) {
            if ( self::matches( $pattern, $subject ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int,string> $patterns
     * @param array<int,?string> $subjects
     */
    public static function any_subject_matches( array $patterns, array $subjects ): bool {
        foreach ( $subjects as $subject ) {
            if ( null !== $subject && self::matches_any( $patterns, $subject ) ) {
                return true;
            }
        }

        return false;
    }

    private static function compile( string $pattern ): string {
        if ( isset( self::$compiled[ $pattern ] ) ) {
            return self::$compiled[ $pattern ];
        }

        if ( 1 === preg_match( '#^/.+/[a-zA-Z]*$#s', $pattern ) ) {
            $regex = $pattern;
        } else {
            $glob  = ltrim( $pattern, '\\' );
            $regex = '/^' . str_replace( '\*', '.*', preg_quote( $glob, '/' ) ) . '$/i';
        }

        return self::$compiled[ $pattern ] = $regex;
    }
}
