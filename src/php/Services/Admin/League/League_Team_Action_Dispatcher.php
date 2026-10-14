<?php
/**
 * League team action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/League
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\League;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\League_Service;
use function Racketmanager\get_league;
use function Racketmanager\get_team;

/**
 * Handles team mutations for the league admin screen (delete/withdraw, add teams, ranking).
 */
readonly final class League_Team_Action_Dispatcher {

    public function __construct(
        private League_Service $league_service,
        private Action_Guard_Interface $action_guard,
    ) {
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

        $action = isset( $post['action'] ) ? sanitize_text_field( wp_unslash( strval( $post['action'] ) ) ) : '';
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
        $season = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null;
        $teams  = isset( $post['team'] ) && is_array( $post['team'] ) ? $post['team'] : array();

        if ( empty( $teams ) ) {
            return new Action_Result_DTO();
        }

        $messages = array();
        foreach ( $teams as $team_id ) {
            $league->delete_team( intval( $team_id ), $season );
            $messages[] = intval( $team_id ) . ' ' . __( 'deleted', 'racketmanager' );
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function withdraw_teams( array $post, object $league ): Action_Result_DTO {
        $season = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null;
        $teams  = isset( $post['team'] ) && is_array( $post['team'] ) ? $post['team'] : array();

        if ( empty( $teams ) ) {
            return new Action_Result_DTO();
        }

        $messages = array();
        foreach ( $teams as $team_id ) {
            $team = get_team( $team_id );
            $league->withdraw_team( intval( $team_id ), $season );
            $team_title = ( $team && isset( $team->title ) ) ? $team->title : strval( $team_id );
            $messages[] = $team_title . ' ' . __( 'withdrawn', 'racketmanager' );
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: Admin_Message_Type::SUCCESS,
        );
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

        $league_id = method_exists( $league, 'get_id' ) ? $league->get_id() : ( isset( $league->id ) ? intval( $league->id ) : ( isset( $query['league_id'] ) ? intval( $query['league_id'] ) : null ) );
        $season    = isset( $post['season'] ) ? intval( $post['season'] ) : null;
        $teams     = isset( $post['team'] ) && is_array( $post['team'] ) ? $post['team'] : array();
        $has_event = isset( $post['event_id'] );

        if ( empty( $teams ) || ! $has_event || null === $season || ! $league_id ) {
            return new Action_Result_DTO();
        }

        $messages  = array();
        $has_error = false;
        foreach ( $teams as $team_id ) {
            try {
                $this->league_service->add_team_to_league( intval( $team_id ), $league_id, $season );
                $messages[] = __( 'Team added', 'racketmanager' );
            } catch ( Team_Not_Found_Exception | League_Not_Found_Exception $e ) {
                $has_error  = true;
                $messages[] = $e->getMessage();
            }
        }

        $tab_override = ( isset( $league->is_championship ) && $league->is_championship ) ? 'preliminary' : null;

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: $has_error ? Admin_Message_Type::ERROR : Admin_Message_Type::SUCCESS,
            tab_override: $tab_override,
        );
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

        $type     = isset( $post['saveRanking'] ) ? 'manual' : ( isset( $post['randomRanking'] ) ? 'random' : 'ratings' );
        $team_ids = isset( $post['table_id'] ) ? array_values( (array) $post['table_id'] ) : array();

        if ( empty( $team_ids ) ) {
            return new Action_Result_DTO();
        }

        $ranking_performed = $this->league_service->rank_teams( $league, $type, $post, $team_ids );
        if ( $ranking_performed ) {
            return new Action_Result_DTO(
                message: __( 'Team ranking saved', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
            );
        }

        return new Action_Result_DTO(
            message: __( 'No updates', 'racketmanager' ),
            message_type: Admin_Message_Type::WARNING,
        );
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

        $required_keys = array( 'points_plus', 'points_minus', 'add_points', 'num_done_matches', 'num_won_matches', 'num_draw_matches', 'num_lost_matches', 'team_id', 'custom' );
        foreach ( $required_keys as $key ) {
            if ( ! isset( $post[ $key ] ) ) {
                return new Action_Result_DTO();
            }
        }

        $points = array(
            'points_plus'  => $post['points_plus'],
            'points_minus' => $post['points_minus'],
            'add_points'   => $post['add_points'],
        );

        $matches = array(
            'num_done_matches' => $post['num_done_matches'],
            'num_won_matches'  => $post['num_won_matches'],
            'num_draw_matches' => $post['num_draw_matches'],
            'num_lost_matches' => $post['num_lost_matches'],
        );

        $league->save_standings_manually( $post['team_id'], $points, $matches, $post['custom'] );

        return new Action_Result_DTO(
            message: __( 'Standings Table updated', 'racketmanager' ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }
}
