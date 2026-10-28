<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Container;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Account\Ports\User_Favourites_Gateway_Interface;
use Racketmanager\Application\Mail\Mailer_Interface;
use Racketmanager\Application\Payment\Ports\Payment_Gateway_Interface;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Interfaces\Result_Repository_Interface;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Fixture\Fixture_Lifecycle_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

class Container_Bootstrap_Services_Test extends TestCase {
    use Manages_Wordpress_Globals;
    private Simple_Container $container;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $wpdb_mock = $this->createStub( wpdb::class );
        $wpdb_mock->prefix = 'wp_';
        $GLOBALS['wpdb'] = $wpdb_mock;

        $app_mock = $this->createStub( RacketManager::class );
        $app_mock->method( 'get_options' )->willReturn( [] );
        $app_mock->site_url = 'https://example.com';
        $app_mock->site_name = 'Test Site';

        $this->container = Container_Bootstrap::boot( $app_mock );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_container_resolves_result_repository(): void {
        $this->assertTrue( $this->container->has( 'result_repository' ) );
        $repo = $this->container->get( 'result_repository' );
        $this->assertInstanceOf( Result_Repository_Interface::class, $repo );
    }

    public function test_container_resolves_fixture_lifecycle_service(): void {
        $this->assertTrue( $this->container->has( 'fixture_lifecycle_service' ) );
        $service = $this->container->get( 'fixture_lifecycle_service' );
        $this->assertInstanceOf( Fixture_Lifecycle_Service::class, $service );
    }

    public function test_container_resolves_fixture_result_manager_first_and_wires_maintenance(): void {
        $app_mock = $this->createStub( RacketManager::class );
        $app_mock->method( 'get_options' )->willReturn( [] );
        $c = Container_Bootstrap::boot( $app_mock );

        $manager = $c->get( 'fixture_result_manager' );
        $this->assertInstanceOf( \Racketmanager\Services\Fixture\Fixture_Result_Manager::class, $manager );
        $this->assertInstanceOf( \Racketmanager\Services\Fixture\Fixture_Maintenance_Service::class, $manager->get_fixture_maintenance_service() );

        $maintenance = $c->get( 'fixture_maintenance_service' );
        $this->assertSame( $manager->get_fixture_maintenance_service(), $maintenance );
    }

    public function test_container_resolves_fixture_maintenance_service_first_and_shares_instances(): void {
        $app_mock = $this->createStub( RacketManager::class );
        $app_mock->method( 'get_options' )->willReturn( [] );
        $c = Container_Bootstrap::boot( $app_mock );

        $maintenance = $c->get( 'fixture_maintenance_service' );
        $this->assertInstanceOf( \Racketmanager\Services\Fixture\Fixture_Maintenance_Service::class, $maintenance );

        $manager = $c->get( 'fixture_result_manager' );
        $this->assertInstanceOf( \Racketmanager\Services\Fixture\Fixture_Result_Manager::class, $manager );
        $this->assertSame( $maintenance, $manager->get_fixture_maintenance_service() );
    }

    public function test_fixture_repository_and_permission_service_resolve_without_cycle(): void {
        $repository = $this->container->get( 'fixture_repository' );
        $this->assertInstanceOf( Fixture_Repository::class, $repository );

        $service = $this->container->get( 'fixture_permission_service' );
        $this->assertInstanceOf( Fixture_Permission_Service::class, $service );
    }

    public function test_league_repository_and_fixture_repository_resolve_without_cycle(): void {
        $league_repository = $this->container->get( 'league_repository' );
        $this->assertInstanceOf( League_Repository::class, $league_repository );

        $fixture_repository = $this->container->get( 'fixture_repository' );
        $this->assertInstanceOf( Fixture_Repository::class, $fixture_repository );

        $this->assertSame( $league_repository, $this->container->get( 'league_repository' ) );
        $this->assertSame( $fixture_repository, $this->container->get( 'fixture_repository' ) );
    }

    public function test_team_service_and_fixture_repository_resolve_without_cycle(): void {
        $team_service = $this->container->get( 'team_service' );
        $this->assertInstanceOf( \Racketmanager\Services\Team_Service::class, $team_service );

        $fixture_repository = $this->container->get( 'fixture_repository' );
        $this->assertInstanceOf( Fixture_Repository::class, $fixture_repository );
    }

