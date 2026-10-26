<?php
declare( strict_types=1 );
/**
 * Stripe_Settings API: stripe class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Stripe_Settings
 */

namespace Racketmanager\Services;

use Racketmanager\Services\Finance_Service;

/**
 * Class to implement the Stripe_Settings object
 */
final class Stripe_Settings {

    /**
     * Currency
     *
     * @var string|null
     */
    public ?string $currency = null;
    /**
     * Is live indicator
     *
     * @var boolean
     */
    public bool $is_live = false;
    /**
     * Api_publishable_key
     *
     * @var string|null
     */
    public ?string $api_publishable_key = null;
    /**
     * Api_secret_key
     *
     * @var string|null
     */
    public ?string $api_secret_key = null;
    /**
     * Api endpoint secret
     *
     * @var string|null
     */
    public ?string $api_endpoint_key = null;

    /**
     * Constructor
     */
    public function __construct( ?array $billing ) {
        if ( $billing ) {
            $this->currency = $billing['billingCurrency'] ?? null;
            $this->is_live  = $billing['stripe_is_live'] ?? false;
            if ( $this->is_live ) {
                $this->api_publishable_key = $billing['api_publishable_key_live'] ?? null;
                $this->api_secret_key      = $billing['api_secret_key_live'] ?? null;
                $this->api_endpoint_key    = $billing['api_endpoint_key_live'] ?? null;
            } else {
                $this->api_publishable_key = $billing['api_publishable_key_test'] ?? null;
                $this->api_secret_key      = $billing['api_secret_key_test'] ?? null;
                $this->api_endpoint_key    = $billing['api_endpoint_key_test'] ?? null;
            }
        }
    }
    /**
     * Update payment status
     *
     * @param Finance_Service $finance_service
     * @param string $payment_ref paymentIntent id.
     * @param string $status payment status defaults to paid.
     * @return void
     */
    public function update_payment( Finance_Service $finance_service, string $payment_ref, string $status = 'paid' ): void {
        $invoices = $finance_service->get_invoices_by_criteria( array( 'reference' => $payment_ref ) );
        if ( 1 === count( $invoices ) ) {
            $invoice = $invoices[0];
            if ( $status !== $invoice->status ) {
                $invoice->set_status( $status );
            }
        }
    }
}
