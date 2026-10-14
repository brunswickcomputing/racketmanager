<?php
/**
 * Seasons admin page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

use Racketmanager\Domain\Season;
use Racketmanager\Admin\View_Models\Error_Bag;

readonly final class Seasons_Page_View_Model {

    /**
     * @param Season[] $seasons
     */
    public function __construct(
        public array $seasons,
        public Error_Bag $errors = new Error_Bag(),
        public ?string $season_name = null,
    ) {
    }

    /**
     * Export variables expected by templates/admin/show-seasons.php
     *
     * @return array<string, mixed>
     */
    public function to_template_vars(): array {
        return array(
            'seasons' => $this->seasons,
        );
    }
}
