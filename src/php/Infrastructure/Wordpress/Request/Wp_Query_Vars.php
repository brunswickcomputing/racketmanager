<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Request;

use Racketmanager\Application\Ports\Query_Vars_Interface;

final class Wp_Query_Vars implements Query_Vars_Interface {
    public function get( string $key, mixed $default = null ): mixed {
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
        return $default;
    }

    public function has( string $key ): bool {
        global $wp;
        if ( isset( $wp->query_vars[ $key ] ) ) {
            return true;
        }
        if ( function_exists( 'get_query_var' ) ) {
            $val = get_query_var( $key, null );
            return '' !== $val && null !== $val;
        }
        return false;
    }

    public function set( string $key, mixed $value ): void {
        global $wp;
        if ( isset( $wp ) && is_object( $wp ) ) {
            if ( method_exists( $wp, 'set_query_var' ) ) {
                $wp->set_query_var( $key, $value );
            } else {
                if ( ! isset( $wp->query_vars ) || ! is_array( $wp->query_vars ) ) {
                    $wp->query_vars = [];
                }
                $wp->query_vars[ $key ] = $value;
            }
        }
        if ( function_exists( 'set_query_var' ) ) {
            set_query_var( $key, $value );
        }
    }

    public function all(): array {
        global $wp;
        return isset( $wp->query_vars ) && is_array( $wp->query_vars ) ? $wp->query_vars : [];
    }
}
