<?php
declare(strict_types=1);

/**
 * Minimal WordPress function stubs for unit tests.
 *
 * These allow running PHPUnit without bootstrapping WordPress.
 * Keep intentionally tiny: only add functions as tests require them.
 */

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        return $value;
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $value ) {
        return is_string( $value ) ? trim( $value ) : $value;
    }
}

if ( ! function_exists( 'admin_url' ) ) {
    function admin_url( string $path = '' ): string {
        return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
    }
}

if ( ! function_exists( 'add_query_arg' ) ) {
    /**
     * Very small subset of WP's add_query_arg behavior.
     *
     * @param array<string,mixed> $args
     * @param string $url
     */
    function add_query_arg( array $args, string $url ): string {
        $pairs = array();
        foreach ( $args as $k => $v ) {
            if ( null === $v || '' === $v ) {
                continue;
            }
            $pairs[] = rawurlencode( (string) $k ) . '=' . rawurlencode( (string) $v );
        }
        $qs = implode( '&', $pairs );
        return $url . ( $qs ? ( str_contains( $url, '?' ) ? '&' : '?' ) . $qs : '' );
    }
}

if ( ! function_exists( '__' ) ) {
    function __( string $text, string $domain = '' ): string {
        return $text;
    }
}
