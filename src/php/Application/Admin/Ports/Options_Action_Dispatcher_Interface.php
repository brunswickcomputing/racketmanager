<?php
declare( strict_types=1 );

/**
 * Options action dispatcher interface
 *
 * @package RacketManager
 * @subpackage Application/Admin/Ports
 */

namespace Racketmanager\Application\Admin\Ports;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;

interface Options_Action_Dispatcher_Interface {

    /**
     * Handle options request.
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $post  Typically $_POST.
     * @return Action_Result_DTO
     */
    public function handle( array $query, array $post ): Action_Result_DTO;
}
