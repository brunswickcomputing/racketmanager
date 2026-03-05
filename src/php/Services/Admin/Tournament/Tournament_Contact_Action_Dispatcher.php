<?php
/**
 * Tournament contact action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Tournament
 */

namespace Racketmanager\Services\Admin\Tournament;

use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Tournament_Contact_Action_Result_DTO;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Tournament_Service;

readonly final class Tournament_Contact_Action_Dispatcher {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    public function handle( ?int $tournament_id, array $post ): Tournament_Contact_Action_Result_DTO {
        if ( isset( $post['contactTeamPreview'] ) ) {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_contact-teams', 'edit_teams' );

            return new Tournament_Contact_Action_Result_DTO(
                intent: Tournament_Contact_Action_Result_DTO::INTENT_PREVIEW,
            );
        }

        if ( isset( $post['contactTeam'] ) || isset( $post['contactTeamActive'] ) ) {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_contact-teams-preview', 'edit_teams' );

            $message = isset( $post['emailMessage'] ) ? htmlspecialchars_decode( strval( $post['emailMessage'] ) ) : null;
            $active  = isset( $post['contactTeamActive'] );

            try {
                $sent = $this->tournament_service->contact_teams( $tournament_id, $message, $active );

                if ( $sent ) {
                    return new Tournament_Contact_Action_Result_DTO(
                        intent: $active ? Tournament_Contact_Action_Result_DTO::INTENT_SEND_ACTIVE : Tournament_Contact_Action_Result_DTO::INTENT_SEND,
                        message: __( 'Email sent to players', 'racketmanager' ),
                        message_type: Admin_Message_Type::SUCCESS,
                    );
                }

                return new Tournament_Contact_Action_Result_DTO(
                    intent: $active ? Tournament_Contact_Action_Result_DTO::INTENT_SEND_ACTIVE : Tournament_Contact_Action_Result_DTO::INTENT_SEND,
                    message: __( 'Unable to send email', 'racketmanager' ),
                    message_type: Admin_Message_Type::ERROR,
                );
            } catch ( Tournament_Not_Found_Exception $e ) {
                return new Tournament_Contact_Action_Result_DTO(
                    intent: $active ? Tournament_Contact_Action_Result_DTO::INTENT_SEND_ACTIVE : Tournament_Contact_Action_Result_DTO::INTENT_SEND,
                    message: $e->getMessage(),
                    message_type: Admin_Message_Type::ERROR,
                );
            }
        }

        return new Tournament_Contact_Action_Result_DTO();
    }
}
