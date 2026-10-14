<?php
/**
 * Season modify page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

use Racketmanager\Domain\Competition\Competition;

readonly final class Season_Modify_Page_View_Model {

    /**
     * @param Competition $competition
     * @param string|int|null $season
     * @param object|null $current_season
     * @param array $seasons
     * @param array $clubs
     * @param bool $edit_mode
     * @param Error_Bag $errors
     */
    public function __construct(
        public Competition $competition,
        public string|int|null $season,
        public ?object $current_season,
        public array $seasons,
        public array $clubs,
        public bool $edit_mode,
        public Error_Bag $errors = new Error_Bag(),
    ) {
    }

    /**
     * Export template variables for legacy template compatibility.
     *
     * @return array<string, mixed>
     */
    public function to_template_vars(): array {
        return array(
            'competition'    => $this->competition,
            'season'         => $this->season,
            'current_season' => $this->current_season,
            'seasons'        => $this->seasons,
            'clubs'          => $this->clubs,
            'edit_mode'      => $this->edit_mode,
            'errors'         => $this->errors,
        );
    }
}
