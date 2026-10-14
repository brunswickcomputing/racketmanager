<?php
/**
 * Player errors page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

final readonly class Player_Errors_Page_View_Model {

    /**
     * @param array<object> $player_errors
     */
    public function __construct(
        public array $player_errors,
        public ?string $status = null,
        public string $racketmanager_tab = 'errors',
        public Error_Bag $errors = new Error_Bag(),
    ) {
    }
}
