<?php
/**
 * League competition setup action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/League
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\League;

use Racketmanager\Services\Admin\Admin_Command_Input as Input;
use Racketmanager\Application\Admin\Commands\League_Setup_Handler;
use Racketmanager\Application\Admin\Commands\Set_League_Match_Dates_Command;
use Racketmanager\Application\Admin\Commands\Calculate_League_Ratings_Command;


use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;

/**
 * Handles match-date and rating mutations for the league competition setup screen.
 */
readonly final class League_Setup_Action_Dispatcher {

    private League_Setup_Handler $commands;

    public function __construct(
        Competition_Service $competition_service,
        private Action_Guard_Interface $action_guard,
        Event_Repository_Interface $event_repository,
        ?League_Setup_Handler $commands = null,
    ) {
        $this->commands = $commands ?? new League_Setup_Handler( $competition_service, $event_repository );
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['action'] ) ) {
            return $this->handle_match_dates( $query, $post );
        }

        if ( isset( $post['rank'] ) ) {
            return $this->handle_ratings( $post );
        }

        return new Action_Result_DTO();
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_match_dates( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add_championship-fixtures', 'edit_matches' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $command = new Set_League_Match_Dates_Command(
                competition_id: Input::integer( $query, 'competition_id' ),
                season: Input::integer( $post, 'season' ),
                rounds: Input::tree( $post, 'rounds', null ),
            );
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }

        return $this->commands->handle_match_dates( $command );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_ratings( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_calculate_ratings', 'edit_matches' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $command = new Calculate_League_Ratings_Command(
                competition_id: Input::integer( $post, 'competition_id' ),
                season: Input::integer( $post, 'season' ),
            );
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }

        return $this->commands->handle_ratings( $command );
    }
}