    public function test_fixture_repository_and_team_service_resolve_without_cycle_reverse_order(): void {
        $fixture_repository = $this->container->get( 'fixture_repository' );
        $this->assertInstanceOf( Fixture_Repository::class, $fixture_repository );

        $team_service = $this->container->get( 'team_service' );
        $this->assertInstanceOf( \Racketmanager\Services\Team_Service::class, $team_service );
    }

    public function test_container_resolves_player_query_and_stats_services(): void {
        $this->assertTrue( $this->container->has( 'player_query_service' ) );
        $query_service = $this->container->get( 'player_query_service' );
        $this->assertInstanceOf( Player_Query_Service::class, $query_service );

        $this->assertTrue( $this->container->has( 'player_stats_service' ) );
        $stats_service = $this->container->get( 'player_stats_service' );
        $this->assertInstanceOf( Player_Stats_Service::class, $stats_service );
    }

    public function test_container_resolves_gateways_and_services_without_cycle(): void {
        $this->assertTrue( $this->container->has( 'mailer' ) );
        $this->assertInstanceOf( Mailer_Interface::class, $this->container->get( 'mailer' ) );
        $this->assertTrue( $this->container->has( Mailer_Interface::class ) );
        $this->assertSame( $this->container->get( 'mailer' ), $this->container->get( Mailer_Interface::class ) );

        $this->assertTrue( $this->container->has( 'payment_gateway' ) );
        $this->assertInstanceOf( Payment_Gateway_Interface::class, $this->container->get( 'payment_gateway' ) );
        $this->assertTrue( $this->container->has( Payment_Gateway_Interface::class ) );
        $this->assertSame( $this->container->get( 'payment_gateway' ), $this->container->get( Payment_Gateway_Interface::class ) );

        $this->assertTrue( $this->container->has( 'option_gateway' ) );
        $this->assertInstanceOf( Option_Gateway_Interface::class, $this->container->get( 'option_gateway' ) );
        $this->assertTrue( $this->container->has( Option_Gateway_Interface::class ) );
        $this->assertSame( $this->container->get( 'option_gateway' ), $this->container->get( Option_Gateway_Interface::class ) );

        $this->assertTrue( $this->container->has( 'user_favourites_gateway' ) );
        $this->assertInstanceOf( User_Favourites_Gateway_Interface::class, $this->container->get( 'user_favourites_gateway' ) );
        $this->assertTrue( $this->container->has( User_Favourites_Gateway_Interface::class ) );
        $this->assertSame( $this->container->get( 'user_favourites_gateway' ), $this->container->get( User_Favourites_Gateway_Interface::class ) );

        $this->assertTrue( $this->container->has( 'finance_service' ) );
        $this->assertInstanceOf( Finance_Service::class, $this->container->get( 'finance_service' ) );

        $this->assertTrue( $this->container->has( 'tournament_service' ) );
        $this->assertInstanceOf( Tournament_Service::class, $this->container->get( 'tournament_service' ) );

        $this->assertTrue( $this->container->has( 'competition_entry_service' ) );
        $this->assertInstanceOf( Competition_Entry_Service::class, $this->container->get( 'competition_entry_service' ) );

        $this->assertTrue( $this->container->has( 'notification_service' ) );
        $this->assertInstanceOf( Notification_Service::class, $this->container->get( 'notification_service' ) );
    }

