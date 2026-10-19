<?php
declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

final readonly class Admin_League_Schedule_Page_View_Model {

    /**
     * @param object $competition
     * @param string $season
     * @param int $competition_id
     * @param Admin_League_Schedule_Event_View_Model[] $events
     */
    public function __construct(
        public object $competition,
        public string $season,
        public int $competition_id,
        public array $events = array(),
    ) {
    }

    /**
     * Export variables expected by templates/admin/league/show-schedule.php.
     *
     * @return array<string,mixed>
     */
    public function to_template_vars(): array {
        return array(
            'competition'    => $this->competition,
            'season'         => $this->season,
            'competition_id' => $this->competition_id,
            'events'         => $this->events,
        );
    }
}
