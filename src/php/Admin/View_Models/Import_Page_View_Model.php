<?php
/**
 * Import Page View Model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

/**
 * View model representing the state for the admin import screen.
 */
readonly final class Import_Page_View_Model {

    /**
     * @param array<object> $clubs
     * @param array<object> $competitions
     * @param Error_Bag     $errors
     */
    public function __construct(
        public array $clubs = array(),
        public array $competitions = array(),
        public Error_Bag $errors = new Error_Bag(),
    ) {
    }
}
