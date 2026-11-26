<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition;

use InvalidArgumentException;
use Racketmanager\Domain\DTO\Competition\League_Hydration_DTO;

/**
 * Factory for creating League entities.
 */
final class League_Factory {
    /**
     * Creates a League instance from a hydration DTO and associated event.
     *
     * @param League_Hydration_DTO $dto Data transfer object containing league hydration data.
     * @param Event|null           $event Optional event associated with the league.
     *
     * @return League|null Created league instance, sport-specific league instance, or null when no event is provided.
     * @throws InvalidArgumentException When the league title is required or passed event does not match.
     */
    public static function create_from_dto(
        League_Hydration_DTO $dto,
        ?Event $event = null
    ): ?League {
        if ( null === $event ) {
            return null;
        }

        if ( '' === trim( $dto->title ) ) {
            throw new InvalidArgumentException( esc_html__( 'League title is required.', 'racketmanager' ) );
        }

        if ( null !== $dto->event_id && isset( $event->id ) && (int) $dto->event_id !== $event->id ) {
            throw new InvalidArgumentException( esc_html( sprintf( 'League event_id %d does not match Event id %d.', $dto->event_id, $event->id ) ) );
        }

        // The constructor derives event_id from the Event when the DTO value is null.
        if ( ! empty( $event->competition->sport ) ) {
            $sport_class = 'Racketmanager\\sports\\League_' . ucfirst( $event->competition->sport );
            if ( class_exists( $sport_class ) ) {
                return new $sport_class(
                    $dto,
                    $event
                );
            }
        }

        return new League(
            $dto,
            $event
        );
    }

    /**
     * Creates a League instance from object data.
     *
     * @param object     $row Object containing league data.
     * @param Event|null $event Optional event associated with the league.
     *
     * @return League|null Created league instance, or null when the object data cannot be converted.
     */
    public static function from_object(
        object $row,
        ?Event $event = null
    ): ?League {
        if ( null === $event && isset( $row->event ) && $row->event instanceof Event ) {
            $event = $row->event;
        }
        $dto = League_Hydration_DTO::from_object( $row );
        return self::create_from_dto(
            $dto,
            $event
        );
    }

    /**
     * Creates a League instance from array data.
     *
     * @param array      $data Data used to create the league.
     * @param Event|null $event Optional event associated with the league.
     *
     * @return League|null Created league instance, or null when the data cannot be converted.
     */
    public static function from_array(
        array $data,
        ?Event $event = null
    ): ?League {
        return self::from_object( (object) $data, $event );
    }
}
