<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Container\Providers;

use Racketmanager\Application\Finance\Commands\Set_Purchase_Order_Handler;
use Racketmanager\Application\Finance\Commands\Update_Payment_Handler;
use Racketmanager\Application\General\Queries\Get_Alert_Handler;
use Racketmanager\Application\General\Queries\Get_Favourites_Handler;
use Racketmanager\Application\General\Queries\Get_Invoice_Handler;
use Racketmanager\Application\General\Queries\Get_Memberships_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Search_Handler;
use Racketmanager\Application\General\Queries\Get_Players_Handler;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Team_Order_Handler;
use Racketmanager\Application\Login\Queries\Get_Login_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Member_Account_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Password_Lost_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Password_Reset_Form_Handler;
use Racketmanager\Application\Login\Services\Auth_Service;
use Racketmanager\Application\Message\Queries\Get_Message_Handler;
use Racketmanager\Application\Message\Queries\Get_Messages_Handler;
use Racketmanager\Application\Export\Ports\Data_Exporter_Interface;
use Racketmanager\Application\Mail\Mailer_Interface;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Application\Player\Ports\Registration_Service_Interface;
use Racketmanager\Application\Ports\Competition_Config_Validator_Factory_Interface;
use Racketmanager\Application\Ports\Finance_Service_Interface;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Application\Ports\Settings_Service_Interface;
use Racketmanager\Application\Payment\Ports\Payment_Gateway_Interface;
use Racketmanager\Domain\Events\Domain_Event_Dispatcher_Interface;
use Racketmanager\Domain\Ports\Clock_Interface;
use Racketmanager\Domain\Ports\Rating_Gateway_Interface;
use Racketmanager\Application\Account\Ports\User_Favourites_Gateway_Interface;
use Racketmanager\Infrastructure\Events\Simple_Domain_Event_Dispatcher;
use Racketmanager\Infrastructure\Gateways\Account\Wp_User_Favourites_Gateway;
use Racketmanager\Infrastructure\Gateways\Mail\Wp_Mailer;
use Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway;
use Racketmanager\Infrastructure\Gateways\Payment\Stripe_Payment_Gateway;
use Racketmanager\Infrastructure\Gateways\Rating\Lta_Wtn_Gateway_Adapter;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Repositories\Charge_Repository;
use Racketmanager\Repositories\Interfaces\Charge_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Invoice_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Result_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Report_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Season_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Repositories\Invoice_Repository;
use Racketmanager\Repositories\Message_Repository;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Repositories\Season_Repository;
use Racketmanager\Repositories\User_Repository;
use Racketmanager\Infrastructure\Security\Security_Service;
use Racketmanager\Infrastructure\Time\Wordpress_Clock;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Ajax\Ajax_Registry;
use Racketmanager\Infrastructure\Wordpress\Ajax\Finance_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Finance_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Assets\Asset_Manager;
use Racketmanager\Infrastructure\Wordpress\Bootstrap\Plugin_Bootstrap;
use Racketmanager\Infrastructure\Wordpress\Component\Component_Coordinator;
use Racketmanager\Infrastructure\Wordpress\Component\Component_Coordinator_Interface;
use Racketmanager\Infrastructure\Wordpress\Auth\Auth_Email_Filter_Adapter;
use Racketmanager\Infrastructure\Wordpress\Auth\Login_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Auth\Password_Reset_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Export\Export_Delivery_Adapter;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Infrastructure\Wordpress\Request\Wp_Query_Vars;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Rest\Rest_Controller_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Email_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\General_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Login_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Message_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Shortcode_Registrar;
use Racketmanager\Application\Ports\Template_Renderer_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Email_Shortcode_Presenter_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Invoice_Presenter_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Message_Presenter_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Player_Presenter_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Shortcode_View_Presenter_Interface;
use Racketmanager\Infrastructure\Wordpress\Rewrites\Rewrite_Rules;
use Racketmanager\Infrastructure\Wordpress\Templates\Template_Loader;
use Racketmanager\RacketManager;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Export\Export_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Mail\Mail_Filter_Service;
use Racketmanager\Services\Mail\Mail_Filter_Service_Interface;
use Racketmanager\Services\Notification\Notification_Presenter_Interface;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Notify_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Result\Result_Reporting_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Seo\Page_Title_Service;
use Racketmanager\Services\Seo\Page_Title_Service_Interface;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Validator\Competition_Config_Validator_Factory;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Util\Util_Lookup;

