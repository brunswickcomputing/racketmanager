<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Container\Providers;

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
use Racketmanager\Domain\Ports\Rating_Gateway_Interface;
use Racketmanager\Gateways\Mail\Mailer_Interface;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Gateways\Payment\Payment_Gateway_Interface;
use Racketmanager\Infrastructure\Gateways\Mail\Wp_Mailer;
use Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway;
use Racketmanager\Infrastructure\Gateways\Payment\Stripe_Payment_Gateway;
use Racketmanager\Infrastructure\Gateways\Rating\Lta_Wtn_Gateway_Adapter;
use Racketmanager\Infrastructure\Security\Security_Service;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Ajax\Ajax_Registry;
use Racketmanager\Infrastructure\Wordpress\Ajax\Finance_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Finance_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Export\Export_Delivery_Adapter;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory;
use Racketmanager\Infrastructure\Wordpress\Rest\Rest_Controller_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Email_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\General_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Login_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Message_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Email_Shortcode_Presenter;
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
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Notify_Service;
use Racketmanager\Services\Season_Service;
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
        $container->set( 'rating_gateway', fn(): Rating_Gateway_Interface => new Lta_Wtn_Gateway_Adapter() );
        $container->set( 'wtn_api_client', fn( Simple_Container $c ): Rating_Gateway_Interface => $c->get( 'rating_gateway' ) );
        $container->set( 'mailer', fn(): Mailer_Interface => new Wp_Mailer() );
        $container->set( 'payment_gateway', fn(): Payment_Gateway_Interface => new Stripe_Payment_Gateway( $app?->get_options( 'billing' ) ?? [] ) );
        $container->set( 'option_gateway', fn(): Option_Gateway_Interface => new Wp_Option_Gateway() );

        // Core App & Settings
        $container->set( 'racketmanager_app', fn() => $app );
        $container->set( 'settings_service', fn() => new Settings_Service( $app?->get_options() ?? [] ) );
        $container->set( 'security_service', fn() => new Security_Service() );
        $container->set( 'json_response_factory', fn() => new Json_Response_Factory() );
        $container->set( 'ajax_registry', fn() => new Ajax_Registry() );
        $container->set( 'view_renderer', fn() => new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) );

        // Core Repositories
        $container->set( 'season_repository', fn() => new Season_Repository() );
        $container->set( 'charge_repository', fn() => new Charge_Repository() );
        $container->set( 'invoice_repository', fn() => new Invoice_Repository() );

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
            $app?->site_name ?? ''
        ) );

        $container->set( 'notification_service', fn( Simple_Container $c ) => new Notification_Service(
            $c->get( 'repository_provider' ),
            $c->get( 'settings_service' ),
            $c->get( 'notification_presenter' ),
            $c->get( 'view_renderer' ),
            $c->get( 'racketmanager_app' ),
            $c->get( 'mailer' )
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
            $c->get( 'json_response_factory' )
        ) );
        $container->set( 'account_ajax_controller', fn( Simple_Container $c ) => new Account_Ajax_Controller( $app, $c->get( 'account_ajax_adapter' ) ) );

        $container->set( 'finance_ajax_adapter', fn( Simple_Container $c ) => new Finance_Ajax_Adapter(
            $c->get( 'security_service' ),
            $c->get( 'json_response_factory' ),
            $c->get( 'finance_service' )
        ) );
        $container->set( 'finance_ajax_controller', fn( Simple_Container $c ) => new Finance_Ajax_Controller( $app, $c->get( 'finance_ajax_adapter' ) ) );

        // Core Presentation & View Handlers
        $container->set( 'shortcode_template_renderer', fn( Simple_Container $c ) => new Shortcode_Template_Renderer(
            $c->get( 'view_renderer' ),
            $c->get( 'club_presenter' ),
            $c->get( 'player_presenter' )
        ) );
        $container->set( 'shortcode_view_presenter', fn( Simple_Container $c ) => new Shortcode_View_Presenter( $c->get( 'shortcode_template_renderer' ) ) );

        $container->set( 'email_shortcode_presenter', fn( Simple_Container $c ) => new Email_Shortcode_Presenter(
            tournament_service: $c->get( 'tournament_service' ),
            competition_service: $c->get( 'competition_service' ),
            team_service: $c->get( 'team_service' ),
            fixture_link_service: $c->get( 'fixture_link_service' ),
            permission_service: $c->get( 'fixture_permission_service' ),
            site_url: $app?->site_url ?? '',
            site_name: $app?->site_name ?? ''
        ) );
        $container->set( 'email_shortcode_adapter', fn( Simple_Container $c ) => new Email_Shortcode_Adapter(
            fixture_repository: $c->get( 'fixture_repository' ),
            presenter: $c->get( 'email_shortcode_presenter' ),
            template_renderer: new Shortcode_Template_Renderer(
                $c->get( 'view_renderer' ),
                $c->get( 'club_presenter' ),
                $c->get( 'player_presenter' )
            )
        ) );

        // General / Auth / Message Queries & Shortcodes
        $container->set( 'get_login_form_handler', fn() => new Get_Login_Form_Handler( $app ) );
        $container->set( 'get_password_lost_form_handler', fn() => new Get_Password_Lost_Form_Handler() );
        $container->set( 'get_password_reset_form_handler', fn() => new Get_Password_Reset_Form_Handler() );
        $container->set( 'get_member_account_form_handler', fn( Simple_Container $c ) => new Get_Member_Account_Form_Handler( $c->get( 'player_service' ) ) );

        $container->set( 'login_shortcode_adapter', fn( Simple_Container $c ) => new Login_Shortcode_Adapter(
            $app,
            view_presenter: $c->get( 'shortcode_view_presenter' ),
            login_form_handler: $c->get( 'get_login_form_handler' ),
            password_lost_form_handler: $c->get( 'get_password_lost_form_handler' ),
            password_reset_form_handler: $c->get( 'get_password_reset_form_handler' ),
            member_account_form_handler: $c->get( 'get_member_account_form_handler' ),
            template_renderer: $c->get( 'shortcode_template_renderer' )
        ) );

        $container->set( 'get_messages_handler', fn() => new Get_Messages_Handler() );
        $container->set( 'get_message_handler', fn() => new Get_Message_Handler() );

        $container->set( 'message_shortcode_adapter', fn( Simple_Container $c ) => new Message_Shortcode_Adapter(
            $app,
            view_presenter: $c->get( 'shortcode_view_presenter' ),
            messages_handler: $c->get( 'get_messages_handler' ),
            message_handler: $c->get( 'get_message_handler' ),
            template_renderer: $c->get( 'shortcode_template_renderer' )
        ) );

        $container->set( 'get_favourites_handler', fn() => new Get_Favourites_Handler() );
        $container->set( 'get_invoice_handler', fn( Simple_Container $c ) => new Get_Invoice_Handler(
            $c->get( 'finance_service' ),
            $app
        ) );
        $container->set( 'get_purchase_order_handler', fn( Simple_Container $c ) => new Get_Purchase_Order_Handler(
            $c->get( 'finance_service' )
        ) );
        $container->set( 'get_memberships_handler', fn( Simple_Container $c ) => new Get_Memberships_Handler(
            $c->get( 'player_service' ),
            $c->get( 'registration_service' )
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
            template_renderer: $c->get( 'shortcode_template_renderer' )
        ) );
    }
}
