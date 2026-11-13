<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Application\Player\Ports\Registration_Service_Interface;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Already_Registered_Exception;
use Racketmanager\Exceptions\Registration_Not_Found_Exception;

/**
 * Handles club player administration commands.
 *
 * Coordinates adding players to clubs, removing selected club player
 * registrations, and scheduling player rating updates. Converts service
 * responses and domain exceptions into action result DTOs containing admin
 * messages and message types.
 */
final readonly class Club_Player_Handler {
    /**
     * Initialises the service with registration and player service dependencies.
     *
     * @param Registration_Service_Interface $registration_service Registration service dependency.
     * @param Player_Service_Interface       $player_service Player service dependency.
     *
     * @return void
     */
    public function __construct(
        private Registration_Service_Interface $registration_service,
        private Player_Service_Interface $player_service,
    ) {
    }

    /**
     * Handles adding a player to a club and returns the registration result.
     *
     * @param Add_Club_Player_Command $command Command containing the club and user identifiers.
     *
     * @return Action_Result_DTO Result containing the outcome message and message type.
     */
    public function handle_add_player( Add_Club_Player_Command $command ): Action_Result_DTO {
        $club_id = $command->club_id;
        $user_id = $command->user_id;

        try {
            $response = $this->registration_service->register_player_to_club( $club_id, $user_id );
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

    /**
     * Deletes selected club player registrations for the specified user and returns the action result.
     *
     * @param Delete_Club_Players_Command $command Command containing the club player identifiers and user identifier.
     *
     * @return Action_Result_DTO Result containing status messages and the corresponding admin message type.
     */
    public function handle_delete_club_players( Delete_Club_Players_Command $command ): Action_Result_DTO {
        $club_players = $command->club_player;
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
                $this->registration_service->remove_registration( (int) $club_player_id, $user_id );
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

    /**
     * Handles scheduling player ratings for a club.
     *
     * @param Schedule_Club_Player_Ratings_Command $command Command containing the club identifier.
     *
     * @return Action_Result_DTO Result containing the operation message and message type.
     */
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
