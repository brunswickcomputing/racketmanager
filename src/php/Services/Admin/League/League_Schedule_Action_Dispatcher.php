<?php
/**
 * League schedule action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/League
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\League;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;

/**
 * Handles schedule mutations for the league admin screen (schedule league matches, delete event matches).
 */
readonly final class League_Schedule_Action_Dispatcher {

    public function __construct(
        private League_Schedule_Service $league_schedule_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( ! isset( $post['scheduleAction'] ) ) {
            return new Action_Result_DTO();
        }

        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_schedule-matches', 'edit_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $action = isset( $post['actionSchedule'] ) ? sanitize_text_field( wp_unslash( strval( $post['actionSchedule'] ) ) ) : null;
        $events = isset( $post['event'] ) && is_array( $post['event'] ) ? array_values( wp_unslash( $post['event'] ) ) : array();

        switch ( $action ) {
            case 'schedule':
                if ( empty( $events ) ) {
                    return new Action_Result_DTO(
                        message: __( 'No events selected', 'racketmanager' ),
                        message_type: Admin_Message_Type::WARNING,
                    );
                }
                return $this->league_schedule_service->schedule_league_matches( $events );

            case 'delete':
                if ( empty( $events ) ) {
                    return new Action_Result_DTO(
                        message: __( 'No events selected', 'racketmanager' ),
                        message_type: Admin_Message_Type::WARNING,
                    );
                }
                return $this->league_schedule_service->delete_event_matches( $events );

            default:
                return new Action_Result_DTO(
                    message: __( 'No action specified', 'racketmanager' ),
                    message_type: Admin_Message_Type::WARNING,
                );
        }
    }
}
