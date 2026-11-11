<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Finance\DTOs;

/**
 * Stripe settings data transfer object.
 */
class Stripe_Settings {

    /**
     * Currency.
     *
     * @var string|null
     */
    public ?string $currency = null;

    /**
     * Is live indicator.
     *
     * @var boolean
     */
    public bool $is_live = false;

    /**
     * API publishable key.
     *
     * @var string|null
     */
    public ?string $api_publishable_key = null;

    /**
     * API secret key.
     *
     * @var string|null
     */
    public ?string $api_secret_key = null;

    /**
     * API endpoint secret.
     *
     * @var string|null
     */
    public ?string $api_endpoint_key = null;

    /**
     * Constructor.
     *
     * @param array<string, mixed>|null $billing Billing options.
     */
    public function __construct( ?array $billing = null ) {
        if ( $billing ) {
            $this->currency = $billing['billingCurrency'] ?? null;
            $this->is_live  = (bool) ( $billing['stripe_is_live'] ?? false );
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
}
