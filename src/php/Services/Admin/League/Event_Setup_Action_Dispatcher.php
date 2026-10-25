<?php
/**
 * Event setup action dispatcher
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
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;

/**
 * Handles match-date mutations for the league event setup screen.
 */
readonly final class Event_Setup_Action_Dispatcher {

    public function __construct(
        private Competition_Service $competition_service,
        private Action_Guard_Interface $action_guard,
        private Event_Repository_Interface $event_repository,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( ! isset( $post['action'] ) ) {
            return new Action_Result_DTO();
        }

        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add_championship-matches', 'edit_matches' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $event_id = isset( $query['event_id'] ) ? intval( $query['event_id'] ) : null;
        $season   = isset( $post['season'] ) ? intval( $post['season'] ) : null;
        if ( ! $event_id || ! $season || ! isset( $post['rounds'] ) || ! is_array( $post['rounds'] ) ) {
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
            $event = $this->competition_service->get_event_by_id( $event_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $current_season = $event->get_season_by_name( (string) $season );
        if ( null === $current_season ) {
            return new Action_Result_DTO(
                message: __( 'Season not found', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $current_season['match_dates'] = $validated['dates'];
        $seasons                       = $event->get_seasons();
        $seasons[ $season ]            = $current_season;
        $updates                       = $event->update_seasons( $seasons );
        if ( ! $updates ) {
            return new Action_Result_DTO(
                message: __( 'No updates', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        if ( ! empty( $event->id ) ) {
            $this->event_repository->update_seasons( (int) $event->id, $seasons );
        }

        return new Action_Result_DTO(
            message: __( 'Match dates updated', 'racketmanager' ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }
}
