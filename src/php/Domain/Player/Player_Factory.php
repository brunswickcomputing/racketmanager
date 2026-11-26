<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Player;

use Racketmanager\Domain\DTO\Player\Player_Hydration_DTO;
use Racketmanager\Util\Player_Link_Util;

/**
 * Factory for creating Player entities from hydration DTOs or database objects / WP_User.
 */
final class Player_Factory {
    /**
     * Creates a player from the provided hydration DTO and applies derived field values.
     *
     * @param Player_Hydration_DTO $dto Data transfer object containing values used to hydrate the player.
     *
     * @return Player Hydrated player instance with derived fields applied.
     */
    public static function create_from_dto(
        Player_Hydration_DTO $dto
    ): Player {
        $player = new Player( $dto );
        self::apply_derived_fields( $player );

        return $player;
    }

    /**
     * Applies missing derived field values to the player.
     *
     * @param Player $player Player instance to update with derived values when applicable.
     *
     * @return void
     */
    private static function apply_derived_fields( Player $player ): void {
        if ( empty( $player->locked_user_name ) ) {
            $player->set_locked_user_name( '' );
        }
        if ( empty( $player->link ) && ! empty( $player->display_name ) ) {
            $player->set_link( Player_Link_Util::generate_link( $player->display_name, $player->btm ?? null ) );
        }
    }

    /**
     * Creates a player from a source object.
     *
     * @param object $row Source object containing player data.
     *
     * @return Player Player instance created from the source object.
     */
    public static function from_object(
        object $row
    ): Player {
        $dto = Player_Hydration_DTO::from_object( $row );
        return self::create_from_dto( $dto );
    }

    /**
     * Creates a player from an array of data.
     *
     * @param array $data Source data used to hydrate the player.
     *
     * @return Player Created player instance.
     */
    public static function from_array(
        array $data
    ): Player {
        $dto = Player_Hydration_DTO::from_array( $data );
        return self::create_from_dto( $dto );
    }
}
