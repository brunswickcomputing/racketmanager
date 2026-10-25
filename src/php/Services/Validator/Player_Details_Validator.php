<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Validator;

use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use stdClass;
use WP_Error;

/**
 * Validator for player details and creation input.
 */
class Player_Details_Validator {
    private Player_Repository_Interface $player_repository;

    /**
     * @param Player_Repository_Interface $player_repository
     */
    public function __construct( Player_Repository_Interface $player_repository ) {
        $this->player_repository = $player_repository;
    }

    /**
     * Validate player details and return a WP_Error or stdClass player object.
     *
     * @param array|null $request
     * @return stdClass|WP_Error
     */
    public function validate_player( ?array $request = null ): stdClass|WP_Error {
        [ $player, $validator ] = $this->build_player_and_validator( $request );

        if ( ! empty( $validator->error ) ) {
            $validator->err->add_data( $player, 'player' );
            return $validator->err;
        }

        return $player;
    }

    /**
     * Validate player details from request/input data and return a legacy tuple.
     *
     * @param array|null $input Optional input array; null defaults to $_POST.
     * @return array
     */
    public function validate_player_details( ?array $input = null ): array {
        [ $player, $validator ] = $this->build_player_and_validator( $input );

        // The legacy tuple contract always exposes contactno as a string.
        $player->contactno = $player->contactno ?? '';

        if ( empty( $validator->error ) ) {
            return [ true, $player ];
        }

        return [ false, $validator->err_flds, $validator->err_msgs, $player ];
    }

    /**
     * Build and validate the player object from input data.
     *
     * An absent or empty contact number is left as null.
     *
     * @param array|null $input
     * @return array{0: stdClass, 1: Validator}
     */
    public function build_player_and_validator( ?array $input = null ): array {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $source        = $input ?? ( $_POST ?? [] );
        $player_id     = isset( $source['playerId'] ) ? intval( $source['playerId'] ) : null;
        $firstname     = isset( $source['firstname'] ) ? trim( sanitize_text_field( wp_unslash( $source['firstname'] ) ) ) : null;
        $surname       = isset( $source['surname'] ) ? trim( sanitize_text_field( wp_unslash( $source['surname'] ) ) ) : null;
        $gender        = isset( $source['gender'] ) ? sanitize_text_field( wp_unslash( $source['gender'] ) ) : null;
        $btm           = isset( $source['btm'] ) ? sanitize_text_field( wp_unslash( $source['btm'] ) ) : null;
        $contactno     = empty( $source['contactno'] ) ? null : sanitize_text_field( wp_unslash( $source['contactno'] ) );
        $email         = isset( $source['email'] ) ? sanitize_text_field( wp_unslash( $source['email'] ) ) : null;
        $locked        = isset( $source['locked'] );
        $year_of_birth = empty( $source['year_of_birth'] ) ? null : intval( $source['year_of_birth'] );

        $validator     = new Validator();
        $validator     = $validator->first_name( $firstname );
        $validator     = $validator->surname( $surname );
        $validator     = $validator->gender( $gender );

        if ( empty( $validator->error ) && empty( $player_id ) ) {
            $name            = $firstname . ' ' . $surname;
            $existing_player = $this->player_repository->find( $name, 'name' );
            if ( $existing_player ) {
                $player_id = (int) $existing_player->get_id();
            }
        }

        $validator     = $validator->btm( intval( $btm ), $player_id );
        $validator     = $validator->email( $email, $player_id, false );
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        $player                = new stdClass();
        $player->id            = $player_id;
        $player->firstname     = $firstname;
        $player->surname       = $surname;
        $player->fullname      = $firstname . ' ' . $surname;
        $player->user_login    = strtolower( (string) $firstname ) . '.' . strtolower( (string) $surname );
        $player->email         = $email;
        $player->btm           = $btm;
        $player->contactno     = $contactno;
        $player->gender        = $gender;
        $player->locked        = $locked;
        $player->year_of_birth = $year_of_birth;

        return [ $player, $validator ];
    }
}
