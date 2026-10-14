<?php

namespace Racketmanager\Services\Container;

use Racketmanager\Application\Club\Queries\Get_Club_Competitions_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Invoices_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Players_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Role_Modal_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Roles_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Handler;
use Racketmanager\Application\Fixture\Queries\Get_Fixture_Details_Handler;
use Racketmanager\Application\League\Queries\Get_League_Archive_Handler;
use Racketmanager\Application\League\Queries\Get_League_Championship_Handler;
use Racketmanager\Application\League\Queries\Get_League_Crosstable_Handler;
use Racketmanager\Application\League\Queries\Get_League_Last_5_Handler;
use Racketmanager\Application\League\Queries\Get_League_Match_Dropdown_Handler;
use Racketmanager\Application\League\Queries\Get_League_Matches_Handler;
use Racketmanager\Application\League\Queries\Get_League_Players_Handler;
use Racketmanager\Application\League\Queries\Get_League_Season_Dropdown_Handler;
use Racketmanager\Application\League\Queries\Get_League_Standings_Handler;
use Racketmanager\Application\League\Queries\Get_League_Teams_Handler;
use Racketmanager\Application\Matches\Queries\Get_Daily_Matches_Handler;
use Racketmanager\Application\Matches\Queries\Get_Latest_Results_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Detail_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Header_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Score_Handler;
use Racketmanager\Infrastructure\Persistence\Legacy_Fixture_Listing_Finder;
use Racketmanager\Presenters\Club_Shortcode_Presenter;
use Racketmanager\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presenters\League_Standings_Presenter;
use Racketmanager\Presenters\Match_Shortcode_Presenter;
use Racketmanager\Presenters\Matches_List_Presenter;
use Racketmanager\Presenters\Shortcode_View_Presenter;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Domain\Ports\Rating_Gateway_Interface;
use Racketmanager\Exceptions\Interface_Exception;
use Racketmanager\Gateways\Mail\Mailer_Interface;
use Racketmanager\Gateways\Payment\Payment_Gateway_Interface;
use Racketmanager\Infrastructure\Gateways\Mail\Wp_Mailer;
use Racketmanager\Infrastructure\Gateways\Payment\Stripe_Payment_Gateway;
use Racketmanager\Infrastructure\Gateways\Rating\Lta_Wtn_Gateway_Adapter;
use Racketmanager\Infrastructure\Wordpress\Export\Export_Delivery_Adapter;
use Racketmanager\Infrastructure\Wordpress\Rest\Rest_Controller_Adapter;
use Racketmanager\Presenters\Club_Presenter;
use Racketmanager\Presenters\Fixture_Presenter;
use Racketmanager\Presenters\Player_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Admin\Controllers\Tournament_Contact_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Teams_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Draw_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Information_Admin_Controller;
use Racketmanager\Services\Admin\Overview\Tournament_Overview_Action_Dispatcher;
use Racketmanager\Services\Admin\Tournament\Tournament_Fixtures_Admin_Service;
use Racketmanager\Admin\Controllers\Tournament_Fixtures_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Competition_Config_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Event_Config_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Team_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Setup_Event_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Setup_Admin_Controller;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Championship_Admin_Service;
use Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Role_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Team_Action_Dispatcher;
use Racketmanager\Services\Admin\Competition\Competition_Action_Dispatcher;
use Racketmanager\Services\Admin\Competition\Competition_Season_Action_Dispatcher;
use Racketmanager\Services\Admin\Cup\Cup_Plan_Action_Dispatcher;
use Racketmanager\Services\Admin\Cup\Cup_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\Event\Event_Action_Dispatcher;
use Racketmanager\Services\Admin\Finance\Finance_Charge_Action_Dispatcher;
use Racketmanager\Services\Admin\Finance\Finance_Invoice_Action_Dispatcher;
use Racketmanager\Services\Admin\Player\Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Player\Player_Error_Action_Dispatcher;
use Racketmanager\Services\Admin\Player\Player_Request_Action_Dispatcher;
use Racketmanager\Services\Admin\Index\Index_Action_Dispatcher;
use Racketmanager\Services\Admin\Import\Import_Action_Dispatcher;
use Racketmanager\Services\Admin\Options\Options_Action_Dispatcher;
use Racketmanager\Services\Admin\Result\Results_Checker_Action_Dispatcher;
use Racketmanager\Services\Admin\League\Event_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Constitution_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Contact_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Event_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Match_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Schedule_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Schedule_Service;
use Racketmanager\Services\Admin\League\League_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Team_Action_Dispatcher;
use Racketmanager\Services\Admin\League\Season_Delete_Action_Dispatcher;
use Racketmanager\Services\Admin\Season\Season_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Wp_Action_Guard;
use Racketmanager\Admin\Presenters\Admin_Date_Presenter;
use Racketmanager\Admin\Presenters\Admin_League_Matches_Presenter;
use Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter;
use Racketmanager\Admin\Presenters\Admin_Results_Match_Presenter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Infrastructure\Wordpress\Admin\Wp_Admin_Redirector;
use Racketmanager\Services\Admin\Tournament\Tournament_Contact_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Admin\Tournament\Tournament_Action_Dispatcher;
use Racketmanager\Services\Admin\Tournament\Tournament_Information_Action_Dispatcher;
use Racketmanager\Repositories\Charge_Repository;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Invoice_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Registration_Repository;
use Racketmanager\Repositories\Club_Role_Repository;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Error_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Registration_Repository_Interface;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Repositories\Player_Error_Repository;
use Racketmanager\Repositories\Result_Repository;
use Racketmanager\Repositories\Interfaces\Result_Repository_Interface;
use Racketmanager\Repositories\Results_Checker_Repository;
use Racketmanager\Repositories\Results_Report_Repository;
use Racketmanager\Repositories\Rubber_Repository;
use Racketmanager\Repositories\Season_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Repositories\Tournament_Entry_Repository;
use Racketmanager\Repositories\Tournament_Repository;
use Racketmanager\Admin\Controllers\Tournament_Plan_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Tournaments_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Overview_Admin_Controller;
use Racketmanager\Services\Competition\Competition_Notification_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Presenters\Notification_Presenter;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Notify_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Export\Export_Service;
use Racketmanager\Services\Result_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Result\Result_Reporting_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Admin\Controllers\Tournament_Admin_Controller;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Fixture\Fixture_Lifecycle_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Domain\Fixture\Services\Fixture_Detail_Service as Domain_Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Finalization_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Service_Provider as Fixture_Service_Provider;
use Racketmanager\Services\Result\Results_Checker_Manager;
use Racketmanager\Presentation\Presenters\Results_Checker_Presenter;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Validator\Player_Validation_Service;
use Racketmanager\Services\Validator\Score_Validation_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Services\Competition\Knockout_Progression_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Infrastructure\Security\Security_Service;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory;
use Racketmanager\Infrastructure\Wordpress\Response\Logging_Json_Response_Factory;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Account_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Ajax\Admin_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Admin_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Ajax\Ajax_Registry;
use Racketmanager\Infrastructure\Wordpress\Ajax\Club_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Club_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Ajax\Finance_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Finance_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Ajax\Frontend_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Frontend_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Ajax\Tournament_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Tournament_Ajax_Controller;
use Racketmanager\Application\Event\Queries\Get_Event_Clubs_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Draw_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Dropdown_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Matches_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Partner_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Players_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Standings_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Team_Matches_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Handler;
use Racketmanager\Application\Event\Queries\Get_Team_Order_Players_Handler;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Club_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Competition_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Email_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Fixture_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Fixture_Shortcode_Controller;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\General_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\League_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Login_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Matches_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Message_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Tournament_Shortcode_Adapter;
use Racketmanager\Application\Tournament\Queries\Get_Latest_Tournament_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Order_Of_Play_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Draws_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Events_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Match_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Matches_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Overview_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Players_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Winners_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Withdrawal_Handler;
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
use Racketmanager\Application\Message\Queries\Get_Message_Handler;
use Racketmanager\Application\Message\Queries\Get_Messages_Handler;
use Racketmanager\Presenters\Email_Shortcode_Presenter;

