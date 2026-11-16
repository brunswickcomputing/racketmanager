<?php
declare( strict_types=1 );
/**
 * Results Checker Action Dispatcher Interface
 *
 * @package RacketManager
 * @subpackage Application/Admin/Ports
 */

namespace Racketmanager\Application\Admin\Ports;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;

interface Results_Checker_Action_Dispatcher_Interface {
    /**
     * Handle results checker request.
     *
     * @param array<string, mixed> $post Typically $_POST.
     * @return Action_Result_DTO
     */
    public function handle( array $post ): Action_Result_DTO;
}
