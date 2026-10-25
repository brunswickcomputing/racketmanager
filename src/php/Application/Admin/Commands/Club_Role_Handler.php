<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

use Exception;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Services\Club_Service;

final readonly class Club_Role_Handler {
    public function __construct(
        private Club_Service $club_service,
    ) {
    }

    public function handle_add_role( Add_Club_Role_Command $command ): Action_Result_DTO {
        $club_id = $command->club_id;
        $role_id = $command->role_id;
        $user_id = $command->user_id;

        try {
            $club_role = $this->club_service->set_club_role( $club_id, $role_id, $user_id );
            if ( $club_role ) {
                return new Action_Result_DTO(
                    message: __( 'Role added', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS,
                );
            }

            return new Action_Result_DTO(
                message: __( 'Unable to add role', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        } catch ( Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }
    }

    public function handle_delete_roles( Delete_Club_Roles_Command $command ): Action_Result_DTO {
        $roles = $command->role;
        if ( empty( $roles ) ) {
            return new Action_Result_DTO(
                message: __( 'No roles selected', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        $messages = array();
        foreach ( $roles as $role_id ) {
            try {
                $this->club_service->remove_club_role( (int) $role_id );
                $messages[] = __( 'Role deleted', 'racketmanager' );
            } catch ( Exception $e ) {
                $messages[] = $e->getMessage();
            }
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }
}
