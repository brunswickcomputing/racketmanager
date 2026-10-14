<?php
/**
 * Player request action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Player
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Player;

use Exception;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Registration_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Registration_Service;

/**
 * Handles club player registration request bulk mutations.
 */
readonly final class Player_Request_Action_Dispatcher {

    public function __construct(
        private Registration_Service $registration_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $post Typically $_POST
     */
    public function handle( array $post ): Action_Result_DTO {
        if ( ! isset( $post['doPlayerRequest'] ) ) {
            return new Action_Result_DTO();
        }

        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_club-player-request-bulk', 'edit_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        if ( empty( $post['action'] ) || ( 'approve' !== $post['action'] && 'delete' !== $post['action'] ) ) {
            return new Action_Result_DTO(
                message: __( 'No action selected', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        $request_ids = isset( $post['playerRequest'] ) && is_array( $post['playerRequest'] ) ? array_values( $post['playerRequest'] ) : array();
        if ( empty( $request_ids ) ) {
            return new Action_Result_DTO(
                message: __( 'No selection made', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        $user_id       = function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;
        $messages      = array();
        $message_error = false;

        if ( 'approve' === $post['action'] ) {
            foreach ( $request_ids as $player_request_id ) {
                try {
                    $this->registration_service->approve_registration( (int) $player_request_id, $user_id );
                    /* translators: %s: Player request ID */
                    $messages[] = sprintf( __( '%s - Player has been approved', 'racketmanager' ), (int) $player_request_id );
                } catch ( Registration_Not_Found_Exception | Exception $e ) {
                    $messages[]    = $e->getMessage();
                    $message_error = true;
                }
            }
        } elseif ( 'delete' === $post['action'] ) {
            foreach ( $request_ids as $player_request_id ) {
                try {
                    $this->registration_service->remove_registration( (int) $player_request_id, $user_id );
                    /* translators: %s: Player request ID */
                    $messages[] = sprintf( __( '%s - Player has been removed from club', 'racketmanager' ), (int) $player_request_id );
                } catch ( Registration_Not_Found_Exception | Exception $e ) {
                    $messages[]    = $e->getMessage();
                    $message_error = true;
                }
            }
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: $message_error ? Admin_Message_Type::WARNING : Admin_Message_Type::SUCCESS,
        );
    }
}
