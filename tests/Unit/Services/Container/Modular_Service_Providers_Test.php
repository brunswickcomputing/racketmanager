<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Container;

use PHPUnit\Framework\TestCase;
use Racketmanager\RacketManager;
use Racketmanager\Application\League\Queries\Get_League_Last_5_Handler;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Container\Providers\Admin_Service_Provider;
use Racketmanager\Services\Container\Providers\Club_Service_Provider;
use Racketmanager\Services\Container\Providers\Competition_Service_Provider;
use Racketmanager\Services\Container\Providers\Core_Service_Provider;
use Racketmanager\Services\Container\Providers\Fixture_Service_Provider;
use Racketmanager\Services\Container\Providers\Player_Service_Provider;
use Racketmanager\Services\Container\Providers\Service_Provider_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

class Modular_Service_Providers_Test extends TestCase {
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

    public function test_providers_implement_service_provider_interface(): void {
        $providers = [
            new Core_Service_Provider( $this->app ),
            new Club_Service_Provider( $this->app ),
            new Player_Service_Provider( $this->app ),
            new Fixture_Service_Provider( $this->app ),
            new Competition_Service_Provider( $this->app ),
            new Admin_Service_Provider( $this->app ),
        ];

        foreach ( $providers as $provider ) {
            $this->assertInstanceOf( Service_Provider_Interface::class, $provider );
        }
    }

    public function test_core_service_provider_registers_expected_keys(): void {
        $container = new Simple_Container();
        $provider  = new Core_Service_Provider( $this->app );
        $provider->register( $container );

        $this->assertTrue( $container->has( 'mailer' ) );
        $this->assertTrue( $container->has( 'payment_gateway' ) );
        $this->assertTrue( $container->has( 'rating_gateway' ) );
        $this->assertTrue( $container->has( 'wtn_api_client' ) );
        $this->assertTrue( $container->has( 'season_repository' ) );
        $this->assertTrue( $container->has( 'charge_repository' ) );
        $this->assertTrue( $container->has( 'invoice_repository' ) );
        $this->assertTrue( $container->has( 'finance_service' ) );
        $this->assertTrue( $container->has( 'security_service' ) );
        $this->assertTrue( $container->has( 'legacy_user_factory' ) );
    }

    public function test_club_service_provider_registers_expected_keys(): void {
        $container = new Simple_Container();
        $provider  = new Club_Service_Provider( $this->app );
        $provider->register( $container );

        $this->assertTrue( $container->has( 'club_repository' ) );
        $this->assertTrue( $container->has( 'registration_repository' ) );
        $this->assertTrue( $container->has( 'club_role_repository' ) );
        $this->assertTrue( $container->has( 'team_repository' ) );
        $this->assertTrue( $container->has( 'club_service' ) );
        $this->assertTrue( $container->has( 'registration_service' ) );
        $this->assertTrue( $container->has( 'team_service' ) );
        $this->assertTrue( $container->has( 'club_presenter' ) );
    }

    public function test_player_service_provider_registers_expected_keys(): void {
        $container = new Simple_Container();
        $provider  = new Player_Service_Provider( $this->app );
        $provider->register( $container );

        $this->assertTrue( $container->has( 'player_repository' ) );
        $this->assertTrue( $container->has( 'player_error_repository' ) );
        $this->assertTrue( $container->has( 'player_query_service' ) );
        $this->assertTrue( $container->has( 'player_stats_service' ) );
        $this->assertTrue( $container->has( 'player_service' ) );
        $this->assertTrue( $container->has( 'player_validation_service' ) );
        $this->assertTrue( $container->has( 'player_presenter' ) );
    }

    public function test_fixture_service_provider_registers_expected_keys(): void {
        $container = new Simple_Container();
        $provider  = new Fixture_Service_Provider( $this->app );
        $provider->register( $container );

        $this->assertTrue( $container->has( 'fixture_repository' ) );
        $this->assertTrue( $container->has( 'rubber_repository' ) );
        $this->assertTrue( $container->has( 'result_repository' ) );
        $this->assertTrue( $container->has( 'fixture_service' ) );
        $this->assertTrue( $container->has( 'fixture_result_manager' ) );
        $this->assertTrue( $container->has( 'fixture_permission_service' ) );
        $this->assertTrue( $container->has( 'fixture_detail_service' ) );
        $this->assertTrue( $container->has( 'fixture_presenter' ) );
    }