/**
 * Service provider for core infrastructure, gateways, shared repositories, and general handlers.
 */
final readonly class Core_Service_Provider implements Service_Provider_Interface {
    /**
     * @param RacketManager|null $app a RacketManager instance.
     */
    public function __construct(
        private ?RacketManager $app = null
    ) {}

    /**
     * @param Simple_Container $container a Simple_Container instance.
     *
     * @return void
     */
    public function register( Simple_Container $container ): void {
        $this->register_gateways_and_infrastructure( $container );
        $this->register_core_services( $container );
        $this->register_repositories( $container );
        $this->register_services_and_exports( $container );
        $this->register_ajax_controllers( $container );
        $this->register_shortcodes_and_handlers( $container );

        Util_Lookup::set_query_vars( $container->get( Query_Vars_Interface::class ) );
    }

    /**
     * @param Simple_Container $container a Simple_Container instance.
     *
     * @return void
     */
    private function register_gateways_and_infrastructure( Simple_Container $container ): void {
        $app = $this->app;

        $container->set( Domain_Event_Dispatcher_Interface::class, fn(): Domain_Event_Dispatcher_Interface => new Simple_Domain_Event_Dispatcher() );
        $container->alias( Simple_Domain_Event_Dispatcher::class, Domain_Event_Dispatcher_Interface::class );
        $container->alias( 'domain_event_dispatcher', Domain_Event_Dispatcher_Interface::class );

        $container->set( Rating_Gateway_Interface::class, fn(): Rating_Gateway_Interface => new Lta_Wtn_Gateway_Adapter() );
        $container->alias( Lta_Wtn_Gateway_Adapter::class, Rating_Gateway_Interface::class );
        $container->alias( 'rating_gateway', Rating_Gateway_Interface::class );
        $container->alias( 'wtn_api_client', Rating_Gateway_Interface::class );

        $container->set( Mailer_Interface::class, fn(): Mailer_Interface => new Wp_Mailer() );
        $container->alias( Wp_Mailer::class, Mailer_Interface::class );
        $container->alias( 'mailer', Mailer_Interface::class );

        $container->set( Clock_Interface::class, fn(): Clock_Interface => new Wordpress_Clock() );
        $container->alias( Wordpress_Clock::class, Clock_Interface::class );
        $container->alias( 'clock', Clock_Interface::class );

        $container->set( Option_Gateway_Interface::class, fn(): Option_Gateway_Interface => new Wp_Option_Gateway() );
        $container->alias( Wp_Option_Gateway::class, Option_Gateway_Interface::class );
        $container->alias( 'option_gateway', Option_Gateway_Interface::class );

        $container->set( Query_Vars_Interface::class, fn(): Query_Vars_Interface => new Wp_Query_Vars() );
        $container->alias( Wp_Query_Vars::class, Query_Vars_Interface::class );

        $container->set( Option_Service::class, fn( Simple_Container $c ): Option_Service => $app?->option_service ?? new Option_Service( $c->get( Option_Gateway_Interface::class ) ) );
        $container->alias( 'option_service', Option_Service::class );

        $container->set( Asset_Manager::class, fn(): Asset_Manager => $app?->asset_manager ?? new Asset_Manager() );
        $container->alias( 'asset_manager', Asset_Manager::class );

        $container->set( Template_Loader::class, fn(): Template_Loader => $app?->template_loader ?? new Template_Loader() );
        $container->alias( 'template_loader', Template_Loader::class );

        $container->set( Plugin_Bootstrap::class, fn( Simple_Container $c ): Plugin_Bootstrap => new Plugin_Bootstrap( $c ) );
        $container->alias( 'plugin_bootstrap', Plugin_Bootstrap::class );

        $container->set( Shortcode_Registrar::class, fn( Simple_Container $c ): Shortcode_Registrar => new Shortcode_Registrar( $c ) );
        $container->alias( 'shortcode_registrar', Shortcode_Registrar::class );

        $container->set( Rewrite_Rules::class, fn(): Rewrite_Rules => new Rewrite_Rules() );

        $container->set( Payment_Gateway_Interface::class, fn( Simple_Container $c ): Payment_Gateway_Interface => new Stripe_Payment_Gateway( $c->get( Option_Service::class )->get_options( 'billing' ) ?? [] ) );
        $container->alias( Stripe_Payment_Gateway::class, Payment_Gateway_Interface::class );
        $container->alias( 'payment_gateway', Payment_Gateway_Interface::class );

        $container->set( User_Favourites_Gateway_Interface::class, fn( Simple_Container $c ): User_Favourites_Gateway_Interface => new Wp_User_Favourites_Gateway( $c->get( Security_Service_Interface::class ) ) );
        $container->alias( Wp_User_Favourites_Gateway::class, User_Favourites_Gateway_Interface::class );
        $container->alias( 'user_favourites_gateway', User_Favourites_Gateway_Interface::class );
    }

    /**
     * @param Simple_Container $container a Simple_Container instance.
     *
     * @return void
     */
    private function register_core_services( Simple_Container $container ): void {
        $app = $this->app;

        $container->set( RacketManager::class, fn(): ?RacketManager => $app );
        $container->alias( 'racketmanager_app', RacketManager::class );

        $container->set( Settings_Service_Interface::class, fn( Simple_Container $c ): Settings_Service_Interface => new Settings_Service( $c->get( Option_Service::class )->get_options(), $c->get( Option_Gateway_Interface::class ) ) );
        $container->alias( Settings_Service::class, Settings_Service_Interface::class );
        $container->alias( 'settings_service', Settings_Service_Interface::class );

        $container->set( Security_Service_Interface::class, fn(): Security_Service_Interface => new Security_Service() );
        $container->alias( Security_Service::class, Security_Service_Interface::class );
        $container->alias( 'security_service', Security_Service_Interface::class );

        $container->set( Json_Response_Factory_Interface::class, fn(): Json_Response_Factory_Interface => new Json_Response_Factory() );
        $container->alias( Json_Response_Factory::class, Json_Response_Factory_Interface::class );
        $container->alias( 'json_response_factory', Json_Response_Factory_Interface::class );

        $container->set( Ajax_Registry::class, fn(): Ajax_Registry => new Ajax_Registry() );
        $container->alias( 'ajax_registry', Ajax_Registry::class );

        $container->set( View_Renderer_Interface::class, fn(): View_Renderer_Interface => new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) );
        $container->alias( Php_View_Renderer::class, View_Renderer_Interface::class );
        $container->alias( 'view_renderer', View_Renderer_Interface::class );

        $container->set( Page_Title_Service_Interface::class, fn( Simple_Container $c ): Page_Title_Service_Interface => new Page_Title_Service( $app?->site_name, $c->get( Query_Vars_Interface::class ) ) );
        $container->alias( Page_Title_Service::class, Page_Title_Service_Interface::class );
        $container->alias( 'page_title_service', Page_Title_Service_Interface::class );

        $container->set(
            Auth_Service::class,
            fn( Simple_Container $c ): Auth_Service => new Auth_Service(
                $c->get( Option_Service::class ),
                $c->get( Template_Renderer_Interface::class )
            )
        );

        $container->set(
            Password_Reset_Action_Adapter::class,
            fn( Simple_Container $c ): Password_Reset_Action_Adapter => new Password_Reset_Action_Adapter(
                $c->get( Auth_Service::class )
            )
        );

        $container->set(
            Auth_Email_Filter_Adapter::class,
            fn( Simple_Container $c ): Auth_Email_Filter_Adapter => new Auth_Email_Filter_Adapter(
                $c->get( Auth_Service::class )
            )
        );

        $container->set(
            Login_Action_Adapter::class,
            fn( Simple_Container $c ): Login_Action_Adapter => new Login_Action_Adapter(
                $c->get( Auth_Service::class ),
                $c->get( Option_Service::class ),
                $c->get( Password_Reset_Action_Adapter::class ),
                $c->get( Auth_Email_Filter_Adapter::class )
            )
        );

        $container->set( Mail_Filter_Service_Interface::class, fn( Simple_Container $c ): Mail_Filter_Service_Interface => new Mail_Filter_Service( $c->get( Message_Repository_Interface::class ) ) );
        $container->alias( Mail_Filter_Service::class, Mail_Filter_Service_Interface::class );
        $container->alias( 'mail_filter_service', Mail_Filter_Service_Interface::class );

        $container->set(
            Component_Coordinator_Interface::class,
            fn( Simple_Container $c ): Component_Coordinator_Interface => new Component_Coordinator(
                $c,
                $c->get( Ajax_Registry::class )
            )
        );
        $container->alias( Component_Coordinator::class, Component_Coordinator_Interface::class );
        $container->alias( 'component_coordinator', Component_Coordinator_Interface::class );
    }

    /**
     * @param Simple_Container $container a Simple_Container instance.
     *
     * @return void
     */
    private function register_repositories( Simple_Container $container ): void {
        $container->set( Season_Repository_Interface::class, fn(): Season_Repository_Interface => new Season_Repository() );
        $container->alias( Season_Repository::class, Season_Repository_Interface::class );
        $container->alias( 'season_repository', Season_Repository_Interface::class );

        $container->set( Charge_Repository_Interface::class, fn(): Charge_Repository_Interface => new Charge_Repository() );
        $container->alias( Charge_Repository::class, Charge_Repository_Interface::class );
        $container->alias( 'charge_repository', Charge_Repository_Interface::class );

        $container->set( Invoice_Repository_Interface::class, fn(): Invoice_Repository_Interface => new Invoice_Repository() );
        $container->alias( Invoice_Repository::class, Invoice_Repository_Interface::class );
        $container->alias( 'invoice_repository', Invoice_Repository_Interface::class );

        $container->set(
            User_Repository_Interface::class,
            fn( Simple_Container $c ): User_Repository_Interface => new User_Repository(
                fn() => $c->get( League_Repository_Interface::class ),
                fn() => $c->get( Club_Repository_Interface::class ),
                fn() => $c->get( Event_Repository_Interface::class ),
                fn() => $c->get( Player_Repository_Interface::class ),
                fn() => $c->get( Team_Repository_Interface::class )
            )
        );
        $container->alias( User_Repository::class, User_Repository_Interface::class );
        $container->alias( 'user_repository', User_Repository_Interface::class );

        $container->set( Message_Repository_Interface::class, fn(): Message_Repository_Interface => new Message_Repository() );
        $container->alias( Message_Repository::class, Message_Repository_Interface::class );
        $container->alias( 'message_repository', Message_Repository_Interface::class );

        $container->set(
            Repository_Provider::class,
            fn( Simple_Container $c ): Repository_Provider => new Repository_Provider(
                $c->get( League_Repository_Interface::class ),
                $c->get( Event_Repository_Interface::class ),
                $c->get( Competition_Repository_Interface::class ),
                $c->get( League_Team_Repository_Interface::class ),
                $c->get( Team_Repository_Interface::class ),
                $c->get( Player_Repository_Interface::class ),
                $c->get( Rubber_Repository_Interface::class ),
                $c->get( Results_Checker_Repository_Interface::class ),
                $c->get( Results_Report_Repository_Interface::class ),
                $c->get( Result_Repository_Interface::class ),
                $c->get( Fixture_Repository_Interface::class ),
                $c->get( Club_Repository_Interface::class )
            )
        );
        $container->alias( 'repository_provider', Repository_Provider::class );
    }

    /**
     * @param Simple_Container $container a Simple_Container instance.
     *
     * @return void
     */
    private function register_services_and_exports( Simple_Container $container ): void {
        $app = $this->app;

        $container->set(
            Season_Service::class,
            fn( Simple_Container $c ): Season_Service => new Season_Service(
                $c->get( Season_Repository_Interface::class )
            )
        );
        $container->alias( 'season_service', Season_Service::class );

        $container->set(
            Finance_Service_Interface::class,
            fn( Simple_Container $c ): Finance_Service_Interface => new Finance_Service(
                $app,
                $c->get( Charge_Repository_Interface::class ),
                $c->get( Invoice_Repository_Interface::class ),
                $c->get( Club_Repository_Interface::class ),
                $c->get( Tournament_Repository_Interface::class ),
                $c->get( Competition_Service::class ),
                $c->get( Player_Service::class ),
                $c->get( Payment_Gateway_Interface::class ),
                $c->get( Mailer_Interface::class ),
                $c->get( Option_Service::class )
            )
        );
        $container->alias( Finance_Service::class, Finance_Service_Interface::class );
        $container->alias( 'finance_service', Finance_Service_Interface::class );

        $container->set(
            Competition_Config_Validator_Factory_Interface::class,
            fn( Simple_Container $c ): Competition_Config_Validator_Factory_Interface => new Competition_Config_Validator_Factory(
                $c->get( Competition_Service::class )
            )
        );
        $container->alias( Competition_Config_Validator_Factory::class, Competition_Config_Validator_Factory_Interface::class );

        $container->set(
            Update_Payment_Handler::class,
            fn( Simple_Container $c ): Update_Payment_Handler => new Update_Payment_Handler(
                $c->get( Finance_Service_Interface::class )
            )
        );

        $container->set( Notify_Service::class, fn( Simple_Container $c ): Notify_Service => new Notify_Service( $app, $c->get( Mailer_Interface::class ) ) );
        $container->alias( 'notify_service', Notify_Service::class );

        $container->set(
            Notification_Service::class,
            fn( Simple_Container $c ): Notification_Service => new Notification_Service(
                $c->get( Repository_Provider::class ),
                $c->get( Settings_Service::class ),
                $c->get( Notification_Presenter_Interface::class ),
                $c->get( View_Renderer_Interface::class ),
                $c->get( RacketManager::class ),
                $c->get( Mailer_Interface::class ),
                $c->get( Option_Service::class ),
                $c->get( Event_Query_Service::class ),
                fn() => $c->get( League_Query_Service::class )
            )
        );
        $container->alias( 'notification_service', Notification_Service::class );

        $container->set(
            Data_Exporter_Interface::class,
            fn( Simple_Container $c ): Data_Exporter_Interface => new Export_Service(
                $c->get( Fixture_Repository_Interface::class ),
                $c->get( Result_Reporting_Service::class ),
                $c->get( Fixture_Detail_Service::class ),
                $c->get( Club_Repository_Interface::class )
            )
        );
        $container->alias( Export_Service::class, Data_Exporter_Interface::class );
        $container->alias( 'exporter', Data_Exporter_Interface::class );

        $container->set(
            Rest_Controller_Adapter::class,
            fn( Simple_Container $c ): Rest_Controller_Adapter => new Rest_Controller_Adapter(
                $c->get( League_Repository_Interface::class ),
                $c->get( Team_Repository_Interface::class ),
                $c->get( Competition_Service::class )
            )
        );
        $container->alias( 'rest_controller_adapter', Rest_Controller_Adapter::class );

        $container->set(
            Export_Delivery_Adapter::class,
            fn( Simple_Container $c ): Export_Delivery_Adapter => new Export_Delivery_Adapter(
                $c->get( RacketManager::class )
            )
        );
        $container->alias( 'export_delivery_adapter', Export_Delivery_Adapter::class );
        $container->alias( 'export_admin_controller', Export_Delivery_Adapter::class );
    }

    /**
     * @param Simple_Container $container a Simple_Container instance.
     *
     * @return void
     */
    private function register_ajax_controllers( Simple_Container $container ): void {
        $app = $this->app;

        $container->set(
            Account_Ajax_Adapter::class,
            fn( Simple_Container $c ): Account_Ajax_Adapter => new Account_Ajax_Adapter(
                $c->get( Security_Service_Interface::class ),
                $c->get( Json_Response_Factory::class ),
                $c->get( Message_Repository_Interface::class ),
                $c->get( User_Repository_Interface::class ),
                $c->get( Option_Service::class )
            )
        );
        $container->alias( 'account_ajax_adapter', Account_Ajax_Adapter::class );

        $container->set( Account_Ajax_Controller::class, fn( Simple_Container $c ): Account_Ajax_Controller => new Account_Ajax_Controller( $app, $c->get( Account_Ajax_Adapter::class ) ) );
        $container->alias( 'account_ajax_controller', Account_Ajax_Controller::class );

        $container->set(
            Set_Purchase_Order_Handler::class,
            fn( Simple_Container $c ): Set_Purchase_Order_Handler => new Set_Purchase_Order_Handler(
                $c->get( Finance_Service_Interface::class )
            )
        );
        $container->alias( 'set_purchase_order_handler', Set_Purchase_Order_Handler::class );

        $container->set(
            Finance_Ajax_Adapter::class,
            fn( Simple_Container $c ): Finance_Ajax_Adapter => new Finance_Ajax_Adapter(
                security_service: $c->get( Security_Service_Interface::class ),
                response_factory: $c->get( Json_Response_Factory::class ),
                finance_service: $c->get( Finance_Service::class ),
                set_purchase_order_handler: $c->get( Set_Purchase_Order_Handler::class ),
                get_purchase_order_handler: $c->get( Get_Purchase_Order_Handler::class ),
                view_presenter: $c->get( Shortcode_View_Presenter_Interface::class )
            )
        );
        $container->alias( 'finance_ajax_adapter', Finance_Ajax_Adapter::class );

        $container->set( Finance_Ajax_Controller::class, fn( Simple_Container $c ): Finance_Ajax_Controller => new Finance_Ajax_Controller( $app, $c->get( Finance_Ajax_Adapter::class ) ) );
        $container->alias( 'finance_ajax_controller', Finance_Ajax_Controller::class );
    }

    /**
     * @param Simple_Container $container a Simple_Container instance.
     *
     * @return void
     */
    private function register_shortcodes_and_handlers( Simple_Container $container ): void {
        $app = $this->app;

        $container->set(
            Email_Shortcode_Adapter::class,
            fn( Simple_Container $c ): Email_Shortcode_Adapter => new Email_Shortcode_Adapter(
                fixture_repository: $c->get( Fixture_Repository_Interface::class ),
                presenter: $c->get( Email_Shortcode_Presenter_Interface::class ),
                template_renderer: $c->get( Template_Renderer_Interface::class ),
                event_query_service: $c->get( Event_Query_Service::class ),
                league_query_service: $c->get( League_Query_Service::class ),
                team_repository: $c->get( Team_Repository_Interface::class ),
                league_repository: $c->get( League_Repository_Interface::class ),
                event_repository: $c->get( Event_Repository_Interface::class ),
                competition_repository: $c->get( Competition_Repository_Interface::class ),
                query_vars: $c->get( Query_Vars_Interface::class )
            )
        );
        $container->alias( 'email_shortcode_adapter', Email_Shortcode_Adapter::class );

        $container->set( Get_Login_Form_Handler::class, fn( Simple_Container $c ): Get_Login_Form_Handler => new Get_Login_Form_Handler( $c->get( Option_Gateway_Interface::class ), $c->get( Settings_Service_Interface::class ) ) );
        $container->alias( 'get_login_form_handler', Get_Login_Form_Handler::class );

        $container->set( Get_Password_Lost_Form_Handler::class, fn(): Get_Password_Lost_Form_Handler => new Get_Password_Lost_Form_Handler() );
        $container->alias( 'get_password_lost_form_handler', Get_Password_Lost_Form_Handler::class );

        $container->set( Get_Password_Reset_Form_Handler::class, fn(): Get_Password_Reset_Form_Handler => new Get_Password_Reset_Form_Handler() );
        $container->alias( 'get_password_reset_form_handler', Get_Password_Reset_Form_Handler::class );

        $container->set( Get_Member_Account_Form_Handler::class, fn( Simple_Container $c ): Get_Member_Account_Form_Handler => new Get_Member_Account_Form_Handler( $c->get( User_Repository_Interface::class ) ) );
        $container->alias( 'get_member_account_form_handler', Get_Member_Account_Form_Handler::class );

        $container->set(
            Login_Shortcode_Adapter::class,
            fn( Simple_Container $c ): Login_Shortcode_Adapter => new Login_Shortcode_Adapter(
                $app,
                view_presenter: $c->get( Shortcode_View_Presenter_Interface::class ),
                login_form_handler: $c->get( Get_Login_Form_Handler::class ),
                password_lost_form_handler: $c->get( Get_Password_Lost_Form_Handler::class ),
                password_reset_form_handler: $c->get( Get_Password_Reset_Form_Handler::class ),
                member_account_form_handler: $c->get( Get_Member_Account_Form_Handler::class ),
                template_renderer: $c->get( Template_Renderer_Interface::class ),
                security_service: $c->get( Security_Service_Interface::class ),
                query_vars: $c->get( Query_Vars_Interface::class )
            )
        );
        $container->alias( 'login_shortcode_adapter', Login_Shortcode_Adapter::class );

        $container->set( Get_Messages_Handler::class, fn( Simple_Container $c ): Get_Messages_Handler => new Get_Messages_Handler( $c->get( User_Repository_Interface::class ), $c->get( Message_Repository_Interface::class ) ) );
        $container->alias( 'get_messages_handler', Get_Messages_Handler::class );

        $container->set( Get_Message_Handler::class, fn( Simple_Container $c ): Get_Message_Handler => new Get_Message_Handler( $c->get( Message_Repository_Interface::class ) ) );
        $container->alias( 'get_message_handler', Get_Message_Handler::class );

        $container->set(
            Message_Shortcode_Adapter::class,
            fn( Simple_Container $c ): Message_Shortcode_Adapter => new Message_Shortcode_Adapter(
                $app,
                view_presenter: $c->get( Shortcode_View_Presenter_Interface::class ),
                messages_handler: $c->get( Get_Messages_Handler::class ),
                message_handler: $c->get( Get_Message_Handler::class ),
                message_presenter: $c->get( Message_Presenter_Interface::class ),
                template_renderer: $c->get( Template_Renderer_Interface::class ),
                security_service: $c->get( Security_Service_Interface::class ),
                query_vars: $c->get( Query_Vars_Interface::class )
            )
        );
        $container->alias( 'message_shortcode_adapter', Message_Shortcode_Adapter::class );

        $container->set(
            Get_Favourites_Handler::class,
            fn( Simple_Container $c ): Get_Favourites_Handler => new Get_Favourites_Handler(
                $c->get( User_Repository_Interface::class ),
                $c->get( Club_Repository_Interface::class )
            )
        );
        $container->alias( 'get_favourites_handler', Get_Favourites_Handler::class );

        $container->set(
            Get_Invoice_Handler::class,
            fn( Simple_Container $c ): Get_Invoice_Handler => new Get_Invoice_Handler(
                $c->get( Finance_Service_Interface::class ),
                $c->get( Settings_Service_Interface::class ),
                $c->get( Option_Gateway_Interface::class )
            )
        );
        $container->alias( 'get_invoice_handler', Get_Invoice_Handler::class );

        $container->set(
            Get_Purchase_Order_Handler::class,
            fn( Simple_Container $c ): Get_Purchase_Order_Handler => new Get_Purchase_Order_Handler(
                $c->get( Finance_Service_Interface::class )
            )
        );
        $container->alias( 'get_purchase_order_handler', Get_Purchase_Order_Handler::class );

        $container->set(
            Get_Memberships_Handler::class,
            fn( Simple_Container $c ): Get_Memberships_Handler => new Get_Memberships_Handler(
                $c->get( Player_Service_Interface::class ),
                $c->get( Registration_Service_Interface::class )
            )
        );
        $container->alias( 'get_memberships_handler', Get_Memberships_Handler::class );

        $container->set( Get_Alert_Handler::class, fn(): Get_Alert_Handler => new Get_Alert_Handler() );
        $container->alias( 'get_alert_handler', Get_Alert_Handler::class );

        $container->set(
            General_Shortcode_Adapter::class,
            fn( Simple_Container $c ): General_Shortcode_Adapter => new General_Shortcode_Adapter(
                $app,
                view_presenter: $c->get( Shortcode_View_Presenter_Interface::class ),
                players_handler: $c->get( Get_Players_Handler::class ),
                player_handler: $c->get( Get_Player_Handler::class ),
                favourites_handler: $c->get( Get_Favourites_Handler::class ),
                invoice_handler: $c->get( Get_Invoice_Handler::class ),
                purchase_order_handler: $c->get( Get_Purchase_Order_Handler::class ),
                memberships_handler: $c->get( Get_Memberships_Handler::class ),
                player_search_handler: $c->get( Get_Player_Search_Handler::class ),
                team_order_handler: $c->get( Get_Team_Order_Handler::class ),
                alert_handler: $c->get( Get_Alert_Handler::class ),
                player_presenter: $c->get( Player_Presenter_Interface::class ),
                invoice_presenter: $c->get( Invoice_Presenter_Interface::class ),
                template_renderer: $c->get( Template_Renderer_Interface::class ),
                security_service: $c->get( Security_Service_Interface::class ),
                query_vars: $c->get( Query_Vars_Interface::class )
            )
        );
        $container->alias( 'general_shortcode_adapter', General_Shortcode_Adapter::class );
    }
}
