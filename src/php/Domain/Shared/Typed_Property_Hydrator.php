<?php
declare( strict_types=1 );
/**
 * Typed_Property_Hydrator API: Typed_Property_Hydrator class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/Shared
 */

namespace Racketmanager\Domain\Shared;

/**
 * Assigns raw database row values to typed entity properties.
 *
 * Legacy `$wpdb` rows deliver every column as a string (or as an int/float once a value has been
 * round-tripped through a serialized settings column). Under `strict_types=1` PHP no longer coerces
 * such scalars when they are assigned to a typed property, so entities that hydrate themselves with a
 * `foreach ( get_object_vars( $row ) as $key => $value )` loop route the assignment through this helper.
 *
 * The coercion mirrors PHP's weak-mode scalar rules for the declared property type:
 *  - `null` is skipped for non-nullable properties (the default value is kept);
 *  - `''` becomes the type's empty value (`0`, `0.0`, `false`, `[]`, or `null` when nullable);
 *  - numeric values are cast for `int`/`float`, scalars for `bool` and `string`;
 *  - untyped, union-typed and undeclared (dynamic) properties receive the value unchanged.
 */
final class Typed_Property_Hydrator {

    /**
     * Assign a raw value to a property, coercing scalars to the declared property type.
     *
     * @param object $target Entity being hydrated.
     * @param string $key    Property name.
     * @param mixed  $value  Raw value from the database row.
     */
    public static function assign( object $target, string $key, mixed $value ): void {
        $property = self::find_property( $target, $key );
        if ( null === $property ) {
            $target->$key = $value;
            return;
        }

        $type = $property->getType();
        if ( ! $type instanceof \ReflectionNamedType ) {
            $property->setValue( $target, $value );
            return;
        }

        if ( null === $value && ! $type->allowsNull() ) {
            return;
        }

        // Reflection assignment also reaches private/protected properties, as `$this->$key` did inside the entity.
        $property->setValue( $target, self::coerce( $value, $type ) );
    }

    /**
     * Locate a declared (non-static) property, including private ones inherited from a parent entity.
     *
     * @param object $target Entity being hydrated.
     * @param string $key    Property name.
     *
     * @return \ReflectionProperty|null Null when the property is not declared anywhere in the hierarchy.
     */
    private static function find_property( object $target, string $key ): ?\ReflectionProperty {
        for ( $class = new \ReflectionObject( $target ); false !== $class; $class = $class->getParentClass() ) {
            if ( ! $class->hasProperty( $key ) ) {
                continue;
            }
            $property = $class->getProperty( $key );
            if ( ! $property->isStatic() ) {
                return $property;
            }
        }

        return null;
    }

    /**
     * Coerce a raw scalar to the declared named type following weak-mode semantics.
     *
     * @param mixed                $value Raw value.
     * @param \ReflectionNamedType $type  Declared property type.
     *
     * @return mixed Coerced value, or the original value when no coercion applies.
     */
    public static function coerce( mixed $value, \ReflectionNamedType $type ): mixed {
        $type_name = $type->getName();

        if ( '' === $value ) {
            return match ( true ) {
                'int' === $type_name                                  => 0,
                'float' === $type_name                                => 0.0,
                'bool' === $type_name                                 => false,
                'array' === $type_name                                => array(),
                $type->allowsNull() && 'string' !== $type_name        => null,
                default                                               => $value,
            };
        }

        return match ( true ) {
            'int' === $type_name && is_numeric( $value )                           => (int) $value,
            'float' === $type_name && is_numeric( $value )                         => (float) $value,
            'bool' === $type_name && ( is_bool( $value ) || is_numeric( $value ) ) => (bool) $value,
            'string' === $type_name && is_scalar( $value )                         => (string) $value,
            default                                                                => $value,
        };
    }
}
