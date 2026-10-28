<?php
declare( strict_types=1 );
/**
 * RacketManager-Admin API: RacketManager-admin-competition class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Competition
 */

namespace Racketmanager\Admin;

use Racketmanager\Admin\Controllers\Competition_Config_Admin_Controller;
use Racketmanager\Admin\Controllers\Competition_Season_Modify_Admin_Controller;
use Racketmanager\Admin\View_Models\Season_Modify_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Competition_Config_Page_View_Model;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Container\Simple_Container;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration Competition panel
 */
final readonly class Admin_Competition {
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
     * Function to handle administration competition displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        $view_map = array(
            'config' => array( $this, 'display_config_page' ),
            'modify' => array( $this, 'display_season_modify_page' ),
        );

        try {
            $callback = $view_map[ $view ] ?? array( $this, 'display_config_page' );
            $callback();
        } catch ( Competition_Not_Found_Exception | Tournament_Not_Found_Exception | Season_Not_Found_Exception | Invalid_Status_Exception $e ) {
            $this->message_service->set_message( $e->getMessage(), true );
            $this->message_service->show_message();
        }
    }

    /**
     * Handle config page function
     *
     * @return void
     */
    public function display_config_page(): void {
        $flash      = $this->screens->apply_flash();
        $flash_data = $flash['flash_data'] ?? array();

        /** @var Competition_Config_Admin_Controller $controller */
        $controller = $this->container->get( 'competition_config_admin_controller' );
        $this->complete_request( $controller->config_page( $_GET, $_POST, $flash_data ), Tournament_Competition_Config_Page_View_Model::class, 'admin/includes/competition-config' );
    }

    /**
     * Display create/edit season page
     *
     * @return void
     */
    public function display_season_modify_page(): void {
        $flash      = $this->screens->apply_flash();
        $flash_data = $flash['flash_data'] ?? array();

        /** @var Competition_Season_Modify_Admin_Controller $controller */
        $controller = $this->container->get( 'competition_season_modify_admin_controller' );
        $this->complete_request( $controller->season_modify_page( $_GET, $_POST, $flash_data ), Season_Modify_Page_View_Model::class, 'admin/includes/season-edit' );
    }
}
