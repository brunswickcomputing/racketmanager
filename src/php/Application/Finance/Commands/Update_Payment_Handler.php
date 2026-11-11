<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Finance\Commands;

use Racketmanager\Application\Ports\Finance_Service_Interface;

/**
 * Handler for updating payment status on an invoice.
 */
readonly class Update_Payment_Handler {
    /**
     * Constructor.
     *
     * @param Finance_Service_Interface $finance_service Finance service port.
     */
    public function __construct(
        private Finance_Service_Interface $finance_service
    ) {
    }

    /**
     * Handle updating payment status.
     *
     * @param Update_Payment_Command $command Update payment command.
     * @return void
     */
    public function handle( Update_Payment_Command $command ): void {
        $this->finance_service->update_payment( $command->reference, $command->status );
    }
}
