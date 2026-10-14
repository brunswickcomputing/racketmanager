<?php
/**
 * Player error action dispatcher
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
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Player_Service;

/**
 * Handles player error bulk mutations.
 */
readonly final class Player_Error_Action_Dispatcher {

    public function __construct(
        private Player_Service $player_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $post Typically $_POST
     */
    public function handle( array $post ): Action_Result_DTO {
        if ( ! isset( $post['doPlayerErrorBulk'] ) ) {
            return new Action_Result_DTO();
        }

        if ( ! isset( $post['action'] ) || 'delete' !== $post['action'] ) {
            return new Action_Result_DTO();
        }

        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_player-error-bulk', 'edit_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $error_ids = isset( $post['playerError'] ) && is_array( $post['playerError'] ) ? array_values( $post['playerError'] ) : array();
        if ( empty( $error_ids ) ) {
            return new Action_Result_DTO(
                message: __( 'No selection made', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        $messages = array();
        foreach ( $error_ids as $player_error_id ) {
            try {
                $this->player_service->remove_player_error( (int) $player_error_id );
                /* translators: %s: Player error ID */
                $messages[] = sprintf( __( '%s - Player error has been removed', 'racketmanager' ), (int) $player_error_id );
            } catch ( Exception $e ) {
                $messages[] = $e->getMessage();
            }
        }

        return new Action_Result_DTO(
            message: implode( '<br>', $messages ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }
}
