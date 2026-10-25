<?php
/**
 * League constitution action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/League
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\League;

use Racketmanager\Services\Admin\Admin_Command_Input as Input;
use Racketmanager\Application\Admin\Commands\League_Constitution_Handler;
use Racketmanager\Application\Admin\Commands\Delete_Constitution_Teams_Command;
use Racketmanager\Application\Admin\Commands\Save_League_Constitution_Command;
use Racketmanager\Application\Admin\Commands\Promote_Relegate_League_Teams_Command;
use Racketmanager\Application\Admin\Commands\Add_Constitution_Teams_Command;
use Racketmanager\Application\Admin\Commands\Generate_Constitution_Matches_Command;


use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition\League_Schedule_Service as Competition_League_Schedule_Service;
use Racketmanager\Services\Competition\Promotion_Relegation_Service;
use Racketmanager\Services\League_Service;

/**
 * Handles constitution mutations for the league admin screen (delete teams, save constitution, promote/relegate, add teams, generate matches).
 */
readonly final class League_Constitution_Action_Dispatcher {

    private League_Constitution_Handler $commands;

    public function __construct(
        private Action_Guard_Interface $action_guard,
        League_Team_Repository_Interface $league_team_repository,
        League_Service $league_service,
        Competition_League_Schedule_Service $league_schedule_service,
        League_Repository_Interface $league_repository,
        Event_Repository_Interface $event_repository,
        Team_Repository_Interface $team_repository,
        Promotion_Relegation_Service $promotion_relegation_service,
        ?League_Constitution_Handler $commands = null,
    ) {
        $this->commands = $commands ?? new League_Constitution_Handler( $league_team_repository, $league_service, $league_schedule_service, $league_repository, $event_repository, $team_repository, $promotion_relegation_service );
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     * @param object|null          $event Event domain entity/model
     */
    public function handle( array $query, array $post, ?object $event = null ): Action_Result_DTO {
        if ( isset( $post['doActionConstitution'] ) && isset( $post['action'] ) && 'delete' === $post['action'] ) {
            return $this->handle_delete_teams( $post );
        }

        if ( isset( $post['saveConstitution'] ) ) {
            return $this->handle_save_constitution( $post );
        }

        if ( isset( $post['promoteRelegate'] ) ) {
            return $this->handle_promote_relegate( $query, $post, $event );
        }

        if ( isset( $post['action'] ) && 'addTeamsToLeague' === $post['action'] ) {
            return $this->handle_add_teams( $post );
        }

        if ( isset( $post['generate_matches'] ) ) {
            return $this->handle_generate_matches( $query, $post, $event );
        }

        return new Action_Result_DTO();
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_delete_teams( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'constitution-bulk', 'del_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $command = new Delete_Constitution_Teams_Command(
                latest_season: Input::text( $post, 'latest_season' ) ?? '',
                table: Input::column( $post, 'table' ),
                team_id: Input::column( $post, 'team_id' ),
                league_id: Input::column( $post, 'league_id' ),
            );
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }

        return $this->commands->handle_delete_teams( $command );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_save_constitution( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'constitution-bulk', 'edit_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $command = new Save_League_Constitution_Command(
                js_active: 1 === Input::integer( $post, 'js-active' ),
                latest_season: Input::text( $post, 'latest_season' ),
                constitutionAction: Input::text( $post, 'constitutionAction' ) ?? '',
                table_id: Input::column( $post, 'table_id' ),
                team_id: Input::column( $post, 'team_id' ),
                league_id: Input::column( $post, 'league_id' ),
                rank: Input::column( $post, 'rank' ),
                status: Input::column( $post, 'status' ),
                profile: Input::column( $post, 'profile' ),
            );
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }

        return $this->commands->handle_save_constitution( $command );
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_promote_relegate( array $query, array $post, ?object $event ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'constitution-bulk', 'edit_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $command = new Promote_Relegate_League_Teams_Command(
                js_active: 1 === Input::integer( $post, 'js-active' ),
                latest_season: Input::integer( $post, 'latest_season' ) ?? 0,
                event_id: Input::integer( $post, 'event_id' ) ?? Input::integer( $query, 'event_id' ),
                table_id: Input::column( $post, 'table_id' ),
                team_id: Input::column( $post, 'team_id' ),
                league_id: Input::column( $post, 'league_id' ),
                rank: Input::column( $post, 'rank' ),
                old_rank: Input::column( $post, 'old_rank' ),
                status: Input::column( $post, 'status' ),
            );
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }

        return $this->commands->handle_promote_relegate( $command, $event );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_add_teams( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add-teams-bulk', 'edit_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $command = new Add_Constitution_Teams_Command(
                league_id: Input::integer( $post, 'league_id' ) ?? 0,
                season: Input::text( $post, 'season' ) ?? '',
                team: Input::column( $post, 'team' ),
            );
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }

        return $this->commands->handle_add_teams( $command );
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_generate_matches( array $query, array $post, ?object $event ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'constitution-bulk', 'edit_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $command = new Generate_Constitution_Matches_Command(
                event_id: Input::integer( $post, 'event_id' ) ?? Input::integer( $query, 'event_id' ),
                latest_season: Input::integer( $post, 'latest_season' ),
            );
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }

        return $this->commands->handle_generate_matches( $command, $event );
    }
}
