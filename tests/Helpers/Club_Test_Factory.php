<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Domain\Club;

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
        $club_data = (object) array_filter(
            [
                'id'         => $id,
                'name'       => $name,
                'shortcode'  => $shortcode,
                'type'       => $type,
                'website'    => $website,
                'contactno'  => $contactno,
                'founded'    => $founded,
                'facilities' => $facilities,
                'address'    => $address,
            ],
            static fn( $value ) => null !== $value
        );

        $club = new Club( $club_data );

        foreach ( $overrides as $key => $value ) {
            if ( ! property_exists( Club::class, $key ) ) {
                throw new \InvalidArgumentException( sprintf( 'Unknown Club test property: %s', $key ) );
            }
            $club->$key = $value;
        }

        return $club;
    }
}
