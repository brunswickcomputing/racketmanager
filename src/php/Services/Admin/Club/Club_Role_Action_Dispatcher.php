<?php
/**
 * Club role action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Club
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Club;

use Exception;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;

/**
 * Handles club role mutations (add role to club, delete roles).
 */
readonly final class Club_Role_Action_Dispatcher {

    public function __construct(
        private Club_Service $club_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['addRole'] ) ) {
            return $this->handle_add_role( $post );
        }

        if ( isset( $post['delRole'] ) && isset( $post['action'] ) && 'delete' === $post['action'] ) {
            return $this->handle_delete_roles( $post );
        }

        return new Action_Result_DTO();
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_add_role( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add-club-role', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $club_id = isset( $post['club_id'] ) ? (int) $post['club_id'] : null;
        $role_id = isset( $post['role_id'] ) ? (int) $post['role_id'] : null;
        $user_id = isset( $post['user_id'] ) ? (int) $post['user_id'] : null;

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
     * @param array<string, mixed> $post
     */
    private function handle_delete_roles( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_roles-bulk', 'del_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $roles = isset( $post['role'] ) && is_array( $post['role'] ) ? $post['role'] : array();
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
