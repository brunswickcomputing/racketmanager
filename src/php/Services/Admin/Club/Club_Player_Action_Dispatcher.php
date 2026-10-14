<?php
/**
 * Club player action dispatcher
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
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Player_Already_Registered_Exception;
use Racketmanager\Exceptions\Registration_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;

/**
 * Handles club player mutations (register player to club, remove registration, schedule player ratings).
 */
readonly final class Club_Player_Action_Dispatcher {

    public function __construct(
        private Registration_Service $registration_service,
        private Player_Service $player_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['addPlayer'] ) ) {
            return $this->handle_add_player( $query, $post );
        }

        if ( isset( $post['doClubPlayerDel'] ) && isset( $post['action'] ) && 'delete' === $post['action'] ) {
            return $this->handle_delete_club_players( $post );
        }

        if ( isset( $post['doPlayerRatings'] ) ) {
            return $this->handle_player_ratings( $query, $post );
        }

        return new Action_Result_DTO();
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_add_player( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_manage-player', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $club_id = isset( $post['club_Id'] ) ? (int) $post['club_Id'] : ( isset( $query['club_id'] ) ? (int) $query['club_id'] : null );
        $user_id = function_exists( 'get_current_user_id' ) ? get_current_user_id() : ( wp_get_current_user()->ID ?? 0 );

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

    /**
     * @param array<string, mixed> $post
     */
    private function handle_delete_club_players( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_club-players-bulk', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $club_players = isset( $post['clubPlayer'] ) && is_array( $post['clubPlayer'] ) ? $post['clubPlayer'] : array();
        if ( empty( $club_players ) ) {
            return new Action_Result_DTO(
                message: __( 'No players selected', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        $user_id       = function_exists( 'get_current_user_id' ) ? get_current_user_id() : ( wp_get_current_user()->ID ?? 0 );
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

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_player_ratings( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_club-players-bulk', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $club_id = isset( $post['club_id'] ) ? (int) $post['club_id'] : ( isset( $query['club_id'] ) ? (int) $query['club_id'] : null );

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
