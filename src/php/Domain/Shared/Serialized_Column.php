<?php
declare( strict_types=1 );
/**
 * Serialized_Column API: Serialized_Column class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/Shared
 */

namespace Racketmanager\Domain\Shared;

use stdClass;

/**
 * Pure-PHP decoder for legacy PHP-serialized database columns (settings, custom, roster, seasons).
 *
 * Mirrors the WordPress `maybe_unserialize()` / `stripslashes_deep()` contracts without depending on
 * WordPress so that Domain entities and DTOs can hydrate serialized columns:
 *  - non-strings are returned unchanged;
 *  - strings that do not look serialized (`is_serialized()` heuristics) are returned unchanged;
 *  - serialized strings are decoded allowing only `stdClass` (legacy rows store plain data objects);
 *    any other class becomes `__PHP_Incomplete_Class` instead of being instantiated;
 *  - `decode()` returns malformed serialized strings unchanged, whereas `decode_legacy()` keeps the
 *    exact `maybe_unserialize()` contract and yields `false` for them (entity hydration relies on that);
 *  - `unslash()` strips slashes from every string in a nested array/object, like `stripslashes_deep()`.
 */
final class Serialized_Column {

    /**
     * Decode a column value if, and only if, it is a PHP-serialized string.
     *
     * @param mixed $value Raw column value.
     *
     * @return mixed Decoded value, or the original value when it was not serialized.
     */
    public static function decode( mixed $value ): mixed {
        if ( ! is_string( $value ) ) {
            return $value;
        }
        $serialized = trim( $value );
        if ( ! self::is_serialized( $serialized ) ) {
            return $value;
        }
        if ( 'b:0;' === $serialized ) {
            return false;
        }
        $decoded = @unserialize( $serialized, array( 'allowed_classes' => array( stdClass::class ) ) );
        return false === $decoded ? $value : $decoded;
    }

    /**
     * Decode a column value with the exact WordPress `maybe_unserialize()` contract.
     *
     * Identical to {@see decode()} except that a string which looks serialized but cannot be
     * unserialized yields `false` (the `unserialize()` failure value) instead of the raw string, so
     * legacy call sites that previously read `maybe_unserialize()` output keep seeing the same types.
     *
     * @param mixed $value Raw column value.
     *
     * @return mixed Decoded value, `false` for a malformed serialized string, or the original value.
     */
    public static function decode_legacy( mixed $value ): mixed {
        if ( ! is_string( $value ) ) {
            return $value;
        }
        $serialized = trim( $value );
        if ( ! self::is_serialized( $serialized ) ) {
            return $value;
        }
        return @unserialize( $serialized, array( 'allowed_classes' => array( stdClass::class ) ) );
    }

    /**
     * Decode a column value and strip slashes from every string it contains.
     *
     * Equivalent to the legacy `stripslashes_deep( maybe_unserialize( $value ) )` hydration, including
     * the `false` result for malformed serialized strings (see {@see decode_legacy()}).
     *
     * @param mixed $value Raw column value.
     *
     * @return mixed
     */
    public static function decode_unslashed( mixed $value ): mixed {
        return self::unslash( self::decode_legacy( $value ) );
    }

    /**
     * Recursively strip slashes from strings, descending into arrays and object properties.
     *
     * Equivalent to WordPress `stripslashes_deep()`: non-string scalars are returned unchanged and
     * object properties are rewritten in place on the same instance.
     *
     * @param mixed $value Value to unslash.
     *
     * @return mixed
     */
    public static function unslash( mixed $value ): mixed {
        if ( is_string( $value ) ) {
            return stripslashes( $value );
        }
        if ( is_array( $value ) ) {
            foreach ( $value as $key => $item ) {
                $value[ $key ] = self::unslash( $item );
            }
            return $value;
        }
        if ( is_object( $value ) ) {
            foreach ( get_object_vars( $value ) as $property => $item ) {
                $value->$property = self::unslash( $item );
            }
        }
        return $value;
    }

    /**
     * Decode a column value and coerce the result to an array.
     *
     * `null`, `''`, `false` and malformed serialized strings become an empty array; scalars are wrapped; objects are cast.
     *
     * @param mixed $value Raw column value.
     *
     * @return array
     */
    public static function decode_array( mixed $value ): array {
        $decoded = self::decode( $value );
        if ( null === $decoded || '' === $decoded || false === $decoded ) {
            return array();
        }
        if ( is_string( $decoded ) && self::is_serialized( trim( $decoded ) ) ) {
            return array(); // Malformed serialized payload: nothing usable to hydrate.
        }
        return (array) $decoded;
    }

    /**
     * Heuristic check for a PHP-serialized string, equivalent to WordPress `is_serialized( $data, true )`.
     *
     * @param string $data Trimmed candidate string.
     *
     * @return bool
     */
    public static function is_serialized( string $data ): bool {
        if ( 'N;' === $data ) {
            return true;
        }
        if ( strlen( $data ) < 4 || ':' !== $data[1] ) {
            return false;
        }
        $last_char = substr( $data, -1 );
        if ( ';' !== $last_char && '}' !== $last_char ) {
            return false;
        }
        $token = $data[0];
        switch ( $token ) {
            case 's':
                if ( '"' !== substr( $data, -2, 1 ) ) {
                    return false;
                }
                // Fall through.
            case 'a':
            case 'O':
            case 'E':
                return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
            case 'b':
            case 'i':
            case 'd':
                return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$/", $data );
        }
        return false;
    }
}
