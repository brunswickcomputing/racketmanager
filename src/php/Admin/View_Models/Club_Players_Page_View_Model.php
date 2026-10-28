<?php
declare( strict_types=1 );
/**
 * Club Players Page View Model
 *
 * View model for admin club players page.
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

use Racketmanager\Domain\Club\Club;

final readonly class Club_Players_Page_View_Model {

    /**
     * @param Club        $club
     * @param array       $players
     * @param string|null $active
     * @param string|null $gender
     * @param int         $club_id
     */
    public function __construct(
        public Club $club,
        public array $players,
        public ?string $active,
        public ?string $gender,
        public int $club_id,
    ) {
    }
}
