<?php
declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

final readonly class Admin_League_Schedule_Page_View_Model {

    /**
     * @param object $competition
     * @param string $season
     * @param int $competition_id
     * @param Admin_League_Schedule_Event_View_Model[] $events
     * @param array{retry?:int,scheduled?:int,conflicts?:array<int,array<string,mixed>>,alerts?:array<int,array<string,mixed>>,resource_pairings?:array<string,array<int,array<string,mixed>>>} $schedule_report Report from the last scheduling run (empty when none).
     */
    public function __construct(
        public object $competition,
        public string $season,
        public int $competition_id,
        public array $events = array(),
        public array $schedule_report = array(),
    ) {
    }

    /**
     * Whether the last scheduling run produced anything worth reporting.
     */
    public function has_schedule_report(): bool {
        return ! empty( $this->schedule_report['conflicts'] )
            || ! empty( $this->schedule_report['alerts'] )
            || ! empty( $this->schedule_report['resource_pairings'] );
    }

    /**
     * Export variables expected by templates/admin/league/show-schedule.php.
     *
     * @return array<string,mixed>
     */
    public function to_template_vars(): array {
        return array(
            'competition'     => $this->competition,
            'season'          => $this->season,
            'competition_id'  => $this->competition_id,
            'events'          => $this->events,
            'schedule_report' => $this->schedule_report,
        );
    }
}
