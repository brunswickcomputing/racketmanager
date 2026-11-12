<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

use Exception;
use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;

/**
 * Handles club role administration commands.
 *
 * Coordinates role assignment and removal operations through the club service and
 * converts service outcomes or caught exceptions into action result DTOs suitable
 * for administrative feedback.
 */
final readonly class Club_Role_Handler {
    /**
     * Initialises the service with the club service dependency.
     *
     * @param Club_Service_Interface $club_service Club service dependency.
     *
     * @return void
     */
    public function __construct(
        private Club_Service_Interface $club_service,
    ) {
    }

    /**
     * Handles adding a role to a user within a club.
     *
     * @param Add_Club_Role_Command $command Command containing the club, role, and user identifiers.
     *
     * @return Action_Result_DTO Result describing whether the role was added successfully.
     */
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

    /**
     * Handles deletion of selected club roles and returns the resulting action status.
     *
     * @param Delete_Club_Roles_Command $command Command containing the club role identifiers to delete.
     *
     * @return Action_Result_DTO Result containing the deletion messages and message type.
     */
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
