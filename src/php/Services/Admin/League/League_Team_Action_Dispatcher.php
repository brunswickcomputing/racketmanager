<?php
/**
 * League team action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/League
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\League;

use InvalidArgumentException;
use LogicException;
use Racketmanager\Domain\Competition\League;
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
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Standings\Standings_Service;

/**
 * Handles team mutations for the league admin screen (delete/withdraw, add teams, ranking).
 */
final readonly class League_Team_Action_Dispatcher {

    private League_Team_Handler $commands;

    /**
     * Initialises the league team action dispatcher with required services and command handling.
     *
     * @param League_Service                 $league_service Service used to access and manage league data.
     * @param Action_Guard_Interface         $action_guard Guard used to validate whether actions are permitted.
     * @param Standings_Service              $standings_service Service used when creating the default team command handler.
     * @param League_Team_Handler|null       $commands Optional command handler for league team actions.
     * @param Team_Repository_Interface|null $team_repository Optional team repository required when no command handler is provided.
     *
     * @return void
     * @throws LogicException Missing League_Team_Handler or Team_Repository_Interface.
     */
    public function __construct(
        private League_Service $league_service,
        private Action_Guard_Interface $action_guard,
        Standings_Service $standings_service,
        ?League_Team_Handler $commands = null,
        ?Team_Repository_Interface $team_repository = null,
    ) {
        if ( null === $commands ) {
            if ( null === $team_repository ) {
                throw new LogicException( 'League_Team_Action_Dispatcher requires a League_Team_Handler or a Team_Repository_Interface.' );
            }
            $commands = new League_Team_Handler( $league_service, $standings_service, $team_repository );
        }
        $this->commands = $commands;
    }

    /**
     * Handles league administration actions based on query and post data.
     *
     * @param array<string, mixed> $query query data.
     * @param array<string, mixed> $post post data.
     * @param League|null          $league League object.
     *
     * @return Action_Result_DTO
     */
    public function handle( array $query, array $post, ?League $league = null ): Action_Result_DTO {
        $league = $this->resolve_league( $query, $post, $league );
        if ( null === $league ) {
            $dto = new Action_Result_DTO();
        } elseif ( isset( $post['doAction'] ) ) {
            $dto = $this->handle_bulk_action( $post, $league );
        } elseif ( isset( $post['action'] ) && 'addTeamsToLeague' === $post['action'] ) {
            $dto = $this->handle_add_teams( $query, $post, $league );
        } elseif ( isset( $post['saveRanking'] ) || isset( $post['randomRanking'] ) || isset( $post['ratingPointsRanking'] ) ) {
            $dto = $this->handle_ranking( $post, $league );
        } elseif ( isset( $post['updateLeague'] ) && 'teams_manual' === $post['updateLeague'] ) {
            $dto = $this->handle_manual_standings( $post, $league );
        }

        return $dto ?? new Action_Result_DTO();
    }

    /**
     * Resolves the league from the provided instance or request data.
     *
     * @param array<string, mixed> $query query data.
     * @param array<string, mixed> $post post data.
     * @param League|null          $league League object.
     *
     * @return League|null
     */
    private function resolve_league( array $query, array $post, ?League $league ): ?League {
        if ( null !== $league ) {
            return $league;
        }

        $post_league_id = isset( $post['league_id'] ) ? intval( $post['league_id'] ) : null;
        $league_id      = isset( $query['league_id'] ) ? intval( $query['league_id'] ) : $post_league_id;
        if ( ! $league_id ) {
            return null;
        }

        return $this->league_service->get_league( $league_id );
    }

    /**
     * Handles bulk action for league teams.
     *
     * @param array<string, mixed> $post post data.
     * @param League               $league League object.
     *
     * @return Action_Result_DTO
     */
    private function handle_bulk_action( array $post, League $league ): Action_Result_DTO {
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
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }
        if ( 'delete' === $action ) {
            $dto = $this->delete_teams( $post, $league );
        }

        if ( 'withdraw' === $action ) {
            $dto = $this->withdraw_teams( $post, $league );
        }

        return $dto ?? new Action_Result_DTO();
    }

    /**
     * Deletes teams from the specified league based on the submitted request data.
     *
     * @param array<string, mixed> $post array of request data.
     * @param League               $league League object.
     *
     * @return Action_Result_DTO
     */
    private function delete_teams( array $post, League $league ): Action_Result_DTO {
        try {
            $command = new Delete_League_Teams_Command(
                season: Input::text( $post, 'season' ),
                team: Input::column( $post, 'team' ),
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }

        return $this->commands->delete_teams( $command, $league );
    }

    /**
     * Withdraws teams from a league using submitted request data.
     *
     * @param array  $post Submitted data containing the season and team values.
     * @param League $league League context used to process the withdrawal.
     *
     * @return Action_Result_DTO Result of the withdrawal action or an error result when input validation fails.
     */
    private function withdraw_teams( array $post, League $league ): Action_Result_DTO {
        try {
            $command = new Withdraw_League_Teams_Command(
                season: Input::text( $post, 'season' ),
                team: Input::column( $post, 'team' ),
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }

        return $this->commands->withdraw_teams( $command, $league );
    }

    /**
     * Adds teams to a league using submitted request data after validating action permissions.
     *
     * @param array  $query Query data used to resolve the league identifier when it is not available from the league object.
     * @param array  $post Submitted data containing the season, team, and optional event values.
     * @param League $league League context used to process the team addition.
     *
     * @return Action_Result_DTO Result of the add-teams action or an error result when authorization or input validation fails.
     */
    private function handle_add_teams( array $query, array $post, League $league ): Action_Result_DTO {
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
                league_id: $this->resolve_league_id( $league, $query ),
                season: Input::integer( $post, 'season' ),
                team: Input::column( $post, 'team' ),
                has_event: isset( $post['event_id'] ),
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }

        return $this->commands->handle_add_teams( $command, $league );
    }

    /**
     * Resolves the league identifier from the league context or query parameters.
     *
     * @param League               $league League context.
     * @param array<string, mixed> $query Query parameters used when the league identifier is not present on the league.
     *
     * @return int|null League identifier, or null if not found.
     */
    private function resolve_league_id( League $league, array $query ): ?int {
        if ( method_exists( $league, 'get_id' ) ) {
            return $league->get_id();
        }

        if ( isset( $league->id ) ) {
            return (int) $league->id;
        }

        return Input::integer( $query, 'league_id' );
    }

    /**
     * Handles league team ranking updates using submitted request data.
     *
     * @param array  $post Submitted data containing ranking action, table identifiers, ranks, rating points, and JavaScript state.
     * @param League $league League context used to process the ranking update.
     *
     * @return Action_Result_DTO Result of the ranking action or an error result when authorization or input validation fails.
     */
    private function handle_ranking( array $post, League $league ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_teams-bulk', 'update_results' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $type      = $this->resolve_ranking_type( $post );
            $js_active = 'manual' === $type && '1' === Input::text( $post, 'js-active' );
            $command   = new Rank_League_Teams_Command(
                type: $type,
                table_id: Input::column( $post, 'table_id' ),
                rank: 'manual' === $type && ! $js_active ? Input::column( $post, 'rank', null ) : null,
                rating_points: 'ratings' === $type ? Input::column( $post, 'rating_points', null ) : null,
                js_active: $js_active,
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }

        return $this->commands->handle_ranking( $command, $league );
    }

    /**
     * Resolves the ranking type from submitted post data.
     *
     * @param array<string, mixed> $post Submitted data containing ranking action.
     *
     * @return string Ranking type ('manual', 'random', or 'ratings').
     */
    private function resolve_ranking_type( array $post ): string {
        return match ( true ) {
            isset( $post['saveRanking'] )   => 'manual',
            isset( $post['randomRanking'] ) => 'random',
            default                         => 'ratings',
        };
    }

    /**
     * Handles manual league standings updates using submitted request data.
     *
     * @param array  $post Submitted data containing standings values, team identifiers, and custom standings data.
     * @param League $league League context used to process the standings update.
     *
     * @return Action_Result_DTO Result of the manual standings update or an error result when authorization or input validation fails.
     */
    private function handle_manual_standings( array $post, League $league ): Action_Result_DTO {
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
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR );
        }

        return $this->commands->handle_manual_standings( $command, $league );
    }
}
