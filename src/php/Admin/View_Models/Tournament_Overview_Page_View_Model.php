<?php
/**
 * Tournament overview admin page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

readonly final class Tournament_Overview_Page_View_Model {

    /**
     * @param object $tournament
     * @param object $overview
     * @param array $events
     * @param string $tab
     * @param array $confirmed_entries
     * @param array $unpaid_entries
     * @param array $pending_entries
     * @param array $withdrawn_entries
     */
    public function __construct(
        public object $tournament,
        public object $overview,
        public array $events,
        public string $tab,
        public array $confirmed_entries,
        public array $unpaid_entries,
        public array $pending_entries,
        public array $withdrawn_entries,
    ) {
    }

    /**
     * Export variables expected by templates/admin/show-tournament.php (and included partials).
     *
     * @return array<string,mixed>
     */
    public function to_template_vars(): array {
        return array(
            'tournament'        => $this->tournament,
            'overview'          => $this->overview,
            'events'            => $this->events,
            'tab'               => $this->tab,
            'confirmed_entries' => $this->confirmed_entries,
            'unpaid_entries'    => $this->unpaid_entries,
            'pending_entries'   => $this->pending_entries,
            'withdrawn_entries' => $this->withdrawn_entries,
        );
    }
}
