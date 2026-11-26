<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Request;

/**
 * Provides sanitised access to competition-related request parameters.
 *
 * Reads values from request data and WordPress query variables, normalising
 * supported competition filters such as season, match day, pagination, and
 * final-round identifiers.
 */
final class Competition_Request {
    /**
     * Retrieves the season value from request or query variables.
     *
     * @param int  $id Identifier used to resolve an ID-specific season query value.
     * @param bool $include_post Whether to include the POST season value in the lookup.
     *
     * @return string|null Sanitized season value, or null when no valid season value is found.
     */
    public static function season( int $id, bool $include_post = false ): ?string {
        foreach ( [
            $include_post ? ( $_POST['season'] ?? null ) : null,
            $_GET['season'] ?? null,
            $_GET[ 'season_' . $id ] ?? null,
            self::query_var( 'season' ),
            self::query_var( 'season_' . $id ),
        ] as $value ) {
            $value = self::text( $value );
            if ( null !== $value ) {
                return htmlspecialchars( $value );
            }
        }
        return null;
    }

    /**
     * Retrieves and validates the match day value for the given identifier.
     *
     * @param int $id Match day identifier used to check identifier-specific request or query values.
     *
     * @return false|string|int Valid match day number, -1, "current", "last", or false when no valid value is found.
     */
    public static function match_day( int $id ): false|string|int {
        $qv = self::query_var( 'match_day' ) ?? self::query_var( 'match_day_' . $id );
        foreach ( [
            $_GET['match_day'] ?? null,
            $_GET[ 'match_day_' . $id ] ?? null,
            $_POST['match_day'] ?? null,
            $qv,
        ] as $source => $value ) {
            if ( 3 === $source && in_array( $value, [ 'current', 'last' ], true ) ) {
                return $value;
            }
            $value = self::integer( $value );
            if ( null !== $value && ( -1 === $value || $value > 0 ) ) {
                return $value;
            }
        }
        return false;
    }

    /**
     * Determines the current page number for the requested paginated collection.
     *
     * @param int    $id Unique identifier used to check collection-specific page variables.
     * @param string $which Collection type to resolve, such as matches or teams.
     *
     * @return int Current positive page number, or 1 when no valid page is found.
     */
    public static function page( int $id, string $which = 'matches' ): int {
        $key = match ( $which ) {
            'matches' => 'match_paged',
            'teams' => 'team_paged',
            default => null,
        };
        if ( null === $key ) {
            return 1;
        }
        foreach ( [
            $_GET[ $key ] ?? null,
            self::query_var( $key ),
            $_GET[ $key . '_' . $id ] ?? null,
            self::query_var( $key . '_' . $id ),
        ] as $value ) {
            $value = self::integer( $value );
            if ( null !== $value && $value > 0 ) {
                return $value;
            }
        }
        return 1;
    }

    /**
     * Retrieves the requested final round value from the request or query variables.
     *
     * @return string|null Sanitized final round value, or null when no valid value is provided.
     */
    public static function final_round(): ?string {
        return self::text( $_GET['final'] ?? self::query_var( 'final' ) );
    }

    /**
     * Retrieves a query variable value from the current WordPress request.
     *
     * @param string $key Query variable key to look up.
     *
     * @return mixed Query variable value when found, or null when unavailable.
     */
    private static function query_var( string $key ): mixed {
        global $wp;
        if ( isset( $wp->query_vars[ $key ] ) ) {
            return $wp->query_vars[ $key ];
        }
        if ( function_exists( 'get_query_var' ) ) {
            $val = get_query_var( $key, null );
            if ( '' !== $val && null !== $val ) {
                return $val;
            }
        }
        return null;
    }

    /**
     * Sanitises and normalises a scalar text value.
     *
     * @param mixed $value Value to sanitize when it is a string or integer.
     *
     * @return string|null Sanitized non-empty text, or null when the value is unsupported or empty.
     */
    private static function text( mixed $value ): ?string {
        if ( ! is_string( $value ) && ! is_int( $value ) ) {
            return null;
        }
        $value = trim( sanitize_text_field( wp_unslash( (string) $value ) ) );
        return '' !== $value ? $value : null;
    }

    /**
     * Converts a string or integer value to a validated integer.
     *
     * @param mixed $value Value to validate and convert.
     *
     * @return int|null Valid integer value, or null when the value is not a valid integer.
     */
    private static function integer( mixed $value ): ?int {
        if ( ! is_string( $value ) && ! is_int( $value ) ) {
            return null;
        }
        $value = filter_var( $value, FILTER_VALIDATE_INT );
        return false !== $value ? $value : null;
    }
}
