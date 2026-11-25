<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use DateMalformedStringException;
use InvalidArgumentException;
use ReflectionException;
use ReflectionProperty;
use Racketmanager\Domain\Tournament\Tournament;

/**
 * Builds real Tournament aggregates for tests.
 */
final class Tournament_Test_Factory {
    /**
     * @param int|null $id
     * @param string $name
     * @param string|null $season
     * @param string|null $date_start
     * @param string $date_end
     * @param array $overrides Arbitrary property overrides applied directly to the Tournament.
     * @return Tournament
     * @throws DateMalformedStringException If an overridden date is malformed.
     * @throws InvalidArgumentException If an override does not target a public Tournament property.
     * @throws ReflectionException If a Tournament property cannot be reflected.
     */
    public static function create(
        ?int $id = 1,
        string $name = 'Test Tournament',
        ?string $season = '2026',
        ?string $date_start = '2026-06-01',
        string $date_end = '2026-06-30',
        array $overrides = []
    ): Tournament {
        $raw_data  = [
            'id'         => $id,
            'name'       => $name,
            'season'     => $season,
            'date_start' => $date_start,
            'date_end'   => $date_end,
        ];
        $filtered  = array_filter(
            $raw_data,
            static function ( $value ): bool {
                return null !== $value;
            }
        );
        $tournament_data = (object) $filtered;

        $ref_properties = [];
        foreach ( array_keys( $overrides ) as $key ) {
            if ( ! property_exists( Tournament::class, $key ) ) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new InvalidArgumentException( sprintf( 'Unknown Tournament test property: %s', $key ) );
            }
            $key_str  = (string) $key;
            $ref_prop = new ReflectionProperty( Tournament::class, $key_str );
            if ( ! $ref_prop->isPublic() ) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new InvalidArgumentException( sprintf( 'Unknown Tournament test property: %s', $key_str ) );
            }
            $ref_properties[ $key_str ] = $ref_prop;
        }

        $date_setters = [
            'date_end'        => 'set_end_date',
            'date_closing'    => 'set_closing_date',
            'date_withdrawal' => 'set_withdrawal_date',
            'date_open'       => 'set_opening_date',
            'date_start'      => 'set_start_date',
        ];

        $tournament = Tournament::from_object( $tournament_data );
        $has_date_override = false;

        foreach ( $date_setters as $property => $setter ) {
            if ( ! array_key_exists( $property, $overrides ) ) {
                continue;
            }

            $tournament->$setter( $overrides[ $property ] );
            $has_date_override = true;
        }

        if ( $has_date_override ) {
            $tournament->set_tournament_info();
        }

        foreach ( $overrides as $key => $value ) {
            $key_str = (string) $key;
            if ( array_key_exists( $key_str, $date_setters ) ) {
                continue;
            }

            $setter = 'set_' . $key_str;

            if ( method_exists( $tournament, $setter ) ) {
                $tournament->$setter( $value );
                continue;
            }

            $ref_properties[ $key_str ]->setValue( $tournament, $value );
        }

        return $tournament;
    }
}
