<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Container;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Account\Ports\User_Favourites_Gateway_Interface;
use Racketmanager\Application\Export\Ports\Data_Exporter_Interface;
use Racketmanager\Application\General\Queries\Get_Alert_Handler;
use Racketmanager\Application\Login\Queries\Get_Password_Lost_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Password_Reset_Form_Handler;
use Racketmanager\Application\Login\Services\Auth_Service;
use Racketmanager\Application\Mail\Mailer_Interface;
use Racketmanager\Application\Payment\Ports\Payment_Gateway_Interface;
use Racketmanager\Application\Ports\Template_Renderer_Interface;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Domain\Events\Domain_Event_Dispatcher_Interface;
use Racketmanager\Domain\Ports\Clock_Interface;
use Racketmanager\Domain\Ports\Rating_Gateway_Interface;
use Racketmanager\Infrastructure\Events\Simple_Domain_Event_Dispatcher;
use Racketmanager\Infrastructure\Gateways\Account\Wp_User_Favourites_Gateway;
use Racketmanager\Infrastructure\Gateways\Mail\Wp_Mailer;
use Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway;
use Racketmanager\Infrastructure\Gateways\Payment\Stripe_Payment_Gateway;
use Racketmanager\Infrastructure\Gateways\Rating\Lta_Wtn_Gateway_Adapter;
use Racketmanager\Infrastructure\Security\Security_Service;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Time\Wordpress_Clock;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Ajax\Ajax_Registry;
use Racketmanager\Infrastructure\Wordpress\Ajax\Finance_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Finance_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Assets\Asset_Manager;
use Racketmanager\Infrastructure\Wordpress\Auth\Auth_Email_Filter_Adapter;
use Racketmanager\Infrastructure\Wordpress\Auth\Login_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Auth\Password_Reset_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Bootstrap\Plugin_Bootstrap;
use Racketmanager\Infrastructure\Wordpress\Component\Component_Coordinator;
use Racketmanager\Infrastructure\Wordpress\Component\Component_Coordinator_Interface;
use Racketmanager\Infrastructure\Wordpress\Export\Export_Delivery_Adapter;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory;
use Racketmanager\Infrastructure\Wordpress\Rest\Rest_Controller_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Email_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\General_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Login_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Message_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Shortcode_Registrar;
use Racketmanager\Infrastructure\Wordpress\Rewrites\Rewrite_Rules;
use Racketmanager\Infrastructure\Wordpress\Templates\Template_Loader;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Charge_Repository;
use Racketmanager\Repositories\Interfaces\Charge_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Invoice_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Season_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Repositories\Invoice_Repository;
use Racketmanager\Repositories\Message_Repository;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Repositories\Season_Repository;
use Racketmanager\Repositories\User_Repository;
use Racketmanager\Services\Container\Providers\Core_Service_Provider;
use Racketmanager\Services\Container\Providers\Service_Provider_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Export\Export_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Mail\Mail_Filter_Service;
use Racketmanager\Services\Mail\Mail_Filter_Service_Interface;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Notify_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Seo\Page_Title_Service;
use Racketmanager\Services\Seo\Page_Title_Service_Interface;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionProperty;
use TypeError;
use wpdb;

class Core_Service_ProviderTest extends TestCase {
    use Manages_Wordpress_Globals;

    private RacketManager $app;
    private Core_Service_Provider $provider;
    private Simple_Container $container;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $wpdb_mock = $this->createStub( wpdb::class );
        $wpdb_mock->prefix = 'wp_';
        $GLOBALS['wpdb'] = $wpdb_mock; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

        $this->app = $this->createStub( RacketManager::class );
        $this->app->method( 'get_options' )->willReturn( [] );
        $this->app->site_url = 'https://example.com';
        $this->app->site_name = 'Test Site';

        $this->provider  = new Core_Service_Provider( $this->app );
        $this->container = new Simple_Container();
        $this->provider->register( $this->container );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_implements_service_provider_interface(): void {
        $this->assertInstanceOf( Service_Provider_Interface::class, $this->provider );
    }

