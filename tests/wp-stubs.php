<?php
declare(strict_types=1);

/**
 * Minimal WordPress function stubs for unit tests.
 *
 * These allow running PHPUnit without bootstrapping WordPress.
 * Keep intentionally tiny: only add functions as tests require them.
 */

define( 'ABSPATH', __DIR__ . '/' );

if ( ! function_exists( 'absint' ) ) {
    function absint( $x ) {
        return abs( intval( $x ) );
    }
}

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

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( $value ) {
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
     * Very small subset of WP's add_query_arg behaviour.
     *
     * Supports the common WP signatures:
     * - add_query_arg( array $args, string $url = '')
     * - add_query_arg( string $key, string $value, string $url = '')
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
                $arg1 => $arg2,
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

if ( ! function_exists( 'mysql2date' ) ) {
    function mysql2date( string $format, string $date_string, bool $translate = true ): string|int|false {
        return date( $format, strtotime( $date_string ) );
    }
}

if ( ! function_exists( 'maybe_unserialize' ) ) {
    function maybe_unserialize( $data ) {
        if ( is_serialized( $data ) ) {
            return unserialize( $data );
        }
        return $data;
    }
}

if ( ! function_exists( 'is_serialized' ) ) {
    function is_serialized( $data ): bool {
        return is_string( $data ) && preg_match( '/^([adObis]):/', $data );
    }
}

if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( $url ) {
        return $url;
    }
}

if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $url ) {
        return $url;
    }
}

if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $text ) {
        return $text;
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data ) {
        return json_encode( $data );
    }
}

if ( ! function_exists( '__' ) ) {
    function __( string $text, string $domain = '' ): string {
        return $text;
    }
}

if ( ! function_exists( '_n' ) ) {
    function _n( string $single, string $plural, $number, string $domain = '' ): string {
        return $number == 1 ? $single : $plural;
    }
}

if ( ! isset( $GLOBALS['wp'] ) ) {
    $GLOBALS['wp'] = new class {
        public function set_query_var( $key, $value ) {}
    };
}

if ( ! isset( $GLOBALS['racketmanager'] ) ) {
    $GLOBALS['racketmanager'] = new class {
        public $container;
        public string $date_format = 'Y-m-d';
        public string $site_url = 'https://example.test';
        public function __construct() {
            $this->container = new class {
                public function get( string $id ): object {
                    $reflection = null;
                    try {
                        switch ( $id ) {
                            case 'competition_service':
                                $reflection = new ReflectionClass( 'Racketmanager\Services\Competition_Service' );
                                break;
                            case 'club_service':
                                $reflection = new ReflectionClass( 'Racketmanager\Services\Club_Service' );
                                break;
                            case 'player_service':
                                $reflection = new ReflectionClass( 'Racketmanager\Services\Player_Service' );
                                break;
                            case 'registration_service':
                                $reflection = new ReflectionClass( 'Racketmanager\Services\Registration_Service' );
                                break;
                        }
                    } catch ( ReflectionException ) {
                        return new stdClass();
                    }

                    if ( $reflection ) {
                        return $reflection->newInstanceWithoutConstructor();
                    }

                    return new stdClass();
                }
            };
        }
    };
}

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        public array $errors = array();

        public function __construct( $code = '', $message = '', $data = '' ) {
            if ( ! empty( $code ) ) {
                $this->add( $code, $message, $data );
            }
        }

        public function add( $code, $message, $data = '' ): void {
            $this->errors[ $code ][] = $message;
        }

        public function get_error_messages( $code = '' ) {
            if ( empty( $code ) ) {
                $all = array();
                foreach ( $this->errors as $messages ) {
                    $all = array_merge( $all, $messages );
                }
                return $all;
            }
            return $this->errors[ $code ] ?? array();
        }

        public function get_error_codes(): array {
            return array_keys( $this->errors );
        }

        public function get_error_message( $code = '' ): mixed {
            $messages = $this->get_error_messages( $code );
            return $messages[0] ?? '';
        }
    }

    function is_wp_error( $thing ): bool {
        return $thing instanceof WP_Error;
    }
}