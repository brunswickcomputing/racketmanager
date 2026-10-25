<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Container\Providers;

use Racketmanager\Application\Finance\Commands\Set_Purchase_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Alert_Handler;
use Racketmanager\Application\General\Queries\Get_Favourites_Handler;
use Racketmanager\Application\General\Queries\Get_Invoice_Handler;
use Racketmanager\Application\General\Queries\Get_Memberships_Handler;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Handler;
use Racketmanager\Application\Login\Queries\Get_Login_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Member_Account_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Password_Lost_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Password_Reset_Form_Handler;
use Racketmanager\Application\Message\Queries\Get_Message_Handler;
use Racketmanager\Application\Message\Queries\Get_Messages_Handler;
use Racketmanager\Presentation\Presenters\Message_Presenter;
use Racketmanager\Domain\Events\Domain_Event_Dispatcher_Interface;
use Racketmanager\Domain\Ports\Rating_Gateway_Interface;
use Racketmanager\Gateways\Mail\Mailer_Interface;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Gateways\Payment\Payment_Gateway_Interface;
use Racketmanager\Infrastructure\Events\Simple_Domain_Event_Dispatcher;
use Racketmanager\Infrastructure\Legacy\Legacy_User_Factory;
use Racketmanager\Infrastructure\Gateways\Account\Wp_User_Favourites_Gateway;
use Racketmanager\Infrastructure\Gateways\Mail\Wp_Mailer;
use Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway;
use Racketmanager\Infrastructure\Gateways\Payment\Stripe_Payment_Gateway;
use Racketmanager\Infrastructure\Gateways\Rating\Lta_Wtn_Gateway_Adapter;
use Racketmanager\Repositories\Message_Repository;
use Racketmanager\Repositories\User_Repository;
use Racketmanager\Infrastructure\Security\Security_Service;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Ajax\Ajax_Registry;
use Racketmanager\Infrastructure\Wordpress\Ajax\Finance_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Finance_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Assets\Asset_Manager;
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
use Racketmanager\Infrastructure\Wordpress\Templates\Template_Loader;
use Racketmanager\Presentation\Presenters\Email_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Favourite_Presenter;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Presentation\Presenters\Notification_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Charge_Repository;
use Racketmanager\Repositories\Invoice_Repository;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Repositories\Season_Repository;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Contracts\Data_Exporter_Interface;
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
use Racketmanager\Services\View\Shortcode_Template_Renderer;

/**
 * Service provider for core infrastructure, gateways, shared repositories, and general handlers.
 */
