<?php
declare( strict_types=1 );
/**
 * RacketManager-Admin API: RacketManager-upgrade class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Upgrade
 */

namespace Racketmanager\Admin;

use Racketmanager\Admin\Controllers\Upgrade_Admin_Controller;
use Racketmanager\Admin\View_Models\Upgrade_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Container\Simple_Container;

/**
 * RacketManager Upgrade functions
 * Class to implement RacketManager Admin Upgrade
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Upgrade
 */
final readonly class Admin_Upgrade {
    use Completes_Admin_Requests;

    /**
     * Constructor.
     *
     * @param Simple_Container       $container       Service container (lazy controller lookup).
     * @param Admin_Message_Service  $message_service Message service.
     * @param Admin_Action_Adapter   $actions         POST delivery (PRG).
     * @param Admin_Screen_Presenter $screens         GET delivery.
     */
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
     * Function to handle administration displays.
     *
     * @return void
     */
    public function handle_display(): void {
        $this->display_upgrade_page();
    }

    /**
     * Display upgrade page, or run the upgrade and redirect (Post-Redirect-Get).
     *
     * @return void
     */
    public function display_upgrade_page(): void {
        $this->screens->apply_flash();

        try {
            /** @var Upgrade_Admin_Controller $controller */
            $controller = $this->container->get( Upgrade_Admin_Controller::class );

            // The controller verifies the nonce and capability for mutations.
            // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            $result = $controller->upgrade_page( $_GET, $_POST );
            // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

            $this->complete_request(
                $result,
                Upgrade_Page_View_Model::class,
                'admin/show-upgrade'
            );
        } catch ( Invalid_Status_Exception $e ) {
            $this->message_service->set_message( $e->getMessage(), true );
            $this->message_service->show_message();
        }
    }
}
