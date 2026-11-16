<?php
declare( strict_types=1 );

/**
 * Import Action Dispatcher Interface
 *
 * @package RacketManager
 * @subpackage Application/Admin/Ports
 */

namespace Racketmanager\Application\Admin\Ports;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;

/**
 * Contract for dispatching and handling import mutation requests.
 */
interface Import_Action_Dispatcher_Interface {

    /**
     * Handle an admin import request.
     *
     * @param array<string, mixed>      $query Typically $_GET.
     * @param array<string, mixed>      $post  Typically $_POST.
     * @param array<string, mixed>|null $files Typically $_FILES.
     * @return Action_Result_DTO
     */
    public function handle( array $query, array $post, ?array $files = null ): Action_Result_DTO;
}
