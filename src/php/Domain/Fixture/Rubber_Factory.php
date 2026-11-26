<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Fixture;

use Racketmanager\Domain\DTO\Fixture\Rubber_Hydration_DTO;

/**
 * Factory for creating Rubber entities from hydration DTOs, database rows, or arrays.
 */
final class Rubber_Factory {
    /**
     * Creates a Rubber instance from a hydration DTO.
     *
     * @param Rubber_Hydration_DTO $dto Data transfer object containing the hydration data.
     *
     * @return Rubber The hydrated Rubber instance.
     */
    public static function create_from_dto( Rubber_Hydration_DTO $dto ): Rubber {
        return new Rubber( $dto );
    }

    /**
     * Creates a Rubber instance from an object containing hydration data.
     *
     * @param object $row Object used to hydrate the Rubber instance.
     *
     * @return Rubber The hydrated Rubber instance.
     */
    public static function from_object( object $row ): Rubber {
        return self::create_from_dto( Rubber_Hydration_DTO::from_object( $row ) );
    }

    /**
     * Creates a Rubber instance from an array of hydration data.
     *
     * @param array $data Data used to hydrate the Rubber instance.
     *
     * @return Rubber The hydrated Rubber instance.
     */
    public static function from_array( array $data ): Rubber {
        return self::create_from_dto( Rubber_Hydration_DTO::from_array( $data ) );
    }
}
