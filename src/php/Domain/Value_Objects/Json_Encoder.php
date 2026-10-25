<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Value_Objects;

use stdClass;

/** Native JSON encoding with the same UTF-8 recovery as WordPress's default encoder. */
final class Json_Encoder {
    public static function encode( mixed $value ): string|false {
        $json = json_encode( $value );
        if ( false !== $json || JSON_ERROR_UTF8 !== json_last_error() ) {
            return $json;
        }

        if ( ! function_exists( 'mb_convert_encoding' ) ) {
            return json_encode( $value, JSON_INVALID_UTF8_SUBSTITUTE );
        }

        try {
            return json_encode( self::sanitize( $value, 512 ) );
        } catch ( \RuntimeException ) {
            return false;
        }
    }

    private static function sanitize( mixed $value, int $depth ): mixed {
        if ( $depth < 0 ) {
            throw new \RuntimeException( 'Reached depth limit' );
        }
        if ( is_string( $value ) ) {
            $encoding = mb_detect_encoding( $value, mb_detect_order(), true ) ?: 'UTF-8';
            return mb_convert_encoding( $value, 'UTF-8', $encoding );
        }
        if ( is_array( $value ) || is_object( $value ) ) {
            $result = is_array( $value ) ? array() : new stdClass();
            foreach ( $value as $key => $item ) {
                $key = is_string( $key ) ? self::sanitize( $key, $depth - 1 ) : $key;
                $item = self::sanitize( $item, $depth - 1 );
                if ( is_array( $result ) ) {
                    $result[ $key ] = $item;
                } else {
                    $result->$key = $item;
                }
            }
            return $result;
        }
        return $value;
    }
}