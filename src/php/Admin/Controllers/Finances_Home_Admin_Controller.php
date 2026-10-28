<?php
declare( strict_types=1 );
/**
 * Finances Home Admin Controller
 *
 * Handles default finances screen entry point.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\View_Models\Finances_Charges_Page_View_Model;

final readonly class Finances_Home_Admin_Controller {

    public function __construct(
        private Finances_Charges_Admin_Controller $charges_controller,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     *
     * @return array{view_model?: Finances_Charges_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string, flash_data?: array<string, mixed>}
     */
    public function home_page( array $query, array $post ): array {
        return $this->charges_controller->charges_page( $query, $post );
    }
}
