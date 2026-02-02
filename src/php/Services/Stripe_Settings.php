<?php
/**
 * Stripe_Settings API: stripe class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Stripe_Settings
 */

namespace Racketmanager\Services;

/**
 * Class to implement the Stripe_Settings object
 */
final class Stripe_Settings {

    /**
     * Currency
     *
     * @var string|null
     */
    public ?string $currency;
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
    public ?string $api_publishable_key;
    /**
     * Api_secret_key
     *
     * @var string|null
     */
    public ?string $api_secret_key;
    /**
     * Api endpoint secret
     *
     * @var string|null
     */
    public ?string $api_endpoint_key;
    private RacketManager $racketmanager;
    private Finance_Service $finance_service;

    /**
     * Constructor
     */
    public function __construct( $plugin_instance ) {
        $this->racketmanager   = $plugin_instance;
        $c                     = $this->racketmanager->container;
        $this->finance_service = $c->get( 'finance_service' );

        $billing = $this->racketmanager->get_options( 'billing' );
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
     * @param string $payment_ref paymentIntent id.
     * @param string $status payment status defaults to paid.
     * @return void
     */
    public function update_payment(string $payment_ref, string $status = 'paid' ): void {
        $invoices = $this->finance_service->get_invoices_by_criteria( array( 'reference' => $payment_ref ) );
        if ( 1 === count( $invoices ) ) {
            $invoice = $invoices[0];
            if ( $status !== $invoice->status ) {
                $invoice->set_status( $status );
            }
        }
    }
}
