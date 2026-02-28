<?php
/**
 * Tournament tournaments list page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

readonly final class Tournament_Tournaments_Page_View_Model {

    /**
     * @param array $tournaments
     * @param string $season_select
     * @param string|int $competition_select
     * @param string $age_group_select
     * @param array $seasons
     * @param array $competitions
     * @param array $age_groups
     */
    public function __construct(
        public array $tournaments,
        public string $season_select,
        public string|int $competition_select,
        public string $age_group_select,
        public array $seasons,
        public array $competitions,
        public array $age_groups,
    ) {
    }

    /**
     * Export variables expected by templates/admin/show-tournaments.php.
     *
     * @return array<string,mixed>
     */
    public function to_template_vars(): array {
        return array(
            'tournaments'        => $this->tournaments,
            'season_select'      => $this->season_select,
            'competition_select' => $this->competition_select,
            'age_group_select'   => $this->age_group_select,
            'seasons'            => $this->seasons,
            'competitions'       => $this->competitions,
            'age_groups'         => $this->age_groups,
        );
    }
}
