<?php
/**
 * Tournament overview action handler port
 *
 * @package RacketManager
 * @subpackage Services/Admin/Overview
 */

namespace Racketmanager\Services\Admin\Overview;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Overview\Tournament_Overview_Action_Request_DTO;

interface Tournament_Overview_Action_Handler_Interface {
    public function contact_teams( Tournament_Overview_Action_Request_DTO $dto ): Action_Result_DTO;
}
