<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Finance\Commands;

use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Exceptions\Invoice_Not_Created_Exception;
use Racketmanager\Services\Finance_Service;

/**
 * Handler for generating and sending invoices for a charge.
 */
class Generate_Charge_Invoices_Handler {
    public function __construct(
        private readonly Finance_Service $finance_service
    ) {
    }

    /**
     * Handle generating and sending invoices for a charge.
     *
     * @param Generate_Charge_Invoices_Command $command
     * @return bool
     * @throws Charge_Not_Found_Exception
     * @throws Invoice_Not_Created_Exception
     */
    public function handle( Generate_Charge_Invoices_Command $command ): bool {
        return $this->finance_service->send_invoices_for_charge(
            $command->charge_id,
            $command->send_email
        );
    }
}
