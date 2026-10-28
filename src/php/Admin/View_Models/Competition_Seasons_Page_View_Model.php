<?php
declare( strict_types=1 );
/**
 * Competition Seasons Page View Model
 *
 * View model for admin competition seasons page.
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class Competition_Seasons_Page_View_Model {

    /**
     * @param object $competition
     * @param int    $competition_id
     */
    public function __construct(
        public object $competition,
        public int $competition_id,
    ) {
    }
}
