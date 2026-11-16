<?php
declare( strict_types=1 );
/**
 * RacketManager-Admin API: Admin_Result class
 *
 * @package RacketManager
 * @subpackage Admin
 */

namespace Racketmanager\Admin;

use Racketmanager\Admin\Controllers\Results_Admin_Controller;
use Racketmanager\Admin\View_Models\Results_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Container\Simple_Container;

/**
 * Screen bridge for the results administration screen.
 */
final readonly class Admin_Result {
    use Completes_Admin_Requests;

    /**
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
     * Function to handle administration results displays.
     *
     * @param string|null $view Sub-view slug.
     *
     * @return void
     */
    public function handle_display( ?string $view = null ): void {
        unset( $view );
        $this->display_results_page();
    }

    /**
     * Display results page, or process results checker mutation and redirect (Post-Redirect-Get).
     *
     * @return void
     */
    public function display_results_page(): void {
        $flash      = $this->screens->apply_flash();
        $flash_data = is_array( $flash['flash_data'] ?? null ) ? $flash['flash_data'] : array();

        try {
            /** @var Results_Admin_Controller $controller */
            $controller = $this->container->get( Results_Admin_Controller::class );

            // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            $result = $controller->results_page( $_GET, $_POST, $flash_data );
            // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

            $this->complete_request(
                $result,
                Results_Page_View_Model::class,
                'admin/show-results'
            );
        } catch ( Invalid_Status_Exception $e ) {
            $this->message_service->set_message( $e->getMessage(), true );
            $this->message_service->show_message();
        }
    }
}
