<?php

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Result;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;

interface Results_Checker_Action_Dispatcher_Interface {
    public function handle( array $post ): Action_Result_DTO;
}
