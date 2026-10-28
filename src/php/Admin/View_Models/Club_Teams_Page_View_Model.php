<?php
declare( strict_types=1 );
/**
 * Club Teams Page View Model
 *
 * View model for admin club teams page.
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

use Racketmanager\Domain\Club\Club;

final readonly class Club_Teams_Page_View_Model {

    /**
     * @param Club   $club
     * @param array  $teams
     * @param int    $club_id
     */
    public function __construct(
        public Club $club,
        public array $teams,
        public int $club_id,
    ) {
    }
}
