<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Player;

/**
 * Data Transfer Object for hydrating a Player_Error entity from database rows or input data.
 *
 * Strict allowlist of the player errors columns the entity declares. Unknown row keys are dropped.
 */
readonly class Player_Error_Hydration_DTO {
    /**
     * Initialises a new instance with optional message data.
     *
     * @param int|null    $id The message identifier.
     * @param int|null    $player_id The player identifier associated with the message.
     * @param string|null $message The message content.
     * @param string|null $created_date The date the message was created.
     *
     * @return void
     */
    public function __construct(
        public ?int $id = null,
        public ?int $player_id = null,
        public ?string $message = null,
        public ?string $created_date = null,
    ) {
    }

    /**
     * Creates an instance from an object containing message data.
     *
     * @param object $data The source object containing values to map into the instance.
     *
     * @return self A new instance populated with normalized values from the source object.
     */
    public static function from_object( object $data ): self {
        return new self(
            id: self::nullable_int( $data->id ?? null ),
            player_id: self::nullable_int( $data->player_id ?? null ),
            message: isset( $data->message ) && is_scalar( $data->message ) ? (string) $data->message : null,
            created_date: isset( $data->created_date ) && is_scalar( $data->created_date ) ? (string) $data->created_date : null,
        );
    }

    /**
     * Creates an instance from an associative array of data.
     *
     * @param array $data The data used to create the instance.
     *
     * @return self The created instance.
     */
    public static function from_array( array $data ): self {
        return self::from_object( (object) $data );
    }

    /**
     * Converts a nullable value to an integer.
     *
     * @param mixed $value The value to convert, or null or an empty string to return null.
     *
     * @return int|null The converted integer, or null when the value is null or an empty string.
     */
    private static function nullable_int( mixed $value ): ?int {
        if ( null === $value || '' === $value ) {
            return null;
        }
        return (int) $value;
    }
}
