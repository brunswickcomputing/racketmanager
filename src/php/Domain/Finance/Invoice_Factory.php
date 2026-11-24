<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Finance;

use Racketmanager\Domain\DTO\Finance\Invoice_Hydration_DTO;

/**
 * Factory for creating Invoice entities from hydration DTOs, database objects, or arrays.
 */
final class Invoice_Factory {
    /**
     * Create an Invoice entity from a hydration DTO.
     *
     * @param Invoice_Hydration_DTO $dto Hydration DTO.
     * @return Invoice
     */
    public static function create_from_dto( Invoice_Hydration_DTO $dto ): Invoice {
        return new Invoice( $dto );
    }

    /**
     * Create an Invoice entity from a generic object (e.g., database row).
     *
     * @param object $row Database row object.
     * @return Invoice
     */
    public static function from_object( object $row ): Invoice {
        return self::create_from_dto( Invoice_Hydration_DTO::from_object( $row ) );
    }

    /**
     * Create an Invoice entity from an array.
     *
     * @param array<string, mixed> $data Input data array.
     * @return Invoice
     */
    public static function from_array( array $data ): Invoice {
        return self::create_from_dto( Invoice_Hydration_DTO::from_array( $data ) );
    }
}
