<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Finance\Commands;

use Racketmanager\Application\Ports\Finance_Service_Interface;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;

/**
 * Handler for performing an action on an invoice.
 */
readonly class Action_Invoice_Handler {
    /**
     * Constructor.
     *
     * @param Finance_Service_Interface $finance_service finance service.
     */
    public function __construct(
        private Finance_Service_Interface $finance_service
    ) {
    }

    /**
     * Handle invoice action.
     *
     * @param Action_Invoice_Command $command Action invoice command.
     * @return string
     * @throws Invoice_Not_Found_Exception Invoice not found exception.
     * @throws Invalid_Argument_Exception Invalid argument exception.
     */
    public function handle( Action_Invoice_Command $command ): string {
        return $this->finance_service->action_invoice(
            $command->invoice_id,
            $command->action
        );
    }
}
