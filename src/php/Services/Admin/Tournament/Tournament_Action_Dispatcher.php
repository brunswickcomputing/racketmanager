<?php
/**
 * Tournament action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Tournament
 */

namespace Racketmanager\Services\Admin\Tournament;

use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Tournament_Action_Result_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Request_DTO;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Updated_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Tournament_Service;

readonly final class Tournament_Action_Dispatcher {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param int|null $tournament_id
     * @param array $post
     *
     * @return Tournament_Action_Result_DTO
     * @throws Tournament_Not_Found_Exception
     */
    public function handle( ?int $tournament_id, array $post ): Tournament_Action_Result_DTO {
        if ( isset( $post['addTournament'] ) ) {
            return $this->handle_add( $post );
        }

        if ( isset( $post['editTournament'] ) ) {
            return $this->handle_edit( $tournament_id, $post );
        }

        return new Tournament_Action_Result_DTO();
    }

    private function handle_add( array $post ): Tournament_Action_Result_DTO {
        $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add-tournament', 'edit_teams' );

        $request  = new Tournament_Request_DTO( $post );
        $response = $this->tournament_service->add_tournament( $request );

        if ( is_wp_error( $response ) ) {
            return new Tournament_Action_Result_DTO(
                intent: Tournament_Action_Result_DTO::INTENT_ADD,
                message: __( 'Unable to add tournament', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
                raw_error: $response
            );
        }

        return new Tournament_Action_Result_DTO(
            intent: Tournament_Action_Result_DTO::INTENT_ADD,
            tournament_id: intval( $response->id ?? 0 ),
            message: __( 'Tournament added', 'racketmanager' ),
            message_type: Admin_Message_Type::SUCCESS
        );
    }

    private function handle_edit( ?int $tournament_id, array $post ): Tournament_Action_Result_DTO {
        $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_manage-tournament', 'edit_teams' );

        $request = new Tournament_Request_DTO( $post );

        try {
            $response = $this->tournament_service->update_tournament( $request );

            if ( is_wp_error( $response ) ) {
                return new Tournament_Action_Result_DTO(
                    intent: Tournament_Action_Result_DTO::INTENT_EDIT,
                    tournament_id: $tournament_id,
                    message: $response->get_error_message(),
                    message_type: Admin_Message_Type::ERROR,
                    raw_error: $response
                );
            }

            return new Tournament_Action_Result_DTO(
                intent: Tournament_Action_Result_DTO::INTENT_EDIT,
                tournament_id: intval( $tournament_id ?? ( $response->id ?? 0 ) ),
                message: __( 'Tournament updated', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS
            );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        } catch ( Tournament_Not_Updated_Exception $e ) {
            return new Tournament_Action_Result_DTO(
                intent: Tournament_Action_Result_DTO::INTENT_EDIT,
                tournament_id: $tournament_id,
                message: $e->getMessage(),
                message_type: Admin_Message_Type::WARNING,
                raw_error: $e
            );
        }
    }
}
