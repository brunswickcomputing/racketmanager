<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Player;

use Racketmanager\Domain\DTO\Player\Player_Hydration_DTO;
use Racketmanager\Util\Player_Link_Util;
use Racketmanager\Util\Util;
use RuntimeException;

/**
 * Factory for creating Player entities from hydration DTOs or database objects / WP_User.
 */
final class Player_Factory {
	/**
	 * Create a Player entity from a hydration DTO.
	 *
	 * @param Player_Hydration_DTO $dto
	 * @return Player
	 */
	public static function create_from_dto(
		Player_Hydration_DTO $dto
	): Player {
		$player = new Player( $dto );
		self::apply_derived_fields( $player );

		return $player;
	}

	/**
	 * Derive WordPress-backed display fields that are not persisted on the DTO.
	 *
	 * @param Player $player
	 * @return void
	 */
	private static function apply_derived_fields( Player $player ): void {
		if ( ! empty( $player->locked_user ) && empty( $player->locked_user_name ) ) {
			if ( ! function_exists( 'get_userdata' ) ) {
				throw new RuntimeException( 'get_userdata() is required to resolve Player locked_user_name.' );
			}
			$user_data              = get_userdata( $player->locked_user );
			$player->locked_user_name = ( $user_data && isset( $user_data->display_name ) ) ? $user_data->display_name : '';
		} elseif ( empty( $player->locked_user_name ) ) {
			$player->locked_user_name = '';
		}
		if ( empty( $player->link ) && ! empty( $player->display_name ) ) {
			$player->link = Player_Link_Util::generate_link( $player->display_name, $player->btm ?? null );
		}
	}

	/**
	 * Create a Player entity from a generic object (e.g. database row or WP_User).
	 *
	 * @param object $row
	 * @return Player
	 */
	public static function from_object(
		object $row
	): Player {
		$dto = Player_Hydration_DTO::from_object( $row );
		return self::create_from_dto( $dto );
	}

	/**
	 * Create a Player entity from an array.
	 *
	 * @param array $data
	 * @return Player
	 */
	public static function from_array(
		array $data
	): Player {
		$dto = Player_Hydration_DTO::from_array( $data );
		return self::create_from_dto( $dto );
	}
}
