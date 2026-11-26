<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Factory;
use ReflectionProperty;

/**
 * Builds real League entities for tests with valid default Event aggregates.
 */
final class League_Test_Factory {
    /**
     * Properties whose domain mutator is not named set_{property}() or has a different parameter contract.
     */
    private const SETTER_OVERRIDES = array(
        'season'    => 'set_season_name',
        'finals'    => 'set_finals_list',
        'num_teams' => 'set_num_teams_count',
    );

    /**
     * Create a default Event aggregate for tests.
     */
    public static function default_event( int $event_id = 1, ?Competition $competition = null, array|string $seasons = array() ): Event {
        return Event_Test_Factory::league_event(
            id: $event_id,
            competition: $competition,
            seasons: $seasons
        );
    }

    /**
     * Create a League entity from a generic row or array, automatically providing a valid Event if none is supplied.
     */
    public static function from_object( object $row, ?Event $event = null ): League {
        $row = clone $row;
        if ( null === $event ) {
            if ( isset( $row->event ) && $row->event instanceof Event ) {
                $event = $row->event;
            } else {
                $event_id = isset( $row->event_id ) && (int) $row->event_id > 0 ? (int) $row->event_id : 1;
                $seasons  = ! empty( $row->seasons ) ? $row->seasons : array( '2026' => array( 'name' => '2026' ) );
                $event    = self::default_event( $event_id, null, $seasons );
            }
        }
        if ( empty( $row->title ) && empty( $row->name ) ) {
            $row->title = 'Test League';
        }
        $league = League_Factory::from_object( $row, $event );
        if ( null === $league ) {
            throw new \RuntimeException( 'Failed to hydrate test League.' );
        }
        if ( isset( $row->sport ) ) {
            $league->set_sport( (string) $row->sport );
        }
        return $league;
    }

    /**
     * Mutate a public League property on an entity or test double.
     *
     * Real entities are mutated through their domain mutator when one exists. Test doubles, and properties
     * without a mutator, are written through reflection on League so that private(set) does not block the test.
     *
     * @param object $entity   League instance or test double.
     * @param string $property Public League property name.
     * @param mixed  $value    Value to assign.
     * @return void
     * @throws InvalidArgumentException If the property is unknown or not public on League.
     */
    public static function set_property( object $entity, string $property, mixed $value ): void {
        $ref_property = self::reflect_league_property( $property );

        if ( ! ( $entity instanceof Stub || $entity instanceof MockObject ) ) {
            $setter = self::SETTER_OVERRIDES[ $property ] ?? 'set_' . $property;
            if ( method_exists( $entity, $setter ) ) {
                $entity->$setter( $value );
                return;
            }
        }

        $ref_property->setValue( $entity, $value );
    }

    /**
     * Resolve a League property for test writes, rejecting unknown and non-public properties.
     *
     * @param string $property Property name.
     * @return ReflectionProperty Reflection bound to the League declaration.
     * @throws InvalidArgumentException If the property is unknown or not public.
     */
    private static function reflect_league_property( string $property ): ReflectionProperty {
        if ( ! property_exists( League::class, $property ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new InvalidArgumentException( sprintf( 'Unknown League test property: %s', $property ) );
        }

        $ref_property = new ReflectionProperty( League::class, $property );
        if ( ! $ref_property->isPublic() ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new InvalidArgumentException( sprintf( 'Non-public League test property: %s', $property ) );
        }

        return $ref_property;
    }

    /**
     * Create a League entity from an array, automatically providing a valid Event if none is supplied.
     */
    public static function from_array( array $data, ?Event $event = null ): League {
        return self::from_object( (object) $data, $event );
    }

    /**
     * Create a League entity with explicit parameters and overrides.
     */
    public static function create(
        ?int $id = 1,
        string $title = 'Division 1',
        ?int $event_id = null,
        ?Event $event = null,
        array $settings = [],
        array $seasons = [],
        array $overrides = []
    ): League {
        $event    ??= self::default_event( $event_id ?? 1 );
        $event_id ??= $event->get_id();
        $raw        = [
            'id'       => $id,
            'title'    => $title,
            'event_id' => $event_id,
            'settings' => $settings,
            'seasons'  => $seasons,
        ];
        $row    = (object) array_filter( $raw, static fn( $value ) => null !== $value );

        $league = self::from_object( $row, $event );
        foreach ( $overrides as $key => $value ) {
            self::set_property( $league, (string) $key, $value );
        }

        return $league;
    }
}
