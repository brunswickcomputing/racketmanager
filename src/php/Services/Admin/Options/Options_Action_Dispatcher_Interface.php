<?php
/**
 * Options action dispatcher interface
 *
 * @package RacketManager
 * @subpackage Services/Admin/Options
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Options;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;

interface Options_Action_Dispatcher_Interface {

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO;
}
