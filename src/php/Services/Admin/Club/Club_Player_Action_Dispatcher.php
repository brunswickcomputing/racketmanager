<?php
/**
 * Club player action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Club
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Club;

use InvalidArgumentException;
use Racketmanager\Application\Admin\Commands\Add_Club_Player_Command;
use Racketmanager\Application\Admin\Commands\Club_Player_Handler;
use Racketmanager\Application\Admin\Commands\Delete_Club_Players_Command;
use Racketmanager\Application\Admin\Commands\Schedule_Club_Player_Ratings_Command;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Admin_Command_Input as Input;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;

/**
 * Handles club player mutations (register player to club, remove registration, schedule player ratings).
 */
readonly final class Club_Player_Action_Dispatcher {

    private Club_Player_Handler $commands;

    public function __construct(
        Registration_Service $registration_service,
        Player_Service $player_service,
        private Action_Guard_Interface $action_guard,
        ?Club_Player_Handler $commands = null,
    ) {
        $this->commands = $commands ?? new Club_Player_Handler( $registration_service, $player_service );
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

        try {
            $club_id = Input::integer( $post, 'club_Id' ) ?? Input::integer( $query, 'club_id' );
            $user_id = function_exists( 'get_current_user_id' ) ? get_current_user_id() : ( wp_get_current_user()->ID ?? 0 );

            $command = new Add_Club_Player_Command(
                club_id: $club_id,
                user_id: (int) $user_id,
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        return $this->commands->handle_add_player( $command );
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

        try {
            $user_id = function_exists( 'get_current_user_id' ) ? get_current_user_id() : ( wp_get_current_user()->ID ?? 0 );

            $command = new Delete_Club_Players_Command(
                clubPlayer: Input::column( $post, 'clubPlayer' ),
                user_id: (int) $user_id,
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        return $this->commands->handle_delete_club_players( $command );
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

        try {
            $club_id = Input::integer( $post, 'club_id' ) ?? Input::integer( $query, 'club_id' );

            $command = new Schedule_Club_Player_Ratings_Command(
                club_id: $club_id,
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        return $this->commands->handle_player_ratings( $command );
    }
}
