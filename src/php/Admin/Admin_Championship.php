<?php
declare( strict_types=1 );
/**
 * RacketManager-Admin API: Admin_Championship class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Championship
 */

namespace Racketmanager\Admin;

use Racketmanager\Admin\Controllers\Championship_Admin_Controller;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Container\Simple_Container;

/**
 * Screen bridge for managing tournament and cup championship finals and results.
 */
final readonly class Admin_Championship {
    use Completes_Admin_Requests;

    /**
     * Constructor.
     *
     * @param Simple_Container       $container       Dependency injection container.
     * @param Admin_Message_Service  $message_service Admin messaging service.
     * @param Admin_Action_Adapter   $actions         Action adapter for PRG redirects.
     * @param Admin_Screen_Presenter $screens         Screen presenter for view rendering.
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
     * Function to handle administration championship displays.
     *
     * @param string|null $view Current view.
     *
     * @return void
     */
    public function handle_display( ?string $view = null ): void {
        unset( $view );
        $this->handle_championship_admin_page();
    }

    /**
     * Handle administration panel.
     *
     * @param object|null $league Target league entity or descriptor.
     *
     * @return string Active tab name.
     */
    public function handle_championship_admin_page( ?object $league = null ): string {
        try {
            /** @var Championship_Admin_Controller $controller */
            $controller = $this->container->get( Championship_Admin_Controller::class );

            // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            $result = $controller->handle_championship_page( $league, $_GET, $_POST );
            // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

            if ( ! empty( $result['redirect'] ) ) {
                $this->actions->deliver_redirect( $result );
                return $result['tab'] ?? 'finalResults';
            }

            if ( ! empty( $result['message'] ) ) {
                $this->message_service->set_message( $result['message'], $result['message_type'] ?? false );
                $this->message_service->show_message();
            }

            return $result['tab'] ?? 'finalResults';
        } catch ( Invalid_Status_Exception $e ) {
            $this->message_service->set_message( $e->getMessage(), true );
            $this->message_service->show_message();
            return 'finalResults';
        }
    }
}
