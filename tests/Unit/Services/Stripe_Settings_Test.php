<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Finance\DTOs\Stripe_Settings as Stripe_Settings_DTO;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Stripe_Settings;

/**
 * Tests for legacy Stripe_Settings and Application Stripe_Settings DTO.
 */
class Stripe_Settings_Test extends TestCase {

    public function test_legacy_stripe_settings_extends_application_dto(): void {
        $settings = new Stripe_Settings( null );

        $this->assertInstanceOf( Stripe_Settings_DTO::class, $settings );
        $this->assertNull( $settings->currency );
        $this->assertFalse( $settings->is_live );
        $this->assertNull( $settings->api_publishable_key );
        $this->assertNull( $settings->api_secret_key );
        $this->assertNull( $settings->api_endpoint_key );
    }

    public function test_constructor_populates_test_keys_when_not_live(): void {
        $billing = array(
            'billingCurrency'        => 'GBP',
            'stripe_is_live'         => false,
            'api_publishable_key_test' => 'pk_test_123',
            'api_secret_key_test'    => 'sk_test_456',
            'api_endpoint_key_test'  => 'whsec_test_789',
        );

        $settings = new Stripe_Settings( $billing );

        $this->assertSame( 'GBP', $settings->currency );
        $this->assertFalse( $settings->is_live );
        $this->assertSame( 'pk_test_123', $settings->api_publishable_key );
        $this->assertSame( 'sk_test_456', $settings->api_secret_key );
        $this->assertSame( 'whsec_test_789', $settings->api_endpoint_key );
    }

    public function test_constructor_populates_live_keys_when_live(): void {
        $billing = array(
            'billingCurrency'        => 'EUR',
            'stripe_is_live'         => true,
            'api_publishable_key_live' => 'pk_live_123',
            'api_secret_key_live'    => 'sk_live_456',
            'api_endpoint_key_live'  => 'whsec_live_789',
        );

        $settings = new Stripe_Settings( $billing );

        $this->assertSame( 'EUR', $settings->currency );
        $this->assertTrue( $settings->is_live );
        $this->assertSame( 'pk_live_123', $settings->api_publishable_key );
        $this->assertSame( 'sk_live_456', $settings->api_secret_key );
        $this->assertSame( 'whsec_live_789', $settings->api_endpoint_key );
    }

    public function test_legacy_update_payment_delegates_to_finance_service(): void {
        $finance_service = $this->createMock( Finance_Service::class );
        $finance_service->expects( self::once() )
            ->method( 'update_payment' )
            ->with( 'pi_test_intent', 'paid' );

        $settings = new Stripe_Settings( null );
        $settings->update_payment( $finance_service, 'pi_test_intent', 'paid' );
    }
}