    public function test_construct_with_null_and_app_instance(): void {
        $null_provider  = new Core_Service_Provider( null );
        $null_container = new Simple_Container();
        $null_provider->register( $null_container );

        $this->assertTrue( $null_container->has( 'racketmanager_app' ) );
        $this->assertTrue( $null_container->has_definition( 'racketmanager_app' ) );

        $app_provider  = new Core_Service_Provider( $this->app );
        $app_container = new Simple_Container();
        $app_provider->register( $app_container );

        $this->assertTrue( $app_container->has( 'racketmanager_app' ) );
        $this->assertSame( $this->app, $app_container->get( 'racketmanager_app' ) );
    }

    public function test_get_racketmanager_app_when_null_throws_type_error(): void {
        $null_provider  = new Core_Service_Provider( null );
        $null_container = new Simple_Container();
        $null_provider->register( $null_container );

        $this->expectException( TypeError::class );
        $null_container->get( 'racketmanager_app' );
    }

    public function test_registers_gateways_and_infrastructure_keys_and_aliases(): void {
        $this->assertTrue( $this->container->has( 'domain_event_dispatcher' ) );
        $this->assertTrue( $this->container->has( Domain_Event_Dispatcher_Interface::class ) );
        $this->assertTrue( $this->container->has( Simple_Domain_Event_Dispatcher::class ) );

        $this->assertTrue( $this->container->has( 'rating_gateway' ) );
        $this->assertTrue( $this->container->has( Rating_Gateway_Interface::class ) );
        $this->assertTrue( $this->container->has( Lta_Wtn_Gateway_Adapter::class ) );
        $this->assertTrue( $this->container->has( 'wtn_api_client' ) );

        $this->assertTrue( $this->container->has( 'mailer' ) );
        $this->assertTrue( $this->container->has( Mailer_Interface::class ) );
        $this->assertTrue( $this->container->has( Wp_Mailer::class ) );

        $this->assertTrue( $this->container->has( 'clock' ) );
        $this->assertTrue( $this->container->has( Clock_Interface::class ) );
        $this->assertTrue( $this->container->has( Wordpress_Clock::class ) );

        $this->assertTrue( $this->container->has( 'option_gateway' ) );
        $this->assertTrue( $this->container->has( Option_Gateway_Interface::class ) );
        $this->assertTrue( $this->container->has( Wp_Option_Gateway::class ) );

        $this->assertTrue( $this->container->has( 'option_service' ) );
        $this->assertTrue( $this->container->has( Option_Service::class ) );

        $this->assertTrue( $this->container->has( 'asset_manager' ) );
        $this->assertTrue( $this->container->has( Asset_Manager::class ) );

        $this->assertTrue( $this->container->has( 'template_loader' ) );
        $this->assertTrue( $this->container->has( Template_Loader::class ) );

        $this->assertTrue( $this->container->has( 'plugin_bootstrap' ) );
        $this->assertTrue( $this->container->has( Plugin_Bootstrap::class ) );

        $this->assertTrue( $this->container->has( 'shortcode_registrar' ) );
        $this->assertTrue( $this->container->has( Shortcode_Registrar::class ) );

        $this->assertTrue( $this->container->has( Rewrite_Rules::class ) );
        $this->assertInstanceOf( Rewrite_Rules::class, $this->container->get( Rewrite_Rules::class ) );

        $this->assertTrue( $this->container->has( 'payment_gateway' ) );
        $this->assertTrue( $this->container->has( Payment_Gateway_Interface::class ) );
        $this->assertTrue( $this->container->has( Stripe_Payment_Gateway::class ) );

        $this->assertTrue( $this->container->has( 'user_favourites_gateway' ) );
        $this->assertTrue( $this->container->has( User_Favourites_Gateway_Interface::class ) );
        $this->assertTrue( $this->container->has( Wp_User_Favourites_Gateway::class ) );
    }

