<?php
declare( strict_types=1 );
/**
 * Player Errors Admin Controller
 *
 * Handles the player errors view (view=errors).
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Player_Errors_Page_View_Model;
use Racketmanager\Services\Admin\Player\Player_Error_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Player_Service;

final readonly class Player_Errors_Admin_Controller {

    public function __construct(
        private Player_Service $player_service,
        private Player_Error_Action_Dispatcher $player_error_action_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $flash_data
     *
     * @return array{view_model?:Player_Errors_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string, flash_data?:array<string, mixed>}
     */
    public function player_errors_page( array $query, array $post, array $flash_data = array() ): array {
        $this->action_guard->assert_capability( 'edit_leagues' );

        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
        $is_post        = 'POST' === strtoupper( $request_method )
            || isset( $post['doPlayerErrorBulk'] );

        if ( $is_post ) {
            $result       = $this->player_error_action_dispatcher->handle( $post );
            $redirect_url = Admin_Redirect_Url_Builder::players_screen( $query, array( 'view' => 'errors' ) );

            return array(
                'redirect'     => $redirect_url,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            );
        }

        $errors        = new Error_Bag( $flash_data['errors'] ?? array() );
        $status        = isset( $query['status'] ) ? sanitize_text_field( wp_unslash( strval( $query['status'] ) ) ) : null;
        $player_errors = $this->player_service->get_player_errors( $status );

        $vm = new Player_Errors_Page_View_Model(
            player_errors: $player_errors,
            status: $status,
            racketmanager_tab: 'errors',
            errors: $errors,
            tab_title: __( 'Errors', 'racketmanager' ),
        );

        return array(
            'view_model' => $vm,
        );
    }
}
