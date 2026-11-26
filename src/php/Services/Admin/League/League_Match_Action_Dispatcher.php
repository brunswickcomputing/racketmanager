<?php
/**
 * League match action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/League
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\League;

use InvalidArgumentException;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Services\Admin\Admin_Command_Input as Input;
use Racketmanager\Application\Admin\Commands\League_Match_Handler;
use Racketmanager\Application\Admin\Commands\Delete_League_Matches_Command;
use Racketmanager\Application\Admin\Commands\Manage_League_Matches_Command;
use Racketmanager\Application\Admin\Commands\Update_League_Results_Command;


use LogicException;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;

/**
 * Handles match/fixture mutations for the league admin screen (delete matches, manage matches, update results).
 */
final readonly class League_Match_Action_Dispatcher {

    /**
     * Initialises the service with action guard, and command dependencies.
     *
     * @param Action_Guard_Interface           $action_guard Action guard dependency.
     * @param League_Match_Handler             $commands League match command handler dependency.
     * @param League_Repository_Interface|null $league_repository Optional league repository dependency.
     *
     * @return void
     */
    public function __construct(
        private Action_Guard_Interface $action_guard,
        private League_Match_Handler $commands,
        private ?League_Repository_Interface $league_repository = null,
    ) {
    }

    /**
     * Handles league match administration actions for the current request.
     *
     * Resolves the league context, routes submitted form data to the appropriate
     * action handler for deleting matches, managing matches, or updating results,
     * and returns an empty result when no supported action is requested or no
     * league can be resolved.
     *
     * @param array       $query Request query parameters used to resolve the league context.
     * @param array       $post Submitted form data indicating the requested league match action.
     * @param League|null $league Optional league context to use instead of resolving one from the request.
     *
     * @return Action_Result_DTO Result of the routed action, or an empty result when no action is performed.
     */
    public function handle( array $query, array $post, ?League $league = null ): Action_Result_DTO {
        $league = $this->resolve_league( $query, $post, $league );
        if ( null === $league ) {
            $dto = new Action_Result_DTO();
        } elseif ( isset( $post['delMatches'] ) ) {
            $dto = $this->handle_delete_matches( $post );
        } elseif ( isset( $post['updateLeague'] ) && 'match' === $post['updateLeague'] ) {
            $dto = $this->handle_manage_matches( $post, $league );
        } elseif ( isset( $post['updateLeague'] ) && 'results' === $post['updateLeague'] ) {
            $dto = $this->handle_update_results( $post, $league );
        }

        return $dto ?? new Action_Result_DTO();
    }

    /**
     * Resolves the league context for the current request.
     *
     * Returns the provided league when available, otherwise attempts to read a league
     * identifier from the query parameters or submitted form data and load the league
     * from the repository. Returns null when no league can be identified.
     *
     * @param array       $query Request query parameters that may contain a league identifier.
     * @param array       $post Submitted form data that may contain a league identifier.
     * @param League|null $league Existing league context, when already available.
     *
     * @return League|null Resolved league object, or null when no league identifier is provided.
     *
     * @throws LogicException When a league identifier is present but no league repository is available.
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

        if ( null === $this->league_repository ) {
            throw new LogicException( 'League_Match_Action_Dispatcher requires a League_Repository_Interface to resolve a league by id.' );
        }

        return $this->league_repository->find_by_id( $league_id );
    }

    /**
     * Handles the bulk delete matches action.
     *
     * Validates the current request permissions, builds a delete league matches command
     * from submitted form data, and delegates deletion to the command handler.
     * Returns an error result when authorisation fails or submitted input is invalid.
     *
     * @param array $post Submitted form data containing the selected match identifiers.
     *
     * @return Action_Result_DTO Result of the delete matches action, including any status message and tab override.
     */
    private function handle_delete_matches( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_matches-bulk', 'del_matches' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'matches',
            );
        }

        try {
            $command = new Delete_League_Matches_Command(
                match: Input::column( $post, 'match' ),
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR, tab_override: 'matches' );
        }

        return $this->commands->handle_delete_matches( $command );
    }

    /**
     * Handles the manage matches action for a league.
     *
     * Validates the current request permissions, builds a manage league matches command
     * from submitted form data, and delegates processing to the command handler.
     * Returns an error result when authorisation fails or submitted input is invalid.
     *
     * @param array  $post Submitted form data used to create or update match details.
     * @param League $league League context for the matches being managed.
     *
     * @return Action_Result_DTO Result of the manage matches action, including any status message and tab override.
     */
    private function handle_manage_matches( array $post, League $league ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_manage-matches', 'edit_matches' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'matches',
            );
        }

        try {
            $mode    = Input::text( $post, 'mode' ) ?? '';
            $command = new Manage_League_Matches_Command(
                mode: $mode,
                match: Input::column( $post, 'match' ),
                season: 'add' === $mode ? Input::text( $post, 'season' ) : null,
                final: Input::text( $post, 'final' ),
                league_id: 'add' === $mode ? Input::text( $post, 'league_id' ) : null,
                num_rubbers: 'add' === $mode ? Input::integer( $post, 'num_rubbers' ) : null,
                match_day: Input::match_day( $post ),
                home_team: Input::column( $post, 'home_team' ),
                away_team: Input::column( $post, 'away_team' ),
                host: Input::column( $post, 'host' ),
                location: Input::column( $post, 'location' ),
                myDatePicker: Input::column( $post, 'myDatePicker' ),
                begin_hour: Input::column( $post, 'begin_hour' ),
                begin_minutes: Input::column( $post, 'begin_minutes' ),
                year: 'add' === $mode ? [] : Input::column( $post, 'year' ),
                month: 'add' === $mode ? [] : Input::column( $post, 'month' ),
                day: 'add' === $mode ? [] : Input::column( $post, 'day' ),
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR, tab_override: 'matches' );
        }

        return $this->commands->handle_manage_matches( $command, $league );
    }

    /**
     * Handles a bulk league results update request after validating permissions and input data.
     *
     * @param array<string, mixed> $post Submitted form data containing season, match IDs, points, and custom result data.
     * @param League               $league League context used when applying the result updates.
     *
     * @return Action_Result_DTO Result of the update operation, including any admin message or tab override.
     */
    private function handle_update_results( array $post, League $league ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_matches-bulk', 'update_results' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'matches',
            );
        }

        try {
            $command = new Update_League_Results_Command(
                season: Input::text( $post, 'season' ) ?? '',
                matches: Input::column( $post, 'matches' ),
                home_points: Input::column( $post, 'home_points' ),
                away_points: Input::column( $post, 'away_points' ),
                custom: Input::tree( $post, 'custom' ),
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR, tab_override: 'matches' );
        }

        return $this->commands->handle_update_results( $command, $league );
    }
}
