<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Request;

/** Resolves legacy competition URL selectors without coupling aggregates to HTTP. */
final class Competition_Request {
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

    public static function final_round(): ?string {
        return self::text( $_GET['final'] ?? self::query_var( 'final' ) );
    }

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

    private static function text( mixed $value ): ?string {
        if ( ! is_string( $value ) && ! is_int( $value ) ) {
            return null;
        }
        $value = trim( sanitize_text_field( wp_unslash( (string) $value ) ) );
        return '' !== $value ? $value : null;
    }

    private static function integer( mixed $value ): ?int {
        if ( ! is_string( $value ) && ! is_int( $value ) ) {
            return null;
        }
        $value = filter_var( $value, FILTER_VALIDATE_INT );
        return false !== $value ? $value : null;
    }
}