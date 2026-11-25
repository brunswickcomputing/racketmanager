<?php
/**
 * Club action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Club
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Club;

use InvalidArgumentException;
use Racketmanager\Application\Admin\Commands\Add_Club_Admin_Command;
use Racketmanager\Application\Admin\Commands\Club_Handler;
use Racketmanager\Application\Admin\Commands\Delete_Clubs_Command;
use Racketmanager\Application\Admin\Commands\Edit_Club_Admin_Command;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Admin_Command_Input as Input;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;

/**
 * Handles club mutations for the club admin screen (add/edit club, delete clubs, schedule player ratings).
 */
final readonly class Club_Action_Dispatcher {

    /**
     * Initialises the handler with required club and player services, action guard, and command handler.
     *
     * @param Action_Guard_Interface $action_guard Action guard dependency.
     * @param Club_Handler           $commands Club command handler dependency.
     *
     * @return void
     */
    public function __construct(
        private Action_Guard_Interface $action_guard,
        private Club_Handler $commands,
    ) {
    }

    /**
     * Handles club-related form actions based on query and post data.
     *
     * @param array $query Request query parameters.
     * @param array $post Submitted post data containing the requested action.
     *
     * @return Action_Result_DTO Result of the handled action, or an empty result when no supported action is requested.
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['addClub'] ) ) {
            $dto = $this->handle_add_club( $post );
        }

        if ( isset( $post['editClub'] ) ) {
            $dto = $this->handle_edit_club( $query, $post );
        }

        if ( isset( $post['doClubDel'] ) && isset( $post['action'] ) && 'delete' === $post['action'] ) {
            $dto = $this->handle_delete_clubs( $post );
        }

        if ( isset( $post['doSchedulePlayerRatings'] ) ) {
            $dto = $this->handle_schedule_player_ratings();
        }

        return $dto ?? new Action_Result_DTO();
    }

    /**
     * Handles an add-club request by validating permissions, building the add-club command from submitted data, and returning the action result.
     *
     * @param array $post Submitted club data.
     *
     * @return Action_Result_DTO Result of the add-club action, including any validation or permission error message.
     */
    private function handle_add_club( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add-club', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $command = new Add_Club_Admin_Command(
                name: Input::text( $post, 'club' ),
                type: Input::text( $post, 'type' ),
                shortcode: Input::text( $post, 'shortcode' ),
                address: Input::text( $post, 'address' ),
                contactno: Input::text( $post, 'contactno' ),
                website: Input::text( $post, 'website' ),
                founded: Input::optional_integer( $post, 'founded' ),
                facilities: Input::text( $post, 'facilities' ),
                match_secretary_id: Input::integer( $post, 'match_secretary' ),
                match_secretary_name: Input::text( $post, 'match_secretary_name' ),
                match_secretary_contactno: Input::text( $post, 'match_secretary_contact_no' ),
                match_secretary_email: Input::text( $post, 'match_secretary_email' ),
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        return $this->commands->handle_add_club( $command );
    }

    /**
     * Handles an admin request to edit a club by validating permissions, building the edit command from request data, and delegating processing.
     *
     * @param array $query Query parameters containing fallback club identifiers.
     * @param array $post Posted form data containing club details to update.
     *
     * @return Action_Result_DTO Result of the edit operation, including success or error message details.
     */
    private function handle_edit_club( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_manage-club', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $club_id = Input::integer( $post, 'club_id' ) ?? Input::integer( $query, 'club_id' );

            $command = new Edit_Club_Admin_Command(
                club_id: $club_id,
                name: Input::text( $post, 'club' ),
                type: Input::text( $post, 'type' ),
                shortcode: Input::text( $post, 'shortcode' ),
                address: Input::text( $post, 'address' ),
                contactno: Input::text( $post, 'contactno' ),
                website: Input::text( $post, 'website' ),
                founded: Input::optional_integer( $post, 'founded' ),
                facilities: Input::text( $post, 'facilities' ),
                match_secretary_id: Input::integer( $post, 'match_secretary' ),
                match_secretary_name: Input::text( $post, 'match_secretary_name' ),
                match_secretary_contactno: Input::text( $post, 'match_secretary_contact_no' ),
                match_secretary_email: Input::text( $post, 'match_secretary_email' ),
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        return $this->commands->handle_edit_club( $command );
    }

    /**
     * Handles a bulk club deletion request by validating the action, creating the delete command, and delegating execution.
     *
     * @param array $post Posted request data containing selected club identifiers.
     *
     * @return Action_Result_DTO Result of the delete operation or an error result when validation fails.
     */
    private function handle_delete_clubs( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'clubs-bulk', 'del_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $command = new Delete_Clubs_Command(
                club: Input::column( $post, 'club' ),
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        return $this->commands->handle_delete_clubs( $command );
    }

    /**
     * Handles scheduling player ratings after verifying the current request is authorised.
     *
     * @return Action_Result_DTO Result of the schedule player ratings action, including any authorization error message.
     */
    private function handle_schedule_player_ratings(): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'clubs-bulk', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        return $this->commands->handle_schedule_player_ratings();
    }
}
