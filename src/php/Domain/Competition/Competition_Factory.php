<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition;

use Racketmanager\Domain\DTO\Competition\Competition_Hydration_DTO;

/**
 * Factory for creating Competition entities.
 */
final class Competition_Factory {
    /**
     * Creates a Competition instance from the provided hydration DTO.
     *
     * @param Competition_Hydration_DTO $dto Data transfer object containing competition hydration data.
     *
     * @return Competition Competition instance populated from the DTO.
     */
    public static function create_from_dto( Competition_Hydration_DTO $dto ): Competition {
        return new Competition(
            name: $dto->name,
            type: $dto->type,
            age_group: $dto->age_group ?: '',
            seasons: $dto->seasons,
            settings: $dto->settings,
            id: $dto->id
        );
    }

    /**
     * Creates a Competition instance from the provided object.
     *
     * @param object $row Object containing competition data to hydrate.
     *
     * @return Competition Competition instance populated from the object data.
     */
    public static function from_object( object $row ): Competition {
        $dto = Competition_Hydration_DTO::from_object( $row );
        return self::create_from_dto( $dto );
    }
}
