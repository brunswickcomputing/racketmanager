<?php
declare( strict_types=1 );
/**
 * Player Requests Admin Controller
 *
 * Handles the player requests view (view=requests).
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Player_Requests_Page_View_Model;
use Racketmanager\Services\Admin\Player\Player_Request_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Registration_Service;

final readonly class Player_Requests_Admin_Controller {

    public function __construct(
        private Registration_Service $registration_service,
        private Club_Service $club_service,
        private Player_Request_Action_Dispatcher $player_request_action_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $flash_data
     *
     * @return array{view_model?:Player_Requests_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string, flash_data?:array<string, mixed>}
     */
    public function player_requests_page( array $query, array $post, array $flash_data = array() ): array {
        $this->action_guard->assert_capability( 'view_leagues' );

        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
        $is_post        = 'POST' === strtoupper( $request_method )
            || isset( $post['doPlayerRequest'] );

        if ( $is_post ) {
            $result       = $this->player_request_action_dispatcher->handle( $post );
            $redirect_url = Admin_Redirect_Url_Builder::players_screen( $query, array( 'view' => 'requests' ) );

            return array(
                'redirect'     => $redirect_url,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            );
        }

        $errors  = new Error_Bag( $flash_data['errors'] ?? array() );
        $club_id = isset( $query['club'] ) && 'all' !== $query['club'] ? intval( $query['club'] ) : null;
        $status  = isset( $query['status'] ) ? sanitize_text_field( wp_unslash( strval( $query['status'] ) ) ) : 'outstanding';

        $clubs           = $this->club_service->get_clubs();
        $player_requests = $this->registration_service->get_registered_players_list( null, $status, $club_id );

        $vm = new Player_Requests_Page_View_Model(
            player_requests: $player_requests,
            clubs: $clubs,
            club_id: $club_id,
            status: $status,
            racketmanager_tab: 'requests',
            errors: $errors,
            tab_title: __( 'Requests', 'racketmanager' ),
        );

        return array(
            'view_model' => $vm,
        );
    }
}