/**
 * Registers core services in the Simple_Container.
 */
final class Container_Bootstrap {
    public static function boot( RacketManager $app ): Simple_Container {
        $c = new Simple_Container();

        self::register_repositories( $c );
        self::register_external_clients( $c, $app );
        self::register_services( $c, $app );
        self::register_export_services( $c );
        self::register_admin_controllers( $c );
        self::register_admin_draw_controllers( $c );
        self::register_admin_tournament_controllers( $c );
        self::register_ajax_components( $c, $app );
        self::register_shortcode_components( $c, $app );

        return $c;
    }

    private static function register_ajax_components( Simple_Container $c, RacketManager $app ): void {
        $c->set( 'security_service', fn() => new Security_Service() );
        $c->set( 'json_response_factory', fn() => new Json_Response_Factory() );
        $c->set( 'ajax_registry', fn() => new Ajax_Registry() );

        $c->set( 'fixture_ajax_adapter', fn( Simple_Container $c ) => new Fixture_Ajax_Adapter(
            $c,
            $c->get( 'security_service' ),
            new Logging_Json_Response_Factory( $c->get( 'json_response_factory' ) ),
            $c->get( 'fixture_detail_service' ),
            $c->get( 'view_renderer' ),
            $c->get( 'fixture_presenter' ),
            $c->get( 'get_fixture_details_handler' )
        ) );
        $c->set( 'fixture_ajax_controller', fn( Simple_Container $c ) => new Fixture_Ajax_Controller( $app, $c->get( 'fixture_ajax_adapter' ) ) );

        $c->set( 'club_ajax_adapter', fn( Simple_Container $c ) => new Club_Ajax_Adapter(
            $c->get( 'security_service' ),
            $c->get( 'json_response_factory' ),
            $c->get( 'club_service' ),
            $c->get( 'registration_service' )
        ) );
        $c->set( 'club_ajax_controller', fn( Simple_Container $c ) => new Club_Ajax_Controller( $app, $c->get( 'club_ajax_adapter' ) ) );

        $c->set( 'tournament_ajax_adapter', fn( Simple_Container $c ) => new Tournament_Ajax_Adapter(
            $c->get( 'security_service' ),
            $c->get( 'json_response_factory' ),
            $c->get( 'competition_entry_service' ),
            $c->get( 'finance_service' )
        ) );
        $c->set( 'tournament_ajax_controller', fn( Simple_Container $c ) => new Tournament_Ajax_Controller( $app, $c->get( 'tournament_ajax_adapter' ) ) );

        $c->set( 'account_ajax_adapter', fn( Simple_Container $c ) => new Account_Ajax_Adapter(
            $c->get( 'security_service' ),
            $c->get( 'json_response_factory' )
        ) );
        $c->set( 'account_ajax_controller', fn( Simple_Container $c ) => new Account_Ajax_Controller( $app, $c->get( 'account_ajax_adapter' ) ) );

        $c->set( 'finance_ajax_adapter', fn( Simple_Container $c ) => new Finance_Ajax_Adapter(
            $c->get( 'security_service' ),
            $c->get( 'json_response_factory' ),
            $c->get( 'finance_service' )
        ) );
        $c->set( 'finance_ajax_controller', fn( Simple_Container $c ) => new Finance_Ajax_Controller( $app, $c->get( 'finance_ajax_adapter' ) ) );

        $c->set( 'frontend_ajax_adapter', fn( Simple_Container $c ) => new Frontend_Ajax_Adapter(
            $c->get( 'security_service' ),
            $c->get( 'json_response_factory' ),
            $c->get( 'competition_service' ),
            $c->get( 'competition_entry_service' ),
            $c->get( 'team_service' ),
            $c->get( 'player_service' ),
            $c->get( 'registration_service' ),
            $c->get( 'club_service' ),
            $c->get( 'tournament_service' ),
            $c->get( 'fixture_repository' )
        ) );
        $c->set( 'frontend_ajax_controller', fn( Simple_Container $c ) => new Frontend_Ajax_Controller( $app, $c->get( 'frontend_ajax_adapter' ) ) );

        $c->set( 'admin_ajax_adapter', fn( Simple_Container $c ) => new Admin_Ajax_Adapter(
            $c->get( 'security_service' ),
            $c->get( 'json_response_factory' ),
            $app,
            $c->get( 'competition_service' ),
            $c->get( 'competition_entry_service' ),
            $c->get( 'notification_service' ),
            $c->get( 'fixture_repository' )
        ) );
        $c->set( 'admin_ajax_controller', fn( Simple_Container $c ) => new Admin_Ajax_Controller( $app, $c->get( 'admin_ajax_adapter' ) ) );
    }

