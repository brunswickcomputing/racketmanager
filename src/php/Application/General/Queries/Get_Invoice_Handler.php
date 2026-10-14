<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\RacketManager;
use Racketmanager\Services\Finance_Service;
use function __;
use function is_object;
use function method_exists;

class Get_Invoice_Handler {
    public function __construct(
        private readonly ?Finance_Service $finance_service = null,
        private readonly ?RacketManager $racketmanager = null
    ) {}

    public function handle( Get_Invoice_Query $query ): Shortcode_View_Model {
        if ( ! $query->id || ! $this->finance_service ) {
            return Shortcode_View_Model::for_error( __( 'Invoice not found', 'racketmanager' ) );
        }

        try {
            $invoice = $this->finance_service->get_full_invoice_details( $query->id );
        } catch ( Invoice_Not_Found_Exception $e ) {
            return Shortcode_View_Model::for_error( $e->getMessage() );
        }

        $billing           = ( $this->racketmanager && method_exists( $this->racketmanager, 'get_options' ) ) ? ( $this->racketmanager->get_options( 'billing' ) ?: [] ) : [];
        $organisation_name = $this->racketmanager->site_name ?? '';

        $invoice_number = '';
        if ( is_object( $invoice ) ) {
            if ( isset( $invoice->invoice ) && is_object( $invoice->invoice ) && method_exists( $invoice->invoice, 'get_invoice_number' ) ) {
                $invoice_number = $invoice->invoice->get_invoice_number();
            } elseif ( isset( $invoice->invoice_number ) ) {
                $invoice_number = (string) $invoice->invoice_number;
            }
        }

        $filename = ! empty( $query->template ) ? 'invoice-' . $query->template : 'invoice';

        return Shortcode_View_Model::for_template( $filename, [
            'organisation_name' => $organisation_name,
            'invoice'           => $invoice,
            'billing'           => $billing,
            'invoice_number'    => $invoice_number,
        ] );
    }
}