    public function test_competition_service_provider_registers_expected_keys(): void {
        $container = new Simple_Container();
        $provider  = new Competition_Service_Provider( $this->app );
        $provider->register( $container );

        $this->assertTrue( $container->has( 'competition_repository' ) );
        $this->assertTrue( $container->has( 'event_repository' ) );
        $this->assertTrue( $container->has( 'league_repository' ) );
        $this->assertTrue( $container->has( 'league_team_repository' ) );
        $this->assertTrue( $container->has( 'tournament_repository' ) );
        $this->assertTrue( $container->has( 'competition_service' ) );
        $this->assertTrue( $container->has( 'league_service' ) );
        $this->assertTrue( $container->has( 'standings_service' ) );
        $this->assertTrue( $container->has( 'knockout_progression_service' ) );
        $this->assertTrue( $container->has( 'tournament_service' ) );
    }

    public function test_admin_service_provider_registers_expected_keys(): void {
        $container = new Simple_Container();
        $provider  = new Admin_Service_Provider( $this->app );
        $provider->register( $container );

        $this->assertTrue( $container->has( 'admin_flash_message_store' ) );
        $this->assertTrue( $container->has( 'admin_message_service' ) );
        $this->assertTrue( $container->has( 'action_guard' ) );
        $this->assertTrue( $container->has( 'championship_admin_service' ) );
        $this->assertTrue( $container->has( 'draw_action_dispatcher' ) );
        $this->assertTrue( $container->has( 'tournament_admin_controller' ) );
        $this->assertTrue( $container->has( 'admin_display' ) );
        $this->assertTrue( $container->has( \Racketmanager\Admin\Admin_Display::class ) );
        $this->assertTrue( $container->has( 'admin' ) );
        $this->assertTrue( $container->has( \Racketmanager\Admin::class ) );
    }

    public function test_container_bootstrap_boots_all_providers(): void {
        $container = Container_Bootstrap::boot( $this->app );

        $this->assertTrue( $container->has( 'mailer' ) );
        $this->assertTrue( $container->has( 'club_repository' ) );
        $this->assertTrue( $container->has( 'player_repository' ) );
        $this->assertTrue( $container->has( 'fixture_repository' ) );
        $this->assertTrue( $container->has( 'league_repository' ) );
        $this->assertTrue( $container->has( 'tournament_admin_controller' ) );
    }

    public function test_last_5_handler_is_wired_with_fixture_presenter(): void {
        $container = Container_Bootstrap::boot( $this->app );
        $handler = $container->get( 'get_league_last_5_handler' );

        $this->assertInstanceOf( Get_League_Last_5_Handler::class, $handler );
        $this->assertSame(
            $container->get( 'fixture_presenter' ),
            ( new \ReflectionProperty( Get_League_Last_5_Handler::class, 'fixture_presenter' ) )->getValue( $handler )
        );
    }

    public function test_providers_wire_domain_event_dispatcher_to_services(): void {
        $container = Container_Bootstrap::boot( $this->app );

        $dispatcher = $container->get( 'domain_event_dispatcher' );
        $this->assertInstanceOf( \Racketmanager\Domain\Events\Domain_Event_Dispatcher_Interface::class, $dispatcher );

        $season_service = $container->get( 'competition_season_service' );
        $ref_season     = new \ReflectionProperty( \Racketmanager\Services\Competition\Competition_Season_Service::class, 'event_dispatcher' );
        $this->assertSame( $dispatcher, $ref_season->getValue( $season_service ) );

        $reg_service = $container->get( 'registration_service' );
        $ref_reg     = new \ReflectionProperty( \Racketmanager\Services\Registration_Service::class, 'event_dispatcher' );
        $this->assertSame( $dispatcher, $ref_reg->getValue( $reg_service ) );

        $fixture_finalization = $container->get( 'fixture_finalization_service' );
        $ref_fixture          = new \ReflectionProperty( \Racketmanager\Services\Fixture\Fixture_Finalization_Service::class, 'event_dispatcher' );
        $this->assertSame( $dispatcher, $ref_fixture->getValue( $fixture_finalization ) );
    }
}
