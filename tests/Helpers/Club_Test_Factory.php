<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Domain\Club\Club;

/**
 * Builds real Club aggregates for tests.
 */
final class Club_Test_Factory {
    /**
     * @param int|null $id
     * @param string $name
     * @param string|null $shortcode
     * @param string|null $type
     * @param string|null $website
     * @param string|null $contactno
     * @param int|null $founded
     * @param string|null $facilities
     * @param string|null $address
     * @param array $overrides Arbitrary property overrides applied directly to the Club.
     * @return Club
     */
    public static function create(
        ?int $id = 1,
        string $name = 'Test Club',
        ?string $shortcode = 'test-club',
        ?string $type = null,
        ?string $website = null,
        ?string $contactno = null,
        ?int $founded = null,
        ?string $facilities = null,
        ?string $address = null,
        array $overrides = []
    ): Club {
        $raw_data  = [
            'id'         => $id,
            'name'       => $name,
            'shortcode'  => $shortcode,
            'type'       => $type,
            'website'    => $website,
            'contactno'  => $contactno,
            'founded'    => $founded,
            'facilities' => $facilities,
            'address'    => $address,
        ];
        $filtered  = array_filter(
            $raw_data,
            static function ( $value ): bool {
                return null !== $value;
            }
        );
        $club_data = (object) $filtered;

        $club = Club::from_object( $club_data );

        foreach ( $overrides as $key => $value ) {
            if ( ! property_exists( Club::class, $key ) ) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new \InvalidArgumentException( sprintf( 'Unknown Club test property: %s', $key ) );
            }
            $key_str = (string) $key;
            $setter  = 'set_' . $key_str;

            if ( method_exists( $club, $setter ) ) {
                $club->$setter( $value );
                continue;
            }

            $ref_prop = new \ReflectionProperty( Club::class, $key_str );
            $ref_prop->setValue( $club, $value );
        }

        return $club;
    }
}
