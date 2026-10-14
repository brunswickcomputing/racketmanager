<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Matches\Queries;

/**
 * Pure helpers used by match listing queries to normalise untrusted input
 * (shortcode attributes, query vars and GET parameters).
 */
final class Matches_Query_Normaliser {
    private const string AGE_GROUP_PATTERN = '/^[A-Za-z0-9 _+\-]{1,30}$/';

    public static function is_valid_date( string $date ): bool {
        if ( 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts ) ) {
            return false;
        }

        return checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] );
    }

    public static function competition_type( string $competition_type ): string {
        return strtolower( trim( $competition_type ) );
    }

    public static function template( string $template ): string {
        return (string) preg_replace( '/[^A-Za-z0-9_\-]/', '', $template );
    }

    public static function is_valid_age_group( string $age_group ): bool {
        return 1 === preg_match( self::AGE_GROUP_PATTERN, $age_group );
    }

    public static function header_level( int $header_level ): int {
        return ( $header_level >= 1 && $header_level <= 6 ) ? $header_level : 1;
    }
}