final readonly class Core_Service_Provider implements Service_Provider_Interface {
    public function __construct(
        private ?RacketManager $app = null
    ) {}

    public function register( Simple_Container $container ): void {
        $app = $this->app;

        // Gateways & External Clients
        $container->set( 'domain_event_dispatcher', fn(): Domain_Event_Dispatcher_Interface => new Simple_Domain_Event_Dispatcher() );
        $container->set( Domain_Event_Dispatcher_Interface::class, fn( Simple_Container $c ): Domain_Event_Dispatcher_Interface => $c->get( 'domain_event_dispatcher' ) );
        $container->set( 'rating_gateway', fn(): Rating_Gateway_Interface => new Lta_Wtn_Gateway_Adapter() );
        $container->set( 'wtn_api_client', fn( Simple_Container $c ): Rating_Gateway_Interface => $c->get( 'rating_gateway' ) );
        $container->set( 'mailer', fn(): Mailer_Interface => new Wp_Mailer() );
        $container->set( 'option_gateway', fn(): Option_Gateway_Interface => new Wp_Option_Gateway() );
        $container->set( 'option_service', fn( Simple_Container $c ): Option_Service => $app?->option_service ?? new Option_Service( $c->get( 'option_gateway' ) ) );
        $container->set( Option_Service::class, fn( Simple_Container $c ): Option_Service => $c->get( 'option_service' ) );
        $container->set( 'asset_manager', fn(): Asset_Manager => $app?->asset_manager ?? new Asset_Manager() );
        $container->set( Asset_Manager::class, fn( Simple_Container $c ): Asset_Manager => $c->get( 'asset_manager' ) );
        $container->set( 'template_loader', fn(): Template_Loader => $app?->template_loader ?? new Template_Loader() );
        $container->set( Template_Loader::class, fn( Simple_Container $c ): Template_Loader => $c->get( 'template_loader' ) );
        $container->set( 'plugin_bootstrap', fn( Simple_Container $c ): Plugin_Bootstrap => new Plugin_Bootstrap( $c ) );
        $container->set( Plugin_Bootstrap::class, fn( Simple_Container $c ): Plugin_Bootstrap => $c->get( 'plugin_bootstrap' ) );
        $container->set( 'shortcode_registrar', fn( Simple_Container $c ): Shortcode_Registrar => new Shortcode_Registrar( $c ) );
        $container->set( Shortcode_Registrar::class, fn( Simple_Container $c ): Shortcode_Registrar => $c->get( 'shortcode_registrar' ) );
        $container->set( 'payment_gateway', fn( Simple_Container $c ): Payment_Gateway_Interface => new Stripe_Payment_Gateway( $c->get( 'option_service' )->get_options( 'billing' ) ?? [] ) );

        // Core App & Settings
        $container->set( 'racketmanager_app', fn() => $app );
        $container->set( 'settings_service', fn( Simple_Container $c ) => new Settings_Service( $c->get( 'option_service' )->get_options(), $c->get( 'option_gateway' ) ) );
        $container->set( 'security_service', fn() => new Security_Service() );
        $container->set( 'json_response_factory', fn() => new Json_Response_Factory() );
        $container->set( 'ajax_registry', fn() => new Ajax_Registry() );
        $container->set( 'view_renderer', fn() => new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) );
        $container->set( 'page_title_service', fn(): Page_Title_Service => new Page_Title_Service( $app?->site_name ) );
        $container->set( Page_Title_Service::class, fn( Simple_Container $c ): Page_Title_Service => $c->get( 'page_title_service' ) );
        $container->set( Page_Title_Service_Interface::class, fn( Simple_Container $c ): Page_Title_Service => $c->get( 'page_title_service' ) );
        $container->set( 'mail_filter_service', fn( Simple_Container $c ): Mail_Filter_Service => new Mail_Filter_Service( $c->get( 'message_repository' ) ) );
        $container->set( Mail_Filter_Service::class, fn( Simple_Container $c ): Mail_Filter_Service => $c->get( 'mail_filter_service' ) );
        $container->set( Mail_Filter_Service_Interface::class, fn( Simple_Container $c ): Mail_Filter_Service => $c->get( 'mail_filter_service' ) );
        $container->set( 'component_coordinator', fn( Simple_Container $c ): Component_Coordinator => new Component_Coordinator(
            $c,
            $c->get( 'ajax_registry' )
        ) );
        $container->set( Component_Coordinator::class, fn( Simple_Container $c ): Component_Coordinator => $c->get( 'component_coordinator' ) );
        $container->set( Component_Coordinator_Interface::class, fn( Simple_Container $c ): Component_Coordinator_Interface => $c->get( 'component_coordinator' ) );

        // Core Repositories
        $container->set( 'season_repository', fn() => new Season_Repository() );
        $container->set( 'charge_repository', fn() => new Charge_Repository() );
        $container->set( 'invoice_repository', fn() => new Invoice_Repository() );
        $container->set( 'user_repository', fn() => new User_Repository() );
        $container->set( 'message_repository', fn() => new Message_Repository() );
        $container->set( 'legacy_user_factory', fn( Simple_Container $c ) => new Legacy_User_Factory( $c->get( 'message_repository' ) ) );

        // Repository Provider Aggregate
        $container->set( 'repository_provider', fn( Simple_Container $c ) => new Repository_Provider(
            $c->get( 'league_repository' ),
            $c->get( 'event_repository' ),
            $c->get( 'competition_repository' ),
            $c->get( 'league_team_repository' ),
            $c->get( 'team_repository' ),
            $c->get( 'player_repository' ),
            $c->get( 'rubber_repository' ),
            $c->get( 'results_checker_repository' ),
            $c->get( 'results_report_repository' ),
            $c->get( 'result_repository' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'club_repository' )
        ) );

        // Core Services
        $container->set( 'season_service', fn( Simple_Container $c ) => new Season_Service(
            $app,
            $c->get( 'season_repository' )
        ) );

        $container->set( 'finance_service', fn( Simple_Container $c ) => new Finance_Service(
            $app,
            $c->get( 'charge_repository' ),
            $c->get( 'invoice_repository' ),
            $c->get( 'club_repository' ),
            $c->get( 'tournament_repository' ),
            $c->get( 'competition_service' ),
            $c->get( 'player_service' ),
            $c->get( 'payment_gateway' ),
            $c->get( 'mailer' )
        ) );

        $container->set( 'notify_service', fn( Simple_Container $c ) => new Notify_Service( $app, $c->get( 'mailer' ) ) );

        $container->set( 'notification_presenter', fn( Simple_Container $c ) => new Notification_Presenter(
            $c->get( 'team_repository' ),
            $c->get( 'club_repository' ),
            $c->get( 'tournament_repository' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'fixture_link_service' ),
            $c->get( 'team_service' ),
            $c->get( 'competition_service' ),
            $c->get( 'fixture_permission_service' ),
            $app?->site_url ?? '',
            $app?->site_name ?? '',
            $c->get( 'rubber_repository' ),
            $c->get( 'option_gateway' )
        ) );

        $container->set( 'notification_service', fn( Simple_Container $c ) => new Notification_Service(
            $c->get( 'repository_provider' ),
            $c->get( 'settings_service' ),
            $c->get( 'notification_presenter' ),
            $c->get( 'view_renderer' ),
            $c->get( 'racketmanager_app' ),
            $c->get( 'mailer' ),
            $c->get( 'event_query_service' ),
            fn() => $c->get( 'league_query_service' )
        ) );

        // Export Services
        $container->set( 'exporter', fn( Simple_Container $c ): Data_Exporter_Interface => new Export_Service(
            $c->get( 'fixture_repository' ),
            $c->get( 'result_reporting_service' ),
            $c->get( 'fixture_detail_service' ),
            $c->get( 'club_repository' )
        ) );
        $container->set( 'rest_controller_adapter', fn() => new Rest_Controller_Adapter() );
        $container->set( 'export_delivery_adapter', fn( Simple_Container $c ) => new Export_Delivery_Adapter(
            $c->get( 'racketmanager_app' )
        ) );
        $container->set( 'export_admin_controller', fn( Simple_Container $c ) => $c->get( 'export_delivery_adapter' ) );

        // Core AJAX Controllers & Adapters
        $container->set( 'account_ajax_adapter', fn( Simple_Container $c ) => new Account_Ajax_Adapter(
            $c->get( 'security_service' ),
            $c->get( 'json_response_factory' ),
            $c->get( 'message_repository' ),
            $c->get( 'user_repository' )
        ) );
        $container->set( 'account_ajax_controller', fn( Simple_Container $c ) => new Account_Ajax_Controller( $app, $c->get( 'account_ajax_adapter' ) ) );

        $container->set( 'set_purchase_order_handler', fn( Simple_Container $c ) => new Set_Purchase_Order_Handler(
            $c->get( 'finance_service' )
        ) );

        $container->set( 'finance_ajax_adapter', fn( Simple_Container $c ) => new Finance_Ajax_Adapter(
            security_service: $c->get( 'security_service' ),
            response_factory: $c->get( 'json_response_factory' ),
            finance_service: $c->get( 'finance_service' ),
            set_purchase_order_handler: $c->get( 'set_purchase_order_handler' ),
            get_purchase_order_handler: $c->get( 'get_purchase_order_handler' ),
            view_presenter: $c->get( 'shortcode_view_presenter' )
        ) );
        $container->set( 'finance_ajax_controller', fn( Simple_Container $c ) => new Finance_Ajax_Controller( $app, $c->get( 'finance_ajax_adapter' ) ) );

        // Core Presentation & View Handlers
        $container->set( 'user_favourites_gateway', fn( Simple_Container $c ) => new Wp_User_Favourites_Gateway( $c->get( 'security_service' ) ) );
        $container->set( 'favourite_presenter', fn( Simple_Container $c ) => new Favourite_Presenter( $c->get( 'user_favourites_gateway' ) ) );
        $container->set( 'shortcode_template_renderer', fn( Simple_Container $c ) => new Shortcode_Template_Renderer(
            $c->get( 'view_renderer' ),
            $c->get( 'club_presenter' ),
            $c->get( 'player_presenter' ),
            $c->get( 'favourite_presenter' )
        ) );
        $container->set( 'shortcode_view_presenter', fn( Simple_Container $c ) => new Shortcode_View_Presenter( $c->get( 'shortcode_template_renderer' ) ) );

        $container->set( 'email_shortcode_presenter', fn( Simple_Container $c ) => new Email_Shortcode_Presenter(
            tournament_service: $c->get( 'tournament_service' ),
            competition_service: $c->get( 'competition_service' ),
            team_service: $c->get( 'team_service' ),
            fixture_link_service: $c->get( 'fixture_link_service' ),
            permission_service: $c->get( 'fixture_permission_service' ),
            site_url: $app?->site_url ?? '',
            site_name: $app?->site_name ?? '',
            rubber_repository: $c->get( 'rubber_repository' ),
            option_gateway: $c->get( 'option_gateway' )
        ) );
        $container->set( 'email_shortcode_adapter', fn( Simple_Container $c ) => new Email_Shortcode_Adapter(
            fixture_repository: $c->get( 'fixture_repository' ),
            presenter: $c->get( 'email_shortcode_presenter' ),
            template_renderer: new Shortcode_Template_Renderer(
                $c->get( 'view_renderer' ),
                $c->get( 'club_presenter' ),
                $c->get( 'player_presenter' ),
                $c->get( 'favourite_presenter' )
            )
        ) );

        // General / Auth / Message Queries & Shortcodes
        $container->set( 'get_login_form_handler', fn( Simple_Container $c ) => new Get_Login_Form_Handler( $c->get( 'option_gateway' ), $c->get( 'settings_service' ) ) );
        $container->set( 'get_password_lost_form_handler', fn() => new Get_Password_Lost_Form_Handler() );
        $container->set( 'get_password_reset_form_handler', fn() => new Get_Password_Reset_Form_Handler() );
        $container->set( 'get_member_account_form_handler', fn( Simple_Container $c ) => new Get_Member_Account_Form_Handler( $c->get( 'user_repository' ) ) );

        $container->set( 'login_shortcode_adapter', fn( Simple_Container $c ) => new Login_Shortcode_Adapter(
            $app,
            view_presenter: $c->get( 'shortcode_view_presenter' ),
            login_form_handler: $c->get( 'get_login_form_handler' ),
            password_lost_form_handler: $c->get( 'get_password_lost_form_handler' ),
            password_reset_form_handler: $c->get( 'get_password_reset_form_handler' ),
            member_account_form_handler: $c->get( 'get_member_account_form_handler' ),
            template_renderer: $c->get( 'shortcode_template_renderer' ),
            security_service: $c->get( 'security_service' )
        ) );

        $container->set( 'message_presenter', fn() => new Message_Presenter() );
        $container->set( 'get_messages_handler', fn( Simple_Container $c ) => new Get_Messages_Handler( $c->get( 'user_repository' ), $c->get( 'message_repository' ), $c->get( 'message_presenter' ) ) );
        $container->set( 'get_message_handler', fn( Simple_Container $c ) => new Get_Message_Handler( $c->get( 'message_repository' ), $c->get( 'message_presenter' ) ) );

        $container->set( 'message_shortcode_adapter', fn( Simple_Container $c ) => new Message_Shortcode_Adapter(
            $app,
            view_presenter: $c->get( 'shortcode_view_presenter' ),
            messages_handler: $c->get( 'get_messages_handler' ),
            message_handler: $c->get( 'get_message_handler' ),
            template_renderer: $c->get( 'shortcode_template_renderer' ),
            security_service: $c->get( 'security_service' )
        ) );

        $container->set( 'get_favourites_handler', fn( Simple_Container $c ) => new Get_Favourites_Handler(
            $c->get( 'user_repository' ),
            $c->get( 'club_repository' )
        ) );
        $container->set( 'invoice_presenter', fn() => new Invoice_Presenter() );
        $container->set( 'get_invoice_handler', fn( Simple_Container $c ) => new Get_Invoice_Handler(
            $c->get( 'finance_service' ),
            $c->get( 'settings_service' ),
            $c->get( 'option_gateway' ),
            $c->get( 'invoice_presenter' )
        ) );
        $container->set( 'get_purchase_order_handler', fn( Simple_Container $c ) => new Get_Purchase_Order_Handler(
            $c->get( 'finance_service' )
        ) );
        $container->set( 'get_memberships_handler', fn( Simple_Container $c ) => new Get_Memberships_Handler(
            $c->get( 'player_service' ),
            $c->get( 'registration_service' ),
            $c->get( 'player_presenter' )
        ) );
        $container->set( 'get_alert_handler', fn() => new Get_Alert_Handler() );

        $container->set( 'general_shortcode_adapter', fn( Simple_Container $c ) => new General_Shortcode_Adapter(
            $app,
            view_presenter: $c->get( 'shortcode_view_presenter' ),
            players_handler: $c->get( 'get_players_handler' ),
            player_handler: $c->get( 'get_player_handler' ),
            favourites_handler: $c->get( 'get_favourites_handler' ),
            invoice_handler: $c->get( 'get_invoice_handler' ),
            purchase_order_handler: $c->get( 'get_purchase_order_handler' ),
            memberships_handler: $c->get( 'get_memberships_handler' ),
            player_search_handler: $c->get( 'get_player_search_handler' ),
            team_order_handler: $c->get( 'get_team_order_handler' ),
            alert_handler: $c->get( 'get_alert_handler' ),
            template_renderer: $c->get( 'shortcode_template_renderer' ),
            security_service: $c->get( 'security_service' )
        ) );
    }
}
