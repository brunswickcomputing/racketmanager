<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

use InvalidArgumentException;

final class Command_Validation {
    /** @param array<int|string, int|string> $values */
    public static function ids( array $values, string $field ): void {
        foreach ( $values as $value ) {
            if ( ! ( is_int( $value ) || is_string( $value ) ) || ! ctype_digit( (string) $value ) || (int) $value <= 0 ) {
                throw new InvalidArgumentException( 'Invalid field: ' . $field );
            }
        }
    }

    public static function column( array|int|null $values, string $field ): void {
        if ( ! is_array( $values ) ) {
            return;
        }
        foreach ( $values as $value ) {
            if ( null !== $value && ! is_scalar( $value ) ) {
                throw new InvalidArgumentException( 'Invalid field: ' . $field );
            }
        }
    }

    public static function tree( ?array $values, string $field ): void {
        foreach ( $values ?? [] as $value ) {
            if ( is_array( $value ) ) {
                self::tree( $value, $field );
            } else {
                self::column( [ $value ], $field );
            }
        }
    }

    public static function choice( string $value, array $choices ): void {
        if ( ! in_array( $value, $choices, true ) ) {
            throw new InvalidArgumentException( 'Invalid action' );
        }
    }
}
