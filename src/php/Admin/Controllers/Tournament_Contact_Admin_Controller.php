<?php
/**
 * Tournament Contact Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Tournament_Contact_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Tournament_Contact_Action_Result_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Contact_Request_DTO;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Admin\Tournament\Tournament_Contact_Action_Dispatcher;
use Racketmanager\Services\Tournament_Service;

final readonly class Tournament_Contact_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private Tournament_Contact_Action_Dispatcher $dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Controller for admin.php?page=racketmanager-tournaments&view=contact
     *
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     * @return array{view_model?:Tournament_Contact_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string}
     *
     * @throws Tournament_Not_Found_Exception
     */
    public function contact_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_teams' );

        $tournament_id = $this->extract_tournament_id( $query, $post );

        if ( 'POST' === strtoupper( strval( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) {
            return $this->handle_post_request( $tournament_id, $post );
        }

        return array(
            'view_model' => $this->build_view_model( $tournament_id, $query, 'compose' ),
        );
    }

    private function extract_tournament_id( array $query, array $post ): ?int {
        if ( isset( $query['tournament_id'] ) ) {
            return intval( $query['tournament_id'] );
        }

        if ( isset( $post['tournament_id'] ) ) {
            return intval( $post['tournament_id'] );
        }

        return null;
    }

    private function handle_post_request( ?int $tournament_id, array $post ): array {
        $action_result = $this->dispatcher->handle( $tournament_id, $post );

        if ( Tournament_Contact_Action_Result_DTO::INTENT_PREVIEW === $action_result->intent ) {
            return array(
                'view_model' => $this->build_view_model( $tournament_id, $post, 'preview' ),
            );
        }

        if ( $this->is_send_intent( $action_result->intent ) ) {
            return $this->build_redirect_response( $tournament_id, $action_result );
        }

        return array(
            'view_model' => $this->build_view_model( $tournament_id, $post, 'compose' ),
        );
    }

    private function is_send_intent( string $intent ): bool {
        return Tournament_Contact_Action_Result_DTO::INTENT_SEND === $intent
            || Tournament_Contact_Action_Result_DTO::INTENT_SEND_ACTIVE === $intent;
    }

    private function build_redirect_response( ?int $tournament_id, Tournament_Contact_Action_Result_DTO $action_result ): array {
        $result = array(
            'redirect' => add_query_arg(
                array(
                    'page'          => 'racketmanager-tournaments',
                    'view'          => 'contact',
                    'tournament_id' => $tournament_id,
                ),
                admin_url( 'admin.php' )
            ),
        );

        if ( null !== $action_result->message ) {
            $result['message'] = $action_result->message;
            $result['message_type'] = null !== $action_result->message_type
                ? Admin_Message_Mapper::to_legacy( $action_result->message_type )
                : false;
        }

        return $result;
    }

    /**
     * @throws Tournament_Not_Found_Exception
     */
    private function build_view_model( ?int $tournament_id, array $source, string $tab ): Tournament_Contact_Page_View_Model {
        $tournament = $this->tournament_service->get_tournament( $tournament_id );
        $request    = new Tournament_Contact_Request_DTO( $source );

        $season = $request->season ?? strval( $tournament->get_season() );

        $email_message = '';
        if ( 'preview' === $tab ) {
            $email_message = $this->tournament_service->get_contact_preview(
                $tournament_id,
                $season,
                $request->email_title,
                $request->email_intro,
                $request->email_body,
                $request->email_close
            );
        }

        return new Tournament_Contact_Page_View_Model(
            tournament: $tournament,
            object_name: 'tournament_id',
            object_id: $tournament->id,
            season: $season,
            tab: $tab,
            email_title: $request->email_title,
            email_intro: $request->email_intro,
            email_body: $request->email_body,
            email_close: $request->email_close,
            email_message: $email_message,
        );
    }
}
