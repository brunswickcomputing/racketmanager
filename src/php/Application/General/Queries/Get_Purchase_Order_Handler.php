<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Finance_Service;
use function __;

class Get_Purchase_Order_Handler {
    public function __construct(
        private readonly ?Finance_Service $finance_service = null
    ) {}

    public function handle( Get_Purchase_Order_Query $query ): Shortcode_View_Model {
        if ( ! $query->id || ! $this->finance_service ) {
            return Shortcode_View_Model::for_error( __( 'Invoice not found', 'racketmanager' ), 'modal' );
        }

        try {
            $invoice = $this->finance_service->get_invoice( $query->id );
        } catch ( Invoice_Not_Found_Exception $e ) {
            return Shortcode_View_Model::for_error( $e->getMessage(), 'modal' );
        }

        $filename = ! empty( $query->template ) ? 'purchase-order-modal-' . $query->template : 'purchase-order-modal';

        return Shortcode_View_Model::for_template( $filename, [
            'invoice' => $invoice,
            'modal'   => $query->modal,
        ], 'club' );
    }
}
