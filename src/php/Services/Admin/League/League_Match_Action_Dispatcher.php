<?php
/**
 * League match action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/League
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\League;

use Racketmanager\Services\Admin\Admin_Command_Input as Input;
use Racketmanager\Application\Admin\Commands\League_Match_Handler;
use Racketmanager\Application\Admin\Commands\Delete_League_Matches_Command;
use Racketmanager\Application\Admin\Commands\Manage_League_Matches_Command;
use Racketmanager\Application\Admin\Commands\Update_League_Results_Command;


use LogicException;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Fixture_Service;

/**
 * Handles match/fixture mutations for the league admin screen (delete matches, manage matches, update results).
 */
readonly final class League_Match_Action_Dispatcher {

    private League_Match_Handler $commands;

    public function __construct(
        Fixture_Repository_Interface $fixture_repository,
        Fixture_Maintenance_Service $fixture_maintenance_service,
        private Action_Guard_Interface $action_guard,
        Fixture_Result_Manager $fixture_result_manager,
        Fixture_Service $fixture_service,
        ?League_Match_Handler $commands = null,
        ?League_Query_Service $league_query_service = null,
        private ?League_Repository_Interface $league_repository = null,
    ) {
        $this->commands = $commands ?? new League_Match_Handler(
            $fixture_repository,
            $fixture_maintenance_service,
            $fixture_result_manager,
            $fixture_service,
            $league_query_service ?? throw new LogicException( 'League_Match_Action_Dispatcher requires either a League_Match_Handler or a League_Query_Service.' )
        );
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

        if ( isset( $post['delMatches'] ) ) {
            return $this->handle_delete_matches( $post );
        }

        if ( isset( $post['updateLeague'] ) && 'match' === $post['updateLeague'] ) {
            return $this->handle_manage_matches( $query, $post, $league );
        }

        if ( isset( $post['updateLeague'] ) && 'results' === $post['updateLeague'] ) {
            return $this->handle_update_results( $post, $league );
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

        if ( null === $this->league_repository ) {
            throw new LogicException( 'League_Match_Action_Dispatcher requires a League_Repository_Interface to resolve a league by id.' );
        }

        return $this->league_repository->find_by_id( $league_id );
    }

    /**
     * @param array<string, mixed> $post
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
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR, tab_override: 'matches' );
        }

        return $this->commands->handle_delete_matches( $command );
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_manage_matches( array $query, array $post, object $league ): Action_Result_DTO {
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
            $mode = Input::text( $post, 'mode' ) ?? '';
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
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR, tab_override: 'matches' );
        }

        return $this->commands->handle_manage_matches( $command, $league );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_update_results( array $post, object $league ): Action_Result_DTO {
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
        } catch ( \InvalidArgumentException $e ) {
            return new Action_Result_DTO( message: $e->getMessage(), message_type: Admin_Message_Type::ERROR, tab_override: 'matches' );
        }

        return $this->commands->handle_update_results( $command, $league );
    }
}
