<?php
/**
 * RacketManager-Admin-Players API: RacketManager-admin-player class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Player
 */

declare( strict_types=1 );

namespace Racketmanager\Admin;

use Exception;
use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Admin\Controllers\Admin_Redirect_Url_Builder;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Player_Errors_Page_View_Model;
use Racketmanager\Admin\View_Models\Player_Page_View_Model;
use Racketmanager\Admin\View_Models\Player_Requests_Page_View_Model;
use Racketmanager\Admin\View_Models\Players_Page_View_Model;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Player\Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Player\Player_Error_Action_Dispatcher;
use Racketmanager\Services\Admin\Player\Player_Request_Action_Dispatcher;
use Racketmanager\Services\Validator\Validator;
use stdClass;

/**
 * RacketManager player administration functions
 * Class to implement RacketManager Administration Players panel
 */
final class Admin_Player extends Admin_Display {

    private Admin_Action_Adapter $actions;
    private Admin_Screen_Presenter $screens;
    private Player_Action_Dispatcher $player_action_dispatcher;
    private Player_Error_Action_Dispatcher $player_error_action_dispatcher;
    private Player_Request_Action_Dispatcher $player_request_action_dispatcher;

    public function __construct(
        $plugin_instance,
        ?Admin_Action_Adapter $actions = null,
        ?Admin_Screen_Presenter $screens = null,
        ?Player_Action_Dispatcher $player_action_dispatcher = null,
        ?Player_Error_Action_Dispatcher $player_error_action_dispatcher = null,
        ?Player_Request_Action_Dispatcher $player_request_action_dispatcher = null,
    ) {
        parent::__construct( $plugin_instance );
        $c                                      = $this->racketmanager->container;
        $this->actions                          = $actions ?? $c->get( 'admin_action_adapter' );
        $this->screens                          = $screens ?? $c->get( 'admin_screen_presenter' );
        $this->player_action_dispatcher         = $player_action_dispatcher ?? $c->get( 'player_action_dispatcher' );
        $this->player_error_action_dispatcher   = $player_error_action_dispatcher ?? $c->get( 'player_error_action_dispatcher' );
        $this->player_request_action_dispatcher = $player_request_action_dispatcher ?? $c->get( 'player_request_action_dispatcher' );
    }

