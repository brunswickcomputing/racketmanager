<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Application\General\DTOs\Purchase_Order_Result;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Services\Finance_Service;
use function __;

class Get_Purchase_Order_Handler {
    public function __construct(
        private readonly ?Finance_Service $finance_service = null
    ) {}

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
