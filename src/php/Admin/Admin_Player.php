<?php
declare( strict_types=1 );
/**
 * RacketManager-Admin-Players API: RacketManager-admin-player class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Player
 */

namespace Racketmanager\Admin;

use Racketmanager\Admin\Controllers\Player_Admin_Controller;
use Racketmanager\Admin\Controllers\Player_Errors_Admin_Controller;
use Racketmanager\Admin\Controllers\Player_Requests_Admin_Controller;
use Racketmanager\Admin\Controllers\Players_Admin_Controller;
use Racketmanager\Admin\View_Models\Player_Errors_Page_View_Model;
use Racketmanager\Admin\View_Models\Player_Page_View_Model;
use Racketmanager\Admin\View_Models\Player_Requests_Page_View_Model;
use Racketmanager\Admin\View_Models\Players_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Container\Simple_Container;

/**
 * RacketManager player administration functions
 * Class to implement RacketManager Administration Players panel
 */
final readonly class Admin_Player {
    use Completes_Admin_Requests;

    public function __construct(
        private Simple_Container $container,
        private Admin_Message_Service $message_service,
        Admin_Action_Adapter $actions,
        Admin_Screen_Presenter $screens,
    ) {
        $this->actions = $actions;
        $this->screens = $screens;
    }

    /**
     * Function to handle administration players displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        $view_map = array(
            'player'   => array( $this, 'display_player_page' ),
            'errors'   => array( $this, 'display_errors_page' ),
            'requests' => array( $this, 'display_requests_page' ),
            'players'  => array( $this, 'display_players_page' ),
        );

        try {
            $callback = $view_map[ $view ] ?? array( $this, 'display_players_section' );
            call_user_func( $callback );
        } catch ( Invalid_Status_Exception | Player_Not_Found_Exception $e ) {
            $this->message_service->set_message( $e->getMessage(), true );
            $this->message_service->show_message();
        }
    }

    /**
     * Display player page
     */
    public function display_player_page(): void {
        $flash      = $this->screens->apply_flash();
        $flash_data = $flash['flash_data'] ?? array();

        /** @var Player_Admin_Controller $controller */
        $controller = $this->container->get( 'player_admin_controller' );
        $this->complete_request(
            $controller->player_page( $_GET, $_POST, $flash_data ),
            Player_Page_View_Model::class,
            'admin/players/show-player'
        );
    }

    /**
     * Display player errors page
     */
    public function display_errors_page(): void {
        $flash      = $this->screens->apply_flash();
        $flash_data = $flash['flash_data'] ?? array();

        /** @var Player_Errors_Admin_Controller $controller */
        $controller = $this->container->get( 'player_errors_admin_controller' );
        $this->complete_request(
            $controller->player_errors_page( $_GET, $_POST, $flash_data ),
            Player_Errors_Page_View_Model::class,
            'admin/players/show-errors'
        );
    }

    /**
     * Display player requests page
     */
    public function display_requests_page(): void {
        $flash      = $this->screens->apply_flash();
        $flash_data = $flash['flash_data'] ?? array();

        /** @var Player_Requests_Admin_Controller $controller */
        $controller = $this->container->get( 'player_requests_admin_controller' );
        $this->complete_request(
            $controller->player_requests_page( $_GET, $_POST, $flash_data ),
            Player_Requests_Page_View_Model::class,
            'admin/players/show-requests'
        );
    }

    /**
     * Display players page
     */
    public function display_players_page(): void {
        $flash      = $this->screens->apply_flash();
        $flash_data = $flash['flash_data'] ?? array();

        /** @var Players_Admin_Controller $controller */
        $controller = $this->container->get( 'players_admin_controller' );
        $this->complete_request(
            $controller->players_page( $_GET, $_POST, $flash_data ),
            Players_Page_View_Model::class,
            'admin/players/show-players'
        );
    }

    /**
     * Display players default section
     */
    public function display_players_section(): void {
        $this->display_errors_page();
    }
}
