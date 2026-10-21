<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Options;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Infrastructure\Gateways\Payment\Stripe_Payment_Gateway;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Option_Service_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_loads_default_values_when_options_are_empty(): void {
        $gateway = $this->createMock( Option_Gateway_Interface::class );
        $gateway->method( 'get_option' )->willReturnCallback(
            fn( string $key, mixed $default = false ) => match ( $key ) {
                'racketmanager' => [],
                'date_format'   => 'Y-m-d',
                'time_format'   => 'H:i',
                'blogname'      => 'Test League',
                'admin_email'   => 'admin@example.org',
                'siteurl'       => 'https://example.org',
                default         => $default,
            }
        );

        $service = new Option_Service( $gateway );

        self::assertSame( [], $service->get_options() );
        self::assertSame( 'Y-m-d', $service->get_date_format() );
        self::assertSame( 'H:i', $service->get_time_format() );
        self::assertSame( 'Test League', $service->get_site_name() );
        self::assertSame( 'admin@example.org', $service->get_admin_email() );
        self::assertSame( 'https://example.org', $service->get_site_url() );
        self::assertSame( $gateway, $service->get_gateway() );
    }

    public function test_loads_configured_plugin_options_and_site_configuration(): void {
        $gateway = $this->createMock( Option_Gateway_Interface::class );
        $options_data = [
            'rosters'  => [ 'limit' => 12, 'btm' => 1 ],
            'version'  => '2.0.0',
        ];

        $gateway->method( 'get_option' )->willReturnCallback(
            fn( string $key, mixed $default = false ) => match ( $key ) {
                'racketmanager' => $options_data,
                'date_format'   => 'd/m/Y',
                'time_format'   => 'g:i a',
                'blogname'      => 'Custom &amp; Club',
                'admin_email'   => 'league@example.com',
                'siteurl'       => 'https://tennis.local',
                default         => $default,
            }
        );

        $service = new Option_Service( $gateway );

        self::assertSame( $options_data, $service->get_options() );
        self::assertSame( [ 'limit' => 12, 'btm' => 1 ], $service->get_options( 'rosters' ) );
        self::assertNull( $service->get_options( 'non_existent_key' ) );
        self::assertSame( '2.0.0', $service->get_option( 'version' ) );
        self::assertSame( 'd/m/Y', $service->get_date_format() );
        self::assertSame( 'g:i a', $service->get_time_format() );
        self::assertSame( 'Custom & Club', $service->get_site_name() );
        self::assertSame( 'league@example.com', $service->get_admin_email() );
        self::assertSame( 'https://tennis.local', $service->get_site_url() );
        self::assertTrue( $service->is_lta_number_required() );
    }

    public function test_is_lta_number_required_returns_false_when_not_configured(): void {
        $gateway = $this->createMock( Option_Gateway_Interface::class );
        $gateway->method( 'get_option' )->willReturnCallback(
            fn( string $key, mixed $default = false ) => 'racketmanager' === $key ? [ 'rosters' => [ 'limit' => 10 ] ] : $default
        );

        $service = new Option_Service( $gateway );
        self::assertFalse( $service->is_lta_number_required() );
    }

    public function test_get_option_returns_default_when_key_missing(): void {
        $gateway = $this->createMock( Option_Gateway_Interface::class );
        $gateway->method( 'get_option' )->willReturnCallback(
            fn( string $key, mixed $default = false ) => 'racketmanager' === $key ? [] : $default
        );

        $service = new Option_Service( $gateway );

        self::assertNull( $service->get_option( 'missing_key' ) );
        self::assertSame( 'fallback', $service->get_option( 'missing_key', 'fallback' ) );
    }

    public function test_set_options_updates_section_and_persists_to_gateway(): void {
        $storage = [ 'initial' => 'value' ];
        $gateway = $this->createMock( Option_Gateway_Interface::class );
        $gateway->method( 'get_option' )->willReturnCallback(
            fn( string $key, mixed $default = false ) => 'racketmanager' === $key ? $storage : $default
        );
        $gateway->expects( self::once() )
            ->method( 'update_option' )
            ->with( 'racketmanager', [ 'initial' => 'value', 'scoring' => [ 'format' => 'sets' ] ] )
            ->willReturn( true );

        $service = new Option_Service( $gateway );
        $service->set_options( 'scoring', [ 'format' => 'sets' ] );

        self::assertSame( [ 'format' => 'sets' ], $service->get_options( 'scoring' ) );
        self::assertSame(
            [ 'initial' => 'value', 'scoring' => [ 'format' => 'sets' ] ],
            $service->get_options()
        );
    }

    public function test_update_plugin_options_persists_plugin_options(): void {
        $gateway = $this->createMock( Option_Gateway_Interface::class );
        $gateway->method( 'get_option' )->willReturnCallback(
            fn( string $key, mixed $default = false ) => 'racketmanager' === $key ? [] : $default
        );
        $new_options = [
            'season' => 2026,
        ];

        $gateway->expects( self::once() )
            ->method( 'update_option' )
            ->with( 'racketmanager', $new_options )
            ->willReturn( true );

        $service = new Option_Service( $gateway );
        $result  = $service->update_plugin_options( $new_options );

        self::assertTrue( $result );
        self::assertSame( $new_options, $service->get_options() );
    }

    public function test_load_options_reloads_values_from_gateway(): void {
        $data = [ 'counter' => 1 ];
        $gateway = $this->createMock( Option_Gateway_Interface::class );
        $gateway->method( 'get_option' )->willReturnCallback(
            function( string $key, mixed $default = false ) use ( &$data ) {
                return 'racketmanager' === $key ? $data : $default;
            }
        );

        $service = new Option_Service( $gateway );
        self::assertSame( 1, $service->get_option( 'counter' ) );

        $data = [ 'counter' => 2 ];
        $service->load_options();
        self::assertSame( 2, $service->get_option( 'counter' ) );
    }

    public function test_container_wiring_and_resolution(): void {
        $container = Container_Bootstrap::boot();

        self::assertTrue( $container->has( 'option_service' ) );
        self::assertTrue( $container->has( Option_Service::class ) );

        $service1 = $container->get( 'option_service' );
        $service2 = $container->get( Option_Service::class );

        self::assertInstanceOf( Option_Service::class, $service1 );
        self::assertSame( $service1, $service2 );
    }

    public function test_container_uses_app_option_service_for_settings_and_payment_gateway(): void {
        $options_data = [
            'billing' => [
                'stripe_is_live'       => true,
                'api_secret_key_live'  => 'live-key',
                'api_secret_key_test'  => 'test-key',
            ],
            'rosters' => [ 'limit' => 12 ],
        ];
        $gateway = $this->createMock( Option_Gateway_Interface::class );
        $gateway->method( 'get_option' )->willReturnCallback(
            fn( string $key, mixed $default = false ) => 'racketmanager' === $key ? $options_data : $default
        );
        $option_service = new Option_Service( $gateway );

        $reflection = new \ReflectionClass( RacketManager::class );
        /** @var RacketManager $app */
        $app = $reflection->newInstanceWithoutConstructor();
        $app->option_service = $option_service;

        $container = Container_Bootstrap::boot( $app );

        self::assertSame( $option_service, $container->get( 'option_service' ) );
        self::assertSame( $option_service, $container->get( Option_Service::class ) );
        self::assertSame( $options_data, $container->get( 'settings_service' )->get_all_options() );

        $payment_gateway = $container->get( 'payment_gateway' );
        self::assertInstanceOf( Stripe_Payment_Gateway::class, $payment_gateway );
        $billing_options = new \ReflectionProperty( Stripe_Payment_Gateway::class, 'billing_options' );
        self::assertSame( $options_data['billing'], $billing_options->getValue( $payment_gateway ) );
    }

    public function test_racketmanager_delegates_to_option_service(): void {
        $gateway = $this->createMock( Option_Gateway_Interface::class );
        $options_store = [ 'existing' => 'data' ];
        $gateway->method( 'get_option' )->willReturnCallback(
            fn( string $key, mixed $default = false ) => 'racketmanager' === $key ? $options_store : $default
        );
        $gateway->method( 'update_option' )->willReturnCallback(
            function( string $key, mixed $val ) use ( &$options_store ) {
                if ( 'racketmanager' === $key ) {
                    $options_store = $val;
                    return true;
                }
                return false;
            }
        );

        $option_service = new Option_Service( $gateway );

        $reflection = new \ReflectionClass( RacketManager::class );
        /** @var RacketManager $app */
        $app = $reflection->newInstanceWithoutConstructor();
        $app->option_service = $option_service;
        $app->options = $option_service->get_options();

        self::assertSame( [ 'existing' => 'data' ], $app->get_options() );
        self::assertSame( 'data', $app->get_options( 'existing' ) );

        $app->update_plugin_options( [ 'new_key' => 'new_val' ] );
        self::assertSame( 'new_val', $app->get_options( 'new_key' ) );
        self::assertSame( 'new_val', $option_service->get_option( 'new_key' ) );
        self::assertSame( [ 'new_key' => 'new_val' ], $app->options );

        $app->set_options( 'section', [ 'setting' => true ] );
        self::assertSame( [ 'setting' => true ], $app->get_options( 'section' ) );
        self::assertSame( [ 'setting' => true ], $option_service->get_options( 'section' ) );
        self::assertSame( [ 'new_key' => 'new_val', 'section' => [ 'setting' => true ] ], $app->options );
    }
}
