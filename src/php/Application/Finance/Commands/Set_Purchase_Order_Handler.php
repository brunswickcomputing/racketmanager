<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Finance\Commands;

use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Services\Finance_Service;

/**
 * Handler for setting a purchase order on an invoice.
 */
class Set_Purchase_Order_Handler {
    public function __construct(
        private readonly Finance_Service $finance_service
    ) {
    }

    /**
     * Handle updating invoice purchase order.
     *
     * @param Set_Purchase_Order_Command $command
     * @return bool
     * @throws Invoice_Not_Found_Exception
     * @throws Invalid_Argument_Exception
     */
    public function handle( Set_Purchase_Order_Command $command ): bool {
        return $this->finance_service->set_purchase_order_for_invoice(
            $command->invoice_id,
            $command->purchase_order
        );
    }
}
