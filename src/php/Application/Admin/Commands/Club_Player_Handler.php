<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Already_Registered_Exception;
use Racketmanager\Exceptions\Registration_Not_Found_Exception;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;

final readonly class Club_Player_Handler {
    public function __construct(
        private Registration_Service $registration_service,
        private Player_Service $player_service,
    ) {
    }

    public function handle_add_player( Add_Club_Player_Command $command ): Action_Result_DTO {
        $club_id = $command->club_id;
        $user_id = $command->user_id;

        try {
            $response = $this->registration_service->register_player_to_club( $club_id, (int) $user_id );
            if ( is_wp_error( $response ) ) {
                return new Action_Result_DTO(
                    message: __( 'Error with player details', 'racketmanager' ),
                    message_type: Admin_Message_Type::ERROR,
                );
            }

            $message = is_string( $response ) ? $response : __( 'Player registered to club', 'racketmanager' );
            return new Action_Result_DTO(
                message: $message,
                message_type: Admin_Message_Type::SUCCESS,
            );
        } catch ( Club_Not_Found_Exception | Player_Already_Registered_Exception | Registration_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }
    }

    public function handle_delete_club_players( Delete_Club_Players_Command $command ): Action_Result_DTO {
        $club_players = $command->clubPlayer;
        if ( empty( $club_players ) ) {
            return new Action_Result_DTO(
                message: __( 'No players selected', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        $user_id       = $command->user_id;
        $messages      = array();
        $message_error = false;

        foreach ( $club_players as $club_player_id ) {
            try {
                $this->registration_service->remove_registration( (int) $club_player_id, (int) $user_id );
                /* translators: %s: Club player ID */
                $messages[] = sprintf( __( '%s - Player has been removed from club', 'racketmanager' ), (int) $club_player_id );
            } catch ( Club_Not_Found_Exception | Registration_Not_Found_Exception $e ) {
                $messages[]    = $e->getMessage();
                $message_error = true;
            }
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: $message_error ? Admin_Message_Type::ERROR : Admin_Message_Type::SUCCESS,
        );
    }

    public function handle_player_ratings( Schedule_Club_Player_Ratings_Command $command ): Action_Result_DTO {
        $club_id = $command->club_id;

        try {
            $this->player_service->schedule_player_ratings( $club_id );
            return new Action_Result_DTO(
                message: __( 'Player ratings scheduled', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
            );
        } catch ( Club_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }
    }
}
