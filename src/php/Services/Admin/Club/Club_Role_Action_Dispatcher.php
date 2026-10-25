<?php
/**
 * Club role action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Club
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Club;

use InvalidArgumentException;
use Racketmanager\Application\Admin\Commands\Add_Club_Role_Command;
use Racketmanager\Application\Admin\Commands\Club_Role_Handler;
use Racketmanager\Application\Admin\Commands\Delete_Club_Roles_Command;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Admin_Command_Input as Input;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;

/**
 * Handles club role mutations (add role to club, delete roles).
 */
readonly final class Club_Role_Action_Dispatcher {

    private Club_Role_Handler $commands;

    public function __construct(
        Club_Service $club_service,
        private Action_Guard_Interface $action_guard,
        ?Club_Role_Handler $commands = null,
    ) {
        $this->commands = $commands ?? new Club_Role_Handler( $club_service );
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

        try {
            $command = new Add_Club_Role_Command(
                club_id: Input::integer( $post, 'club_id' ),
                role_id: Input::integer( $post, 'role_id' ),
                user_id: Input::integer( $post, 'user_id' ),
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        return $this->commands->handle_add_role( $command );
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

        try {
            $command = new Delete_Club_Roles_Command(
                role: Input::column( $post, 'role' ),
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        return $this->commands->handle_delete_roles( $command );
    }
}
