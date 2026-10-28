<?php
declare( strict_types=1 );
/**
 * Club Page View Model
 *
 * View model for admin single club add/edit page.
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

use Racketmanager\Domain\Club\Club;

final readonly class Club_Page_View_Model {

    /**
     * @param Club|null   $club
     * @param bool        $edit
     * @param string      $form_title
     * @param string      $form_action
     * @param int|null    $club_id
     */
    public function __construct(
        public ?Club $club,
        public bool $edit,
        public string $form_title,
        public string $form_action,
        public ?int $club_id = null,
    ) {
    }
}
