<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition;

use Racketmanager\Domain\DTO\Competition\League_Team_Hydration_DTO;

/**
 * Factory for creating league team instances from supported hydration sources.
 *
 * Centralises construction of league team objects from DTO, object, and array data.
 */
final class League_Team_Factory {
    /**
     * Creates a league team instance from a hydration DTO.
     *
     * @param League_Team_Hydration_DTO $dto Data transfer object containing league team hydration data.
     *
     * @return League_Team Hydrated league team instance.
     */
    public static function create_from_dto( League_Team_Hydration_DTO $dto ): League_Team {
        return new League_Team( $dto );
    }

    /**
     * Creates a league team instance from object data.
     *
     * @param object $row Object containing data used to hydrate the league team.
     *
     * @return League_Team Hydrated league team instance.
     */
    public static function from_object( object $row ): League_Team {
        return self::create_from_dto( League_Team_Hydration_DTO::from_object( $row ) );
    }

    /**
     * Creates a league team instance from array data.
     *
     * @param array $data Data used to hydrate the league team.
     *
     * @return League_Team Hydrated league team instance.
     */
    public static function from_array( array $data ): League_Team {
        return self::create_from_dto( League_Team_Hydration_DTO::from_array( $data ) );
    }
}
