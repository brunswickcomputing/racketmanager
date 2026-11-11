<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Finance\Commands;

use Racketmanager\Application\Ports\Finance_Service_Interface;
use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Exceptions\Invoice_Not_Created_Exception;

/**
 * Handler for generating and sending invoices for a charge.
 */
readonly class Generate_Charge_Invoices_Handler {
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
     * Handle generating and sending invoices for a charge.
     *
     * @param Generate_Charge_Invoices_Command $command Generate charge invoices command.
     * @return bool
     * @throws Charge_Not_Found_Exception Charge not found exception.
     * @throws Invoice_Not_Created_Exception Invoice not created exception.
     */
    public function handle( Generate_Charge_Invoices_Command $command ): bool {
        return $this->finance_service->send_invoices_for_charge(
            $command->charge_id,
            $command->send_email
        );
    }
}
