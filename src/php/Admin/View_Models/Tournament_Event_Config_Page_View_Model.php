<?php
/**
 * Tournament event config page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;

readonly final class Tournament_Event_Config_Page_View_Model {

    /**
     * @param Competition $competition
     * @param Event|object|null $event
     * @param object|null $tournament
     * @param bool $new_event
     * @param array $leagues
     */
    public function __construct(
        public Competition $competition,
        public mixed $event,
        public ?object $tournament,
        public bool $new_event = false,
        public Error_Bag $errors = new Error_Bag(),
        public string $tab = 'general',
        public array $leagues = array(),
    ) {
    }

    /**
     * Export variables expected by templates/admin/includes/event-config.php.
     *
     * @return array<string,mixed>
     */
    public function to_template_vars(): array {
        return array(
            'competition' => $this->competition,
            'event'       => $this->event,
            'tournament'  => $this->tournament,
            'new_event'   => $this->new_event,
            'errors'      => $this->errors,
            'tab'         => $this->tab,
            'leagues'     => $this->leagues,
        );
    }
}
