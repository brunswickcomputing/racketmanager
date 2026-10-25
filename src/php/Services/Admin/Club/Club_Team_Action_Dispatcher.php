<?php
/**
 * Club team action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Club
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Club;

use InvalidArgumentException;
use Racketmanager\Application\Admin\Commands\Add_Club_Team_Command;
use Racketmanager\Application\Admin\Commands\Club_Team_Handler;
use Racketmanager\Application\Admin\Commands\Delete_Club_Teams_Command;
use Racketmanager\Application\Admin\Commands\Edit_Club_Team_Command;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Admin_Command_Input as Input;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Team_Service;

/**
 * Handles club team mutations (add/edit team for club, delete teams).
 */
readonly final class Club_Team_Action_Dispatcher {

    private Club_Team_Handler $commands;

    public function __construct(
        Club_Service $club_service,
        Team_Service $team_service,
        private Action_Guard_Interface $action_guard,
        ?Club_Team_Handler $commands = null,
    ) {
        $this->commands = $commands ?? new Club_Team_Handler( $club_service, $team_service );
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['addTeam'] ) ) {
            return $this->handle_add_team( $query, $post );
        }

        if ( isset( $post['editTeam'] ) ) {
            return $this->handle_edit_team( $post );
        }

        if ( isset( $post['doTeamDel'] ) && isset( $post['action'] ) && 'delete' === $post['action'] ) {
            return $this->handle_delete_teams( $post );
        }

        return new Action_Result_DTO();
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_add_team( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add-team', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $club_id   = Input::integer( $post, 'club' ) ?? ( Input::integer( $query, 'club_id' ) ?? 0 );
            $team_type = Input::text( $post, 'team_type' ) ?? '';

            $command = new Add_Club_Team_Command(
                club_id: $club_id,
                team_type: $team_type,
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        return $this->commands->handle_add_team( $command );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_edit_team( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_manage-teams', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $team_id   = Input::text( $post, 'team_id' );
            $club_id   = Input::integer( $post, 'clubId' ) ?? 0;
            $team_type = Input::text( $post, 'team_type' ) ?? '';

            $command = new Edit_Club_Team_Command(
                team_id: $team_id,
                club_id: $club_id,
                team_type: $team_type,
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        return $this->commands->handle_edit_team( $command );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_delete_teams( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_teams-bulk', 'del_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $command = new Delete_Club_Teams_Command(
                team: Input::column( $post, 'team' ),
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        return $this->commands->handle_delete_teams( $command );
    }
}