    public function test_registers_core_services_keys_and_aliases(): void {
        $this->assertTrue( $this->container->has( 'racketmanager_app' ) );

        $this->assertTrue( $this->container->has( 'settings_service' ) );
        $this->assertTrue( $this->container->has( Settings_Service::class ) );

        $this->assertTrue( $this->container->has( 'security_service' ) );
        $this->assertTrue( $this->container->has( Security_Service_Interface::class ) );
        $this->assertTrue( $this->container->has( Security_Service::class ) );

        $this->assertTrue( $this->container->has( 'json_response_factory' ) );
        $this->assertTrue( $this->container->has( Json_Response_Factory::class ) );

        $this->assertTrue( $this->container->has( 'ajax_registry' ) );
        $this->assertTrue( $this->container->has( Ajax_Registry::class ) );

        $this->assertTrue( $this->container->has( 'view_renderer' ) );
        $this->assertTrue( $this->container->has( View_Renderer_Interface::class ) );
        $this->assertTrue( $this->container->has( Php_View_Renderer::class ) );

        $this->assertTrue( $this->container->has( 'page_title_service' ) );
        $this->assertTrue( $this->container->has( Page_Title_Service::class ) );
        $this->assertTrue( $this->container->has( Page_Title_Service_Interface::class ) );

        $this->container->set( 'shortcode_template_renderer', $this->createStub( Template_Renderer_Interface::class ) );
        $this->assertTrue( $this->container->has( Auth_Service::class ) );
        $this->assertInstanceOf( Auth_Service::class, $this->container->get( Auth_Service::class ) );
        $this->assertTrue( $this->container->has( Password_Reset_Action_Adapter::class ) );
        $this->assertInstanceOf( Password_Reset_Action_Adapter::class, $this->container->get( Password_Reset_Action_Adapter::class ) );
        $this->assertTrue( $this->container->has( Auth_Email_Filter_Adapter::class ) );
        $this->assertInstanceOf( Auth_Email_Filter_Adapter::class, $this->container->get( Auth_Email_Filter_Adapter::class ) );
        $this->assertTrue( $this->container->has( Login_Action_Adapter::class ) );
        $this->assertInstanceOf( Login_Action_Adapter::class, $this->container->get( Login_Action_Adapter::class ) );

        $this->assertTrue( $this->container->has( 'mail_filter_service' ) );
        $this->assertTrue( $this->container->has( Mail_Filter_Service::class ) );
        $this->assertTrue( $this->container->has( Mail_Filter_Service_Interface::class ) );

        $this->assertTrue( $this->container->has( 'component_coordinator' ) );
        $this->assertTrue( $this->container->has( Component_Coordinator::class ) );
        $this->assertTrue( $this->container->has( Component_Coordinator_Interface::class ) );
    }

    public function test_login_adapter_receives_shared_subadapters_and_registers_twenty_hooks_once(): void {
        $GLOBALS['wp_stubs_added_actions'] = array();
        $GLOBALS['wp_stubs_added_filters'] = array();

        $this->container->set( 'shortcode_template_renderer', $this->createStub( Template_Renderer_Interface::class ) );

        $pw_adapter    = $this->container->get( Password_Reset_Action_Adapter::class );
        $email_adapter = $this->container->get( Auth_Email_Filter_Adapter::class );

        // Subadapters alone do not register hooks on construction.
        $this->assertEmpty( $GLOBALS['wp_stubs_added_actions'] );
        $this->assertEmpty( $GLOBALS['wp_stubs_added_filters'] );

        $login_adapter = $this->container->get( Login_Action_Adapter::class );

        $pw_prop = new ReflectionProperty( Login_Action_Adapter::class, 'password_reset_adapter' );
        $pw_prop->setAccessible( true );
        $this->assertSame( $pw_adapter, $pw_prop->getValue( $login_adapter ) );

        $email_prop = new ReflectionProperty( Login_Action_Adapter::class, 'email_filter_adapter' );
        $email_prop->setAccessible( true );
        $this->assertSame( $email_adapter, $email_prop->getValue( $login_adapter ) );

        $action_count = array_sum( array_map( 'count', $GLOBALS['wp_stubs_added_actions'] ) );
        $filter_count = array_sum( array_map( 'count', $GLOBALS['wp_stubs_added_filters'] ) );

        $this->assertSame( 12, $action_count );
        $this->assertSame( 8, $filter_count );
        $this->assertSame( 20, $action_count + $filter_count );

        // Resolving again or calling subadapters does not duplicate hooks.
        $this->container->get( Login_Action_Adapter::class );
        $pw_adapter->register_hooks();
        $email_adapter->register_hooks();

        $action_count_after = array_sum( array_map( 'count', $GLOBALS['wp_stubs_added_actions'] ) );
        $filter_count_after = array_sum( array_map( 'count', $GLOBALS['wp_stubs_added_filters'] ) );

        $this->assertSame( 12, $action_count_after );
        $this->assertSame( 8, $filter_count_after );
    }

