<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Season;

use Racketmanager\Domain\DTO\Season\Season_Hydration_DTO;

/**
 * Factory for creating Season entities from supported data representations.
 *
 * Centralises Season instantiation by converting raw input data into a
 * Season_Hydration_DTO before constructing the entity.
 */
final class Season_Factory {
    /**
     * Creates a Season instance from a hydration DTO.
     *
     * @param Season_Hydration_DTO $dto Data transfer object used to hydrate the season.
     *
     * @return Season The created season instance.
     */
    public static function create_from_dto( Season_Hydration_DTO $dto ): Season {
        return new Season( $dto );
    }

    /**
     * Creates a Season instance from object data.
     *
     * @param object $row Object used to hydrate the season.
     *
     * @return Season The created season instance.
     */
    public static function from_object( object $row ): Season {
        return self::create_from_dto( Season_Hydration_DTO::from_object( $row ) );
    }

    /**
     * Creates a Season instance from array data.
     *
     * @param array $data Data used to hydrate the season.
     *
     * @return Season The created season instance.
     */
    public static function from_array( array $data ): Season {
        return self::create_from_dto( Season_Hydration_DTO::from_array( $data ) );
    }
}
