<?php
/**
 * Player requests page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

final readonly class Player_Requests_Page_View_Model {

    /**
     * @param array<object> $player_requests
     * @param array<object> $clubs
     */
    public function __construct(
        public array $player_requests,
        public array $clubs = array(),
        public ?int $club_id = null,
        public string $status = 'outstanding',
        public string $racketmanager_tab = 'requests',
        public Error_Bag $errors = new Error_Bag(),
        public string $tab_title = 'Requests',
    ) {
    }
}
