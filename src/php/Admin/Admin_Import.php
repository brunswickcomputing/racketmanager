<?php
declare( strict_types=1 );
/**
 * RacketManager-Admin API: RacketManager-import class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Import
 */

namespace Racketmanager\Admin;

use Racketmanager\Admin\Controllers\Import_Admin_Controller;
use Racketmanager\Admin\View_Models\Import_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Container\Simple_Container;

/**
 * Screen bridge for importing leagues, fixtures, players, and registrations.
 */
final readonly class Admin_Import {
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
	 * Function to handle administration import displays.
	 *
	 * @param string|null $view Current view.
	 *
	 * @return void
	 */
	public function handle_display( ?string $view = null ): void {
		unset( $view );
		$this->display_import_page();
	}

	/**
	 * Display import Page.
	 */
	public function display_import_page(): void {
		$flash      = $this->screens->apply_flash();
		$flash_data = $flash['flash_data'] ?? array();

		try {
			/** @var Import_Admin_Controller $controller */
			$controller = $this->container->get( Import_Admin_Controller::class );

			// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
			$result = $controller->import_page( $_GET, $_POST, $_FILES, $flash_data );
			// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

			$this->complete_request(
				$result,
				Import_Page_View_Model::class,
				'admin/tools/import'
			);
		} catch ( Invalid_Status_Exception $e ) {
			$this->message_service->set_message( $e->getMessage(), true );
			$this->message_service->show_message();
		}
	}
}