    public function test_container_resolves_admin_delivery_adapters_and_league_contact_dispatcher(): void {
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface::class, $this->container->get( 'admin_redirector' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter::class, $this->container->get( 'admin_action_adapter' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter::class, $this->container->get( 'admin_screen_presenter' ) );
        $this->assertInstanceOf( \Racketmanager\Services\Admin\League\League_Contact_Action_Dispatcher::class, $this->container->get( 'league_contact_action_dispatcher' ) );
        $this->assertInstanceOf( \Racketmanager\Services\Admin\League\League_Setup_Action_Dispatcher::class, $this->container->get( 'league_setup_action_dispatcher' ) );
        $this->assertInstanceOf( \Racketmanager\Services\Admin\League\Event_Setup_Action_Dispatcher::class, $this->container->get( 'event_setup_action_dispatcher' ) );
        $this->assertInstanceOf( \Racketmanager\Services\Admin\League\Season_Delete_Action_Dispatcher::class, $this->container->get( 'season_delete_action_dispatcher' ) );
        $this->assertInstanceOf( \Racketmanager\Services\Admin\League\League_Team_Action_Dispatcher::class, $this->container->get( 'league_team_action_dispatcher' ) );
        $this->assertInstanceOf( \Racketmanager\Services\Admin\League\League_Match_Action_Dispatcher::class, $this->container->get( 'league_match_action_dispatcher' ) );
        $this->assertInstanceOf( \Racketmanager\Services\Admin\Result\Results_Checker_Action_Dispatcher::class, $this->container->get( 'results_checker_action_dispatcher' ) );
    }

    public function test_container_resolves_index_action_dispatcher(): void {
        $this->assertInstanceOf( \Racketmanager\Services\Admin\Index\Index_Action_Dispatcher_Interface::class, $this->container->get( 'index_action_dispatcher' ) );
    }

    public function test_container_resolves_options_action_dispatcher(): void {
        $this->assertInstanceOf( \Racketmanager\Services\Admin\Options\Options_Action_Dispatcher_Interface::class, $this->container->get( 'options_action_dispatcher' ) );
    }

    public function test_container_resolves_rating_and_export_secondary_gateways(): void {
        $this->assertTrue( $this->container->has( 'rating_gateway' ) );
        $this->assertInstanceOf( \Racketmanager\Domain\Ports\Rating_Gateway_Interface::class, $this->container->get( 'rating_gateway' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Gateways\Rating\Lta_Wtn_Gateway_Adapter::class, $this->container->get( 'rating_gateway' ) );
        $this->assertTrue( $this->container->has( \Racketmanager\Domain\Ports\Rating_Gateway_Interface::class ) );
        $this->assertSame( $this->container->get( 'rating_gateway' ), $this->container->get( \Racketmanager\Domain\Ports\Rating_Gateway_Interface::class ) );

        $this->assertTrue( $this->container->has( 'wtn_api_client' ) );
        $this->assertInstanceOf( \Racketmanager\Domain\Ports\Rating_Gateway_Interface::class, $this->container->get( 'wtn_api_client' ) );

        $this->assertTrue( $this->container->has( 'exporter' ) );
        $this->assertInstanceOf( \Racketmanager\Application\Export\Ports\Data_Exporter_Interface::class, $this->container->get( 'exporter' ) );
        $this->assertInstanceOf( \Racketmanager\Services\Contracts\Data_Exporter_Interface::class, $this->container->get( 'exporter' ) );
        $this->assertTrue( $this->container->has( \Racketmanager\Application\Export\Ports\Data_Exporter_Interface::class ) );
        $this->assertSame( $this->container->get( 'exporter' ), $this->container->get( \Racketmanager\Application\Export\Ports\Data_Exporter_Interface::class ) );

        $this->assertTrue( $this->container->has( 'export_delivery_adapter' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Export\Export_Delivery_Adapter::class, $this->container->get( 'export_delivery_adapter' ) );

        $this->assertTrue( $this->container->has( 'rest_controller_adapter' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Rest\Rest_Controller_Adapter::class, $this->container->get( 'rest_controller_adapter' ) );
    }

    public function test_match_presenters_carry_the_shared_team_match_row_presenter(): void {
        $row_presenter = $this->container->get( 'team_match_row_presenter' );
        $this->assertInstanceOf( \Racketmanager\Presentation\Presenters\Team_Match_Row_Presenter::class, $row_presenter );

        foreach ( [ 'tournament_presenter', 'fixture_presenter' ] as $id ) {
            $presenter = $this->container->get( $id );
            $property  = new \ReflectionProperty( $presenter, 'team_match_row_presenter' );
            $this->assertSame( $row_presenter, $property->getValue( $presenter ), $id . ' must be wired with the row presenter.' );
        }
    }

    public function test_container_resolves_matches_shortcode_query_chain(): void {
        $this->assertInstanceOf( \Racketmanager\Application\Matches\Ports\Fixture_Listing_Finder_Interface::class, $this->container->get( 'fixture_listing_finder' ) );
        $this->assertInstanceOf( \Racketmanager\Application\Matches\Queries\Get_Daily_Matches_Handler::class, $this->container->get( 'get_daily_matches_handler' ) );
        $this->assertInstanceOf( \Racketmanager\Application\Matches\Queries\Get_Latest_Results_Handler::class, $this->container->get( 'get_latest_results_handler' ) );
        $this->assertInstanceOf( \Racketmanager\Presentation\Presenters\Matches_List_Presenter::class, $this->container->get( 'matches_list_presenter' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Shortcodes\Matches_Shortcode_Adapter::class, $this->container->get( 'matches_shortcode_adapter' ) );
    }

    public function test_container_wires_match_and_club_shortcode_adapters_with_required_dependencies(): void {
        $this->assertInstanceOf( \Racketmanager\Services\View\Shortcode_Template_Renderer::class, $this->container->get( 'shortcode_template_renderer' ) );
        $this->assertInstanceOf( \Racketmanager\Presentation\Presenters\Shortcode_View_Presenter::class, $this->container->get( 'shortcode_view_presenter' ) );
        $this->assertInstanceOf( \Racketmanager\Application\Matches\Queries\Get_Match_Score_Handler::class, $this->container->get( 'get_match_score_handler' ) );
        $this->assertInstanceOf( \Racketmanager\Application\Matches\Queries\Get_Match_Header_Handler::class, $this->container->get( 'get_match_header_handler' ) );
        $this->assertInstanceOf( \Racketmanager\Application\Matches\Queries\Get_Match_Detail_Handler::class, $this->container->get( 'get_match_detail_handler' ) );
        $this->assertInstanceOf( \Racketmanager\Presentation\Presenters\Match_Shortcode_Presenter::class, $this->container->get( 'match_shortcode_presenter' ) );
        foreach ( [ 'get_club_players_handler', 'get_club_roles_handler', 'get_club_role_modal_handler', 'get_club_competitions_handler', 'get_club_invoices_handler' ] as $id ) {
            $this->assertTrue( $this->container->has( $id ), $id );
            $this->assertIsObject( $this->container->get( $id ), $id );
        }
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Shortcodes\Club_Shortcode_Adapter::class, $this->container->get( 'club_shortcode_adapter' ) );
    }

    public function test_container_resolves_club_and_tournament_ajax_controllers(): void {
        $this->assertTrue( $this->container->has( 'security_service' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Security\Security_Service_Interface::class, $this->container->get( 'security_service' ) );

        $this->assertTrue( $this->container->has( 'json_response_factory' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface::class, $this->container->get( 'json_response_factory' ) );

        $this->assertTrue( $this->container->has( 'fixture_ajax_adapter' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Adapter::class, $this->container->get( 'fixture_ajax_adapter' ) );
        $this->assertTrue( $this->container->has( 'fixture_ajax_controller' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Controller::class, $this->container->get( 'fixture_ajax_controller' ) );

        $this->assertTrue( $this->container->has( 'club_ajax_adapter' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Ajax\Club_Ajax_Adapter::class, $this->container->get( 'club_ajax_adapter' ) );
        $this->assertTrue( $this->container->has( 'club_ajax_controller' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Ajax\Club_Ajax_Controller::class, $this->container->get( 'club_ajax_controller' ) );

        $this->assertTrue( $this->container->has( 'tournament_ajax_adapter' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Ajax\Tournament_Ajax_Adapter::class, $this->container->get( 'tournament_ajax_adapter' ) );
        $this->assertTrue( $this->container->has( 'tournament_ajax_controller' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Ajax\Tournament_Ajax_Controller::class, $this->container->get( 'tournament_ajax_controller' ) );

        $this->assertTrue( $this->container->has( 'account_ajax_adapter' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Adapter::class, $this->container->get( 'account_ajax_adapter' ) );
        $this->assertTrue( $this->container->has( 'account_ajax_controller' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Controller::class, $this->container->get( 'account_ajax_controller' ) );

        $this->assertTrue( $this->container->has( 'finance_ajax_adapter' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Ajax\Finance_Ajax_Adapter::class, $this->container->get( 'finance_ajax_adapter' ) );
        $this->assertTrue( $this->container->has( 'finance_ajax_controller' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Ajax\Finance_Ajax_Controller::class, $this->container->get( 'finance_ajax_controller' ) );

        $this->assertTrue( $this->container->has( 'frontend_ajax_adapter' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Ajax\Frontend_Ajax_Adapter::class, $this->container->get( 'frontend_ajax_adapter' ) );
        $this->assertTrue( $this->container->has( 'frontend_ajax_controller' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Ajax\Frontend_Ajax_Controller::class, $this->container->get( 'frontend_ajax_controller' ) );

        $this->assertTrue( $this->container->has( 'admin_ajax_adapter' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Ajax\Admin_Ajax_Adapter::class, $this->container->get( 'admin_ajax_adapter' ) );
        $this->assertTrue( $this->container->has( 'admin_ajax_controller' ) );
        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Ajax\Admin_Ajax_Controller::class, $this->container->get( 'admin_ajax_controller' ) );
    }
    public function test_container_resolves_tournament_shortcode_components(): void {
        $handlers = [
            'get_tournament_handler',
            'get_tournament_overview_handler',
            'get_tournament_events_handler',
            'get_tournament_draws_handler',
            'get_tournament_players_handler',
            'get_tournament_winners_handler',
            'get_tournament_matches_handler',
            'get_tournament_match_handler',
            'get_order_of_play_handler',
            'get_latest_tournament_handler',
            'get_tournament_withdrawal_handler'
        ];

        foreach ($handlers as $handler_id) {
            $this->assertTrue($this->container->has($handler_id), "Handler $handler_id not found in container");
            $this->assertIsObject($this->container->get($handler_id), "Handler $handler_id is not an object");
        }

        $this->assertInstanceOf(\Racketmanager\Infrastructure\Wordpress\Shortcodes\Tournament_Shortcode_Adapter::class, $this->container->get('tournament_shortcode_adapter'));
    }

    public function test_container_resolves_login_shortcode_components(): void {
        $handlers = [
            'get_login_form_handler',
            'get_password_lost_form_handler',
            'get_password_reset_form_handler',
            'get_member_account_form_handler',
        ];

        foreach ( $handlers as $handler_id ) {
            $this->assertTrue( $this->container->has( $handler_id ), "Handler $handler_id not found in container" );
            $this->assertIsObject( $this->container->get( $handler_id ), "Handler $handler_id is not an object" );
        }

        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Shortcodes\Login_Shortcode_Adapter::class, $this->container->get( 'login_shortcode_adapter' ) );
    }

    public function test_container_resolves_message_shortcode_components(): void {
        $this->assertTrue( $this->container->has( 'message_repository' ) );
        $this->assertInstanceOf( \Racketmanager\Repositories\Interfaces\Message_Repository_Interface::class, $this->container->get( 'message_repository' ) );
        $this->assertTrue( $this->container->has( 'user_repository' ) );
        $this->assertInstanceOf( \Racketmanager\Repositories\Interfaces\User_Repository_Interface::class, $this->container->get( 'user_repository' ) );

        $handlers = [
            'get_messages_handler',
            'get_message_handler',
        ];

        foreach ( $handlers as $handler_id ) {
            $this->assertTrue( $this->container->has( $handler_id ), "Handler $handler_id not found in container" );
            $this->assertIsObject( $this->container->get( $handler_id ), "Handler $handler_id is not an object" );
        }

        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Shortcodes\Message_Shortcode_Adapter::class, $this->container->get( 'message_shortcode_adapter' ) );
    }

    public function test_container_resolves_general_shortcode_components(): void {
        $handlers = [
            'get_players_handler',
            'get_player_handler',
            'get_favourites_handler',
            'get_invoice_handler',
            'get_purchase_order_handler',
            'get_memberships_handler',
            'get_player_search_handler',
            'get_team_order_handler',
            'get_alert_handler',
        ];

        foreach ( $handlers as $handler_id ) {
            $this->assertTrue( $this->container->has( $handler_id ), "Handler $handler_id not found in container" );
            $this->assertIsObject( $this->container->get( $handler_id ), "Handler $handler_id is not an object" );
        }

        $this->assertInstanceOf( \Racketmanager\Infrastructure\Wordpress\Shortcodes\General_Shortcode_Adapter::class, $this->container->get( 'general_shortcode_adapter' ) );
        $this->assertTrue( $this->container->has( 'championship_manager' ) );
        $this->assertInstanceOf( \Racketmanager\Services\Championship_Manager::class, $this->container->get( 'championship_manager' ) );
    }
}
