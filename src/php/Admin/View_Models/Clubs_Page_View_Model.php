<?php
declare( strict_types=1 );
/**
 * Clubs Page View Model
 *
 * View model for admin clubs list page.
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class Clubs_Page_View_Model {

    /**
     * @param array $clubs
     */
    public function __construct(
        public array $clubs,
    ) {
    }
}
