<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

use stdClass;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Competition\League_Schedule_Service as Competition_League_Schedule_Service;
use Racketmanager\Services\Competition\Promotion_Relegation_Service;
use Racketmanager\Services\League_Service;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_league_team;
use function Racketmanager\get_team;

final readonly class League_Constitution_Handler {
    public function __construct(
        private League_Team_Repository_Interface $league_team_repository,
        private League_Service $league_service,
        private Competition_League_Schedule_Service $league_schedule_service,
        private League_Repository_Interface $league_repository,
        private Event_Repository_Interface $event_repository,
        private Team_Repository_Interface $team_repository,
        private Promotion_Relegation_Service $promotion_relegation_service
    ) {
    }

    public function handle_delete_teams( Delete_Constitution_Teams_Command $command ): Action_Result_DTO {
        $tables        = isset( $command->table ) && is_array( $command->table ) ? $command->table : array();
        $latest_season = isset( $command->latest_season ) ? $command->latest_season : '';
        $messages = array();

        try {
            foreach ( $tables as $table_id ) {
                $teams   = isset( $command->team_id ) && is_array( $command->team_id ) ? $command->team_id : array();
                $leagues = isset( $command->league_id ) && is_array( $command->league_id ) ? $command->league_id : array();
                $team    = $teams[ $table_id ] ?? null;
                $league  = $leagues[ $table_id ] ?? null;

                if ( null !== $team && null !== $league ) {
                    $league_obj = get_league( intval( $league ) );
                    if ( ! $league_obj ) {
                        continue;
                    }
                    $this->league_service->delete_team( $league_obj, intval( $team ), $latest_season );
                    $messages[] = $team . ' ' . __( 'deleted', 'racketmanager' );
                }
            }
        } catch ( \RuntimeException | \InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        if ( empty( $messages ) ) {
            return new Action_Result_DTO();
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }

    public function handle_save_constitution( Save_League_Constitution_Command $command ): Action_Result_DTO {
        $table_ids = isset( $command->table_id ) && is_array( $command->table_id ) ? $command->table_id : array();
        if ( empty( $table_ids ) ) {
            return new Action_Result_DTO();
        }

        $js                  = $command->js_active;
        $rank                = 0;
        $updates             = 0;
        $latest_season       = isset( $command->latest_season ) ? $command->latest_season : null;
        $constitution_action = isset( $command->constitutionAction ) ? $command->constitutionAction : '';

        foreach ( $table_ids as $table_id ) {
            $team      = $command->team_id[ $table_id ] ?? null;
            $league_id = isset( $command->league_id[ $table_id ] ) ? intval( $command->league_id[ $table_id ] ) : null;

            if ( $js ) {
                ++$rank;
            } else {
                $rank = isset( $command->rank[ $table_id ] ) ? intval( $command->rank[ $table_id ] ) : null;
            }

            $status  = isset( $command->status[ $table_id ] ) ? $command->status[ $table_id ] : null;
            $profile = isset( $command->profile[ $table_id ] ) ? $command->profile[ $table_id ] : null;

            if ( 'insert' === $constitution_action && null !== $league_id ) {
                $profile = '0';
                $league  = get_league( $league_id );
                $rank_str = null !== $rank ? (string) $rank : null;
                $inserted = $league ? $this->league_repository->add_team_to_league( (int) $league->id, (int) $team, $latest_season, $rank_str, $status, (int) $profile ) : false;
                if ( $inserted ) {
                    ++$updates;
                }
            } elseif ( 'update' === $constitution_action ) {
                $league_team = get_league_team( $table_id );
                if ( null !== $league_team && ( $league_id !== $league_team->league_id || $rank !== intval( $league_team->rank ) || $status !== $league_team->status || $profile !== $league_team->get_profile() ) ) {
                    $this->league_team_repository->set_constitution_rank( (int) $table_id, $league_id, $rank, $status, $profile );
                    $league_team->set_constitution_rank( $league_id, $rank, $status, $profile );
                    ++$updates;
                }
            }
        }

        if ( $updates > 0 ) {
            return new Action_Result_DTO(
                message: __( 'Updated', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
            );
        }

        return new Action_Result_DTO(
            message: __( 'No updates', 'racketmanager' ),
            message_type: Admin_Message_Type::WARNING,
        );
    }

    public function handle_promote_relegate( Promote_Relegate_League_Teams_Command $command, ?object $event ): Action_Result_DTO {
        $table_ids = isset( $command->table_id ) && is_array( $command->table_id ) ? $command->table_id : array();
        if ( empty( $table_ids ) ) {
            return new Action_Result_DTO();
        }

        $js            = $command->js_active;
        $rank          = 0;
        $valid         = true;
        $teams         = array();
        $latest_season = isset( $command->latest_season ) ? intval( $command->latest_season ) : 0;
        $event_id      = isset( $command->event_id ) ? intval( $command->event_id ) : ( isset( $command->event_id ) ? intval( $command->event_id ) : null );

        if ( $latest_season <= 0 ) {
            return new Action_Result_DTO(
                message: __( 'No season set', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        if ( null === $event && $event_id ) {
            $event = get_event( $event_id );
        }

        if ( null === $event ) {
            return new Action_Result_DTO(
                message: __( 'No event set', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        foreach ( $table_ids as $table_id ) {
            $status = isset( $command->status[ $table_id ] ) ? $command->status[ $table_id ] : null;
            if ( empty( $rank ) && $status ) {
                $valid = false;
            }

            $team_id   = $command->team_id[ $table_id ] ?? null;
            $league_id = $command->league_id[ $table_id ] ?? null;
            $old_rank  = $command->old_rank[ $table_id ] ?? null;

            if ( $js ) {
                ++$rank;
            } else {
                $rank = $command->rank[ $table_id ] ?? '';
            }

            $team            = new stdClass();
            $team->team_id   = $team_id;
            $team->league_id = $league_id;
            $team->table_id  = $table_id;
            $team->rank      = $rank;
            $team->old_rank  = $old_rank;
            $team->status    = $status;
            $teams[]         = $team;
        }

        if ( ! $valid ) {
            return new Action_Result_DTO(
                message: __( 'Promotion and relegation has already occurred', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        if ( empty( $teams ) ) {
            return new Action_Result_DTO();
        }

        $result = $this->promotion_relegation_service->promote_and_relegate(
            $event,
            $teams,
            (int) $latest_season
        );
        if ( $result ) {
            return new Action_Result_DTO(
                message: __( 'Promotion and relegation actioned', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
            );
        }

        return new Action_Result_DTO(
            message: __( 'Error with promotion and relegation', 'racketmanager' ),
            message_type: Admin_Message_Type::ERROR,
        );
    }

    public function handle_add_teams( Add_Constitution_Teams_Command $command ): Action_Result_DTO {
        $teams = isset( $command->team ) && is_array( $command->team ) ? array_values( $command->team ) : array();
        if ( empty( $teams ) ) {
            return new Action_Result_DTO(
                message: __( 'No teams to add', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        $league_id = isset( $command->league_id ) ? intval( $command->league_id ) : 0;
        $league    = get_league( $league_id );
        $season    = isset( $command->season ) ? $command->season : '';
        $messages  = array();
        $errors    = 0;

        foreach ( $teams as $team_id ) {
            $inserted = $league ? $this->league_repository->add_team_to_league( (int) $league->id, (int) $team_id, $season, '99', 'NT', 1 ) : false;
            $team     = get_team( $team_id );
            if ( $inserted ) {
                $messages[] = ( $team?->title ?? $team_id ) . ' ' . __( 'added', 'racketmanager' );
            } else {
                ++$errors;
                $messages[] = ( $team?->title ?? $team_id ) . ' ' . __( 'could not be added', 'racketmanager' );
            }
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: $errors > 0 ? ( empty( $messages ) || count( $teams ) === $errors ? Admin_Message_Type::ERROR : Admin_Message_Type::WARNING ) : Admin_Message_Type::SUCCESS,
        );
    }

    public function handle_generate_matches( Generate_Constitution_Matches_Command $command, ?object $event ): Action_Result_DTO {
        $event_id = isset( $command->event_id ) ? intval( $command->event_id ) : ( isset( $command->event_id ) ? intval( $command->event_id ) : null );
        $season   = isset( $command->latest_season ) ? intval( $command->latest_season ) : null;

        if ( ! $event_id ) {
            return new Action_Result_DTO(
                message: __( 'No event set', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        if ( ! $season ) {
            return new Action_Result_DTO(
                message: __( 'No season set', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        if ( null === $event ) {
            $event = get_event( $event_id );
        }

        if ( null === $event ) {
            return new Action_Result_DTO(
                message: __( 'No event set', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $counts = $this->league_schedule_service->generate_box_league_matches( $event );

        if ( 0 === $counts['scheduled'] ) {
            return new Action_Result_DTO(
                message: $counts['skipped'] ? sprintf( _n( 'No leagues scheduled (%d league skipped)', 'No leagues scheduled (%d leagues skipped)', $counts['skipped'], 'racketmanager' ), $counts['skipped'] ) : __( 'No leagues scheduled', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
                flash_data: $counts,
            );
        }

        return new Action_Result_DTO(
            message: $counts['skipped'] ? sprintf( _n( 'Matches generated (%d league skipped)', 'Matches generated (%d leagues skipped)', $counts['skipped'], 'racketmanager' ), $counts['skipped'] ) : __( 'Matches generated', 'racketmanager' ),
            message_type: $counts['skipped'] ? Admin_Message_Type::WARNING : Admin_Message_Type::SUCCESS,
            flash_data: $counts,
        );
    }
}
