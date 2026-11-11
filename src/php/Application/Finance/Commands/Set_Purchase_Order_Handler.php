<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Finance\Commands;

use Racketmanager\Application\Ports\Finance_Service_Interface;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;

/**
 * Handler for setting a purchase order on an invoice.
 */
readonly class Set_Purchase_Order_Handler {
    /**
     * Constructor.
     *
     * @param Finance_Service_Interface $finance_service finqnce interface.
     */
    public function __construct(
        private Finance_Service_Interface $finance_service
    ) {
    }

    /**
     * Handle updating invoice purchase order.
     *
     * @param Set_Purchase_Order_Command $command Set purchase order command.
     * @return bool
     * @throws Invoice_Not_Found_Exception Invoice not found exception.
     * @throws Invalid_Argument_Exception Invalid argument exception.
     */
    public function handle( Set_Purchase_Order_Command $command ): bool {
        return $this->finance_service->set_purchase_order_for_invoice(
            $command->invoice_id,
            $command->purchase_order
        );
    }
}
