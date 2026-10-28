<?php
declare( strict_types=1 );
/**
 * Club Roles Page View Model
 *
 * View model for admin club roles page.
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

use Racketmanager\Domain\Club\Club;

final readonly class Club_Roles_Page_View_Model {

    /**
     * @param Club  $club
     * @param array $roles
     * @param array $club_players
     * @param int   $club_id
     */
    public function __construct(
        public Club $club,
        public array $roles,
        public array $club_players,
        public int $club_id,
    ) {
    }
}
