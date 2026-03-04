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
     * Supports the common WP signatures:
     * - add_query_arg( array $args, string $url = '' )
     * - add_query_arg( string $key, string $value, string $url = '' )
     *
     * @param array<string,mixed>|string $arg1
     * @param mixed $arg2
     * @param string $arg3
     *
     * @return string
     */
    function add_query_arg( array|string $arg1, mixed $arg2 = '', string $arg3 = '' ): string {
        if ( is_array( $arg1 ) ) {
            $args = $arg1;
            $url  = is_string( $arg2 ) ? $arg2 : '';
        } else {
            $args = array(
                (string) $arg1 => $arg2,
            );
            $url = $arg3;
        }

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
