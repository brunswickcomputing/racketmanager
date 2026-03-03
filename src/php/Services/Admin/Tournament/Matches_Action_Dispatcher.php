<?php
/**
 * Matches action dispatcher (tournament matches view)
 *
 * Thin adapter over the shared Draw_Action_Dispatcher to keep per-view wiring explicit.
 *
 * @package RacketManager
 * @subpackage Services/Admin/Tournament
 */

namespace Racketmanager\Services\Admin\Tournament;

use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Response_DTO;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;

readonly final class Matches_Action_Dispatcher {
    public function __construct(
        private Draw_Action_Dispatcher $draw_action_dispatcher,
    ) {
    }

    public function handle( Draw_Action_Request_DTO $dto ): Draw_Action_Response_DTO {
        return $this->draw_action_dispatcher->handle( $dto );
    }
}
