<?php
/**
 * Players list page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

final readonly class Players_Page_View_Model {

    /**
     * @param array<object> $players
     */
    public function __construct(
        public array $players,
        public ?object $player = null,
        public string $racketmanager_tab = 'players',
        public Error_Bag $errors = new Error_Bag(),
        public bool $form_valid = true,
        public string $tab_title = 'Players',
    ) {
    }
}
