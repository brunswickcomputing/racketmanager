<?php
/**
 * Tournament Overview Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\View_Models\Tournament_Overview_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Tournament_Service;

readonly final class Tournament_Overview_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Controller for admin.php?page=racketmanager-tournaments&view=tournament
     *
     * @param array $query Typically $_GET
     * @param array $post Typically $_POST
     *
     * @return array{view_model:Tournament_Overview_Page_View_Model, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    public function overview_page( array $query, array $post ): array {
        $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;

        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
            $overview   = $this->tournament_service->get_tournament_overview( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }

        $message      = null;
        $message_type = false;

        // Optional: handle "contact teams" POST from this page (legacy behaviour).
        if ( isset( $post['contactTeam'] ) || isset( $post['contactTeamActive'] ) ) {
            $contact_result = $this->handle_contact_teams_post( $post );
            $message        = $contact_result['message'];
            $message_type   = $contact_result['message_type'];
        }

        $events = $this->tournament_service->get_leagues_by_event_for_tournament( $tournament_id );
        $tab    = 'overview';

        $confirmed_entries = $this->tournament_service->get_players_for_tournament( $tournament_id, 'confirmed' );
        $unpaid_entries    = $this->tournament_service->get_players_for_tournament( $tournament_id, 'unpaid' );
        $pending_entries   = $this->tournament_service->get_players_for_tournament( $tournament_id, 'pending' );
        $withdrawn_entries = $this->tournament_service->get_players_for_tournament( $tournament_id, 'withdrawn' );

        $vm = new Tournament_Overview_Page_View_Model(
            tournament: $tournament,
            overview: $overview,
            events: $events,
            tab: $tab,
            confirmed_entries: is_array( $confirmed_entries ) ? $confirmed_entries : array(),
            unpaid_entries: is_array( $unpaid_entries ) ? $unpaid_entries : array(),
            pending_entries: is_array( $pending_entries ) ? $pending_entries : array(),
            withdrawn_entries: is_array( $withdrawn_entries ) ? $withdrawn_entries : array(),
        );

        $result = array(
            'view_model' => $vm,
        );

        if ( null !== $message ) {
            $result['message']      = $message;
            $result['message_type'] = $message_type;
        }

        return $result;
    }

    /**
     * @param array $post
     *
     * @return array{message:string, message_type:bool|string}
     * @throws Invalid_Status_Exception
     */
    private function handle_contact_teams_post( array $post ): array {
        $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_contact-teams-preview', 'edit_teams' );

        $tournament_id = isset( $post['tournament_id'] ) ? intval( $post['tournament_id'] ) : null;
        $message       = isset( $post['emailMessage'] ) ? htmlspecialchars_decode( strval( $post['emailMessage'] ) ) : null;
        $active        = isset( $post['contactTeamActive'] );

        try {
            $sent = $this->tournament_service->contact_teams( $tournament_id, $message, $active );
            if ( $sent ) {
                return array(
                    'message'      => __( 'Email sent to players', 'racketmanager' ),
                    'message_type' => false,
                );
            }

            return array(
                'message'      => __( 'Unable to send email', 'racketmanager' ),
                'message_type' => true,
            );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return array(
                'message'      => $e->getMessage(),
                'message_type' => true,
            );
        }
    }
}
