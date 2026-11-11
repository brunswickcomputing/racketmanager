<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Application\General\DTOs\Purchase_Order_Result;
use Racketmanager\Application\Ports\Finance_Service_Interface;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use function __;

/**
 * Handles requests for retrieving purchase order data.
 */
readonly class Get_Purchase_Order_Handler {
    /**
     * Constructor.
     *
     * @param Finance_Service_Interface|null $finance_service finance service.
     */
    public function __construct(
        private ?Finance_Service_Interface $finance_service = null
    ) {}

    /**
     * Handle get purchase order query.
     *
     * @param Get_Purchase_Order_Query $query query.
     *
     * @return Purchase_Order_Result
     */
    public function handle( Get_Purchase_Order_Query $query ): Purchase_Order_Result {
        if ( ! $query->id || ! $this->finance_service ) {
            return Purchase_Order_Result::error( __( 'Invoice not found', 'racketmanager' ) );
        }

        try {
            $invoice = $this->finance_service->get_invoice( $query->id );
        } catch ( Invoice_Not_Found_Exception $e ) {
            return Purchase_Order_Result::error( $e->getMessage() );
        }

        return new Purchase_Order_Result(
            invoice: $invoice,
            modal: $query->modal,
            template: $query->template
        );
    }
}
