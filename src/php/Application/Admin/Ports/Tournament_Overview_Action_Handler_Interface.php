<?php
declare( strict_types=1 );
/**
 * Tournament overview action handler port
 *
 * @package RacketManager
 * @subpackage Application/Admin/Ports
 */

namespace Racketmanager\Application\Admin\Ports;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Overview\Tournament_Overview_Action_Request_DTO;

interface Tournament_Overview_Action_Handler_Interface {
    /**
     * Contact teams action.
     *
     * @param Tournament_Overview_Action_Request_DTO $dto Request DTO.
     * @return Action_Result_DTO
     */
    public function contact_teams( Tournament_Overview_Action_Request_DTO $dto ): Action_Result_DTO;
}
