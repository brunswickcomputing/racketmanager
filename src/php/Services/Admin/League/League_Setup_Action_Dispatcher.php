<?php
/**
 * League competition setup action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/League
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\League;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Util\Util;

/**
 * Handles match-date and rating mutations for the league competition setup screen.
 */
readonly final class League_Setup_Action_Dispatcher {

    public function __construct(
        private Competition_Service $competition_service,
        private Action_Guard_Interface $action_guard,
    ) {
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
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add_championship-matches', 'edit_matches' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $competition_id = isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null;
        $season         = isset( $post['season'] ) ? intval( $post['season'] ) : null;
        if ( ! $competition_id || ! $season || ! isset( $post['rounds'] ) || ! is_array( $post['rounds'] ) ) {
            return new Action_Result_DTO();
        }

        $validated = Match_Date_Round_Validator::validate( $post['rounds'] );
        if ( ! $validated['valid'] ) {
            return new Action_Result_DTO(
                message: strval( $validated['message'] ),
                message_type: Admin_Message_Type::ERROR,
            );
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

        $current_season['match_dates'] = $validated['dates'];
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
            $competition_event->update_seasons( $event_seasons );
        }

        return new Action_Result_DTO(
            message: __( 'Match dates updated', 'racketmanager' ),
            message_type: Admin_Message_Type::SUCCESS,
        );
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

        $competition_id = isset( $post['competition_id'] ) ? intval( $post['competition_id'] ) : null;
        $season         = isset( $post['season'] ) ? intval( $post['season'] ) : null;

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
