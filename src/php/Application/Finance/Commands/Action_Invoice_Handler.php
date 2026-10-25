<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Finance\Commands;

use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Services\Finance_Service;

/**
 * Handler for performing an action on an invoice.
 */
class Action_Invoice_Handler {
    public function __construct(
        private readonly Finance_Service $finance_service
    ) {
    }

    /**
     * Handle invoice action.
     *
     * @param Action_Invoice_Command $command
     * @return string
     * @throws Invoice_Not_Found_Exception
     * @throws Invalid_Argument_Exception
     */
    public function handle( Action_Invoice_Command $command ): string {
        return $this->finance_service->action_invoice(
            $command->invoice_id,
            $command->action
        );
    }
}
