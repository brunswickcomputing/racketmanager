<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Communication;

use Racketmanager\Domain\DTO\Communication\Message_Hydration_DTO;

/**
 * Factory for creating Message entities from hydration DTOs, database objects, or arrays.
 */
final class Message_Factory {
	/**
	 * Create a Message entity from a hydration DTO.
	 *
	 * @param Message_Hydration_DTO $dto
	 * @return Message
	 */
	public static function create_from_dto( Message_Hydration_DTO $dto ): Message {
		return new Message( $dto );
	}

	/**
	 * Create a Message entity from a generic object (e.g., database row).
	 *
	 * @param object $row
	 * @return Message
	 */
	public static function from_object( object $row ): Message {
		return self::create_from_dto( Message_Hydration_DTO::from_object( $row ) );
	}

	/**
	 * Create a Message entity from an array.
	 *
	 * @param array $data
	 * @return Message
	 */
	public static function from_array( array $data ): Message {
		return self::create_from_dto( Message_Hydration_DTO::from_array( $data ) );
	}
}
