<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Gateways\Payment;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Payment\Ports\Payment_Gateway_Interface;
use Racketmanager\Application\Payment\Ports\Payment_Intent_Result;
use Racketmanager\Exceptions\Stripe_API_Exception;
use Racketmanager\Infrastructure\Gateways\Payment\Stripe_Payment_Gateway;
use RuntimeException;
use stdClass;
use Stripe\Exception\InvalidArgumentException;
use Stripe\StripeClient;

class Stripe_Payment_Gateway_Test extends TestCase {

    public function test_implements_payment_gateway_interface(): void {
        $gateway = new Stripe_Payment_Gateway( null, 'sk_test_123' );
        $this->assertInstanceOf( Payment_Gateway_Interface::class, $gateway );
    }

    public function test_create_payment_intent_throws_when_api_key_is_missing(): void {
        $gateway = new Stripe_Payment_Gateway( [] );

        $this->expectException( Stripe_API_Exception::class );
        $this->expectExceptionMessage( 'Stripe API key is not configured' );

        $gateway->create_payment_intent( [ 'amount' => 1000 ] );
    }

    public function test_create_payment_intent_selects_test_key_when_not_live(): void {
        $captured_key = null;
        $factory = function( string $key ) use ( &$captured_key ) {
            $captured_key = $key;
            $client = new class {
                public object $paymentIntents;
            };
            $client->paymentIntents = new class {
                public function create( array $args ): stdClass {
                    $intent = new stdClass();
                    $intent->id = 'pi_test';
                    $intent->client_secret = 'pi_test_secret';
                    return $intent;
                }
            };
            return $client;
        };

        $options = [
            'stripe_is_live'      => false,
            'api_secret_key_test' => 'sk_test_key_abc',
            'api_secret_key_live' => 'sk_live_key_xyz',
        ];

        $gateway = new Stripe_Payment_Gateway( $options, null, $factory );
        $result = $gateway->create_payment_intent( [ 'amount' => 1000 ] );

        $this->assertSame( 'sk_test_key_abc', $captured_key );
        $this->assertSame( 'pi_test', $result->id );
        $this->assertSame( 'pi_test_secret', $result->client_secret );
    }

    public function test_create_payment_intent_selects_live_key_when_live(): void {
        $captured_key = null;
        $factory = function( string $key ) use ( &$captured_key ) {
            $captured_key = $key;
            $client = new class {
                public object $paymentIntents;
            };
            $client->paymentIntents = new class {
                public function create( array $args ): stdClass {
                    $intent = new stdClass();
                    $intent->id = 'pi_live';
                    $intent->client_secret = 'pi_live_secret';
                    return $intent;
                }
            };
            return $client;
        };

        $options = [
            'stripe_is_live'      => true,
            'api_secret_key_test' => 'sk_test_key_abc',
            'api_secret_key_live' => 'sk_live_key_xyz',
        ];

        $gateway = new Stripe_Payment_Gateway( $options, null, $factory );
        $result = $gateway->create_payment_intent( [ 'amount' => 2000 ] );

        $this->assertSame( 'sk_live_key_xyz', $captured_key );
        $this->assertSame( 'pi_live', $result->id );
    }

    public function test_create_payment_intent_maps_intent_to_result_dto(): void {
        $factory = function( string $key ) {
            $client = new class {
                public object $paymentIntents;
            };
            $client->paymentIntents = new class {
                public function create( array $args ): stdClass {
                    $intent = new stdClass();
                    $intent->id = 'pi_999';
                    $intent->client_secret = 'pi_999_secret';
                    $intent->status = 'requires_payment_method';
                    $intent->amount = 2500;
                    $intent->currency = 'gbp';
                    return $intent;
                }
            };
            return $client;
        };

        $gateway = new Stripe_Payment_Gateway( null, 'sk_test_direct', $factory );
        $result = $gateway->create_payment_intent( [ 'amount' => 2500, 'currency' => 'gbp' ] );

        $this->assertInstanceOf( Payment_Intent_Result::class, $result );
        $this->assertSame( 'pi_999', $result->id );
        $this->assertSame( 'pi_999_secret', $result->client_secret );
        $this->assertSame( 'requires_payment_method', $result->status );
        $this->assertSame( 2500, $result->amount );
        $this->assertSame( 'gbp', $result->currency );
    }

    public function test_create_payment_intent_catches_stripe_exception_and_throws_stripe_api_exception(): void {
        $factory = function( string $key ) {
            $client = new class {
                public object $paymentIntents;
            };
            $client->paymentIntents = new class {
                public function create( array $args ): never {
                    throw new InvalidArgumentException( 'Invalid payment amount specified' );
                }
            };
            return $client;
        };

        $gateway = new Stripe_Payment_Gateway( null, 'sk_test_direct', $factory );

        $this->expectException( Stripe_API_Exception::class );
        $this->expectExceptionMessage( 'Invalid payment amount specified' );

        $gateway->create_payment_intent( [ 'amount' => -100 ] );
    }

    public function test_create_payment_intent_does_not_catch_non_stripe_exceptions(): void {
        $factory = function( string $key ) {
            $client = new class {
                public object $paymentIntents;
            };
            $client->paymentIntents = new class {
                public function create( array $args ): never {
                    throw new RuntimeException( 'Unexpected programming defect' );
                }
            };
            return $client;
        };

        $gateway = new Stripe_Payment_Gateway( null, 'sk_test_direct', $factory );

        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( 'Unexpected programming defect' );

        $gateway->create_payment_intent( [ 'amount' => 1000 ] );
    }
}
