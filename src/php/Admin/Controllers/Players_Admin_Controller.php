<?php
declare( strict_types=1 );
/**
 * Players Admin Controller
 *
 * Handles the players list view (view=players).
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Players_Page_View_Model;
use Racketmanager\Services\Admin\Player\Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Player_Service;

final readonly class Players_Admin_Controller {

    public function __construct(
        private Player_Service $player_service,
        private Player_Action_Dispatcher $player_action_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $flash_data
     *
     * @return array{view_model?:Players_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string, flash_data?:array<string, mixed>}
     */
    public function players_page( array $query, array $post, array $flash_data = array() ): array {
        $this->action_guard->assert_capability( 'view_leagues' );

        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
        $is_post        = 'POST' === strtoupper( $request_method )
            || isset( $post['addPlayer'] )
            || isset( $post['doPlayerDel'] );

        if ( $is_post ) {
            $result       = $this->player_action_dispatcher->handle( $query, $post );
            $redirect_url = Admin_Redirect_Url_Builder::players_screen( $query, array( 'view' => 'players' ) );

            return array(
                'redirect'     => $redirect_url,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            );
        }

        $errors = new Error_Bag( $flash_data['errors'] ?? array() );

        $player = null;
        if ( ! empty( $flash_data['form_data'] ) ) {
            $player = (object) $flash_data['form_data'];
        }

        $players        = null;
        $search_message = null;
        if ( isset( $query['doPlayerSearch'] ) ) {
            if ( ! empty( $query['name'] ) ) {
                $players = $this->player_service->get_all_players( array( 'name' => sanitize_text_field( wp_unslash( strval( $query['name'] ) ) ) ) );
            } else {
                $search_message = __( 'No search term specified', 'racketmanager' );
            }
        }
        if ( ! $players ) {
            $players = $this->player_service->get_all_players();
        }

        $vm = new Players_Page_View_Model(
            players: (array) $players,
            player: $player,
            racketmanager_tab: 'players',
            errors: $errors,
            form_valid: empty( $errors->all() ),
            tab_title: __( 'Players', 'racketmanager' ),
        );

        $result = array(
            'view_model' => $vm,
        );
        if ( null !== $search_message ) {
            $result['message']      = $search_message;
            $result['message_type'] = true;
        }

        return $result;
    }
}