    public function test_registers_repository_keys_and_aliases(): void {
        $this->assertTrue( $this->container->has( 'season_repository' ) );
        $this->assertTrue( $this->container->has( Season_Repository_Interface::class ) );
        $this->assertTrue( $this->container->has( Season_Repository::class ) );

        $this->assertTrue( $this->container->has( 'charge_repository' ) );
        $this->assertTrue( $this->container->has( Charge_Repository_Interface::class ) );
        $this->assertTrue( $this->container->has( Charge_Repository::class ) );

        $this->assertTrue( $this->container->has( 'invoice_repository' ) );
        $this->assertTrue( $this->container->has( Invoice_Repository_Interface::class ) );
        $this->assertTrue( $this->container->has( Invoice_Repository::class ) );

        $this->assertTrue( $this->container->has( 'user_repository' ) );
        $this->assertTrue( $this->container->has( User_Repository_Interface::class ) );
        $this->assertTrue( $this->container->has( User_Repository::class ) );

        $this->assertTrue( $this->container->has( 'message_repository' ) );
        $this->assertTrue( $this->container->has( Message_Repository_Interface::class ) );
        $this->assertTrue( $this->container->has( Message_Repository::class ) );

        $this->assertTrue( $this->container->has( 'repository_provider' ) );
        $this->assertTrue( $this->container->has( Repository_Provider::class ) );
    }

    public function test_registers_services_and_exports_keys_and_aliases(): void {
        $this->assertTrue( $this->container->has( 'season_service' ) );
        $this->assertTrue( $this->container->has( Season_Service::class ) );

        $this->assertTrue( $this->container->has( 'finance_service' ) );
        $this->assertTrue( $this->container->has( Finance_Service::class ) );

        $this->assertTrue( $this->container->has( 'notify_service' ) );
        $this->assertTrue( $this->container->has( Notify_Service::class ) );

        $this->assertTrue( $this->container->has( 'notification_service' ) );
        $this->assertTrue( $this->container->has( Notification_Service::class ) );

        $this->assertTrue( $this->container->has( 'exporter' ) );
        $this->assertTrue( $this->container->has( Data_Exporter_Interface::class ) );
        $this->assertTrue( $this->container->has( Export_Service::class ) );

        $this->assertTrue( $this->container->has( 'rest_controller_adapter' ) );
        $this->assertTrue( $this->container->has( Rest_Controller_Adapter::class ) );

        $this->assertTrue( $this->container->has( 'export_delivery_adapter' ) );
        $this->assertTrue( $this->container->has( Export_Delivery_Adapter::class ) );

        $this->assertTrue( $this->container->has( 'export_admin_controller' ) );
    }

    public function test_registers_ajax_controllers_keys_and_aliases(): void {
        $this->assertTrue( $this->container->has( 'account_ajax_adapter' ) );
        $this->assertTrue( $this->container->has( Account_Ajax_Adapter::class ) );

        $this->assertTrue( $this->container->has( 'account_ajax_controller' ) );
        $this->assertTrue( $this->container->has( Account_Ajax_Controller::class ) );

        $this->assertTrue( $this->container->has( 'set_purchase_order_handler' ) );

        $this->assertTrue( $this->container->has( 'finance_ajax_adapter' ) );
        $this->assertTrue( $this->container->has( Finance_Ajax_Adapter::class ) );

        $this->assertTrue( $this->container->has( 'finance_ajax_controller' ) );
        $this->assertTrue( $this->container->has( Finance_Ajax_Controller::class ) );
    }

    public function test_registers_shortcodes_and_handlers_keys_and_aliases(): void {
        $this->assertTrue( $this->container->has( 'email_shortcode_adapter' ) );
        $this->assertTrue( $this->container->has( Email_Shortcode_Adapter::class ) );

        $this->assertTrue( $this->container->has( 'get_login_form_handler' ) );
        $this->assertTrue( $this->container->has( 'get_password_lost_form_handler' ) );
        $this->assertTrue( $this->container->has( 'get_password_reset_form_handler' ) );
        $this->assertTrue( $this->container->has( 'get_member_account_form_handler' ) );

        $this->assertTrue( $this->container->has( 'login_shortcode_adapter' ) );
        $this->assertTrue( $this->container->has( Login_Shortcode_Adapter::class ) );

        $this->assertTrue( $this->container->has( 'get_messages_handler' ) );
        $this->assertTrue( $this->container->has( 'get_message_handler' ) );

        $this->assertTrue( $this->container->has( 'message_shortcode_adapter' ) );
        $this->assertTrue( $this->container->has( Message_Shortcode_Adapter::class ) );

        $this->assertTrue( $this->container->has( 'get_favourites_handler' ) );
        $this->assertTrue( $this->container->has( 'get_invoice_handler' ) );
        $this->assertTrue( $this->container->has( 'get_purchase_order_handler' ) );
        $this->assertTrue( $this->container->has( 'get_memberships_handler' ) );
        $this->assertTrue( $this->container->has( 'get_alert_handler' ) );

        $this->assertTrue( $this->container->has( 'general_shortcode_adapter' ) );
        $this->assertTrue( $this->container->has( General_Shortcode_Adapter::class ) );
    }

