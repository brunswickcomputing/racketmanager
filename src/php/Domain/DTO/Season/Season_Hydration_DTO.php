<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Season;

/**
 * Data Transfer Object for hydrating a Season entity from database rows or input data.
 */
readonly class Season_Hydration_DTO {
    /**
     * Initialises a new instance.
     *
     * @param int|null $id Optional identifier.
     * @param string   $name Name value.
     *
     * @return void
     */
    public function __construct(
        public ?int $id = null,
        public string $name = ''
    ) {
    }

    /**
     * Creates an instance from an object representation.
     *
     * @param object $data Source object containing optional identifier and name values.
     *
     * @return self New instance populated from the provided object.
     */
    public static function from_object( object $data ): self {
        $id   = isset( $data->id ) ? (int) $data->id : null;
        $name = (string) ( $data->name ?? '' );

        return new self(
            id: $id,
            name: $name
        );
    }

    /**
     * Creates an instance from an array of data.
     *
     * @param array $data Source data used to create the instance.
     *
     * @return self Created instance.
     */
    public static function from_array( array $data ): self {
        return self::from_object( (object) $data );
    }
}
