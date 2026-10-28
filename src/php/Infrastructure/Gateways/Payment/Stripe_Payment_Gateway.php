<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Gateways\Payment;

use Racketmanager\Application\Payment\Ports\Payment_Gateway_Interface;
use Racketmanager\Application\Payment\Ports\Payment_Intent_Result;
use Racketmanager\Exceptions\Stripe_API_Exception;
use Stripe\Exception\ExceptionInterface;
use Stripe\StripeClient;

class Stripe_Payment_Gateway implements Payment_Gateway_Interface {
    private ?array $billing_options;
    private ?string $api_secret_key;
    /** @var (callable(string): object)|null */
    private $client_factory;

    public function __construct(
        ?array $billing_options = null,
        ?string $api_secret_key = null,
        ?callable $client_factory = null
    ) {
        $this->billing_options = $billing_options;
        $this->api_secret_key  = $api_secret_key;
        $this->client_factory  = $client_factory;
    }

    private function get_api_secret_key(): ?string {
        if ( null !== $this->api_secret_key ) {
            return $this->api_secret_key;
        }

        if ( null !== $this->billing_options ) {
            $billing = $this->billing_options;
            $is_live = $billing['stripe_is_live'] ?? false;
            return $is_live
                ? ( $billing['api_secret_key_live'] ?? null )
                : ( $billing['api_secret_key_test'] ?? null );
        }

        return null;
    }

    public function create_payment_intent( array $args ): Payment_Intent_Result {
        if ( ! class_exists( StripeClient::class ) ) {
            throw new Stripe_API_Exception( __( 'Stripe client is not installed', 'racketmanager' ) );
        }

        $secret_key = $this->get_api_secret_key();
        if ( empty( $secret_key ) ) {
            throw new Stripe_API_Exception( __( 'Stripe API key is not configured', 'racketmanager' ) );
        }

        try {
            $stripe = $this->client_factory
                ? ( $this->client_factory )( $secret_key )
                : new StripeClient( $secret_key );
            $intent = $stripe->paymentIntents->create( $args );

            return new Payment_Intent_Result(
                id: (string) ( $intent->id ?? '' ),
                client_secret: (string) ( $intent->client_secret ?? '' ),
                status: isset( $intent->status ) ? (string) $intent->status : null,
                amount: isset( $intent->amount ) ? (int) $intent->amount : null,
                currency: isset( $intent->currency ) ? (string) $intent->currency : null,
            );
        } catch ( ExceptionInterface $e ) {
            throw new Stripe_API_Exception( $e->getMessage() );
        }
    }
}
