<?php
/**
 * RacketManager-Admin API: RacketManager-season class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Season
 */

declare( strict_types=1 );

namespace Racketmanager\Admin;

use Racketmanager\Admin\Controllers\Seasons_Admin_Controller;
use Racketmanager\Admin\View_Models\Seasons_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Container\Simple_Container;

/**
 * RacketManager Season Admin functions
 * Class to implement RacketManager Admin Season
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Season
 */
final readonly class Admin_Season {
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
     * Function to handle administration season displays.
     *
     * @param string|null $view Sub-view slug.
     *
     * @return void
     */
    public function handle_display( ?string $view = null ): void {
        unset( $view );
        $this->display_seasons_page();
    }

    /**
     * Display seasons page, or process a season mutation and redirect (Post-Redirect-Get).
     *
     * @return void
     */
    public function display_seasons_page(): void {
        $flash = $this->screens->apply_flash();

        try {
            /** @var Seasons_Admin_Controller $controller */
            $controller = $this->container->get( Seasons_Admin_Controller::class );

            // The season dispatcher verifies the nonce and capability for mutations.
            // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            $result = $controller->seasons_page( $_GET, $_POST, $flash['flash_data'] ?? array() );
            // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

            $this->complete_request(
                $result,
                Seasons_Page_View_Model::class,
                'admin/show-seasons'
            );
        } catch ( Invalid_Status_Exception $e ) {
            $this->message_service->set_message( $e->getMessage(), true );
            $this->message_service->show_message();
        }
    }
}
