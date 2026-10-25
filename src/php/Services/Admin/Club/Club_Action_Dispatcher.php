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
use Racketmanager\Application\Admin\Commands\Schedule_Global_Player_Ratings_Command;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Admin_Command_Input as Input;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Player_Service;

/**
 * Handles club mutations for the club admin screen (add/edit club, delete clubs, schedule player ratings).
 */
readonly final class Club_Action_Dispatcher {

    private Club_Handler $commands;

    public function __construct(
        Club_Service $club_service,
        Player_Service $player_service,
        private Action_Guard_Interface $action_guard,
        ?Club_Handler $commands = null,
    ) {
        $this->commands = $commands ?? new Club_Handler( $club_service, $player_service );
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['addClub'] ) ) {
            return $this->handle_add_club( $post );
        }

        if ( isset( $post['editClub'] ) ) {
            return $this->handle_edit_club( $query, $post );
        }

        if ( isset( $post['doClubDel'] ) && isset( $post['action'] ) && 'delete' === $post['action'] ) {
            return $this->handle_delete_clubs( $post );
        }

        if ( isset( $post['doSchedulePlayerRatings'] ) ) {
            return $this->handle_schedule_player_ratings();
        }

        return new Action_Result_DTO();
    }

    /**
     * @param array<string, mixed> $post
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
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
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
     * @param array<string, mixed> $post
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

    private function handle_schedule_player_ratings(): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'clubs-bulk', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        return $this->commands->handle_schedule_player_ratings( new Schedule_Global_Player_Ratings_Command() );
    }
}
