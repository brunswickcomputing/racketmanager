<?php
/**
 * League team action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/League
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\League;

use Racketmanager\Services\Admin\Admin_Command_Input as Input;
use Racketmanager\Application\Admin\Commands\League_Team_Handler;
use Racketmanager\Application\Admin\Commands\Delete_League_Teams_Command;
use Racketmanager\Application\Admin\Commands\Withdraw_League_Teams_Command;
use Racketmanager\Application\Admin\Commands\Add_League_Teams_Command;
use Racketmanager\Application\Admin\Commands\Rank_League_Teams_Command;
use Racketmanager\Application\Admin\Commands\Save_League_Standings_Command;


use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Standings\Standings_Service;
use function Racketmanager\get_league;

/**
 * Handles team mutations for the league admin screen (delete/withdraw, add teams, ranking).
 */
readonly final class League_Team_Action_Dispatcher {

    private League_Team_Handler $commands;

    public function __construct(
        League_Service $league_service,
        private Action_Guard_Interface $action_guard,
        Standings_Service $standings_service,
        ?League_Team_Handler $commands = null,
    ) {
        $this->commands = $commands ?? new League_Team_Handler( $league_service, $standings_service );
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     * @param object|null          $league League domain entity/model
     */
    public function handle( array $query, array $post, ?object $league = null ): Action_Result_DTO {
        $league = $this->resolve_league( $query, $post, $league );
        if ( null === $league ) {
            return new Action_Result_DTO();
        }

        if ( isset( $post['doAction'] ) ) {
            return $this->handle_bulk_action( $post, $league );
        }

        if ( isset( $post['action'] ) && 'addTeamsToLeague' === $post['action'] ) {
            return $this->handle_add_teams( $query, $post, $league );
        }

        if ( isset( $post['saveRanking'] ) || isset( $post['randomRanking'] ) || isset( $post['ratingPointsRanking'] ) ) {
            return $this->handle_ranking( $post, $league );
        }

        if ( isset( $post['updateLeague'] ) && 'teams_manual' === $post['updateLeague'] ) {
            return $this->handle_manual_standings( $post, $league );
        }

        return new Action_Result_DTO();
    }

    private function resolve_league( array $query, array $post, ?object $league ): ?object {
        if ( null !== $league ) {
            return $league;
        }

        $league_id = isset( $query['league_id'] ) ? intval( $query['league_id'] ) : ( isset( $post['league_id'] ) ? intval( $post['league_id'] ) : null );
        if ( ! $league_id ) {
            return null;
        }

        return get_league( $league_id );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_bulk_action( array $post, object $league ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_teams-bulk', 'del_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $action = Input::text( $post, 'action' ) ?? '';
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }
        if ( 'delete' === $action ) {
            return $this->delete_teams( $post, $league );
        }

        if ( 'withdraw' === $action ) {
            return $this->withdraw_teams( $post, $league );
        }

        return new Action_Result_DTO();
    }

    /**
     * @param array<string, mixed> $post
     */
    private function delete_teams( array $post, object $league ): Action_Result_DTO {
        try {
            $command = new Delete_League_Teams_Command(
                season: Input::text( $post, 'season' ),
                team: Input::column( $post, 'team' ),
            );
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }

        return $this->commands->delete_teams( $command, $league );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function withdraw_teams( array $post, object $league ): Action_Result_DTO {
        try {
            $command = new Withdraw_League_Teams_Command(
                season: Input::text( $post, 'season' ),
                team: Input::column( $post, 'team' ),
            );
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }

        return $this->commands->withdraw_teams( $command, $league );
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_add_teams( array $query, array $post, object $league ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add-teams-bulk', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $command = new Add_League_Teams_Command(
                league_id: method_exists( $league, 'get_id' ) ? $league->get_id() : ( isset( $league->id ) ? (int) $league->id : Input::integer( $query, 'league_id' ) ),
                season: Input::integer( $post, 'season' ),
                team: Input::column( $post, 'team' ),
                has_event: isset( $post['event_id'] ),
            );
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }

        return $this->commands->handle_add_teams( $command, $league );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_ranking( array $post, object $league ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_teams-bulk', 'update_results' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $type = isset( $post['saveRanking'] ) ? 'manual' : ( isset( $post['randomRanking'] ) ? 'random' : 'ratings' );
            $js_active = 'manual' === $type && '1' === Input::text( $post, 'js-active' );
            $command = new Rank_League_Teams_Command(
                type: $type,
                table_id: Input::column( $post, 'table_id' ),
                rank: 'manual' === $type && ! $js_active ? Input::column( $post, 'rank', null ) : null,
                rating_points: 'ratings' === $type ? Input::column( $post, 'rating_points', null ) : null,
                js_active: $js_active,
            );
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }

        return $this->commands->handle_ranking( $command, $league );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_manual_standings( array $post, object $league ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_teams-bulk', 'update_results' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $command = new Save_League_Standings_Command(
                points_plus: Input::column( $post, 'points_plus', null ),
                points_minus: Input::column( $post, 'points_minus', null ),
                add_points: Input::column( $post, 'add_points', null ),
                num_done_matches: Input::column( $post, 'num_done_matches', null ),
                num_won_matches: Input::column( $post, 'num_won_matches', null ),
                num_draw_matches: Input::column( $post, 'num_draw_matches', null ),
                num_lost_matches: Input::column( $post, 'num_lost_matches', null ),
                team_id: Input::column( $post, 'team_id', null ),
                custom: Input::tree( $post, 'custom', null ),
            );
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }

        return $this->commands->handle_manual_standings( $command, $league );
    }
}