    public function test_resolves_standalone_infrastructure_services(): void {
        $this->assertInstanceOf( Wordpress_Clock::class, $this->container->get( 'clock' ) );
        $this->assertInstanceOf( Simple_Domain_Event_Dispatcher::class, $this->container->get( 'domain_event_dispatcher' ) );
        $this->assertInstanceOf( Wp_Mailer::class, $this->container->get( 'mailer' ) );
        $this->assertInstanceOf( Wp_Option_Gateway::class, $this->container->get( 'option_gateway' ) );
        $this->assertInstanceOf( Security_Service::class, $this->container->get( 'security_service' ) );
        $this->assertInstanceOf( Json_Response_Factory::class, $this->container->get( 'json_response_factory' ) );
        $this->assertInstanceOf( Ajax_Registry::class, $this->container->get( 'ajax_registry' ) );
        $this->assertInstanceOf( Php_View_Renderer::class, $this->container->get( 'view_renderer' ) );
        $this->assertInstanceOf( Lta_Wtn_Gateway_Adapter::class, $this->container->get( 'rating_gateway' ) );
        $this->assertInstanceOf( Lta_Wtn_Gateway_Adapter::class, $this->container->get( 'wtn_api_client' ) );
    }

    public function test_resolves_standalone_repositories(): void {
        $this->assertInstanceOf( Season_Repository::class, $this->container->get( 'season_repository' ) );
        $this->assertInstanceOf( Charge_Repository::class, $this->container->get( 'charge_repository' ) );
        $this->assertInstanceOf( Invoice_Repository::class, $this->container->get( 'invoice_repository' ) );
        $this->assertInstanceOf( Message_Repository::class, $this->container->get( 'message_repository' ) );
    }

    public function test_resolves_standalone_query_handlers(): void {
        $this->assertInstanceOf( Get_Password_Lost_Form_Handler::class, $this->container->get( 'get_password_lost_form_handler' ) );
        $this->assertInstanceOf( Get_Password_Reset_Form_Handler::class, $this->container->get( 'get_password_reset_form_handler' ) );
        $this->assertInstanceOf( Get_Alert_Handler::class, $this->container->get( 'get_alert_handler' ) );
    }

