<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Container\Providers;

use Racketmanager\Application\Finance\Commands\Action_Invoice_Handler;
use Racketmanager\Application\Finance\Commands\Generate_Charge_Invoices_Handler;
use Racketmanager\Exceptions\Interface_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Infrastructure\Wordpress\Admin\Wp_Admin_Redirector;
use Racketmanager\Infrastructure\Wordpress\Ajax\Admin_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Admin_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Ajax\Frontend_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Frontend_Ajax_Controller;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;
use Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface;
use Racketmanager\Services\Admin\Championship_Admin_Service;
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
use Racketmanager\Services\Admin\Import\Import_Action_Dispatcher;
use Racketmanager\Services\Admin\Index\Index_Action_Dispatcher;
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
use Racketmanager\Services\Admin\Options\Options_Action_Dispatcher;
use Racketmanager\Services\Admin\Overview\Tournament_Overview_Action_Dispatcher;
use Racketmanager\Services\Admin\Player\Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Player\Player_Error_Action_Dispatcher;
use Racketmanager\Services\Admin\Player\Player_Request_Action_Dispatcher;
use Racketmanager\Services\Admin\Result\Results_Checker_Action_Dispatcher;
use Racketmanager\Services\Admin\Season\Season_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Admin\Security\Wp_Action_Guard;
use Racketmanager\Services\Admin\Tournament\Tournament_Action_Dispatcher;
use Racketmanager\Services\Admin\Tournament\Tournament_Contact_Action_Dispatcher;
use Racketmanager\Services\Admin\Tournament\Tournament_Fixtures_Admin_Service;
use Racketmanager\Services\Admin\Tournament\Tournament_Information_Action_Dispatcher;
use Racketmanager\Services\Container\Simple_Container;

/**
 * Service provider for Administration, Controllers, and Action Dispatchers.
 */
