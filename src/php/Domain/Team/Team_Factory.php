<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Team;

use Racketmanager\Domain\DTO\Team\Team_Hydration_DTO;

/**
 * Factory for creating Team entities from hydration DTOs, database objects, or arrays.
 */
final class Team_Factory {
    /**
     * Creates a Team instance from a hydration DTO.
     *
     * @param Team_Hydration_DTO $dto Hydration DTO containing Team data.
     *
     * @return Team Team instance created from the DTO.
     */
    public static function create_from_dto( Team_Hydration_DTO $dto ): Team {
        return new Team( $dto );
    }

    /**
     * Creates a Team instance from object data.
     *
     * @param object $row Object data used to hydrate the instance.
     *
     * @return Team Hydrated Team instance.
     */
    public static function from_object( object $row ): Team {
        $dto = Team_Hydration_DTO::from_object( $row );
        return self::create_from_dto( $dto );
    }

    /**
     * Creates a Team instance from array data.
     *
     * @param array $data Team data used to hydrate the instance.
     *
     * @return Team Hydrated Team instance.
     */
    public static function from_array( array $data ): Team {
        $dto = Team_Hydration_DTO::from_array( $data );
        return self::create_from_dto( $dto );
    }
}
