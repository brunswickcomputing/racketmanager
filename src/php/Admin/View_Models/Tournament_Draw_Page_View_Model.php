<?php
/**
 * Tournament draw admin page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

readonly final class Tournament_Draw_Page_View_Model {

    public function __construct(
        public object $tournament,
        public object $league,
        public string $tab,
    ) {
    }

    /**
     * Export variables expected by templates/admin/tournament/draw.php (and included partials).
     *
     * @return array<string,mixed>
     */
    public function to_template_vars(): array {
        return array(
            'tournament' => $this->tournament,
            'league'     => $this->league,
            'tab'        => $this->tab,
        );
    }
}
