<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Util\Util;

final readonly class League_Setup_Handler {
    public function __construct(
        private Competition_Service $competition_service,
        private Event_Repository_Interface $event_repository
    ) {
    }

    public function handle_match_dates( Set_League_Match_Dates_Command $command ): Action_Result_DTO {
        $competition_id = $command->competition_id;
        $season = $command->season;
        if ( null === $command->match_dates ) {
            return new Action_Result_DTO();
        }

        try {
            $competition = $this->competition_service->get_competition( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $current_season = $competition->get_season_by_name( (string) $season );
        if ( null === $current_season ) {
            return new Action_Result_DTO(
                message: __( 'Season not found', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $current_season['match_dates'] = $command->match_dates;
        $seasons                       = $competition->get_seasons();
        $seasons[ $season ]            = $current_season;
        $updates                       = $this->competition_service->update_seasons( $competition, $seasons );
        if ( ! $updates ) {
            return new Action_Result_DTO(
                message: __( 'No updates', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        $events = $this->competition_service->get_events_for_competition( $competition_id, $season );
        foreach ( $events as $competition_event ) {
            $event_seasons = $competition_event->get_seasons();
            if ( empty( $competition_event->offset ) ) {
                $match_dates = $current_season['match_dates'];
            } else {
                $match_dates = array();
                $i           = 0;
                foreach ( $current_season['match_dates'] as $match_date ) {
                    $match_dates[ $i ] = Util::amend_date( strval( $match_date ), intval( $competition_event->offset ), '+', 'week' );
                    ++$i;
                }
            }
            $event_seasons[ $season ]['match_dates'] = $match_dates;
            $event_changed = $competition_event->update_seasons( $event_seasons );
            if ( $event_changed && ! empty( $competition_event->id ) ) {
                $this->event_repository->update_seasons( (int) $competition_event->id, $event_seasons );
            }
        }

        return new Action_Result_DTO(
            message: __( 'Match dates updated', 'racketmanager' ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }

    public function handle_ratings( Calculate_League_Ratings_Command $command ): Action_Result_DTO {
        $competition_id = isset( $command->competition_id ) ? intval( $command->competition_id ) : null;
        $season         = isset( $command->season ) ? intval( $command->season ) : null;

        try {
            $this->competition_service->calculate_team_ratings( $competition_id, $season );

            return new Action_Result_DTO(
                message: __( 'League ratings set', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
            );
        } catch ( Competition_Not_Found_Exception | Season_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }
    }
}