    public function test_interface_and_class_aliases_resolve_consistently(): void {
        $clock = $this->container->get( 'clock' );
        $this->assertSame( $clock, $this->container->get( Clock_Interface::class ) );
        $this->assertSame( $clock, $this->container->get( Wordpress_Clock::class ) );

        $dispatcher = $this->container->get( 'domain_event_dispatcher' );
        $this->assertSame( $dispatcher, $this->container->get( Domain_Event_Dispatcher_Interface::class ) );
        $this->assertSame( $dispatcher, $this->container->get( Simple_Domain_Event_Dispatcher::class ) );

        $rating = $this->container->get( 'rating_gateway' );
        $this->assertSame( $rating, $this->container->get( Rating_Gateway_Interface::class ) );
        $this->assertSame( $rating, $this->container->get( Lta_Wtn_Gateway_Adapter::class ) );
        $this->assertSame( $rating, $this->container->get( 'wtn_api_client' ) );

        $mailer = $this->container->get( 'mailer' );
        $this->assertSame( $mailer, $this->container->get( Mailer_Interface::class ) );
        $this->assertSame( $mailer, $this->container->get( Wp_Mailer::class ) );

        $option_gateway = $this->container->get( 'option_gateway' );
        $this->assertSame( $option_gateway, $this->container->get( Option_Gateway_Interface::class ) );
        $this->assertSame( $option_gateway, $this->container->get( Wp_Option_Gateway::class ) );

        $security = $this->container->get( 'security_service' );
        $this->assertSame( $security, $this->container->get( Security_Service_Interface::class ) );
        $this->assertSame( $security, $this->container->get( Security_Service::class ) );

        $this->assertSame( $this->container->get( 'json_response_factory' ), $this->container->get( Json_Response_Factory::class ) );
        $this->assertSame( $this->container->get( 'ajax_registry' ), $this->container->get( Ajax_Registry::class ) );

        $view_renderer = $this->container->get( 'view_renderer' );
        $this->assertSame( $view_renderer, $this->container->get( View_Renderer_Interface::class ) );
        $this->assertSame( $view_renderer, $this->container->get( Php_View_Renderer::class ) );

        $season_repo = $this->container->get( 'season_repository' );
        $this->assertSame( $season_repo, $this->container->get( Season_Repository_Interface::class ) );
        $this->assertSame( $season_repo, $this->container->get( Season_Repository::class ) );

        $charge_repo = $this->container->get( 'charge_repository' );
        $this->assertSame( $charge_repo, $this->container->get( Charge_Repository_Interface::class ) );
        $this->assertSame( $charge_repo, $this->container->get( Charge_Repository::class ) );

        $invoice_repo = $this->container->get( 'invoice_repository' );
        $this->assertSame( $invoice_repo, $this->container->get( Invoice_Repository_Interface::class ) );
        $this->assertSame( $invoice_repo, $this->container->get( Invoice_Repository::class ) );

        $message_repo = $this->container->get( 'message_repository' );
        $this->assertSame( $message_repo, $this->container->get( Message_Repository_Interface::class ) );
        $this->assertSame( $message_repo, $this->container->get( Message_Repository::class ) );

        $page_title = $this->container->get( 'page_title_service' );
        $this->assertSame( $page_title, $this->container->get( Page_Title_Service::class ) );
        $this->assertSame( $page_title, $this->container->get( Page_Title_Service_Interface::class ) );
    }

    public function test_resolves_with_app_properties(): void {
        $custom_option_service  = $this->createStub( Option_Service::class );
        $custom_asset_manager   = $this->createStub( Asset_Manager::class );
        $custom_template_loader = $this->createStub( Template_Loader::class );

        $app                  = $this->createStub( RacketManager::class );
        $app->option_service  = $custom_option_service;
        $app->asset_manager   = $custom_asset_manager;
        $app->template_loader = $custom_template_loader;
        $app->site_name       = 'Custom Site Name';

        $provider  = new Core_Service_Provider( $app );
        $container = new Simple_Container();
        $provider->register( $container );

        $this->assertSame( $custom_option_service, $container->get( 'option_service' ) );
        $this->assertSame( $custom_option_service, $container->get( Option_Service::class ) );

        $this->assertSame( $custom_asset_manager, $container->get( 'asset_manager' ) );
        $this->assertSame( $custom_asset_manager, $container->get( Asset_Manager::class ) );

        $this->assertSame( $custom_template_loader, $container->get( 'template_loader' ) );
        $this->assertSame( $custom_template_loader, $container->get( Template_Loader::class ) );

        $page_title_service = $container->get( 'page_title_service' );
        $this->assertInstanceOf( Page_Title_Service::class, $page_title_service );
        $ref = new ReflectionProperty( Page_Title_Service::class, 'site_name' );
        $this->assertSame( 'Custom Site Name', $ref->getValue( $page_title_service ) );
    }

    public function test_resolves_with_null_app_fallback(): void {
        $provider  = new Core_Service_Provider( null );
        $container = new Simple_Container();
        $provider->register( $container );

        $option_service = $container->get( 'option_service' );
        $this->assertInstanceOf( Option_Service::class, $option_service );
        $this->assertSame( $option_service, $container->get( Option_Service::class ) );

        $asset_manager = $container->get( 'asset_manager' );
        $this->assertInstanceOf( Asset_Manager::class, $asset_manager );
        $this->assertSame( $asset_manager, $container->get( Asset_Manager::class ) );

        $template_loader = $container->get( 'template_loader' );
        $this->assertInstanceOf( Template_Loader::class, $template_loader );
        $this->assertSame( $template_loader, $container->get( Template_Loader::class ) );

        $page_title_service = $container->get( 'page_title_service' );
        $this->assertInstanceOf( Page_Title_Service::class, $page_title_service );
        $ref = new ReflectionProperty( Page_Title_Service::class, 'site_name' );
        $this->assertNull( $ref->getValue( $page_title_service ) );
    }
}