    /**
     * Function to handle administration players displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        switch ( $view ) {
            case 'player':
                $this->display_player_page();
                break;
            case 'errors':
                $this->display_errors_page();
                break;
            case 'requests':
                $this->display_requests_page();
                break;
            case 'players':
                $this->display_players_page();
                break;
            default:
                $this->display_players_section();
                break;
        }
    }

    /**
     * Display player page
     */
    public function display_player_page(): void {
        $validator = new Validator();
        $validator->capability( 'view_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }

        $player_id = isset( $_GET['player_id'] ) ? intval( $_GET['player_id'] ) : ( isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null );
        $club_id   = isset( $_GET['club_id'] ) ? intval( $_GET['club_id'] ) : ( isset( $_POST['club_Id'] ) ? intval( $_POST['club_Id'] ) : null );

        if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
            $result        = $this->player_action_dispatcher->handle( $_GET, $_POST );
            $redirect_args = array(
                'view' => 'player',
            );
            if ( $player_id ) {
                $redirect_args['player_id'] = (string) $player_id;
            }
            if ( $club_id ) {
                $redirect_args['club_id'] = (string) $club_id;
            }
            $redirect_url = Admin_Redirect_Url_Builder::players_screen( $_GET, $redirect_args );
            $this->deliver_player_result( $result, $redirect_url );
            return;
        }

        $flash      = $this->screens->apply_flash();
        $flash_data = is_array( $flash['flash_data'] ?? null ) ? $flash['flash_data'] : array();
        $errors     = new Error_Bag( $flash_data['errors'] ?? array() );

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

        try {
            $player = $this->player_service->get_player( $player_id );

            $form_data      = $flash_data['form_data'] ?? array();
            $player_display = $player;
            if ( ! empty( $form_data ) ) {
                $player_display                = new stdClass();
                $player_display->id            = $player_id;
                $player_display->display_name  = $player->display_name ?? $player->get_fullname();
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

            $this->screens->present(
                array( 'view_model' => $vm ),
                Player_Page_View_Model::class,
                'admin/players/show-player'
            );
        } catch ( Player_Not_Found_Exception | Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
            return;
        }
    }

    /**
     * Display player errors page
     */
    public function display_errors_page(): void {
        $validator = new Validator();
        $validator->capability( 'edit_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }

        if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
            $result       = $this->player_error_action_dispatcher->handle( $_POST );
            $redirect_url = Admin_Redirect_Url_Builder::players_screen( $_GET, array( 'view' => 'errors' ) );
            $this->deliver_player_result( $result, $redirect_url );
            return;
        }

        $flash      = $this->screens->apply_flash();
        $flash_data = is_array( $flash['flash_data'] ?? null ) ? $flash['flash_data'] : array();
        $errors     = new Error_Bag( $flash_data['errors'] ?? array() );

        $status        = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : null;
        $player_errors = $this->player_service->get_player_errors( $status );

        $vm = new Player_Errors_Page_View_Model(
            player_errors: $player_errors,
            status: $status,
            racketmanager_tab: 'errors',
            errors: $errors,
            tab_title: __( 'Errors', 'racketmanager' ),
        );

        $this->screens->present(
            array( 'view_model' => $vm ),
            Player_Errors_Page_View_Model::class,
            'admin/players/show-errors'
        );
    }

    /**
     * Display player requests page
     */
    public function display_requests_page(): void {
        $validator = new Validator();
        $validator->capability( 'view_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }

        if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
            $result       = $this->player_request_action_dispatcher->handle( $_POST );
            $redirect_url = Admin_Redirect_Url_Builder::players_screen( $_GET, array( 'view' => 'requests' ) );
            $this->deliver_player_result( $result, $redirect_url );
            return;
        }

        $flash      = $this->screens->apply_flash();
        $flash_data = is_array( $flash['flash_data'] ?? null ) ? $flash['flash_data'] : array();
        $errors     = new Error_Bag( $flash_data['errors'] ?? array() );

        $club_id = isset( $_GET['club'] ) && 'all' !== $_GET['club'] ? intval( $_GET['club'] ) : null;
        $status  = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'outstanding';

        $clubs = $this->club_service->get_clubs();
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

        $this->screens->present(
            array( 'view_model' => $vm ),
            Player_Requests_Page_View_Model::class,
            'admin/players/show-requests'
        );
    }

    /**
     * Display players page
     */
    public function display_players_page(): void {
        $validator = new Validator();
        $validator->capability( 'view_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }

        if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
            $result       = $this->player_action_dispatcher->handle( $_GET, $_POST );
            $redirect_url = Admin_Redirect_Url_Builder::players_screen( $_GET, array( 'view' => 'players' ) );
            $this->deliver_player_result( $result, $redirect_url );
            return;
        }

        $flash      = $this->screens->apply_flash();
        $flash_data = is_array( $flash['flash_data'] ?? null ) ? $flash['flash_data'] : array();
        $errors     = new Error_Bag( $flash_data['errors'] ?? array() );

        $player = null;
        if ( ! empty( $flash_data['form_data'] ) ) {
            $player = (object) $flash_data['form_data'];
        }

        $players        = null;
        $search_message = null;
        if ( isset( $_GET['doPlayerSearch'] ) ) {
            if ( ! empty( $_GET['name'] ) ) {
                $players = $this->player_service->get_all_players( array( 'name' => sanitize_text_field( wp_unslash( $_GET['name'] ) ) ) );
            } else {
                $search_message = __( 'No search term specified', 'racketmanager' );
                $this->set_message( $search_message, true );
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

        $present_args = array( 'view_model' => $vm );
        if ( null !== $search_message ) {
            $present_args['message']      = $search_message;
            $present_args['message_type'] = true;
        }

        $this->screens->present(
            $present_args,
            Players_Page_View_Model::class,
            'admin/players/show-players'
        );
    }

    /**
     * Display players default section
     */
    public function display_players_section(): void {
        $validator = new Validator();
        $validator->capability( 'view_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        $this->display_errors_page();
    }

    /**
     * Deliver a dispatcher result as a PRG redirect with flash message.
     *
     * @param Action_Result_DTO $result Dispatcher result.
     * @param string            $redirect_url Redirect target.
     *
     * @return bool True when a redirect was issued.
     */
    private function deliver_player_result( Action_Result_DTO $result, string $redirect_url ): bool {
        return $this->actions->deliver_redirect(
            array(
                'redirect'     => $redirect_url,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            ),
            true
        );
    }
}
