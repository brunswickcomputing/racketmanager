<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Container;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Admin\Commands\Competition_Season_Handler;
use Racketmanager\Application\Finance\Commands\Update_Payment_Handler;
use Racketmanager\Application\Ports\Competition_Config_Validator_Factory_Interface;
use Racketmanager\Application\Ports\Finance_Service_Interface;
use Racketmanager\Application\Ports\Settings_Service_Interface;
use Racketmanager\Application\Tournament\Ports\Tournament_Service_Interface;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\Validator\Competition_Config_Validator_Factory;
use Racketmanager\Services\Validator\Validator_Config;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

final class Container_Ports_Wiring_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private RacketManager $app;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $wpdb_mock = $this->createStub( wpdb::class );
        $wpdb_mock->prefix = 'wp_';
        $GLOBALS['wpdb'] = $wpdb_mock;

        $this->app = $this->createStub( RacketManager::class );
        $this->app->method( 'get_options' )->willReturn( [] );
        $this->app->site_url = 'https://example.com';
        $this->app->site_name = 'Test Site';
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_container_resolves_finance_and_settings_service_interfaces(): void {
        $container = Container_Bootstrap::boot( $this->app );

        self::assertTrue( $container->has( Finance_Service_Interface::class ) );
        $finance_service = $container->get( Finance_Service_Interface::class );
        self::assertInstanceOf( Finance_Service_Interface::class, $finance_service );
        self::assertInstanceOf( Finance_Service::class, $finance_service );
        self::assertSame( $finance_service, $container->get( 'finance_service' ) );

        self::assertTrue( $container->has( Settings_Service_Interface::class ) );
        $settings_service = $container->get( Settings_Service_Interface::class );
        self::assertInstanceOf( Settings_Service_Interface::class, $settings_service );
        self::assertInstanceOf( Settings_Service::class, $settings_service );
        self::assertSame( $settings_service, $container->get( 'settings_service' ) );
    }

    public function test_container_resolves_competition_config_validator_factory_and_update_payment_handler(): void {
        $container = Container_Bootstrap::boot( $this->app );

        self::assertTrue( $container->has( Competition_Config_Validator_Factory_Interface::class ) );
        $factory = $container->get( Competition_Config_Validator_Factory_Interface::class );
        self::assertInstanceOf( Competition_Config_Validator_Factory_Interface::class, $factory );
        self::assertInstanceOf( Competition_Config_Validator_Factory::class, $factory );

        $validator = $factory->create();
        self::assertInstanceOf( Validator_Config::class, $validator );

        self::assertTrue( $container->has( Update_Payment_Handler::class ) );
        $handler = $container->get( Update_Payment_Handler::class );
        self::assertInstanceOf( Update_Payment_Handler::class, $handler );
    }

    public function test_container_resolves_competition_season_handler_with_injected_dependencies(): void {
        $container = Container_Bootstrap::boot( $this->app );

        self::assertTrue( $container->has( 'competition_season_handler' ) );
        $handler = $container->get( 'competition_season_handler' );
        self::assertInstanceOf( Competition_Season_Handler::class, $handler );
    }

    public function test_container_resolves_tournament_service_interface(): void {
        $container = Container_Bootstrap::boot( $this->app );

        self::assertTrue( $container->has( Tournament_Service_Interface::class ) );
        $tournament_service = $container->get( Tournament_Service_Interface::class );
        self::assertInstanceOf( Tournament_Service::class, $tournament_service );
        self::assertSame( $tournament_service, $container->get( 'tournament_service' ) );
    }
}
