<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Factory;

/**
 * Builds real League entities for tests with valid default Event aggregates.
 */
final class League_Test_Factory {
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
            $league->sport = (string) $row->sport;
        }
        return $league;
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
        ?int $event_id = 1,
        ?Event $event = null,
        array $settings = [],
        array $seasons = [],
        array $overrides = []
    ): League {
        $event ??= self::default_event( $event_id ?? 1 );
        $raw    = [
            'id'       => $id,
            'title'    => $title,
            'event_id' => $event_id ?? $event->get_id(),
            'settings' => $settings,
            'seasons'  => $seasons,
        ];
        $row    = (object) array_filter( $raw, static fn( $value ) => null !== $value );

        $league = self::from_object( $row, $event );
        foreach ( $overrides as $key => $value ) {
            $league->$key = $value;
        }

        return $league;
    }
}