final readonly class Admin_Service_Provider implements Service_Provider_Interface {
    public function __construct(
        private ?RacketManager $app = null
    ) {}

    public function register( Simple_Container $container ): void {
        $app = $this->app;

        // Message & Redirector infrastructure
        $container->set( 'admin_message_service', fn( Simple_Container $c ) => new Admin_Message_Service(
            $c->get( 'admin_flash_message_store' )
        ) );

        $container->set( 'admin_redirector', fn() => new Wp_Admin_Redirector() );

        $container->set( 'admin_action_adapter', fn( Simple_Container $c ) => new Admin_Action_Adapter(
            $c->get( 'admin_message_service' ),
            $c->get( 'admin_redirector' )
        ) );

        $container->set( 'admin_screen_presenter', fn( Simple_Container $c ) => new Admin_Screen_Presenter(
            $c->get( 'view_renderer' ),
            $c->get( 'admin_message_service' )
        ) );

        // Guards & Handlers
        $container->set( 'wp_action_guard', fn() => new Wp_Action_Guard() );

        $container->set( 'action_guard', function ( Simple_Container $c ) {
            $guard = $c->get( 'wp_action_guard' );
            if ( ! ( $guard instanceof Action_Guard_Interface ) ) {
                throw new Interface_Exception( __( 'Action guard must implement Action_Guard_Interface', 'racketmanager' ) );
            }

            return $guard;
        } );

        $container->set( 'championship_admin_service', fn( Simple_Container $c ) => new Championship_Admin_Service(
            $c->get( 'league_service' ),
            $c->get( 'fixture_service' ),
            $c->get( 'tournament_service' ),
            $c->get( 'league_team_repository' ),
            $c->get( 'team_repository' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'championship_manager' ),
            $c->get( 'event_repository' ),
            $c->get( 'league_query_service' )
        ) );

        $container->set( 'draw_action_handler', function ( Simple_Container $c ) {
            $handler = $c->get( 'championship_admin_service' );
            if ( ! ( $handler instanceof Draw_Action_Handler_Interface ) ) {
                throw new Interface_Exception( __( 'Draw action handler must implement Draw_Action_Handler_Interface', 'racketmanager' ) );
            }

            return $handler;
        } );

        $container->set( 'draw_action_dispatcher', fn( Simple_Container $c ) => new Draw_Action_Dispatcher(
            $c->get( 'draw_action_handler' ),
            $c->get( 'action_guard' ),
            $c->get( 'generate_tournament_bracket_handler' )
        ) );

        // Admin Services
        $container->set( 'tournament_matches_admin_service', fn( Simple_Container $c ) => new Tournament_Fixtures_Admin_Service(
            $c->get( 'tournament_service' ),
            $c->get( 'fixture_service' ),
            $c->get( 'league_service' ),
            $c->get( 'team_service' ),
            $c->get( 'league_query_service' )
        ) );

        $container->set( 'league_schedule_service', fn( Simple_Container $c ) => new League_Schedule_Service(
            $c->get( 'fixture_maintenance_service' ),
            $c->get( 'competition_schedule_service' ),
            $c->get( 'event_repository' ),
            $c->get( 'league_repository' ),
            $c->get( 'league_team_repository' ),
            $c->get( 'event_query_service' ),
            $c->get( 'league_query_service' )
        ) );

        // Action Dispatchers
        $container->set( 'tournament_action_dispatcher', fn( Simple_Container $c ) => new Tournament_Action_Dispatcher(
            $c->get( 'tournament_service' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'tournament_overview_action_dispatcher', fn( Simple_Container $c ) => new Tournament_Overview_Action_Dispatcher(
            $c->get( 'championship_admin_service' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'tournament_contact_action_dispatcher', fn( Simple_Container $c ) => new Tournament_Contact_Action_Dispatcher(
            $c->get( 'tournament_service' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'tournament_information_action_dispatcher', fn( Simple_Container $c ) => new Tournament_Information_Action_Dispatcher(
            $c->get( 'tournament_service' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'league_contact_action_dispatcher', fn( Simple_Container $c ) => new League_Contact_Action_Dispatcher(
            $c->get( 'league_service' ),
            $c->get( 'competition_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'competition_notification_service' )
        ) );

        $container->set( 'league_setup_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Admin\Commands\League_Setup_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'event_repository' )
        ) );

        $container->set( 'league_setup_action_dispatcher', fn( Simple_Container $c ) => new League_Setup_Action_Dispatcher(
            $c->get( 'competition_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'event_repository' ),
            commands: $c->get( 'league_setup_handler' )
        ) );

        $container->set( 'event_setup_action_dispatcher', fn( Simple_Container $c ) => new Event_Setup_Action_Dispatcher(
            $c->get( 'competition_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'event_repository' )
        ) );

        $container->set( 'season_delete_action_dispatcher', fn( Simple_Container $c ) => new Season_Delete_Action_Dispatcher(
            $c->get( 'competition_service' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'league_team_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Admin\Commands\League_Team_Handler(
            $c->get( 'league_service' ),
            $c->get( 'standings_service' ),
            $c->get( 'team_repository' )
        ) );

        $container->set( 'league_team_action_dispatcher', fn( Simple_Container $c ) => new League_Team_Action_Dispatcher(
            $c->get( 'league_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'standings_service' ),
            commands: $c->get( 'league_team_handler' )
        ) );

        $container->set( 'league_match_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Admin\Commands\League_Match_Handler(
            $c->get( 'fixture_repository' ),
            $c->get( 'fixture_maintenance_service' ),
            $c->get( 'fixture_result_manager' ),
            $c->get( 'fixture_service' ),
            $c->get( 'league_query_service' )
        ) );

        $container->set( 'league_match_action_dispatcher', fn( Simple_Container $c ) => new League_Match_Action_Dispatcher(
            $c->get( 'fixture_repository' ),
            $c->get( 'fixture_maintenance_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'fixture_result_manager' ),
            $c->get( 'fixture_service' ),
            commands: $c->get( 'league_match_handler' ),
            league_repository: $c->get( 'league_repository' )
        ) );

        $container->set( 'league_constitution_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Admin\Commands\League_Constitution_Handler(
            $c->get( 'league_team_repository' ),
            $c->get( 'league_service' ),
            $c->get( 'competition_league_schedule_service' ),
            $c->get( 'league_repository' ),
            $c->get( 'event_repository' ),
            $c->get( 'team_repository' ),
            $c->get( 'promotion_relegation_service' )
        ) );

        $container->set( 'league_constitution_action_dispatcher', fn( Simple_Container $c ) => new League_Constitution_Action_Dispatcher(
            $c->get( 'action_guard' ),
            $c->get( 'league_team_repository' ),
            $c->get( 'league_service' ),
            $c->get( 'competition_league_schedule_service' ),
            $c->get( 'league_repository' ),
            $c->get( 'event_repository' ),
            $c->get( 'team_repository' ),
            $c->get( 'promotion_relegation_service' ),
            commands: $c->get( 'league_constitution_handler' )
        ) );

        $container->set( 'league_event_action_dispatcher', fn( Simple_Container $c ) => new League_Event_Action_Dispatcher(
            $c->get( 'league_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'league_query_service' )
        ) );

        $container->set( 'league_schedule_action_dispatcher', fn( Simple_Container $c ) => new League_Schedule_Action_Dispatcher(
            $c->get( 'league_schedule_service' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'club_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Admin\Commands\Club_Handler(
            $c->get( 'club_service' ),
            $c->get( 'player_service' )
        ) );

        $container->set( 'club_action_dispatcher', fn( Simple_Container $c ) => new Club_Action_Dispatcher(
            $c->get( 'club_service' ),
            $c->get( 'player_service' ),
            $c->get( 'action_guard' ),
            commands: $c->get( 'club_handler' )
        ) );

        $container->set( 'club_player_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Admin\Commands\Club_Player_Handler(
            $c->get( 'registration_service' ),
            $c->get( 'player_service' )
        ) );

        $container->set( 'club_player_action_dispatcher', fn( Simple_Container $c ) => new Club_Player_Action_Dispatcher(
            $c->get( 'registration_service' ),
            $c->get( 'player_service' ),
            $c->get( 'action_guard' ),
            commands: $c->get( 'club_player_handler' )
        ) );

        $container->set( 'club_team_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Admin\Commands\Club_Team_Handler(
            $c->get( 'club_service' ),
            $c->get( 'team_service' )
        ) );

        $container->set( 'club_team_action_dispatcher', fn( Simple_Container $c ) => new Club_Team_Action_Dispatcher(
            $c->get( 'club_service' ),
            $c->get( 'team_service' ),
            $c->get( 'action_guard' ),
            commands: $c->get( 'club_team_handler' )
        ) );

        $container->set( 'club_role_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Admin\Commands\Club_Role_Handler(
            $c->get( 'club_service' )
        ) );

        $container->set( 'club_role_action_dispatcher', fn( Simple_Container $c ) => new Club_Role_Action_Dispatcher(
            $c->get( 'club_service' ),
            $c->get( 'action_guard' ),
            commands: $c->get( 'club_role_handler' )
        ) );

        $container->set( 'season_action_dispatcher', fn( Simple_Container $c ) => new Season_Action_Dispatcher(
            $c->get( 'season_service' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'competition_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Admin\Commands\Competition_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'event_repository' )
        ) );

        $container->set( 'competition_action_dispatcher', fn( Simple_Container $c ) => new Competition_Action_Dispatcher(
            $c->get( 'competition_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'event_repository' ),
            commands: $c->get( 'competition_handler' )
        ) );

        $container->set( 'competition_season_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Admin\Commands\Competition_Season_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'finance_service' ),
            $c->get( 'competition_season_service' ),
            $c->get( 'event_repository' )
        ) );

        $container->set( 'competition_season_action_dispatcher', fn( Simple_Container $c ) => new Competition_Season_Action_Dispatcher(
            $c->get( 'competition_service' ),
            $c->get( 'finance_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'competition_season_service' ),
            $c->get( 'event_repository' ),
            commands: $c->get( 'competition_season_handler' )
        ) );

        $container->set( 'event_action_dispatcher', fn( Simple_Container $c ) => new Event_Action_Dispatcher(
            $c->get( 'competition_service' ),
            $c->get( 'competition_season_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'event_repository' ),
            $c->get( 'league_service' )
        ) );

        $container->set( 'generate_charge_invoices_handler', fn( Simple_Container $c ) => new Generate_Charge_Invoices_Handler(
            $c->get( 'finance_service' )
        ) );

        $container->set( 'action_invoice_handler', fn( Simple_Container $c ) => new Action_Invoice_Handler(
            $c->get( 'finance_service' )
        ) );

        $container->set( 'finance_charge_action_dispatcher', fn( Simple_Container $c ) => new Finance_Charge_Action_Dispatcher(
            $c->get( 'finance_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'generate_charge_invoices_handler' )
        ) );

        $container->set( 'finance_invoice_action_dispatcher', fn( Simple_Container $c ) => new Finance_Invoice_Action_Dispatcher(
            $c->get( 'finance_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'action_invoice_handler' )
        ) );

        $container->set( 'player_action_dispatcher', fn( Simple_Container $c ) => new Player_Action_Dispatcher(
            $c->get( 'player_service' ),
            $c->get( 'action_guard' ),
            update_player_handler: $c->get( 'update_player_handler' ),
            create_player_handler: $c->get( 'create_player_handler' )
        ) );

        $container->set( 'player_error_action_dispatcher', fn( Simple_Container $c ) => new Player_Error_Action_Dispatcher(
            $c->get( 'player_service' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'player_request_action_dispatcher', fn( Simple_Container $c ) => new Player_Request_Action_Dispatcher(
            $c->get( 'registration_service' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'results_checker_handler', fn( Simple_Container $c ) => new \Racketmanager\Application\Admin\Commands\Results_Checker_Handler(
            $c->get( 'results_checker_manager' ),
            $c->get( 'results_checker_repository' )
        ) );

        $container->set( 'results_checker_action_dispatcher', fn( Simple_Container $c ) => new Results_Checker_Action_Dispatcher(
            $c->get( 'results_checker_manager' ),
            $c->get( 'results_checker_repository' ),
            $c->get( 'action_guard' ),
            commands: $c->get( 'results_checker_handler' )
        ) );

        $container->set( 'index_action_dispatcher', fn( Simple_Container $c ) => new Index_Action_Dispatcher(
            $c->get( 'competition_service' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'import_action_dispatcher', fn( Simple_Container $c ) => new Import_Action_Dispatcher(
            $c->get( 'action_guard' ),
            $c->get( 'fixture_service' ),
            $c->get( 'player_service' ),
            $c->get( 'registration_service' ),
            $c->get( 'league_team_repository' ),
            $c->get( 'standings_service' ),
            $c->get( 'league_query_service' ),
            $c->has( 'racketmanager_app' ) ? $c->get( 'racketmanager_app' ) : null,
            $c->get( 'league_repository' )
        ) );

        $container->set( 'options_action_dispatcher', fn( Simple_Container $c ) => new Options_Action_Dispatcher(
            $c->get( 'action_guard' ),
            $c->has( 'racketmanager_app' ) ? $c->get( 'racketmanager_app' ) : null
        ) );

        $container->set( 'cup_setup_action_dispatcher', fn( Simple_Container $c ) => new Cup_Setup_Action_Dispatcher(
            $c->get( 'competition_service' ),
            $c->get( 'draw_action_handler' ),
            $c->get( 'action_guard' ),
            $c->get( 'generate_tournament_bracket_handler' )
        ) );

        $container->set( 'cup_plan_action_dispatcher', fn( Simple_Container $c ) => new Cup_Plan_Action_Dispatcher(
            $c->get( 'competition_service' ),
            $c->get( 'action_guard' )
        ) );


        // Admin & Delivery AJAX
        $container->set( 'admin_ajax_adapter', fn( Simple_Container $c ) => new Admin_Ajax_Adapter(
            $c->get( 'security_service' ),
            $c->get( 'json_response_factory' ),
            $app,
            $c->get( 'competition_service' ),
            $c->get( 'competition_entry_service' ),
            $c->get( 'notification_service' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'settings_service' ),
            $c->get( 'league_team_repository' ),
            $c->get( 'competition_notification_service' ),
            $c->get( 'standings_service' ),
            $c->get( 'league_repository' ),
            $c->get( 'event_repository' ),
            $c->get( 'team_repository' )
        ) );
        $container->set( 'admin_ajax_controller', fn( Simple_Container $c ) => new Admin_Ajax_Controller( $app, $c->get( 'admin_ajax_adapter' ) ) );

        $container->set( 'frontend_ajax_adapter', fn( Simple_Container $c ) => new Frontend_Ajax_Adapter(
            $c->get( 'security_service' ),
            $c->get( 'json_response_factory' ),
            $c->get( 'competition_service' ),
            $c->get( 'competition_entry_service' ),
            $c->get( 'team_service' ),
            $c->get( 'player_service' ),
            $c->get( 'registration_service' ),
            $c->get( 'club_service' ),
            $c->get( 'tournament_service' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'rubber_repository' ),
            submit_cup_entry_handler: $c->get( 'submit_cup_entry_handler' ),
            submit_league_entry_handler: $c->get( 'submit_league_entry_handler' ),
            update_player_handler: $c->get( 'update_player_handler' ),
            search_players_handler: $c->get( 'search_players_handler' ),
            get_player_details_handler: $c->get( 'get_player_details_handler' ),
            league_repository: $c->get( 'league_repository' ),
            event_repository: $c->get( 'event_repository' ),
            view_presenter: $c->get( 'shortcode_view_presenter' )
        ) );
        $container->set( 'frontend_ajax_controller', fn( Simple_Container $c ) => new Frontend_Ajax_Controller( $app, $c->get( 'frontend_ajax_adapter' ) ) );
    }
}
