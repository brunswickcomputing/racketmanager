<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Domain\DTO\Player\Player_Hydration_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Player\Player_Factory;
use InvalidArgumentException;

/**
 * Builds real Player entities for tests.
 */
final class Player_Test_Factory {
    private const DTO_ALIASES = [
        'ID'          => 'id',
        'user_email'  => 'email',
        'fullname'    => 'display_name',
        'first_name'  => 'firstname',
        'last_name'   => 'surname',
    ];

    /**
     * @param int|null $id
     * @param string|null $display_name
     * @param string|null $btm
     * @param string|null $firstname
     * @param string|null $surname
     * @param string|null $email
     * @param string|null $gender
     * @param array $overrides Arbitrary property overrides applied directly to the Player.
     * @return Player
     */
    public static function create(
        ?int $id = 1,
        ?string $display_name = 'Test Player',
        ?string $btm = '12345678',
        ?string $firstname = 'Test',
        ?string $surname = 'Player',
        ?string $email = 'player@example.test',
        ?string $gender = 'M',
        array $overrides = []
    ): Player {
        return self::hydrate( array_merge(
            [
                'id'           => $id,
                'email'        => $email,
                'display_name' => $display_name,
                'firstname'    => $firstname,
                'surname'      => $surname,
                'gender'       => $gender,
                'btm'          => $btm,
            ],
            $overrides
        ) );
    }

    /**
     * Create a Player using only the supplied attributes, leaving unspecified fields empty.
     *
     * @param array $attributes Player attributes to hydrate.
     * @return Player
     */
    public static function create_sparse( array $attributes ): Player {
        return self::hydrate( $attributes );
    }

    /**
     * Hydrate supported Player attributes and apply declared non-DTO properties.
     *
     * @param array $attributes Player attributes.
     * @return Player
     */
    private static function hydrate( array $attributes ): Player {
        $hydration_data = [];
        $property_overrides = [];

        foreach ( $attributes as $key => $value ) {
            $hydration_key = self::DTO_ALIASES[ $key ] ?? $key;

            if ( property_exists( Player_Hydration_DTO::class, $hydration_key ) ) {
                $hydration_data[ $hydration_key ] = $value;
                continue;
            }

            if ( ! property_exists( Player::class, $key ) ) {
                throw new InvalidArgumentException( sprintf( 'Unknown Player test property: %s', $key ) );
            }

            $property_overrides[ $key ] = $value;
        }

        $player = Player_Factory::from_array( $hydration_data );

        foreach ( $property_overrides as $key => $value ) {
            $player->$key = $value;
        }

        return $player;
    }
}
