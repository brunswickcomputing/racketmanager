<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Player;

use Racketmanager\Domain\DTO\Player\User_Hydration_DTO;

/**
 * Factory for creating User entities from hydration DTOs, WordPress users, database rows, or arrays.
 */
final class User_Factory {
    /**
     * Create a User entity from a hydration DTO.
     *
     * @param User_Hydration_DTO $dto
     *
     * @return User
     */
    public static function create_from_dto( User_Hydration_DTO $dto ): User {
        return new User( $dto );
    }

    /**
     * Create a User entity from an object.
     *
     * @param object $row
     *
     * @return User
     */
    public static function from_object( object $row ): User {
        return self::create_from_dto( User_Hydration_DTO::from_object( $row ) );
    }

    /**
     * Creates a user instance from an array of hydration data.
     *
     * @param array $data User data used to create the instance.
     *
     * @return User Created user instance.
     */
    public static function from_array( array $data ): User {
        return self::create_from_dto( User_Hydration_DTO::from_array( $data ) );
    }
}
