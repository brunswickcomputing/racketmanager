<?php
/**
 * Player edit page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

final readonly class Player_Page_View_Model {

    public function __construct(
        public object $player,
        public ?int $player_id = null,
        public ?object $club = null,
        public ?int $club_id = null,
        public ?string $page_referrer = null,
        public Error_Bag $errors = new Error_Bag(),
        public bool $form_valid = true,
    ) {
    }
}
