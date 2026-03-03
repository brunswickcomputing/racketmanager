<?php
/**
 * Tournament information action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Tournament
 */

namespace Racketmanager\Services\Admin\Tournament;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Tournament\Tournament_Information_Request_DTO;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Tournament_Service;

readonly final class Tournament_Information_Action_Dispatcher {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    public function handle( ?int $tournament_id, array $post ): Action_Result_DTO {
        $result = new Action_Result_DTO();

        if ( isset( $post['setInformation'] ) ) {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_tournament-information', 'edit_teams' );

            try {
                $tournament_information = new Tournament_Information_Request_DTO( $post );
                $response = $this->tournament_service->set_tournament_information( $tournament_id, $tournament_information );

                if ( is_WP_Error( $response ) ) {
                    $result = new Action_Result_DTO( $response->get_error_message(), Admin_Message_Type::ERROR );
                } elseif ( $response ) {
                    $result = new Action_Result_DTO( __( 'Information updated', 'racketmanager' ), Admin_Message_Type::SUCCESS );
                } else {
                    $result = new Action_Result_DTO( __( 'No updates', 'racketmanager' ), Admin_Message_Type::WARNING );
                }
            } catch ( Tournament_Not_Found_Exception $e ) {
                $result = new Action_Result_DTO( $e->getMessage(), Admin_Message_Type::ERROR );
            }
        } elseif ( isset( $post['notifyFinalists'] ) ) {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_tournament-information', 'edit_teams' );

            try {
                $response = $this->tournament_service->notify_finalists_for_tournament( $tournament_id );
                if ( $response ) {
                    $result = new Action_Result_DTO( __( 'Finalists notified', 'racketmanager' ), Admin_Message_Type::SUCCESS );
                } else {
                    $result = new Action_Result_DTO( __( 'No notification', 'racketmanager' ), Admin_Message_Type::ERROR );
                }
            } catch ( Tournament_Not_Found_Exception|Invalid_Argument_Exception $e ) {
                $result = new Action_Result_DTO( $e->getMessage(), Admin_Message_Type::ERROR );
            }
        }

        return $result;
    }
}
