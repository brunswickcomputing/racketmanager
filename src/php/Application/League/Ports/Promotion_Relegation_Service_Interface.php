<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Ports;

use Racketmanager\Domain\Competition\Event;

interface Promotion_Relegation_Service_Interface {
    /**
     * Promote and relegate teams across leagues in an event.
     *
     * @param Event $event Event entity.
     * @param array $teams Submitted team status data.
     * @param int   $season Target season.
     * @return bool
     */
    public function promote_and_relegate( Event $event, array $teams, int $season ): bool;
}
