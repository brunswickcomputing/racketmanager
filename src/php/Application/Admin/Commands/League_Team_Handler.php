<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Standings\Standings_Service;
use function Racketmanager\get_team;

final readonly class League_Team_Handler {
    public function __construct(
        private League_Service $league_service,
        private Standings_Service $standings_service
    ) {
    }

    public function delete_teams( Delete_League_Teams_Command $command, object $league ): Action_Result_DTO {
        $season = isset( $command->season ) ? $command->season : null;
        $teams  = isset( $command->team ) && is_array( $command->team ) ? $command->team : array();

        if ( empty( $teams ) ) {
            return new Action_Result_DTO();
        }

        $messages = array();
        try {
            foreach ( $teams as $team_id ) {
                $this->league_service->delete_team( $league, intval( $team_id ), $season );
                $messages[] = intval( $team_id ) . ' ' . __( 'deleted', 'racketmanager' );
            }
        } catch ( \RuntimeException | \InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }

    public function withdraw_teams( Withdraw_League_Teams_Command $command, object $league ): Action_Result_DTO {
        $season = isset( $command->season ) ? $command->season : null;
        $teams  = isset( $command->team ) && is_array( $command->team ) ? $command->team : array();

        if ( empty( $teams ) ) {
            return new Action_Result_DTO();
        }

        $messages = array();
        try {
            foreach ( $teams as $team_id ) {
                $team = get_team( $team_id );
                $this->league_service->withdraw_team( $league, intval( $team_id ), (string) $season );
                $team_title = ( $team && isset( $team->title ) ) ? $team->title : strval( $team_id );
                $messages[] = $team_title . ' ' . __( 'withdrawn', 'racketmanager' );
            }
        } catch ( \RuntimeException | \InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }

    public function handle_add_teams( Add_League_Teams_Command $command, object $league ): Action_Result_DTO {
        $league_id = $command->league_id;
        $season    = isset( $command->season ) ? intval( $command->season ) : null;
        $teams     = isset( $command->team ) && is_array( $command->team ) ? $command->team : array();
        $has_event = $command->has_event;

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

    public function handle_ranking( Rank_League_Teams_Command $command, object $league ): Action_Result_DTO {
        $type = $command->type;
        $team_ids = isset( $command->table_id ) ? array_values( (array) $command->table_id ) : array();

        if ( empty( $team_ids ) ) {
            return new Action_Result_DTO();
        }

        $ranking_performed = $this->league_service->rank_teams( $league, $type, array_filter( [ 'rank' => $command->rank, 'rating_points' => $command->rating_points, 'js-active' => $command->js_active ? 1 : 0 ], static fn( $value ) => null !== $value ), $team_ids );
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

    public function handle_manual_standings( Save_League_Standings_Command $command, object $league ): Action_Result_DTO {
        $required_keys = array( 'points_plus', 'points_minus', 'add_points', 'num_done_matches', 'num_won_matches', 'num_draw_matches', 'num_lost_matches', 'team_id', 'custom' );
        foreach ( $required_keys as $key ) {
            if ( ! isset( $command->{$key} ) ) {
                return new Action_Result_DTO();
            }
        }

        $points = array(
            'points_plus'  => $command->points_plus,
            'points_minus' => $command->points_minus,
            'add_points'   => $command->add_points,
        );

        $matches = array(
            'num_done_matches' => $command->num_done_matches,
            'num_won_matches'  => $command->num_won_matches,
            'num_draw_matches' => $command->num_draw_matches,
            'num_lost_matches' => $command->num_lost_matches,
        );

        $this->standings_service->save_standings_manually( $league, $command->team_id, $points, $matches, $command->custom );

        return new Action_Result_DTO(
            message: __( 'Standings Table updated', 'racketmanager' ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }
}
