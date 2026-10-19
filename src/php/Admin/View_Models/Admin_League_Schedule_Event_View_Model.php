<?php
declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

final readonly class Admin_League_Schedule_Event_View_Model {
    public function __construct(
        public int $id,
        public string $name,
        public string $season,
        public string $event_link,
        public bool $can_send_fixtures = false,
    ) {
    }
}
