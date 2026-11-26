<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Result;

use Racketmanager\Domain\DTO\Result\Results_Checker_Data;

/**
 * Factory for creating Results_Checker entities from hydration DTOs, database objects, or arrays.
 */
final class Results_Checker_Factory {
    /**
     * Creates a results checker instance from a data transfer object.
     *
     * @param Results_Checker_Data $dto Data transfer object used to create the results checker.
     *
     * @return Results_Checker Created results checker instance.
     */
    public static function create_from_dto( Results_Checker_Data $dto ): Results_Checker {
        return new Results_Checker( $dto );
    }

    /**
     * Creates a results checker instance from object data.
     *
     * @param object $row Object data used to create the results checker.
     *
     * @return Results_Checker Created results checker instance.
     */
    public static function from_object( object $row ): Results_Checker {
        return self::create_from_dto( Results_Checker_Data::from_object( $row ) );
    }

    /**
     * Creates a results checker instance from array data.
     *
     * @param array $data Data used to create the results checker.
     *
     * @return Results_Checker Created results checker instance.
     */
    public static function from_array( array $data ): Results_Checker {
        return self::create_from_dto( Results_Checker_Data::from_array( $data ) );
    }
}
