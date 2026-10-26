<?php
declare( strict_types=1 );
/**
 * League contact action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/League
 */

namespace Racketmanager\Services\Admin\League;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition\Competition_Notification_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\League_Service;

/**
 * Handles the "send email to teams" mutation of the league and competition admin screens.
 */
readonly final class League_Contact_Action_Dispatcher {

    public function __construct(
        private League_Service $league_service,
        private Competition_Service $competition_service,
        private Action_Guard_Interface $action_guard,
        private Competition_Notification_Service $competition_notification_service,
    ) {
    }

    /**
     * Guard (nonce + capability) and send the previewed message to the teams of a league or competition.
     *
     * @param array $post Typically $_POST
     */
    public function handle( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_contact-teams-preview', 'edit_teams' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $league_id      = isset( $post['league_id'] ) ? intval( $post['league_id'] ) : null;
        $competition_id = isset( $post['competition_id'] ) ? intval( $post['competition_id'] ) : null;
        $season         = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null;
        $message        = isset( $post['emailMessage'] ) ? htmlspecialchars_decode( strval( $post['emailMessage'] ) ) : null;

        $sent = false;
        if ( $season && $message ) {
            if ( $league_id ) {
                $league = $this->league_service->get_league( $league_id );
                if ( $league ) {
                    $sent = $this->competition_notification_service->contact_league_teams( $league, $season, $message );
                }
            } elseif ( $competition_id ) {
                $competition = $this->competition_service->get_competition( $competition_id );
                if ( $competition ) {
                    $sent = $this->competition_notification_service->contact_teams( $competition, $season, $message );
                }
            }
        }

        if ( $sent ) {
            return new Action_Result_DTO(
                message: __( 'Email sent to captains', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
            );
        }

        return new Action_Result_DTO();
    }
}