    private static function register_shortcode_components( Simple_Container $c, RacketManager $app ): void {
        $c->set( 'fixture_shortcode_controller', fn() => new Fixture_Shortcode_Controller( $app ) );

        $c->set( 'get_club_handler', fn( Simple_Container $c ) => new Get_Club_Handler(
            $c->get( 'club_service' ),
            $c->get( 'settings_service' )
        ) );
        $c->set( 'club_shortcode_presenter', fn( Simple_Container $c ) => new Club_Shortcode_Presenter(
            $c->get( 'view_renderer' ),
            $c->get( 'club_presenter' ),
            $c->get( 'player_presenter' )
        ) );
        $c->set( 'shortcode_template_renderer', fn( Simple_Container $c ) => new Shortcode_Template_Renderer(
            $c->get( 'view_renderer' ),
            $c->get( 'club_presenter' ),
            $c->get( 'player_presenter' )
        ) );
        $c->set( 'shortcode_view_presenter', fn( Simple_Container $c ) => new Shortcode_View_Presenter( $c->get( 'shortcode_template_renderer' ) ) );
        $c->set( 'get_club_players_handler', fn( Simple_Container $c ) => new Get_Club_Players_Handler(
            $c->get( 'club_service' ),
            $c->get( 'player_service' ),
            $c->get( 'registration_service' )
        ) );
        $c->set( 'get_club_roles_handler', fn( Simple_Container $c ) => new Get_Club_Roles_Handler( $c->get( 'club_service' ) ) );
        $c->set( 'get_club_role_modal_handler', fn( Simple_Container $c ) => new Get_Club_Role_Modal_Handler( $c->get( 'club_service' ) ) );
        $c->set( 'get_club_competitions_handler', fn( Simple_Container $c ) => new Get_Club_Competitions_Handler(
            $c->get( 'club_service' ),
            $c->get( 'competition_service' ),
            $c->get( 'competition_repository' )
        ) );
        $c->set( 'get_club_invoices_handler', fn( Simple_Container $c ) => new Get_Club_Invoices_Handler(
            $c->get( 'club_service' ),
            $c->get( 'finance_service' )
        ) );
        $c->set( 'club_shortcode_adapter', fn( Simple_Container $c ) => new Club_Shortcode_Adapter(
            club_service: $c->get( 'club_service' ),
            get_club_handler: $c->get( 'get_club_handler' ),
            club_shortcode_presenter: $c->get( 'club_shortcode_presenter' ),
            players_handler: $c->get( 'get_club_players_handler' ),
            roles_handler: $c->get( 'get_club_roles_handler' ),
            role_modal_handler: $c->get( 'get_club_role_modal_handler' ),
            competitions_handler: $c->get( 'get_club_competitions_handler' ),
            invoices_handler: $c->get( 'get_club_invoices_handler' ),
            view_presenter: $c->get( 'shortcode_view_presenter' )
        ) );

        $c->set( 'get_competition_handler', fn( Simple_Container $c ) => new Get_Competition_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'club_service' ),
            $c->get( 'player_service' )
        ) );
        $c->set( 'get_competition_overview_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Competition\Queries\Get_Competition_Overview_Handler(
            $c->get( 'competition_service' )
        ) );
        $c->set( 'get_competition_events_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Competition\Queries\Get_Competition_Events_Handler(
            $c->get( 'competition_service' )
        ) );
        $c->set( 'get_competition_teams_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Competition\Queries\Get_Competition_Teams_Handler(
            $c->get( 'competition_service' )
        ) );
        $c->set( 'get_competition_clubs_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Competition\Queries\Get_Competition_Clubs_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'club_service' )
        ) );
        $c->set( 'get_competition_players_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Competition\Queries\Get_Competition_Players_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'player_service' )
        ) );
        $c->set( 'get_competition_winners_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Competition\Queries\Get_Competition_Winners_Handler(
            $c->get( 'competition_service' )
        ) );
        $c->set( 'get_competition_dropdown_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Competition\Queries\Get_Competition_Dropdown_Handler(
            $c->get( 'competition_service' )
        ) );
        $c->set( 'get_competitions_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Competition\Queries\Get_Competitions_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'tournament_service' ),
            $c->get( 'player_service' )
        ) );
        $c->set( 'get_competition_payment_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Competition\Queries\Get_Competition_Payment_Handler(
            $c->get( 'tournament_service' ),
            $c->get( 'player_service' ),
            $c->get( 'finance_service' ),
            $c->get( 'competition_entry_service' )
        ) );
        $c->set( 'club_entry_selection_service', fn( Simple_Container $c ) => new \Racketmanager\Application\Competition\Queries\Club_Entry_Selection_Service(
            $c->get( 'club_service' ),
            $c->get( 'player_service' )
        ) );
        $c->set( 'get_competition_entry_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Handler(
            club_entry_selection: $c->get( 'club_entry_selection_service' ),
            competition_service: $c->get( 'competition_service' ),
            tournament_service: $c->get( 'tournament_service' ),
            player_service: $c->get( 'player_service' ),
            club_service: $c->get( 'club_service' ),
            team_service: $c->get( 'team_service' ),
            registration_service: $c->get( 'registration_service' ),
            competition_entry_service: $c->get( 'competition_entry_service' ),
            finance_service: $c->get( 'finance_service' )
        ) );
        $c->set( 'competition_shortcode_presenter', fn( Simple_Container $c ) => new Competition_Shortcode_Presenter(
            $c->get( 'view_renderer' ),
            $c->get( 'club_presenter' ),
            $c->get( 'player_presenter' )
        ) );
        $c->set( 'competition_shortcode_adapter', fn( Simple_Container $c ) => new Competition_Shortcode_Adapter(
            competition_service: $c->get( 'competition_service' ),
            club_service: $c->get( 'club_service' ),
            team_service: $c->get( 'team_service' ),
            player_service: $c->get( 'player_service' ),
            tournament_service: $c->get( 'tournament_service' ),
            finance_service: $c->get( 'finance_service' ),
            registration_service: $c->get( 'registration_service' ),
            competition_entry_service: $c->get( 'competition_entry_service' ),
            template_renderer: new Shortcode_Template_Renderer(
                $c->get( 'view_renderer' ),
                $c->get( 'club_presenter' ),
                $c->get( 'player_presenter' )
            ),
            get_competition_handler: $c->get( 'get_competition_handler' ),
            competition_shortcode_presenter: $c->get( 'competition_shortcode_presenter' ),
            overview_handler: $c->get( 'get_competition_overview_handler' ),
            events_handler: $c->get( 'get_competition_events_handler' ),
            teams_handler: $c->get( 'get_competition_teams_handler' ),
            clubs_handler: $c->get( 'get_competition_clubs_handler' ),
            players_handler: $c->get( 'get_competition_players_handler' ),
            winners_handler: $c->get( 'get_competition_winners_handler' ),
            dropdown_handler: $c->get( 'get_competition_dropdown_handler' ),
            competitions_handler: $c->get( 'get_competitions_handler' ),
            payment_handler: $c->get( 'get_competition_payment_handler' ),
            club_entry_selection: $c->get( 'club_entry_selection_service' ),
            entry_handler: $c->get( 'get_competition_entry_handler' ),
            view_presenter: $c->get( 'shortcode_view_presenter' ),
            racketmanager: $app
        ) );

        $c->set( 'get_league_standings_handler', fn( Simple_Container $c ) => new Get_League_Standings_Handler(
            $c->get( 'league_service' ),
            $c->get( 'league_repository' )
        ) );
        $c->set( 'get_league_championship_handler', fn( Simple_Container $c ) => new Get_League_Championship_Handler(
            $c->get( 'league_service' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'competition_service' ),
            $c->get( 'team_service' ),
            $c->get( 'fixture_link_service' ),
            $c->get( 'fixture_permission_service' ),
            $c->get( 'shortcode_template_renderer' )
        ) );
        $c->set( 'get_league_archive_handler', fn( Simple_Container $c ) => new Get_League_Archive_Handler(
            $c->get( 'league_service' ),
            $c->get( 'competition_service' ),
            $c->get( 'shortcode_template_renderer' )
        ) );
        $c->set( 'get_league_crosstable_handler', fn( Simple_Container $c ) => new Get_League_Crosstable_Handler(
            $c->get( 'league_service' ),
            $c->get( 'shortcode_template_renderer' )
        ) );
        $c->set( 'get_league_matches_handler', fn( Simple_Container $c ) => new Get_League_Matches_Handler(
            $c->get( 'league_service' ),
            $c->get( 'shortcode_template_renderer' )
        ) );
        $c->set( 'get_league_teams_handler', fn( Simple_Container $c ) => new Get_League_Teams_Handler(
            $c->get( 'league_service' ),
            $c->get( 'team_service' )
        ) );
        $c->set( 'get_league_players_handler', fn( Simple_Container $c ) => new Get_League_Players_Handler(
            $c->get( 'league_service' ),
            $c->get( 'player_service' ),
            $c->get( 'shortcode_template_renderer' )
        ) );
        $c->set( 'get_league_season_dropdown_handler', fn( Simple_Container $c ) => new Get_League_Season_Dropdown_Handler(
            $c->get( 'league_service' ),
            $c->get( 'shortcode_template_renderer' )
        ) );
        $c->set( 'get_league_match_dropdown_handler', fn( Simple_Container $c ) => new Get_League_Match_Dropdown_Handler(
            $c->get( 'league_service' ),
            $c->get( 'shortcode_template_renderer' )
        ) );
        $c->set( 'get_league_last_5_handler', fn( Simple_Container $c ) => new Get_League_Last_5_Handler(
            $c->get( 'league_service' ),
            $c->get( 'shortcode_template_renderer' )
        ) );
        $c->set( 'league_standings_presenter', fn( Simple_Container $c ) => new League_Standings_Presenter(
            $c->get( 'view_renderer' ),
            $c->get( 'club_presenter' ),
            $c->get( 'player_presenter' )
        ) );
        $c->set( 'league_shortcode_adapter', fn( Simple_Container $c ) => new League_Shortcode_Adapter(
            $app,
            get_league_standings_handler: $c->get( 'get_league_standings_handler' ),
            league_standings_presenter: $c->get( 'league_standings_presenter' ),
            view_presenter: $c->get( 'shortcode_view_presenter' ),
            championship_handler: $c->get( 'get_league_championship_handler' ),
            archive_handler: $c->get( 'get_league_archive_handler' ),
            crosstable_handler: $c->get( 'get_league_crosstable_handler' ),
            matches_handler: $c->get( 'get_league_matches_handler' ),
            teams_handler: $c->get( 'get_league_teams_handler' ),
            players_handler: $c->get( 'get_league_players_handler' ),
            season_dropdown_handler: $c->get( 'get_league_season_dropdown_handler' ),
            match_dropdown_handler: $c->get( 'get_league_match_dropdown_handler' ),
            last_5_handler: $c->get( 'get_league_last_5_handler' ),
            template_renderer: $c->get( 'shortcode_template_renderer' )
        ) );

        $c->set( 'get_event_handler', fn( Simple_Container $c ) => new Get_Event_Handler(
            $c->get( 'competition_service' )
        ) );
        $c->set( 'get_event_standings_handler', fn( Simple_Container $c ) => new Get_Event_Standings_Handler(
            $c->get( 'competition_service' )
        ) );
        $c->set( 'get_event_draw_handler', fn( Simple_Container $c ) => new Get_Event_Draw_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'league_service' )
        ) );
        $c->set( 'get_event_matches_handler', fn( Simple_Container $c ) => new Get_Event_Matches_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'league_service' )
        ) );
        $c->set( 'get_event_clubs_handler', fn( Simple_Container $c ) => new Get_Event_Clubs_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'club_service' )
        ) );
        $c->set( 'get_event_teams_handler', fn( Simple_Container $c ) => new Get_Event_Teams_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'team_service' )
        ) );
        $c->set( 'get_event_players_handler', fn( Simple_Container $c ) => new Get_Event_Players_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'player_service' )
        ) );
        $c->set( 'get_event_partner_handler', fn( Simple_Container $c ) => new Get_Event_Partner_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'player_service' )
        ) );
        $c->set( 'get_event_team_matches_handler', fn( Simple_Container $c ) => new Get_Event_Team_Matches_Handler(
            $c->get( 'competition_service' )
        ) );
        $c->set( 'get_team_order_players_handler', fn( Simple_Container $c ) => new Get_Team_Order_Players_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'club_service' ),
            $c->get( 'registration_service' ),
            $app
        ) );
        $c->set( 'get_event_dropdown_handler', fn( Simple_Container $c ) => new Get_Event_Dropdown_Handler(
            $c->get( 'competition_service' )
        ) );

        $c->set( 'event_shortcode_adapter', fn( Simple_Container $c ) => new Event_Shortcode_Adapter(
            $app,
            view_presenter: $c->get( 'shortcode_view_presenter' ),
            event_handler: $c->get( 'get_event_handler' ),
            standings_handler: $c->get( 'get_event_standings_handler' ),
            draw_handler: $c->get( 'get_event_draw_handler' ),
            matches_handler: $c->get( 'get_event_matches_handler' ),
            clubs_handler: $c->get( 'get_event_clubs_handler' ),
            teams_handler: $c->get( 'get_event_teams_handler' ),
            players_handler: $c->get( 'get_event_players_handler' ),
            partner_handler: $c->get( 'get_event_partner_handler' ),
            team_matches_handler: $c->get( 'get_event_team_matches_handler' ),
            team_order_players_handler: $c->get( 'get_team_order_players_handler' ),
            dropdown_handler: $c->get( 'get_event_dropdown_handler' ),
            template_renderer: $c->get( 'shortcode_template_renderer' )
        ) );
        $c->set( 'fixture_listing_finder', fn() => new Legacy_Fixture_Listing_Finder( $app ) );
        $c->set( 'get_daily_matches_handler', fn( Simple_Container $c ) => new Get_Daily_Matches_Handler( $c->get( 'fixture_listing_finder' ) ) );
        $c->set( 'get_latest_results_handler', fn( Simple_Container $c ) => new Get_Latest_Results_Handler(
            $c->get( 'fixture_listing_finder' ),
            $c->get( 'club_repository' ),
            $c->get( 'competition_repository' )
        ) );
        $c->set( 'matches_list_presenter', fn( Simple_Container $c ) => new Matches_List_Presenter(
            $c->get( 'competition_service' ),
            $c->get( 'team_service' ),
            $c->get( 'view_renderer' ),
            $c->get( 'fixture_link_service' ),
            $c->get( 'fixture_permission_service' ),
            $c->get( 'club_presenter' ),
            $c->get( 'player_presenter' )
        ) );
        $c->set( 'get_match_score_handler', fn( Simple_Container $c ) => new Get_Match_Score_Handler( $c->get( 'fixture_detail_service' ) ) );
        $c->set( 'get_match_header_handler', fn( Simple_Container $c ) => new Get_Match_Header_Handler( $c->get( 'fixture_detail_service' ) ) );
        $c->set( 'get_match_detail_handler', fn( Simple_Container $c ) => new Get_Match_Detail_Handler(
            $c->get( 'fixture_detail_service' ),
            $c->get( 'player_service' )
        ) );
        $c->set( 'match_shortcode_presenter', fn( Simple_Container $c ) => new Match_Shortcode_Presenter( $c->get( 'shortcode_view_presenter' ) ) );
        $c->set( 'matches_shortcode_adapter', fn( Simple_Container $c ) => new Matches_Shortcode_Adapter(
            daily_matches_handler: $c->get( 'get_daily_matches_handler' ),
            latest_results_handler: $c->get( 'get_latest_results_handler' ),
            presenter: $c->get( 'matches_list_presenter' ),
            score_handler: $c->get( 'get_match_score_handler' ),
            header_handler: $c->get( 'get_match_header_handler' ),
            detail_handler: $c->get( 'get_match_detail_handler' ),
            match_presenter: $c->get( 'match_shortcode_presenter' )
        ) );
        $c->set( 'email_shortcode_presenter', fn( Simple_Container $c ) => new Email_Shortcode_Presenter(
            tournament_service: $c->get( 'tournament_service' ),
            competition_service: $c->get( 'competition_service' ),
            team_service: $c->get( 'team_service' ),
            fixture_link_service: $c->get( 'fixture_link_service' ),
            permission_service: $c->get( 'fixture_permission_service' ),
            site_url: $app->site_url,
            site_name: $app->site_name
        ) );
        $c->set( 'email_shortcode_adapter', fn( Simple_Container $c ) => new Email_Shortcode_Adapter(
            fixture_repository: $c->get( 'fixture_repository' ),
            presenter: $c->get( 'email_shortcode_presenter' ),
            template_renderer: new Shortcode_Template_Renderer(
                $c->get( 'view_renderer' ),
                $c->get( 'club_presenter' ),
                $c->get( 'player_presenter' )
            )
        ) );
        $c->set( 'get_tournament_handler', fn( Simple_Container $c ) => new Get_Tournament_Handler(
            $c->get( 'tournament_service' ),
            $c->get( 'get_latest_tournament_handler' )
        ) );
        $c->set( 'get_tournament_overview_handler', fn( Simple_Container $c ) => new Get_Tournament_Overview_Handler( $c->get( 'tournament_service' ) ) );
        $c->set( 'get_tournament_events_handler', fn( Simple_Container $c ) => new Get_Tournament_Events_Handler( $c->get( 'tournament_service' ) ) );
        $c->set( 'get_tournament_draws_handler', fn( Simple_Container $c ) => new Get_Tournament_Draws_Handler( $c->get( 'tournament_service' ) ) );
        $c->set( 'get_tournament_players_handler', fn( Simple_Container $c ) => new Get_Tournament_Players_Handler( $c->get( 'tournament_service' ) ) );
        $c->set( 'get_tournament_winners_handler', fn( Simple_Container $c ) => new Get_Tournament_Winners_Handler( $c->get( 'tournament_service' ) ) );
        $c->set( 'get_tournament_matches_handler', fn( Simple_Container $c ) => new Get_Tournament_Matches_Handler( $c->get( 'tournament_service' ) ) );
        $c->set( 'get_tournament_match_handler', fn( Simple_Container $c ) => new Get_Tournament_Match_Handler(
            $c->get( 'tournament_service' ),
            $c->get( 'fixture_service' )
        ) );
        $c->set( 'get_order_of_play_handler', fn( Simple_Container $c ) => new Get_Order_Of_Play_Handler(
            $c->get( 'tournament_service' ),
            $c->get( 'competition_repository' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'competition_service' ),
            $c->get( 'team_service' )
        ) );
        $c->set( 'get_latest_tournament_handler', fn( Simple_Container $c ) => new Get_Latest_Tournament_Handler( $c->get( 'tournament_service' ) ) );
        $c->set( 'get_tournament_withdrawal_handler', fn( Simple_Container $c ) => new Get_Tournament_Withdrawal_Handler( $c->get( 'tournament_service' ) ) );

        $c->set( 'tournament_shortcode_adapter', fn( Simple_Container $c ) => new Tournament_Shortcode_Adapter(
            $app,
            view_presenter: $c->get( 'shortcode_view_presenter' ),
            tournament_handler: $c->get( 'get_tournament_handler' ),
            overview_handler: $c->get( 'get_tournament_overview_handler' ),
            events_handler: $c->get( 'get_tournament_events_handler' ),
            draws_handler: $c->get( 'get_tournament_draws_handler' ),
            players_handler: $c->get( 'get_tournament_players_handler' ),
            winners_handler: $c->get( 'get_tournament_winners_handler' ),
            matches_handler: $c->get( 'get_tournament_matches_handler' ),
            match_handler: $c->get( 'get_tournament_match_handler' ),
            order_of_play_handler: $c->get( 'get_order_of_play_handler' ),
            latest_tournament_handler: $c->get( 'get_latest_tournament_handler' ),
            withdrawal_handler: $c->get( 'get_tournament_withdrawal_handler' ),
            template_renderer: $c->get( 'shortcode_template_renderer' )
        ) );
        $c->set( 'get_login_form_handler', fn() => new Get_Login_Form_Handler( $app ) );
        $c->set( 'get_password_lost_form_handler', fn() => new Get_Password_Lost_Form_Handler() );
        $c->set( 'get_password_reset_form_handler', fn() => new Get_Password_Reset_Form_Handler() );
        $c->set( 'get_member_account_form_handler', fn( Simple_Container $c ) => new Get_Member_Account_Form_Handler( $c->get( 'player_service' ) ) );

        $c->set( 'login_shortcode_adapter', fn( Simple_Container $c ) => new Login_Shortcode_Adapter(
            $app,
            view_presenter: $c->get( 'shortcode_view_presenter' ),
            login_form_handler: $c->get( 'get_login_form_handler' ),
            password_lost_form_handler: $c->get( 'get_password_lost_form_handler' ),
            password_reset_form_handler: $c->get( 'get_password_reset_form_handler' ),
            member_account_form_handler: $c->get( 'get_member_account_form_handler' ),
            template_renderer: $c->get( 'shortcode_template_renderer' )
        ) );

        $c->set( 'get_messages_handler', fn() => new Get_Messages_Handler() );
        $c->set( 'get_message_handler', fn() => new Get_Message_Handler() );

        $c->set( 'message_shortcode_adapter', fn( Simple_Container $c ) => new Message_Shortcode_Adapter(
            $app,
            view_presenter: $c->get( 'shortcode_view_presenter' ),
            messages_handler: $c->get( 'get_messages_handler' ),
            message_handler: $c->get( 'get_message_handler' ),
            template_renderer: $c->get( 'shortcode_template_renderer' )
        ) );
        $c->set( 'get_players_handler', fn() => new Get_Players_Handler() );
        $c->set( 'get_player_handler', fn( Simple_Container $c ) => new Get_Player_Handler(
            $c->get( 'registration_service' ),
            $c->get( 'player_service' ),
            $c->get( 'tournament_service' )
        ) );
        $c->set( 'get_favourites_handler', fn() => new Get_Favourites_Handler() );
        $c->set( 'get_invoice_handler', fn( Simple_Container $c ) => new Get_Invoice_Handler(
            $c->get( 'finance_service' ),
            $app
        ) );
        $c->set( 'get_purchase_order_handler', fn( Simple_Container $c ) => new Get_Purchase_Order_Handler(
            $c->get( 'finance_service' )
        ) );
        $c->set( 'get_memberships_handler', fn( Simple_Container $c ) => new Get_Memberships_Handler(
            $c->get( 'player_service' ),
            $c->get( 'registration_service' )
        ) );
        $c->set( 'get_player_search_handler', fn() => new Get_Player_Search_Handler( $app ) );
        $c->set( 'get_team_order_handler', fn( Simple_Container $c ) => new Get_Team_Order_Handler(
            $c->get( 'club_service' ),
            $app
        ) );
        $c->set( 'get_alert_handler', fn() => new Get_Alert_Handler() );

        $c->set( 'general_shortcode_adapter', fn( Simple_Container $c ) => new General_Shortcode_Adapter(
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

    private static function register_repositories( Simple_Container $c ): void {
        $c->set( 'club_repository', fn() => new Club_Repository() );
        $c->set( 'registration_repository', fn(): Registration_Repository_Interface => new Registration_Repository() );
        $c->set( 'club_role_repository', fn(): Club_Role_Repository_Interface => new Club_Role_Repository() );
        $c->set( 'player_repository', fn() => new Player_Repository() );
        $c->set( 'player_error_repository', fn(): Player_Error_Repository_Interface => new Player_Error_Repository() );
        $c->set( 'team_repository', fn() => new Team_Repository() );
        $c->set( 'event_repository', fn() => new Event_Repository() );
        $c->set( 'fixture_repository', fn() => new Fixture_Repository() );
        $c->set( 'league_repository', fn() => new League_Repository() );
        $c->set( 'league_team_repository', fn() => new League_Team_Repository() );
        $c->set( 'competition_repository', fn() => new Competition_Repository() );
        $c->set( 'charge_repository', fn() => new Charge_Repository() );
        $c->set( 'invoice_repository', fn() => new Invoice_Repository() );
        $c->set( 'tournament_repository', fn() => new Tournament_Repository() );
        $c->set( 'tournament_entry_repository', fn() => new Tournament_Entry_Repository() );
        $c->set( 'season_repository', fn() => new Season_Repository() );
        $c->set( 'rubber_repository', fn() => new Rubber_Repository() );
        $c->set( 'results_checker_repository', fn() => new Results_Checker_Repository() );
        $c->set( 'results_report_repository', fn() => new Results_Report_Repository() );
        $c->set( 'result_repository', fn(): Result_Repository_Interface => new Result_Repository() );
    }

    private static function register_external_clients( Simple_Container $c, RacketManager $app ): void {
        $c->set( 'rating_gateway', fn(): Rating_Gateway_Interface => new Lta_Wtn_Gateway_Adapter() );
        $c->set( 'wtn_api_client', fn( Simple_Container $c ): Rating_Gateway_Interface => $c->get( 'rating_gateway' ) );
        $c->set( 'mailer', fn(): Mailer_Interface => new Wp_Mailer() );
        $c->set( 'payment_gateway', fn(): Payment_Gateway_Interface => new Stripe_Payment_Gateway( $app->get_options('billing') ) );
    }

    private static function register_services( Simple_Container $c, RacketManager $app ): void {
        self::register_core_services( $c, $app );
        self::register_fixture_services( $c, $app );
        self::register_result_services( $c );
    }

    private static function register_core_services( Simple_Container $c, RacketManager $app ): void {
        $c->set( 'racketmanager_app', fn() => $app );
        $c->set( 'settings_service', fn() => new Settings_Service( $app->get_options() ) );

        $c->set( 'player_query_service', function ( Simple_Container $c ) {
            global $wpdb;
            if ( ! $wpdb instanceof \wpdb ) {
                throw new \RuntimeException( 'Player_Query_Service requires a wpdb instance.' );
            }

            return new Player_Query_Service( $wpdb, $c->get( 'competition_repository' ) );
        } );

        $c->set( 'player_stats_service', function ( Simple_Container $c ) {
            return new Player_Stats_Service( $c->get( 'player_query_service' ) );
        } );

        $c->set( 'player_service', function ( Simple_Container $c ) use ( $app ) {
            return new Player_Service( $app, $c->get( 'player_repository' ), $c->get( 'player_error_repository' ), $c->get( 'club_role_repository' ), $c->get( 'wtn_api_client' ), $c->get( 'league_team_repository' ), $c->get( 'club_repository' ), $c->get( 'registration_repository' ), $c->get( 'mailer' ) );
        } );

        $c->set( 'competition_service', function ( Simple_Container $c ) use ( $app ) {
            return new Competition_Service( $app, $c->get( 'competition_repository' ), $c->get( 'club_repository' ), $c->get( 'event_repository' ), $c->get( 'league_repository' ), $c->get( 'league_team_repository' ), $c->get( 'season_repository' ), $c->get( 'team_repository' ), $c->get( 'fixture_repository' ) );
        } );

        $c->set( 'club_service', function ( Simple_Container $c ) {
            return new Club_Service( $c->get( 'club_repository' ), $c->get( 'registration_repository' ), $c->get( 'club_role_repository' ), $c->get( 'player_repository' ), $c->get( 'team_repository' ), $c->get( 'player_service' ), );
        } );

        $c->set( 'competition_entry_service', function ( Simple_Container $c ) use ( $app ) {
            return new Competition_Entry_Service( $app, $c->get( 'club_repository' ), $c->get( 'league_repository' ), $c->get( 'league_team_repository' ), $c->get( 'team_repository' ), $c->get( 'tournament_repository' ), $c->get( 'tournament_entry_repository' ), $c->get( 'club_service' ), $c->get( 'competition_service' ), $c->get( 'finance_service' ), $c->get( 'player_service' ), $c->get( 'tournament_service' ), $c->get( 'notify_service' ), $c->get( 'mailer' ) );
        } );

        $c->set( 'competition_notification_service', fn( Simple_Container $c ) => new Competition_Notification_Service( $c->get( 'mailer' ) ) );

        $c->set( 'team_service', function ( Simple_Container $c ) {
            return new Team_Service( $c->get( 'team_repository' ), $c->get( 'club_repository' ), $c->get( 'event_repository' ), $c->get( 'player_service' ), );
        } );

        $c->set( 'registration_service', function ( Simple_Container $c ) use ( $app ) {
            return new Registration_Service( $app, $c->get( 'registration_repository' ), $c->get( 'player_repository' ), $c->get( 'club_repository' ), $c->get( 'player_service' ), $c->get( 'mailer' ) );
        } );

        $c->set( 'league_service', function ( Simple_Container $c ) use ( $app ) {
            return new League_Service( $app, $c->get( 'league_repository' ), $c->get( 'event_repository' ), $c->get( 'league_team_repository' ), $c->get( 'team_repository' ), );
        } );

        $c->set( 'finance_service', function ( Simple_Container $c ) use ( $app ) {
            return new Finance_Service(
                $app,
                $c->get( 'charge_repository' ),
                $c->get( 'invoice_repository' ),
                $c->get( 'club_repository' ),
                $c->get( 'tournament_repository' ),
                $c->get( 'competition_service' ),
                $c->get( 'player_service' ),
                $c->get( 'payment_gateway' ),
                $c->get( 'mailer' )
            );
        } );

        $c->set( 'tournament_service', function ( Simple_Container $c ) use ( $app ) {
            $service = new Tournament_Service(
                $app,
                $c->get( 'tournament_repository' ),
                $c->get( 'charge_repository' ),
                $c->get( 'event_repository' ),
                $c->get( 'fixture_service' ),
                $c->get( 'league_team_repository' ),
                $c->get( 'tournament_entry_repository' ),
                $c->get( 'competition_service' ),
                $c->get( 'player_service' ),
                $c->get( 'club_service' ),
                $c->get( 'finance_service' ),
                $c->get( 'league_service' ),
                $c->get( 'mailer' )
            );
            $c->get( 'fixture_link_service' )->set_tournament_service( $service );
            return $service;
        } );

        $c->set( 'season_service', function ( Simple_Container $c ) use ( $app ) {
            return new Season_Service( $app, $c->get( 'season_repository' ), );
        } );

        $c->set( 'view_renderer', function () {
            return new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        } );

        $c->set( 'admin_flash_message_store', fn() => new Admin_Flash_Message_Store() );

        $c->set( 'admin_message_service', function ( Simple_Container $c ) {
            return new Admin_Message_Service( $c->get( 'admin_flash_message_store' ) );
        } );

        $c->set( 'admin_redirector', fn() => new Wp_Admin_Redirector() );

        $c->set( 'admin_action_adapter', function ( Simple_Container $c ) {
            return new Admin_Action_Adapter( $c->get( 'admin_message_service' ), $c->get( 'admin_redirector' ) );
        } );

        $c->set( 'admin_screen_presenter', function ( Simple_Container $c ) {
            return new Admin_Screen_Presenter( $c->get( 'view_renderer' ), $c->get( 'admin_message_service' ) );
        } );

        $c->set( 'admin_date_presenter', fn() => new Admin_Date_Presenter() );

        $c->set( 'admin_league_matches_presenter', function ( Simple_Container $c ) {
            return new Admin_League_Matches_Presenter( $c->get( 'admin_date_presenter' ) );
        } );

        $c->set( 'admin_championship_finals_presenter', function ( Simple_Container $c ) {
            return new Admin_Championship_Finals_Presenter( $c->get( 'admin_date_presenter' ) );
        } );

        $c->set( 'admin_results_match_presenter', function ( Simple_Container $c ) {
            return new Admin_Results_Match_Presenter( $c->get( 'admin_date_presenter' ) );
        } );

        $c->set( 'notify_service', fn( Simple_Container $c ) => new Notify_Service( $app, $c->get( 'mailer' ) ) );
        $c->set( 'fixture_link_service', fn() => new Fixture_Link_Service() );
        $c->set( 'notification_presenter', function ( Simple_Container $c ) use ( $app ) {
            return new Notification_Presenter(
                $c->get( 'team_repository' ),
                $c->get( 'club_repository' ),
                $c->get( 'tournament_repository' ),
                $c->get( 'fixture_repository' ),
                $c->get( 'fixture_link_service' ),
                $c->get( 'team_service' ),
                $c->get( 'competition_service' ),
                $c->get( 'fixture_permission_service' ),
                $app->site_url,
                $app->site_name
            );
        } );

        $c->set( 'notification_service', function ( Simple_Container $c ) {
            return new Notification_Service(
                self::get_repository_provider( $c ),
                $c->get( 'settings_service' ),
                $c->get( 'notification_presenter' ),
                $c->get( 'view_renderer' ),
                $c->get( 'racketmanager_app' ),
                $c->get( 'mailer' )
            );
        } );
    }

    private static function register_fixture_services( Simple_Container $c, RacketManager $app ): void {
        $c->set( 'fixture_permission_service', function ( Simple_Container $c ) use ( $app ) {
            return new Fixture_Permission_Service( self::get_repository_provider( $c ), $c->get( 'registration_service' ), $app->get_options() );
        } );

        $c->set( 'fixture_detail_service', function ( Simple_Container $c ) {
            return new Fixture_Detail_Service(
                self::get_repository_provider( $c ),
                $c->get( 'competition_service' ),
                $c->get( 'team_service' ),
                $c->get( 'fixture_permission_service' ),
                $c->get( 'fixture_link_service' )
            );
        } );

        $c->set( 'fixture_finalization_service', function ( Simple_Container $c ) {
            return new Fixture_Finalization_Service(
                $c->get( 'standings_service' ),
                $c->get( 'knockout_progression_service' ),
                $c->get( 'result_reporting_service' )
            );
        } );

        $c->set( 'fixture_service_provider', function ( Simple_Container $c ) {
            $service_provider = new Fixture_Service_Provider(
                $c->get( 'result_service' ),
                $c->get( 'knockout_progression_service' ),
                $c->get( 'league_service' ),
                $c->get( 'score_validation_service' ),
                $c->get( 'player_validation_service' ),
                $c->get( 'notification_service' ),
                $c->get( 'registration_service' )
            );
            $service_provider->set_team_service( $c->get( 'team_service' ) );
            $service_provider->set_competition_service( $c->get( 'competition_service' ) );
            $service_provider->set_fixture_permission_service( $c->get( 'fixture_permission_service' ) );
            $service_provider->set_fixture_detail_service( $c->get( 'fixture_detail_service' ) );
            $service_provider->set_fixture_finalization_service( $c->get( 'fixture_finalization_service' ) );

            return $service_provider;
        } );

        $c->set( 'fixture_service', function ( Simple_Container $c ) {
            return new Fixture_Service(
                self::get_repository_provider( $c ),
                $c->get( 'fixture_service_provider' ),
                $c->get( 'fixture_permission_service' ),
                $c->get( 'fixture_detail_service' )
            );
        } );

        $c->set( 'fixture_result_manager', function ( Simple_Container $c ) {
            $service_provider = $c->get( 'fixture_service_provider' );
            $service_provider->set_settings_service( $c->get( 'settings_service' ) );

            return new Fixture_Result_Manager( $service_provider, self::get_repository_provider( $c ), $c );
        } );

        $c->set( 'fixture_maintenance_service', function ( Simple_Container $c ) {
            $service_provider = $c->get( 'fixture_service_provider' );
            $service_provider->set_settings_service( $c->get( 'settings_service' ) );

            return new Fixture_Maintenance_Service( $service_provider, self::get_repository_provider( $c ), $c->get( 'fixture_result_manager' ) );
        } );

        $c->set( 'fixture_lifecycle_service', function ( Simple_Container $c ) {
            return new Fixture_Lifecycle_Service(
                self::get_repository_provider( $c ),
                $c->get( 'fixture_maintenance_service' ),
                $c->get( 'notification_service' )
            );
        } );

        $c->set( 'domain_fixture_detail_service', function ( Simple_Container $c ) {
            return new Domain_Fixture_Detail_Service(
                $c->get( 'league_repository' ),
                $c->get( 'event_repository' ),
                $c->get( 'competition_repository' ),
                $c->get( 'team_repository' ),
                $c->get( 'club_repository' ),
                $c->get( 'rubber_repository' ),
                $c->get( 'league_team_repository' ),
                $c->get( 'player_repository' ),
                $c->get( 'fixture_link_service' ),
                $c->get( 'fixture_permission_service' ),
                $c->get( 'club_role_repository' )
            );
        } );

        // Application Layer Handler
        $c->set( 'get_fixture_details_handler', function ( Simple_Container $c ) {
            return new Get_Fixture_Details_Handler(
                $c->get( 'fixture_repository' ),
                $c->get( 'domain_fixture_detail_service' )
            );
        } );

        // Presentation Layer Presenter
        $c->set( 'fixture_presenter', function ( Simple_Container $c ) {
            return new Fixture_Presenter( $c->get( 'fixture_link_service' ) );
        } );

        $c->set( 'club_presenter', function ( Simple_Container $c ) {
            return new Club_Presenter();
        } );

        $c->set( 'player_presenter', function ( Simple_Container $c ) {
            return new Player_Presenter();
        } );

        // Infrastructure Layer Shortcode Adapter
        $c->set( 'fixture_shortcode_adapter', function ( Simple_Container $c ) {
            return new Fixture_Shortcode_Adapter(
                $c->get( 'get_fixture_details_handler' ),
                $c->get( 'fixture_presenter' )
            );
        } );
    }

    private static function register_result_services( Simple_Container $c ): void {
        $c->set( 'result_reporting_service', function ( Simple_Container $c ) {
            return new Result_Reporting_Service( self::get_repository_provider( $c ) );
        } );

        $c->set( 'result_service', function ( Simple_Container $c ) {
            return new Result_Service( $c->get( 'fixture_repository' ), $c->get( 'team_repository' ), $c->get( 'mailer' ) );
        } );

        $c->set( 'score_validation_service', fn() => new Score_Validation_Service() );

        $c->set( 'player_validation_service', function ( Simple_Container $c ) {
            return new Player_Validation_Service( $c->get( 'registration_service' ), $c->get( 'results_checker_repository' ), $c->get( 'fixture_repository' ) );
        } );

        $c->set( 'knockout_progression_service', function ( Simple_Container $c ) {
            return new Knockout_Progression_Service( $c->get( 'fixture_repository' ) );
        } );

        $c->set( 'standings_service', fn() => new Standings_Service() );

        $c->set( 'results_checker_manager', function ( Simple_Container $c ) {
            $manager = new Results_Checker_Manager( $c->get( 'results_checker_repository' ), $c->get( 'fixture_result_manager' ), $c->get( 'league_repository' ), $c->get( 'fixture_repository' ) );
            $manager->set_notification_service( $c->get( 'notification_service' ) );

            return $manager;
        } );

        $c->set( 'results_checker_presenter', function ( Simple_Container $c ) {
            return new Results_Checker_Presenter( $c->get( 'fixture_repository' ), $c->get( 'player_repository' ), $c->get( 'team_repository' ), $c->get( 'league_repository' ), $c->get( 'fixture_detail_service' ) );
        } );
    }

    private static function register_export_services( Simple_Container $c ): void {
        $c->set( 'exporter', function ( Simple_Container $c ): \Racketmanager\Services\Contracts\Data_Exporter_Interface {
            return new Export_Service(
                $c->get( 'fixture_repository' ),
                $c->get( 'result_reporting_service' ),
                $c->get( 'fixture_detail_service' ),
                $c->get( 'club_repository' )
            );
        } );

        $c->set( 'rest_controller_adapter', fn() => new Rest_Controller_Adapter() );

        $c->set( 'export_delivery_adapter', function ( Simple_Container $c ) {
            return new Export_Delivery_Adapter(
                $c->get( 'racketmanager_app' )
            );
        } );

        $c->set( 'export_admin_controller', fn( Simple_Container $c ) => $c->get( 'export_delivery_adapter' ) );
    }

    private static function get_repository_provider( Simple_Container $c ): Repository_Provider {
        return new Repository_Provider(
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
        );
    }

    private static function register_admin_tournament_controllers( Simple_Container $c ): void {
        $c->set( 'tournament_action_dispatcher', function ( Simple_Container $c ) {
            return new Tournament_Action_Dispatcher(
                $c->get( 'tournament_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'club_service' ),
                $c->get( 'competition_service' ),
                $c->get( 'season_service' ),
                $c->get( 'tournament_action_dispatcher' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_plan_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Plan_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_tournaments_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Tournaments_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'competition_service' ),
                $c->get( 'season_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_overview_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Overview_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'tournament_overview_action_dispatcher' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_overview_action_dispatcher', function ( Simple_Container $c ) {
            return new Tournament_Overview_Action_Dispatcher(
                $c->get( 'championship_admin_service' ),
                $c->get( 'action_guard' )
            );
        } );

        $c->set( 'tournament_setup_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Setup_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_competition_config_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Competition_Config_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'competition_service' ),
                $c->get( 'club_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_event_config_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Event_Config_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'competition_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_team_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Team_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'league_service' ),
                $c->get( 'team_service' ),
                $c->get( 'club_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_setup_event_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Setup_Event_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'league_service' ),
                $c->get( 'draw_action_dispatcher' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'league_contact_action_dispatcher', function ( Simple_Container $c ) {
            return new League_Contact_Action_Dispatcher(
                $c->get( 'league_service' ),
                $c->get( 'competition_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'league_setup_action_dispatcher', function ( Simple_Container $c ) {
            return new League_Setup_Action_Dispatcher(
                $c->get( 'competition_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'event_setup_action_dispatcher', function ( Simple_Container $c ) {
            return new Event_Setup_Action_Dispatcher(
                $c->get( 'competition_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'season_delete_action_dispatcher', function ( Simple_Container $c ) {
            return new Season_Delete_Action_Dispatcher(
                $c->get( 'competition_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'league_team_action_dispatcher', function ( Simple_Container $c ) {
            return new League_Team_Action_Dispatcher(
                $c->get( 'league_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'league_match_action_dispatcher', function ( Simple_Container $c ) {
            return new League_Match_Action_Dispatcher(
                $c->get( 'fixture_repository' ),
                $c->get( 'fixture_maintenance_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'league_constitution_action_dispatcher', function ( Simple_Container $c ) {
            return new League_Constitution_Action_Dispatcher(
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'league_event_action_dispatcher', function ( Simple_Container $c ) {
            return new League_Event_Action_Dispatcher(
                $c->get( 'league_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'league_schedule_service', function ( Simple_Container $c ) {
            return new League_Schedule_Service(
                $c->get( 'fixture_maintenance_service' ),
            );
        } );

        $c->set( 'league_schedule_action_dispatcher', function ( Simple_Container $c ) {
            return new League_Schedule_Action_Dispatcher(
                $c->get( 'league_schedule_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'club_action_dispatcher', function ( Simple_Container $c ) {
            return new Club_Action_Dispatcher(
                $c->get( 'club_service' ),
                $c->get( 'player_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'club_player_action_dispatcher', function ( Simple_Container $c ) {
            return new Club_Player_Action_Dispatcher(
                $c->get( 'registration_service' ),
                $c->get( 'player_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'club_team_action_dispatcher', function ( Simple_Container $c ) {
            return new Club_Team_Action_Dispatcher(
                $c->get( 'club_service' ),
                $c->get( 'team_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'club_role_action_dispatcher', function ( Simple_Container $c ) {
            return new Club_Role_Action_Dispatcher(
                $c->get( 'club_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'season_action_dispatcher', function ( Simple_Container $c ) {
            return new Season_Action_Dispatcher(
                $c->get( 'season_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'competition_action_dispatcher', function ( Simple_Container $c ) {
            return new Competition_Action_Dispatcher(
                $c->get( 'competition_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'competition_season_action_dispatcher', function ( Simple_Container $c ) {
            return new Competition_Season_Action_Dispatcher(
                $c->get( 'competition_service' ),
                $c->get( 'finance_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'event_action_dispatcher', function ( Simple_Container $c ) {
            return new Event_Action_Dispatcher(
                $c->get( 'competition_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'finance_charge_action_dispatcher', function ( Simple_Container $c ) {
            return new Finance_Charge_Action_Dispatcher(
                $c->get( 'finance_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'finance_invoice_action_dispatcher', function ( Simple_Container $c ) {
            return new Finance_Invoice_Action_Dispatcher(
                $c->get( 'finance_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'player_action_dispatcher', function ( Simple_Container $c ) {
            return new Player_Action_Dispatcher(
                $c->get( 'player_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'player_error_action_dispatcher', function ( Simple_Container $c ) {
            return new Player_Error_Action_Dispatcher(
                $c->get( 'player_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'player_request_action_dispatcher', function ( Simple_Container $c ) {
            return new Player_Request_Action_Dispatcher(
                $c->get( 'registration_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'results_checker_action_dispatcher', function ( Simple_Container $c ) {
            return new Results_Checker_Action_Dispatcher(
                $c->get( 'results_checker_manager' ),
                $c->get( 'results_checker_repository' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'index_action_dispatcher', function ( Simple_Container $c ) {
            return new Index_Action_Dispatcher(
                $c->get( 'competition_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'import_action_dispatcher', function ( Simple_Container $c ) {
            return new Import_Action_Dispatcher(
                $c->get( 'action_guard' ),
                $c->get( 'fixture_service' ),
                $c->get( 'player_service' ),
                $c->get( 'registration_service' ),
                $c->has( 'racketmanager_app' ) ? $c->get( 'racketmanager_app' ) : null,
            );
        } );

        $c->set( 'options_action_dispatcher', function ( Simple_Container $c ) {
            return new Options_Action_Dispatcher(
                $c->get( 'action_guard' ),
                $c->has( 'racketmanager_app' ) ? $c->get( 'racketmanager_app' ) : null,
            );
        } );

        $c->set( 'cup_setup_action_dispatcher', function ( Simple_Container $c ) {
            return new Cup_Setup_Action_Dispatcher(
                $c->get( 'competition_service' ),
                $c->get( 'championship_admin_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'cup_plan_action_dispatcher', function ( Simple_Container $c ) {
            return new Cup_Plan_Action_Dispatcher(
                $c->get( 'competition_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_contact_action_dispatcher', function ( Simple_Container $c ) {
            return new Tournament_Contact_Action_Dispatcher(
                $c->get( 'tournament_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_contact_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Contact_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'tournament_contact_action_dispatcher' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_information_action_dispatcher', function ( Simple_Container $c ) {
            return new Tournament_Information_Action_Dispatcher(
                $c->get( 'tournament_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_information_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Information_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'tournament_information_action_dispatcher' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_matches_admin_service', function ( Simple_Container $c ) {
            return new Tournament_Fixtures_Admin_Service(
                $c->get( 'tournament_service' ),
                $c->get( 'fixture_service' ),
                $c->get( 'league_service' ),
                $c->get( 'team_service' ),
            );
        } );

        $c->set( 'tournament_fixtures_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Fixtures_Admin_Controller(
                $c->get( 'tournament_matches_admin_service' ),
                $c->get( 'draw_action_dispatcher' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_draw_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Draw_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'league_service' ),
                $c->get( 'draw_action_dispatcher' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_teams_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Teams_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'league_service' ),
                $c->get( 'team_service' ),
                $c->get( 'action_guard' ),
            );
        } );

    }

    private static function register_admin_controllers( Simple_Container $c ): void {
        $c->set( 'championship_admin_service', function ( Simple_Container $c ) {
            return new Championship_Admin_Service(
                $c->get( 'league_service' ),
                $c->get( 'fixture_service' ),
                $c->get( 'tournament_service' ),
                $c->get( 'league_team_repository' ),
                $c->get( 'team_repository' ),
                $c->get( 'fixture_repository' ),
            );
        } );

        // Register the concrete implementation (optional alias).
        $c->set( 'wp_action_guard', fn() => new Wp_Action_Guard() );

        // Register under an interface-ish name for consumers.
        // (Simple_Container is string-keyed, so we keep it as a convention.)
        $c->set( 'action_guard', function ( Simple_Container $c ) {
            $guard = $c->get( 'wp_action_guard' );
            if ( ! ( $guard instanceof Action_Guard_Interface ) ) {
                throw new Interface_Exception( __( 'Action guard must implement Action_Guard_Interface', 'racketmanager' ) );
            }

            return $guard;
        } );

    }

    private static function register_admin_draw_controllers( Simple_Container $c ): void {
        // Register draw action handler under an interface-ish name for consumers.
        $c->set( 'draw_action_handler', function ( Simple_Container $c ) {
            $handler = $c->get( 'championship_admin_service' );
            if ( ! ( $handler instanceof Draw_Action_Handler_Interface ) ) {
                throw new Interface_Exception( __( 'Draw action handler must implement Draw_Action_Handler_Interface', 'racketmanager' ) );
            }

            return $handler;
        } );

        $c->set( 'draw_action_dispatcher', function ( Simple_Container $c ) {
            return new Draw_Action_Dispatcher(
                $c->get( 'draw_action_handler' ),
                $c->get( 'action_guard' ),
            );
        } );

    }

}
