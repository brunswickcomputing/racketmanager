<?php
/**
 * Tournament competition config page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

use Racketmanager\Domain\Competition;

readonly final class Tournament_Competition_Config_Page_View_Model {

    /**
     * @param Competition $competition
     * @param object|null $tournament
     * @param array $rules_options
     * @param array $clubs
     * @param string $tab
     */
    public function __construct(
        public Competition $competition,
        public ?object $tournament,
        public array $rules_options,
        public array $clubs,
        public string $tab = 'general',
    ) {
    }

    /**
     * Export variables expected by templates/admin/includes/competition-config.php.
     *
     * @return array<string,mixed>
     */
    public function to_template_vars(): array {
        return array(
            'competition'   => $this->competition,
            'tournament'    => $this->tournament,
            'rules_options' => $this->rules_options,
            'clubs'         => $this->clubs,
            'tab'           => $this->tab,
            // Legacy variables that were previously set in display_config_page
            'forwin'           => 0,
            'fordraw'          => 0,
            'forloss'          => 0,
            'forwin_overtime'  => 0,
            'forloss_overtime' => 0,
            'is_invalid'       => false,
        );
    }
}
