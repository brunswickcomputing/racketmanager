<?php
declare( strict_types=1 );
/**
 * RacketManager-Admin API: RacketManager-admin-event class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Event
 */

namespace Racketmanager\Admin;

use Racketmanager\Admin\Controllers\Tournament_Event_Config_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Event_Config_Page_View_Model;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Container\Simple_Container;

/**
 * Screen bridge for tournament event configuration administrative displays.
 */
final readonly class Admin_Event {
    use Completes_Admin_Requests;

    /**
     * Constructor.
     *
     * @param Simple_Container       $container Dependency injection container.
     * @param Admin_Action_Adapter   $actions   Action adapter for PRG redirects.
     * @param Admin_Screen_Presenter $screens   Screen presenter for view rendering.
     */
    public function __construct(
        private Simple_Container $container,
        Admin_Action_Adapter $actions,
        Admin_Screen_Presenter $screens,
    ) {
        $this->actions = $actions;
        $this->screens = $screens;
    }

    /**
     * Handle admin event displays.
     *
     * @param string|null $view Current view.
     *
     * @return void
     */
    public function handle_display( ?string $view = null ): void {
        unset( $view );
        $this->display_config_page();
    }

    /**
     * Display event configuration page.
     *
     * @return void
     */
    public function display_config_page(): void {
        $flash      = $this->screens->apply_flash();
        $flash_data = is_array( $flash['flash_data'] ?? null ) ? $flash['flash_data'] : array();

        /** @var Tournament_Event_Config_Admin_Controller $controller */
        $controller = $this->container->get( Tournament_Event_Config_Admin_Controller::class );

        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        $result = $controller->handle( $_GET, $_POST, $flash_data );
        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

        $this->complete_request(
            $result,
            Tournament_Event_Config_Page_View_Model::class,
            'admin/includes/event-config',
            strict: false
        );
    }
}
