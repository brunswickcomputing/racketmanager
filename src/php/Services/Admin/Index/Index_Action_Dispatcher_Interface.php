<?php
/**
 * Index action dispatcher interface
 *
 * @package RacketManager
 * @subpackage Services/Admin/Index
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Index;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;

interface Index_Action_Dispatcher_Interface {

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO;
}
