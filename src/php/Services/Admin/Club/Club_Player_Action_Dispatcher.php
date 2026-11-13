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
final readonly class Club_Player_Action_Dispatcher {

    /**
     * Initialises the service with registration, player, action guard, and command handling dependencies.
     *
     * @param Registration_Service   $registration_service Registration service dependency.
     * @param Player_Service         $player_service Player service dependency.
     * @param Action_Guard_Interface $action_guard Action guard dependency.
     * @param Club_Player_Handler    $commands Club player command handler dependency.
     *
     * @return void
     */
    public function __construct(
        Registration_Service $registration_service,
        Player_Service $player_service,
        private Action_Guard_Interface $action_guard,
        private Club_Player_Handler $commands,
    ) {
    }

    /**
     * Handles club player-related form submissions and returns the resulting action response.
     *
     * @param array $query Query parameters for the current request.
     * @param array $post Posted form data for the current request.
     *
     * @return Action_Result_DTO Result of the handled action, or an empty result when no supported action is requested.
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['addPlayer'] ) ) {
            $dto = $this->handle_add_player( $query, $post );
        } elseif ( isset( $post['doClubPlayerDel'] ) && isset( $post['action'] ) && 'delete' === $post['action'] ) {
            $dto = $this->handle_delete_club_players( $post );
        } elseif ( isset( $post['doPlayerRatings'] ) ) {
            $dto = $this->handle_player_ratings( $query, $post );
        } else {
            $dto = new Action_Result_DTO();
        }

        return $dto;
    }

    /**
     * Handles an add-player request by validating permissions, building the add-player command, and dispatching it.
     *
     * @param array $query Query parameters for the request.
     * @param array $post Posted form data for the request.
     *
     * @return Action_Result_DTO Result of the add-player action, including any success or error message.
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
                user_id: $user_id,
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
     * Handles a bulk request to delete club players after validating permissions and request data.
     *
     * @param array $post Posted form data containing selected club player identifiers.
     *
     * @return Action_Result_DTO Result of the delete operation, or an error result when validation fails.
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
                club_player: Input::column( $post, 'club_player' ),
                user_id: $user_id,
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
     * Handles the request to schedule player rating updates for a club.
     *
     * @param array $query Query parameters supplied with the request.
     * @param array $post Posted form data supplied with the request.
     *
     * @return Action_Result_DTO Result of the player ratings action.
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
