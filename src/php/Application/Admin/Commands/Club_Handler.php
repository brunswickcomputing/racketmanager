<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

use Exception;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Validator\Validator_Club;
use stdClass;

final readonly class Club_Handler {
    public function __construct(
        private Club_Service $club_service,
        private Player_Service $player_service,
    ) {
    }

    public function handle_add_club( Add_Club_Admin_Command $command ): Action_Result_DTO {
        $club_input = new stdClass();
        $club_input->name                          = $command->name;
        $club_input->type                          = $command->type;
        $club_input->shortcode                     = $command->shortcode;
        $club_input->match_secretary               = new stdClass();
        $club_input->match_secretary->id           = $command->match_secretary_id;
        $club_input->match_secretary->display_name = $command->match_secretary_name;
        $club_input->match_secretary->contactno    = $command->match_secretary_contactno;
        $club_input->match_secretary->email        = $command->match_secretary_email;
        $club_input->contactno                     = $command->contactno;
        $club_input->website                       = $command->website;
        $club_input->founded                       = $command->founded;
        $club_input->facilities                    = $command->facilities;
        $club_input->address                       = $command->address;

        $validator = new Validator_Club();
        $validator = $validator->name( $club_input->name );
        $validator = $validator->short_code( $club_input->shortcode );
        $validator = $validator->type( $club_input->type );
        $validator = $validator->address( $club_input->address );

        if ( ! empty( $validator->error ) ) {
            $error_message = ! empty( $validator->err_msgs[0] ) ? strval( $validator->err_msgs[0] ) : __( 'Unable to add club', 'racketmanager' );
            return new Action_Result_DTO(
                message: $error_message,
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $new_club = $this->club_service->add_club( $club_input );
            $club_id  = is_object( $new_club ) && isset( $new_club->id ) ? (int) $new_club->id : null;

            return new Action_Result_DTO(
                message: __( 'Club added', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
                entity_id: $club_id,
                query_params: $club_id ? array( 'view' => 'club', 'club_id' => $club_id ) : array(),
            );
        } catch ( Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }
    }

    public function handle_edit_club( Edit_Club_Admin_Command $command ): Action_Result_DTO {
        $club_id = $command->club_id;
        if ( ! $club_id ) {
            return new Action_Result_DTO(
                message: __( 'Club not found', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $club = $this->club_service->get_club( $club_id );
        } catch ( Club_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $club_updated = clone $club;
        $club_updated->name                          = $command->name;
        $club_updated->type                          = $command->type;
        $club_updated->shortcode                     = $command->shortcode;
        $club_updated->match_secretary               = new stdClass();
        $club_updated->match_secretary->id           = $command->match_secretary_id;
        $club_updated->match_secretary->display_name = $command->match_secretary_name;
        $club_updated->match_secretary->contactno    = $command->match_secretary_contactno;
        $club_updated->match_secretary->email        = $command->match_secretary_email;
        $club_updated->contactno                     = $command->contactno;
        $club_updated->website                       = $command->website;
        $club_updated->founded                       = $command->founded;
        $club_updated->facilities                    = $command->facilities;
        $club_updated->address                       = $command->address;

        $validator = new Validator_Club();
        $validator = $validator->name( $club_updated->name );
        $validator = $validator->short_code( $club_updated->shortcode );
        $validator = $validator->type( $club_updated->type );
        $validator = $validator->address( $club_updated->address );
        $validator = $validator->match_secretary( $club_updated->match_secretary->id );
        $validator = $validator->telephone( $club_updated->match_secretary->contactno );
        $validator = $validator->email( $club_updated->match_secretary->email, $club_updated->match_secretary->id );

        if ( ! empty( $validator->error ) ) {
            $error_message = ! empty( $validator->err_msgs[0] ) ? strval( $validator->err_msgs[0] ) : __( 'Unable to update club', 'racketmanager' );
            return new Action_Result_DTO(
                message: $error_message,
                message_type: Admin_Message_Type::ERROR,
                query_params: array( 'view' => 'club', 'club_id' => $club_id ),
            );
        }

        try {
            $response = $this->club_service->update_club( $club_id, $club_updated );
            if ( $response ) {
                return new Action_Result_DTO(
                    message: __( 'Club updated', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS,
                    entity_id: $club_id,
                    query_params: array( 'view' => 'club', 'club_id' => $club_id ),
                );
            }

            return new Action_Result_DTO(
                message: __( 'Club details unchanged', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
                entity_id: $club_id,
                query_params: array( 'view' => 'club', 'club_id' => $club_id ),
            );
        } catch ( Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                query_params: array( 'view' => 'club', 'club_id' => $club_id ),
            );
        }
    }

    public function handle_delete_clubs( Delete_Clubs_Command $command ): Action_Result_DTO {
        $clubs = $command->club;
        if ( empty( $clubs ) ) {
            return new Action_Result_DTO(
                message: __( 'No clubs selected', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        $messages      = array();
        $message_error = false;

        foreach ( $clubs as $club_id ) {
            try {
                $this->club_service->remove_club( (int) $club_id );
                $messages[] = (int) $club_id . ' ' . __( 'deleted', 'racketmanager' );
            } catch ( Exception $e ) {
                $messages[]    = $e->getMessage();
                $message_error = true;
            }
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: $message_error ? Admin_Message_Type::ERROR : Admin_Message_Type::SUCCESS,
        );
    }

    public function handle_schedule_player_ratings( Schedule_Global_Player_Ratings_Command $command ): Action_Result_DTO {
        try {
            $result = $this->player_service->schedule_global_player_ratings();
            if ( is_wp_error( $result ) ) {
                return new Action_Result_DTO(
                    message: __( 'Error scheduling player ratings calculation', 'racketmanager' ),
                    message_type: Admin_Message_Type::ERROR,
                );
            }

            if ( $result ) {
                return new Action_Result_DTO(
                    message: __( 'Player ratings calculation scheduled', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS,
                );
            }

            return new Action_Result_DTO(
                message: __( 'Player ratings calculation already scheduled', 'racketmanager' ),
                message_type: Admin_Message_Type::INFO,
            );
        } catch ( Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }
    }
}
