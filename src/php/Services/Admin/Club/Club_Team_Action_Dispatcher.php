<?php
/**
 * Club team action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Club
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Club;

use Exception;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Team_Service;
use function Racketmanager\get_team;

/**
 * Handles club team mutations (add/edit team for club, delete teams).
 */
readonly final class Club_Team_Action_Dispatcher {

    public function __construct(
        private Club_Service $club_service,
        private Team_Service $team_service,
        private Action_Guard_Interface $action_guard,
    ) {
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

        $club_id   = absint( $post['club'] ?? ( $query['club_id'] ?? null ) );
        $team_type = sanitize_text_field( wp_unslash( strval( $post['team_type'] ?? '' ) ) );

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

        $team_id   = isset( $post['team_id'] ) ? strval( $post['team_id'] ) : null;
        $club_id   = absint( $post['clubId'] ?? null );
        $team_type = sanitize_text_field( wp_unslash( strval( $post['team_type'] ?? '' ) ) );

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

        $teams = isset( $post['team'] ) && is_array( $post['team'] ) ? $post['team'] : array();
        if ( empty( $teams ) ) {
            return new Action_Result_DTO(
                message: __( 'No teams selected', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        $messages = array();
        foreach ( $teams as $team_id ) {
            $team = get_team( (int) $team_id );
            if ( null !== $team && method_exists( $team, 'delete' ) ) {
                $team->delete();
                $messages[] = ( $team->title ?? (string) $team_id ) . ' ' . __( 'deleted', 'racketmanager' );
            }
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }
}
