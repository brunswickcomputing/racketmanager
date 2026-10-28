<?php
declare( strict_types=1 );
/**
 * Player Admin Controller
 *
 * Handles the player view (view=player).
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Player_Page_View_Model;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Services\Admin\Player\Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Player_Service;
use stdClass;

final readonly class Player_Admin_Controller {

    public function __construct(
        private Player_Service $player_service,
        private Club_Service $club_service,
        private Player_Action_Dispatcher $player_action_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $flash_data
     *
     * @return array{view_model?:Player_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string, flash_data?:array<string, mixed>}
     *
     * @throws Player_Not_Found_Exception
     */
    public function player_page( array $query, array $post, array $flash_data = array() ): array {
        $this->action_guard->assert_capability( 'view_leagues' );

        $player_id = isset( $query['player_id'] ) ? intval( $query['player_id'] ) : ( isset( $post['playerId'] ) ? intval( $post['playerId'] ) : null );
        $club_id   = isset( $query['club_id'] ) ? intval( $query['club_id'] ) : ( isset( $post['club_Id'] ) ? intval( $post['club_Id'] ) : null );

        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
        $is_post        = 'POST' === strtoupper( $request_method )
            || isset( $post['updatePlayer'] )
            || isset( $post['setWTN'] );

        if ( $is_post ) {
            $result = $this->player_action_dispatcher->handle( $query, $post );

            $redirect_args = array(
                'view' => 'player',
            );
            if ( $player_id ) {
                $redirect_args['player_id'] = (string) $player_id;
            }
            if ( $club_id ) {
                $redirect_args['club_id'] = (string) $club_id;
            }

            $redirect_url = Admin_Redirect_Url_Builder::players_screen( $query, $redirect_args );

            return array(
                'redirect'     => $redirect_url,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            );
        }

        $errors = new Error_Bag( $flash_data['errors'] ?? array() );

        $club = null;
        if ( $club_id ) {
            $club = $this->club_service->get_club( $club_id );
        }

        $page_referrer = $flash_data['form_data']['page_referrer'] ?? ( function_exists( 'wp_get_referer' ) ? wp_get_referer() : null );
        if ( ! $page_referrer ) {
            $page_referrer = empty( $club_id )
                ? 'admin.php?page=racketmanager-players&tab=players'
                : 'admin.php?page=racketmanager-clubs&view=players&club_id=' . $club_id;
        }

        $player = $this->player_service->get_player( $player_id );

        $form_data      = $flash_data['form_data'] ?? array();
        $player_display = $player;
        if ( ! empty( $form_data ) ) {
            $player_display                = new stdClass();
            $player_display->id            = $player_id;
            $player_display->display_name  = $player->display_name ?? ( method_exists( $player, 'get_fullname' ) ? $player->get_fullname() : ( $player->fullname ?? '' ) );
            $player_display->firstname     = $form_data['firstname'] ?? $player->firstname;
            $player_display->surname       = $form_data['surname'] ?? $player->surname;
            $player_display->gender        = $form_data['gender'] ?? $player->gender;
            $player_display->btm           = $form_data['btm'] ?? $player->btm;
            $player_display->contactno     = $form_data['contactno'] ?? $player->contactno;
            $player_display->email         = $form_data['email'] ?? $player->email;
            $player_display->year_of_birth = $form_data['year_of_birth'] ?? $player->year_of_birth;
            $player_display->locked        = array_key_exists( 'locked', $form_data ) ? ( ! empty( $form_data['locked'] ) ? 'Locked' : '' ) : '';
            $player_display->wtn           = $player->wtn;
            $player_display->created       = $player->created ?? null;
        }

        $vm = new Player_Page_View_Model(
            player: $player_display,
            player_id: $player_id,
            club: $club,
            club_id: $club_id,
            page_referrer: $page_referrer,
            errors: $errors,
            form_valid: empty( $errors->all() ),
        );

        return array(
            'view_model' => $vm,
        );
    }
}
