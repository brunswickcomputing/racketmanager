<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Container;

use PHPUnit\Framework\TestCase;
use Racketmanager\RacketManager;
use Racketmanager\Admin\Admin_Screens_Service_Provider;
use Racketmanager\Presentation\Presentation_Service_Provider;
use Racketmanager\Application\League\Queries\Get_League_Last_5_Handler;
use Racketmanager\Domain\Ports\Clock_Interface;
use Racketmanager\Infrastructure\Time\Wordpress_Clock;
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
            new Presentation_Service_Provider( $this->app ),
            new Admin_Service_Provider( $this->app ),
            new Admin_Screens_Service_Provider( $this->app ),
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
        $this->assertTrue( $container->has( 'clock' ) );
        $this->assertInstanceOf( Wordpress_Clock::class, $container->get( 'clock' ) );
        $this->assertSame( $container->get( 'clock' ), $container->get( Clock_Interface::class ) );
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
        $this->assertFalse( $container->has( 'club_presenter' ) );
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
        $this->assertFalse( $container->has( 'player_presenter' ) );
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
        $this->assertFalse( $container->has( 'fixture_presenter' ) );
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
        $this->assertTrue( $container->has( 'promotion_relegation_service' ) );
        $this->assertTrue( $container->has( \Racketmanager\Services\Competition\Promotion_Relegation_Service::class ) );
        $this->assertTrue( $container->has( 'tournament_service' ) );
    }

    public function test_admin_service_provider_registers_expected_keys(): void {
        $container = new Simple_Container();
        $provider  = new Admin_Service_Provider( $this->app );
        $provider->register( $container );

        $this->assertTrue( $container->has( 'admin_message_service' ) );
        $this->assertTrue( $container->has( 'action_guard' ) );
        $this->assertTrue( $container->has( 'championship_admin_service' ) );
        $this->assertTrue( $container->has( 'draw_action_dispatcher' ) );
        $this->assertFalse( $container->has( 'admin_flash_message_store' ) );
        $this->assertFalse( $container->has( 'tournament_admin_controller' ) );
        $this->assertFalse( $container->has( 'admin_display' ) );
        $this->assertFalse( $container->has( \Racketmanager\Admin\Admin_Display::class ) );
        $this->assertFalse( $container->has( 'admin' ) );
        $this->assertFalse( $container->has( \Racketmanager\Admin::class ) );
    }

    public function test_admin_screens_service_provider_registers_expected_keys(): void {
        $container = new Simple_Container();
        $provider  = new Admin_Screens_Service_Provider( $this->app );
        $provider->register( $container );

        $this->assertTrue( $container->has( 'competition_config_admin_controller' ) );
        $this->assertTrue( $container->has( 'competition_season_modify_admin_controller' ) );
        $this->assertTrue( $container->has( 'clubs_admin_controller' ) );
        $this->assertTrue( $container->has( 'club_admin_controller' ) );
        $this->assertTrue( $container->has( 'club_players_admin_controller' ) );
        $this->assertTrue( $container->has( 'club_teams_admin_controller' ) );
        $this->assertTrue( $container->has( 'club_team_admin_controller' ) );
        $this->assertTrue( $container->has( 'club_roles_admin_controller' ) );
        $this->assertTrue( $container->has( 'admin_competition_screen' ) );
        $this->assertTrue( $container->has( 'admin_club_screen' ) );
        $this->assertTrue( $container->has( 'admin_player_screen' ) );
        $this->assertTrue( $container->has( 'admin_finances_screen' ) );
        $this->assertTrue( $container->has( 'admin_league_screen' ) );
        $this->assertTrue( $container->has( 'admin_cup_screen' ) );
        $this->assertTrue( $container->has( 'admin_tournament_screen' ) );
        $this->assertTrue( $container->has( 'admin_flash_message_store' ) );
        $this->assertTrue( $container->has( 'admin_plan_presenter' ) );
        $this->assertTrue( $container->has( 'admin_results_match_presenter' ) );
        $this->assertTrue( $container->has( 'tournament_admin_controller' ) );
        $this->assertTrue( $container->has( 'admin_display' ) );
        $this->assertTrue( $container->has( \Racketmanager\Admin\Admin_Display::class ) );
        $this->assertTrue( $container->has( 'admin' ) );
        $this->assertTrue( $container->has( \Racketmanager\Admin::class ) );
    }

    public function test_presentation_service_provider_registers_expected_keys(): void {
        $container = new Simple_Container();
        $provider  = new Presentation_Service_Provider( $this->app );
        $provider->register( $container );

        $this->assertTrue( $container->has( 'club_presenter' ) );
        $this->assertTrue( $container->has( 'player_presenter' ) );
        $this->assertTrue( $container->has( 'favourite_presenter' ) );
        $this->assertTrue( $container->has( 'invoice_presenter' ) );
        $this->assertTrue( $container->has( 'message_presenter' ) );
        $this->assertTrue( $container->has( 'notification_presenter' ) );
        $this->assertTrue( $container->has( 'competition_shortcode_presenter' ) );
        $this->assertTrue( $container->has( 'tournament_presenter' ) );
        $this->assertTrue( $container->has( 'league_archive_presenter' ) );
        $this->assertTrue( $container->has( 'league_standings_presenter' ) );
        $this->assertTrue( $container->has( 'order_of_play_presenter' ) );
        $this->assertTrue( $container->has( 'team_details_presenter' ) );
        $this->assertTrue( $container->has( 'club_shortcode_presenter' ) );
        $this->assertTrue( $container->has( 'team_match_row_presenter' ) );
        $this->assertTrue( $container->has( 'fixture_presenter' ) );
        $this->assertTrue( $container->has( 'results_checker_presenter' ) );
        $this->assertTrue( $container->has( 'matches_list_presenter' ) );
        $this->assertTrue( $container->has( 'match_shortcode_presenter' ) );
        $this->assertTrue( $container->has( 'shortcode_template_renderer' ) );
        $this->assertTrue( $container->has( 'shortcode_view_presenter' ) );
        $this->assertTrue( $container->has( 'email_shortcode_presenter' ) );
    }

    public function test_container_bootstrap_boots_all_providers(): void {
        $container = Container_Bootstrap::boot( $this->app );

        $this->assertTrue( $container->has( 'mailer' ) );
        $this->assertTrue( $container->has( 'club_repository' ) );
        $this->assertTrue( $container->has( 'player_repository' ) );
        $this->assertTrue( $container->has( 'fixture_repository' ) );
        $this->assertTrue( $container->has( 'league_repository' ) );
        $this->assertTrue( $container->has( 'tournament_admin_controller' ) );
        $this->assertTrue( $container->has( 'club_presenter' ) );
        $this->assertTrue( $container->has( 'player_presenter' ) );
        $this->assertTrue( $container->has( 'fixture_presenter' ) );
        $this->assertTrue( $container->has( 'shortcode_template_renderer' ) );
        $this->assertTrue( $container->has( 'admin_flash_message_store' ) );
        $this->assertTrue( $container->has( 'admin_club_screen' ) );
        $this->assertTrue( $container->has( 'admin_competition_screen' ) );
        $this->assertInstanceOf(
            \Racketmanager\Services\Competition\Promotion_Relegation_Service::class,
            $container->get( 'promotion_relegation_service' )
        );
    }

    public function test_booted_container_resolves_moved_presentation_and_admin_keys(): void {
        $container = Container_Bootstrap::boot( $this->app );

        $presentation_keys = [
            'club_presenter'                  => \Racketmanager\Presentation\Presenters\Club_Presenter::class,
            'player_presenter'                => \Racketmanager\Presentation\Presenters\Player_Presenter::class,
            'favourite_presenter'             => \Racketmanager\Presentation\Presenters\Favourite_Presenter::class,
            'invoice_presenter'               => \Racketmanager\Presentation\Presenters\Invoice_Presenter::class,
            'message_presenter'               => \Racketmanager\Presentation\Presenters\Message_Presenter::class,
            'notification_presenter'          => \Racketmanager\Presentation\Presenters\Notification_Presenter::class,
            'competition_shortcode_presenter' => \Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter::class,
            'tournament_presenter'            => \Racketmanager\Presentation\Presenters\Tournament_Presenter::class,
            'league_archive_presenter'        => \Racketmanager\Presentation\Presenters\League_Archive_Presenter::class,
            'league_standings_presenter'      => \Racketmanager\Presentation\Presenters\League_Standings_Presenter::class,
            'order_of_play_presenter'         => \Racketmanager\Presentation\Presenters\Order_Of_Play_Presenter::class,
            'team_details_presenter'          => \Racketmanager\Presentation\Presenters\Team_Details_Presenter::class,
            'club_shortcode_presenter'        => \Racketmanager\Presentation\Presenters\Club_Shortcode_Presenter::class,
            'team_match_row_presenter'        => \Racketmanager\Presentation\Presenters\Team_Match_Row_Presenter::class,
            'fixture_presenter'               => \Racketmanager\Presentation\Presenters\Fixture_Presenter::class,
            'results_checker_presenter'       => \Racketmanager\Presentation\Presenters\Results_Checker_Presenter::class,
            'matches_list_presenter'          => \Racketmanager\Presentation\Presenters\Matches_List_Presenter::class,
            'match_shortcode_presenter'       => \Racketmanager\Presentation\Presenters\Match_Shortcode_Presenter::class,
            'shortcode_template_renderer'     => \Racketmanager\Services\View\Shortcode_Template_Renderer::class,
            'shortcode_view_presenter'        => \Racketmanager\Presentation\Presenters\Shortcode_View_Presenter::class,
            'email_shortcode_presenter'       => \Racketmanager\Presentation\Presenters\Email_Shortcode_Presenter::class,
        ];

        foreach ( $presentation_keys as $key => $class ) {
            $this->assertTrue( $container->has( $key ), $key );
            $this->assertInstanceOf( $class, $container->get( $key ), $key );
        }

        $admin_keys = [
            'admin_flash_message_store'         => \Racketmanager\Admin\Flash\Admin_Flash_Message_Store::class,
            'admin_date_presenter'              => \Racketmanager\Admin\Presenters\Admin_Date_Presenter::class,
            'admin_entry_link_presenter'        => \Racketmanager\Admin\Presenters\Admin_Entry_Link_Presenter::class,
            'admin_league_matches_presenter'    => \Racketmanager\Admin\Presenters\Admin_League_Matches_Presenter::class,
            'admin_championship_finals_presenter' => \Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter::class,
            'admin_results_match_presenter'     => \Racketmanager\Admin\Presenters\Admin_Results_Match_Presenter::class,
            'admin_competition_page_presenter'  => \Racketmanager\Admin\Presenters\Admin_Competition_Page_Presenter::class,
            'admin_plan_presenter'              => \Racketmanager\Admin\Presenters\Admin_Plan_Presenter::class,
            'admin_league_schedule_presenter'   => \Racketmanager\Admin\Presenters\Admin_League_Schedule_Presenter::class,
            'tournament_admin_controller'       => \Racketmanager\Admin\Controllers\Tournament_Admin_Controller::class,
            'admin_display'                     => \Racketmanager\Admin\Admin_Display::class,
            'admin'                             => \Racketmanager\Admin::class,
        ];

        foreach ( $admin_keys as $key => $class ) {
            $this->assertTrue( $container->has( $key ), $key );
            $this->assertInstanceOf( $class, $container->get( $key ), $key );
        }
    }

    public function test_competition_and_tournament_commands_are_wired_into_production_adapters(): void {
        $container = Container_Bootstrap::boot( $this->app );
        $bindings = [
            'submit_cup_entry_handler' => [ 'frontend_ajax_adapter', 'submit_cup_entry_handler' ],
            'submit_league_entry_handler' => [ 'frontend_ajax_adapter', 'submit_league_entry_handler' ],
            'submit_tournament_entry_handler' => [ 'tournament_ajax_adapter', 'submit_tournament_entry_handler' ],
            'withdraw_tournament_entry_handler' => [ 'tournament_ajax_adapter', 'withdraw_tournament_entry_handler' ],
            'submit_tournament_partner_handler' => [ 'tournament_ajax_adapter', 'submit_tournament_partner_handler' ],
            'create_tournament_payment_handler' => [ 'tournament_ajax_adapter', 'create_tournament_payment_handler' ],
            'generate_tournament_bracket_handler' => [ 'draw_action_dispatcher', 'generate_tournament_bracket_handler' ],
        ];

        foreach ( $bindings as $key => [ $adapter_key, $property ] ) {
            $this->assertTrue( $container->has( $key ), $key );
            $this->assertSame( $container->get( $key ), ( new \ReflectionProperty( $container->get( $adapter_key ), $property ) )->getValue( $container->get( $adapter_key ) ), $adapter_key );
        }
        $this->assertSame(
            $container->get( 'generate_tournament_bracket_handler' ),
            ( new \ReflectionProperty( $container->get( 'cup_setup_action_dispatcher' ), 'generate_tournament_bracket_handler' ) )->getValue( $container->get( 'cup_setup_action_dispatcher' ) )
        );
    }

    public function test_player_commands_and_queries_are_wired_into_production_adapters(): void {
        $container = Container_Bootstrap::boot( $this->app );
        $bindings = [
            'update_player_handler'      => [ 'frontend_ajax_adapter', 'update_player_handler' ],
            'search_players_handler'     => [ 'frontend_ajax_adapter', 'search_players_handler' ],
            'get_player_details_handler' => [ 'frontend_ajax_adapter', 'get_player_details_handler' ],
        ];

        foreach ( $bindings as $key => [ $adapter_key, $property ] ) {
            $this->assertTrue( $container->has( $key ), $key );
            $this->assertSame( $container->get( $key ), ( new \ReflectionProperty( $container->get( $adapter_key ), $property ) )->getValue( $container->get( $adapter_key ) ), $adapter_key );
        }

        $this->assertTrue( $container->has( 'create_player_handler' ) );
        $this->assertSame(
            $container->get( 'update_player_handler' ),
            ( new \ReflectionProperty( $container->get( 'player_action_dispatcher' ), 'update_player_handler' ) )->getValue( $container->get( 'player_action_dispatcher' ) )
        );
        $this->assertSame(
            $container->get( 'create_player_handler' ),
            ( new \ReflectionProperty( $container->get( 'player_action_dispatcher' ), 'create_player_handler' ) )->getValue( $container->get( 'player_action_dispatcher' ) )
        );
    }

    public function test_finance_commands_and_queries_are_wired_into_production_adapters(): void {
        $container = Container_Bootstrap::boot( $this->app );
        $bindings = [
            'set_purchase_order_handler'        => [ 'finance_ajax_adapter', 'set_purchase_order_handler' ],
            'get_purchase_order_handler'        => [ 'finance_ajax_adapter', 'get_purchase_order_handler' ],
            'shortcode_view_presenter'          => [ 'finance_ajax_adapter', 'view_presenter' ],
            'generate_charge_invoices_handler'  => [ 'finance_charge_action_dispatcher', 'generate_charge_invoices_handler' ],
            'action_invoice_handler'            => [ 'finance_invoice_action_dispatcher', 'action_invoice_handler' ],
        ];

        foreach ( $bindings as $key => [ $adapter_key, $property ] ) {
            $this->assertTrue( $container->has( $key ), $key );
            $this->assertSame(
                $container->get( $key ),
                ( new \ReflectionProperty( $container->get( $adapter_key ), $property ) )->getValue( $container->get( $adapter_key ) ),
                $adapter_key
            );
        }
    }

    public function test_last_5_handler_is_wired_with_fixture_presenter(): void {
        $container = Container_Bootstrap::boot( $this->app );
        $adapter = $container->get( 'league_shortcode_adapter' );

        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Shortcodes\League_Shortcode_Adapter::class, $adapter );
        $this->assertSame(
            $container->get( 'fixture_presenter' ),
            ( new \ReflectionProperty( \Racketmanager\Infrastructure\Wordpress\Shortcodes\League_Shortcode_Adapter::class, 'fixture_presenter' ) )->getValue( $adapter )
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

    public function test_container_boot_wires_shared_league_query_service_for_five_consumers(): void {
        $container = Container_Bootstrap::boot( $this->app );

        $shared_service = $container->get( 'league_query_service' );
        $this->assertInstanceOf( \Racketmanager\Services\Competition\League_Query_Service::class, $shared_service );

        $consumers = [
            $container->get( 'standings_service' ),
            $container->get( 'knockout_progression_service' ),
            $container->get( 'league_service' ),
            $container->get( 'competition_league_schedule_service' ),
            $container->get( 'get_league_players_handler' ),
        ];

        foreach ( $consumers as $consumer ) {
            $ref = new \ReflectionClass( $consumer );
            $method = $ref->getMethod( 'league_query' );
            $resolved_service = $method->invoke( $consumer );
            $this->assertSame( $shared_service, $resolved_service );
        }
    }

    public function test_competition_provider_wires_competition_schedule_service(): void {
        $container = Container_Bootstrap::boot( $this->app );

        $this->assertTrue( $container->has( 'competition_scheduler' ) );
        $this->assertTrue( $container->has( 'competition_schedule_service' ) );

        $scheduler = $container->get( 'competition_scheduler' );
        $this->assertInstanceOf( \Racketmanager\Domain\Competition\Scheduling\Competition_Scheduler::class, $scheduler );

        $service = $container->get( 'competition_schedule_service' );
        $this->assertInstanceOf( \Racketmanager\Services\Competition\Competition_Schedule_Service::class, $service );
        $this->assertSame( $scheduler, ( new \ReflectionProperty( $service, 'scheduler' ) )->getValue( $service ) );
        $this->assertSame( $container->get( 'fixture_service' ), ( new \ReflectionProperty( $service, 'fixture_service' ) )->getValue( $service ) );
        $this->assertSame( $container->get( 'league_query_service' ), ( new \ReflectionProperty( $service, 'league_query_service' ) )->getValue( $service ) );
        $this->assertSame( $container->get( 'event_query_service' ), ( new \ReflectionProperty( $service, 'event_query_service' ) )->getValue( $service ) );
    }

    public function test_admin_provider_wires_league_schedule_service_to_competition_schedule_service(): void {
        $container = Container_Bootstrap::boot( $this->app );

        $service = $container->get( 'league_schedule_service' );
        $this->assertInstanceOf( \Racketmanager\Services\Admin\League\League_Schedule_Service::class, $service );
        $this->assertSame( $container->get( 'competition_schedule_service' ), ( new \ReflectionProperty( $service, 'competition_schedule_service' ) )->getValue( $service ) );
        $this->assertSame( $container->get( 'event_repository' ), ( new \ReflectionProperty( $service, 'event_repository' ) )->getValue( $service ) );
        $this->assertSame( $container->get( 'league_team_repository' ), ( new \ReflectionProperty( $service, 'league_team_repository' ) )->getValue( $service ) );
        $this->assertSame( $container->get( 'league_query_service' ), ( new \ReflectionProperty( $service, 'league_query_service' ) )->getValue( $service ) );
    }

    public function test_fixture_service_provider_wires_fixture_details_assembler_and_handler(): void {
        $container = Container_Bootstrap::boot( $this->app );

        $this->assertTrue( $container->has( 'fixture_details_assembler' ) );
        $this->assertTrue( $container->has( \Racketmanager\Application\Fixture\Services\Fixture_Details_Assembler::class ) );
        $this->assertTrue( $container->has( 'get_fixture_details_handler' ) );
        $this->assertTrue( $container->has( \Racketmanager\Application\Fixture\Queries\Get_Fixture_Details_Handler::class ) );

        $service = $container->get( 'fixture_details_assembler' );
        $this->assertInstanceOf( \Racketmanager\Application\Fixture\Services\Fixture_Details_Assembler::class, $service );

        $handler = $container->get( 'get_fixture_details_handler' );
        $this->assertInstanceOf( \Racketmanager\Application\Fixture\Queries\Get_Fixture_Details_Handler::class, $handler );
    }
}
