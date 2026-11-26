<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Tournament;

use DateMalformedStringException;
use Racketmanager\Domain\DTO\Tournament\Tournament_Hydration_DTO;

/**
 * Factory for creating Tournament entities from hydration DTOs or database objects.
 */
final class Tournament_Factory {
    /**
     * Creates a tournament instance from a hydration DTO.
     *
     * @param Tournament_Hydration_DTO $dto Data transfer object used to hydrate the tournament.
     *
     * @return Tournament Hydrated tournament instance.
     * @throws DateMalformedStringException When the date string is malformed.
     */
    public static function create_from_dto( Tournament_Hydration_DTO $dto ): Tournament {
        return new Tournament( $dto );
    }

    /**
     * Creates a tournament instance from object data.
     *
     * @param object $row Object containing tournament data used for hydration.
     *
     * @return Tournament Hydrated tournament instance.
     * @throws DateMalformedStringException When the date string is malformed.
     */
    public static function from_object( object $row ): Tournament {
        return self::create_from_dto( Tournament_Hydration_DTO::from_object( $row ) );
    }

    /**
     * Creates a tournament instance from array data.
     *
     * @param array $data Tournament data used for hydration.
     *
     * @return Tournament Hydrated tournament instance.
     * @throws DateMalformedStringException When the date string is malformed.
     */
    public static function from_array( array $data ): Tournament {
        return self::create_from_dto( Tournament_Hydration_DTO::from_array( $data ) );
    }
}
