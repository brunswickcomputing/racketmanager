<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Competition;

use InvalidArgumentException;

/**
 * Data Transfer Object for hydrating a League entity from raw data.
 */
class League_Hydration_DTO {
    /**
     * Backward-compatible property hook for legacy title access.
     *
     * @var string
     */
    public string $title {
    final get => $this->name;
    }

    public readonly string $name;

    /**
     * Initialises a league instance and validates the provided identifiers.
     *
     * @param int|null    $id League identifier, or null when not assigned.
     * @param string      $name League name.
     * @param int|null    $event_id Related event identifier, or null when not assigned.
     * @param string|null $sequence League sequence value, or null when not assigned.
     * @param array       $settings League settings.
     * @param array       $seasons League seasons.
     * @param string|null $title Fallback title used when the name is empty.
     *
     * @return void
     *
     * @throws InvalidArgumentException When the league id is negative.
     * @throws InvalidArgumentException When the event id is not a positive integer.
     */
    public function __construct(
        public readonly ?int $id = null,
        string $name = '',
        public readonly ?int $event_id = null,
        public readonly ?string $sequence = null,
        public readonly array $settings = [],
        public readonly array $seasons = [],
        ?string $title = null
    ) {
        $this->name = '' !== $name ? $name : ( $title ?? '' );

        if ( null !== $this->id && $this->id < 0 ) {
            throw new InvalidArgumentException( 'League id must be non-negative.' );
        }
        if ( null !== $this->event_id && $this->event_id <= 0 ) {
            throw new InvalidArgumentException( 'League event_id must be a positive integer.' );
        }
    }

    /**
     * Creates an instance from a generic data object, normalising serialized or JSON-encoded settings and seasons.
     *
     * @param object $data Source object containing league data.
     *
     * @return self New instance populated from the provided object.
     *
     * @throws InvalidArgumentException When the resolved identifier is negative.
     */
    public static function from_object( object $data ): self {
        $seasons = $data->seasons ?? [];
        if ( is_string( $seasons ) ) {
            $unserialized = self::decode_serialized_array( $seasons );
            if ( is_array( $unserialized ) ) {
                $seasons = $unserialized;
            } else {
                $seasons = json_decode( $seasons, true ) ?: [];
            }
        }

        $settings = $data->settings ?? [];
        if ( is_string( $settings ) ) {
            $unserialized = self::decode_serialized_array( $settings );
            if ( is_array( $unserialized ) ) {
                $settings = $unserialized;
            } else {
                $settings = json_decode( $settings, true ) ?: [];
            }
        }

        $name = (string) ( $data->name ?? ( $data->title ?? '' ) );

        return new self(
            id: isset( $data->id ) && '' !== (string) $data->id ? (int) $data->id : null,
            name: $name,
            event_id: isset( $data->event_id ) && '' !== (string) $data->event_id && (int) $data->event_id > 0 ? (int) $data->event_id : null,
            sequence: isset( $data->sequence ) ? (string) $data->sequence : null,
            settings: (array) $settings,
            seasons: (array) $seasons
        );
    }

    /**
     * Creates an instance from an associative array of data.
     *
     * @param array $data Source data used to create the instance.
     *
     * @return self Created instance.
     */
    public static function from_array( array $data ): self {
        return self::from_object( (object) $data );
    }

    /**
     * Decodes a serialized PHP array string when the provided value matches the expected serialized array format.
     *
     * @param mixed $value Value to inspect and decode.
     *
     * @return mixed Unserialized array data when the value is a serialized array string, the original value when it is not, or false if unserialization fails.
     */
    private static function decode_serialized_array( mixed $value ): mixed {
        if ( ! is_string( $value ) ) {
            return $value;
        }
        $serialized = trim( $value );
        if ( ! preg_match( '/^a:\d+:\{.*\}$/sD', $serialized ) ) {
            return $value;
        }
        return @unserialize( $serialized );
    }
}
