<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Admin;

use InvalidArgumentException;

/** Maps WordPress form values without allowing arrays to masquerade as scalars. */
final class Admin_Command_Input {
    public static function text( array $input, string $key ): ?string {
        if ( ! isset( $input[ $key ] ) ) {
            return null;
        }
        if ( ! is_scalar( $input[ $key ] ) ) {
            throw new InvalidArgumentException( sprintf( __( 'Invalid field: %s', 'racketmanager' ), $key ) );
        }
        return sanitize_text_field( wp_unslash( (string) $input[ $key ] ) );
    }

    public static function integer( array $input, string $key ): ?int {
        $value = self::text( $input, $key );
        return null === $value ? null : (int) $value;
    }

    public static function optional_integer( array $input, string $key ): ?int {
        $value = self::text( $input, $key );
        return ( null === $value || '' === $value ) ? null : (int) $value;
    }

    public static function column( array $input, string $key, ?array $default = [] ): ?array {
        if ( ! isset( $input[ $key ] ) ) {
            return $default;
        }
        if ( ! is_array( $input[ $key ] ) ) {
            throw new InvalidArgumentException( sprintf( __( 'Invalid field: %s', 'racketmanager' ), $key ) );
        }
        $values = [];
        foreach ( $input[ $key ] as $index => $value ) {
            if ( null !== $value && ! is_scalar( $value ) ) {
                throw new InvalidArgumentException( sprintf( __( 'Invalid field: %s', 'racketmanager' ), $key ) );
            }
            $values[ $index ] = is_string( $value ) ? sanitize_text_field( wp_unslash( $value ) ) : $value;
        }
        return $values;
    }

    public static function tree( array $input, string $key, ?array $default = [] ): ?array {
        if ( ! isset( $input[ $key ] ) ) {
            return $default;
        }
        if ( ! is_array( $input[ $key ] ) ) {
            throw new InvalidArgumentException( sprintf( __( 'Invalid field: %s', 'racketmanager' ), $key ) );
        }
        $values = [];
        foreach ( $input[ $key ] as $index => $value ) {
            $values[ $index ] = is_array( $value ) ? self::tree( [ $key => $value ], $key ) : self::column( [ $key => [ $value ] ], $key )[0];
        }
        return $values;
    }

    public static function match_day( array $input ): array|int|null {
        return is_array( $input['match_day'] ?? null ) ? self::column( $input, 'match_day' ) : self::integer( $input, 'match_day' );
    }
}
