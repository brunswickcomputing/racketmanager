<?php
declare( strict_types=1 );
/**
 * RacketManager-Admin API: RacketManager-admin-finances class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Finances
 */

namespace Racketmanager\Admin;

use Racketmanager\Admin\Controllers\Finances_Charge_Admin_Controller;
use Racketmanager\Admin\Controllers\Finances_Charges_Admin_Controller;
use Racketmanager\Admin\Controllers\Finances_Home_Admin_Controller;
use Racketmanager\Admin\Controllers\Finances_Invoice_Admin_Controller;
use Racketmanager\Admin\Controllers\Finances_Invoices_Admin_Controller;
use Racketmanager\Admin\View_Models\Finances_Charge_Page_View_Model;
use Racketmanager\Admin\View_Models\Finances_Charges_Page_View_Model;
use Racketmanager\Admin\View_Models\Finances_Invoice_Page_View_Model;
use Racketmanager\Admin\View_Models\Finances_Invoices_Page_View_Model;
use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Container\Simple_Container;

/**
 * RacketManager finances administration functions
 * Class to implement RacketManager Administration Finances panel
 */
final readonly class Admin_Finances {
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
     * Function to handle administration finances displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        $view_map = array(
            'charges'         => array( $this, 'display_charges_page' ),
            'club-invoices'   => array( $this, 'display_club_invoices_page' ),
            'player-invoices' => array( $this, 'display_player_invoices_page' ),
            'invoice'         => array( $this, 'display_invoice_page' ),
            'charge'          => array( $this, 'display_charge_page' ),
        );

        try {
            $callback = $view_map[ $view ] ?? array( $this, 'display_finances_page' );
            call_user_func( $callback );
        } catch ( Invalid_Status_Exception | Charge_Not_Found_Exception | Invoice_Not_Found_Exception $e ) {
            $this->message_service->set_message( $e->getMessage(), true );
            $this->message_service->show_message();
        }
    }

    /**
     * Display finances page
     */
    public function display_finances_page(): void {
        $this->screens->apply_flash();

        /** @var Finances_Home_Admin_Controller $controller */
        $controller = $this->container->get( 'finances_home_admin_controller' );
        $this->complete_request(
            $controller->home_page( $_GET, $_POST ),
            Finances_Charges_Page_View_Model::class,
            'admin/finances/show-charges'
        );
    }

    /**
     * Display charges page
     */
    public function display_charges_page(): void {
        $this->screens->apply_flash();

        /** @var Finances_Charges_Admin_Controller $controller */
        $controller = $this->container->get( 'finances_charges_admin_controller' );
        $this->complete_request(
            $controller->charges_page( $_GET, $_POST ),
            Finances_Charges_Page_View_Model::class,
            'admin/finances/show-charges'
        );
    }

    /**
     * Display club invoices page
     */
    public function display_club_invoices_page(): void {
        $this->render_invoices_page( 'club-invoices' );
    }

    /**
     * Display player invoices page
     */
    public function display_player_invoices_page(): void {
        $this->render_invoices_page( 'player-invoices' );
    }

    /**
     * Shared render logic for club and player invoices pages
     *
     * @param string $tab 'club-invoices'|'player-invoices'
     */
    private function render_invoices_page( string $tab ): void {
        $this->screens->apply_flash();

        /** @var Finances_Invoices_Admin_Controller $controller */
        $controller = $this->container->get( 'finances_invoices_admin_controller' );
        $this->complete_request(
            $controller->invoices_page( $_GET, $_POST, $tab ),
            Finances_Invoices_Page_View_Model::class,
            'admin/finances/show-invoices'
        );
    }

    /**
     * Display charge edit/add page
     */
    public function display_charge_page(): void {
        $flash      = $this->screens->apply_flash();
        $flash_data = $flash['flash_data'] ?? array();

        /** @var Finances_Charge_Admin_Controller $controller */
        $controller = $this->container->get( 'finances_charge_admin_controller' );
        $this->complete_request(
            $controller->charge_page( $_GET, $_POST, $flash_data ),
            Finances_Charge_Page_View_Model::class,
            'admin/finances/charge'
        );
    }

    /**
     * Display invoice page
     */
    public function display_invoice_page(): void {
        $flash      = $this->screens->apply_flash();
        $flash_data = $flash['flash_data'] ?? array();

        /** @var Finances_Invoice_Admin_Controller $controller */
        $controller = $this->container->get( 'finances_invoice_admin_controller' );
        $this->complete_request(
            $controller->invoice_page( $_GET, $_POST, $flash_data ),
            Finances_Invoice_Page_View_Model::class,
            'admin/finances/invoice'
        );
    }
}
