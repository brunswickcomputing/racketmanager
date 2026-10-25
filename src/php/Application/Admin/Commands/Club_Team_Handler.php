<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Team_Service;

final readonly class Club_Team_Handler {
    public function __construct(
        private Club_Service $club_service,
        private Team_Service $team_service,
    ) {
    }

    public function handle_add_team( Add_Club_Team_Command $command ): Action_Result_DTO {
        $club_id   = $command->club_id;
        $team_type = $command->team_type;

        try {
            $this->club_service->create_team( $club_id, $team_type );
            return new Action_Result_DTO(
                message: __( 'Team added', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
            );
        } catch ( Club_Not_Found_Exception | Invalid_Argument_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }
    }

    public function handle_edit_team( Edit_Club_Team_Command $command ): Action_Result_DTO {
        $team_id   = $command->team_id;
        $club_id   = $command->club_id;
        $team_type = $command->team_type;

        try {
            $response = $this->team_service->amend_team_for_club( $team_id, $club_id, $team_type );
            if ( $response ) {
                return new Action_Result_DTO(
                    message: __( 'Team updated', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS,
                );
            }

            return new Action_Result_DTO(
                message: __( 'Team details unchanged', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        } catch ( Team_Not_Found_Exception | Club_Not_Found_Exception | Invalid_Argument_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }
    }

    public function handle_delete_teams( Delete_Club_Teams_Command $command ): Action_Result_DTO {
        $teams = $command->team;
        if ( empty( $teams ) ) {
            return new Action_Result_DTO(
                message: __( 'No teams selected', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        $messages  = array();
        $has_error = false;
        foreach ( $teams as $team_id ) {
            try {
                $team = $this->team_service->get_team_by_id( (int) $team_id );
            } catch ( Team_Not_Found_Exception ) {
                $team = null;
            }
            if ( null !== $team ) {
                $deleted = $this->team_service->delete_team( (int) $team_id );
                if ( $deleted ) {
                    $messages[] = ( $team->get_name() ?: (string) $team_id ) . ' ' . __( 'deleted', 'racketmanager' );
                } else {
                    $has_error  = true;
                    $messages[] = ( $team->get_name() ?: (string) $team_id ) . ' ' . __( 'failed to delete', 'racketmanager' );
                }
            }
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: $has_error ? Admin_Message_Type::ERROR : Admin_Message_Type::SUCCESS,
        );
    }
}
