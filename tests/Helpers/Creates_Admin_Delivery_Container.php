<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Admin\Admin_Screens_Service_Provider;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\Presenters\Admin_Competition_Page_Presenter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Services\Admin\Admin_Message_Service;
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
use Racketmanager\Services\Admin\Player\Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Player\Player_Error_Action_Dispatcher;
use Racketmanager\Services\Admin\Player\Player_Request_Action_Dispatcher;
use Racketmanager\Services\Admin\Result\Results_Checker_Action_Dispatcher;
use Racketmanager\Services\Admin\Season\Season_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\Validator\Score_Validation_Service;
use Racketmanager\Services\View\View_Renderer_Interface;

trait Creates_Admin_Delivery_Container {

    /**
     * Builds a configured Simple_Container with all admin services and dispatchers.
     * Specific mocks or stubs passed in $services will override default stubs while preserving identity.
     *
     * @param array<string, object> $services
     */
    protected function create_admin_delivery_container(
        array $services = [],
        ?Action_Guard_Interface $guard = null,
        ?Admin_Redirector_Interface $redirector = null,
        ?View_Renderer_Interface $renderer = null,
        ?Admin_Flash_Message_Store $flash_store = null,
    ): Simple_Container {
        $container = new Simple_Container();

        $guard_instance       = $guard ?? $this->createStub( Action_Guard_Interface::class );
        $redirector_instance  = $redirector ?? $this->createStub( Admin_Redirector_Interface::class );
        $renderer_instance    = $renderer ?? $this->createStub( View_Renderer_Interface::class );
        $flash_store_instance = $flash_store ?? $this->createStub( Admin_Flash_Message_Store::class );

        $club_service         = $services['club_service'] ?? $this->createStub( Club_Service::class );
        $player_service       = $services['player_service'] ?? $this->createStub( Player_Service::class );
        $registration_service = $services['registration_service'] ?? $this->createStub( Registration_Service::class );
        $team_service         = $services['team_service'] ?? $this->createStub( Team_Service::class );
        $league_service       = $services['league_service'] ?? null;
        if ( null === $league_service ) {
            $league_service = $this->createStub( League_Service::class );
            $league_service->method( 'get_league' )->willReturnCallback(
                static fn( int|string $id ) => $GLOBALS['wp_stubs_leagues'][ (int) $id ] ?? null
            );
        }
        $standings_service    = $services['standings_service'] ?? $this->createStub( Standings_Service::class );
        $competition_service  = $services['competition_service'] ?? $this->createStub( Competition_Service::class );
        $finance_service      = $services['finance_service'] ?? $this->createStub( Finance_Service::class );
        $season_service       = $services['season_service'] ?? $this->createStub( Season_Service::class );
        $tournament_service   = $services['tournament_service'] ?? $this->createStub( Tournament_Service::class );
        $fixture_service      = $services['fixture_service'] ?? $this->createStub( Fixture_Service::class );
        $fixture_detail_serv  = $services['fixture_detail_service'] ?? $this->createStub( Fixture_Detail_Service::class );
        $fixture_maintenance  = $services['fixture_maintenance_service'] ?? $this->createStub( Fixture_Maintenance_Service::class );
        $fixture_repository   = $services['fixture_repository'] ?? $this->createStub( Fixture_Repository_Interface::class );
        $event_repo           = $services['event_repository'] ?? null;
        if ( null === $event_repo ) {
            $event_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class );
            $event_repo->method( 'find_by_id' )->willReturnCallback(
                static fn( int|string $id ) => $GLOBALS['wp_stubs_events'][ (int) $id ] ?? null
            );
        }
        $league_team_repo     = $services['league_team_repository'] ?? $this->createStub( League_Team_Repository_Interface::class );
        $league_repo          = $services['league_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class );
        $team_repo            = $services['team_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Team_Repository_Interface::class );
        $club_repo            = $services['club_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Club_Repository_Interface::class );
        $player_repo          = $services['player_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Player_Repository_Interface::class );
        $fixture_relation_ldr = $services['fixture_relation_loader'] ?? new \Racketmanager\Services\Fixture\Fixture_Relation_Loader( $league_repo );
        $score_validation     = $services['score_validation_service'] ?? $this->createStub( Score_Validation_Service::class );
        $fixture_result_mgr   = $services['fixture_result_manager'] ?? $this->createStub( \Racketmanager\Services\Fixture\Fixture_Result_Manager::class );

        $message_service      = $services['admin_message_service'] ?? new Admin_Message_Service( $flash_store_instance );
        $action_adapter       = $services['admin_action_adapter'] ?? new Admin_Action_Adapter( $message_service, $redirector_instance );
        $screen_presenter     = $services['admin_screen_presenter'] ?? new Admin_Screen_Presenter( $renderer_instance, $message_service );

        $container->set( 'view_renderer', fn() => $renderer_instance );
        $container->set( 'club_service', fn() => $club_service );
        $container->set( 'player_service', fn() => $player_service );
        $container->set( 'registration_service', fn() => $registration_service );
        $container->set( 'team_service', fn() => $team_service );
        $container->set( 'league_service', fn() => $league_service );
        $container->set( 'standings_service', fn() => $standings_service );
        $container->set( 'competition_service', fn() => $competition_service );
        $container->set( 'finance_service', fn() => $finance_service );
        $container->set( 'season_service', fn() => $season_service );
        $container->set( 'tournament_service', fn() => $tournament_service );
        $container->set( 'fixture_service', fn() => $fixture_service );
        $container->set( 'fixture_detail_service', fn() => $fixture_detail_serv );
        $container->set( 'fixture_maintenance_service', fn() => $fixture_maintenance );
        $container->set( 'fixture_repository', fn() => $fixture_repository );
        $container->set( 'event_repository', fn() => $event_repo );
        $container->set( 'admin_league_schedule_presenter', fn() => $services['admin_league_schedule_presenter'] ?? new \Racketmanager\Admin\Presenters\Admin_League_Schedule_Presenter() );
        $container->set( 'admin_date_presenter', fn() => $services['admin_date_presenter'] ?? new \Racketmanager\Admin\Presenters\Admin_Date_Presenter() );
        $container->set( 'admin_league_matches_presenter', fn( Simple_Container $c ) => $services['admin_league_matches_presenter'] ?? new \Racketmanager\Admin\Presenters\Admin_League_Matches_Presenter( $c->get( 'admin_date_presenter' ) ) );
        $container->set( 'admin_championship_finals_presenter', fn( Simple_Container $c ) => $services['admin_championship_finals_presenter'] ?? new \Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter( $c->get( 'admin_date_presenter' ) ) );
        $container->set( 'player_repository', fn() => $player_repo );
        $container->set( 'fixture_relation_loader', fn() => $fixture_relation_ldr );
        $container->set( 'admin_plan_presenter', fn( Simple_Container $c ) => $services['admin_plan_presenter'] ?? new \Racketmanager\Admin\Presenters\Admin_Plan_Presenter(
            $c->get( 'fixture_detail_service' ),
            $c->get( 'player_repository' ),
            $c->get( 'fixture_relation_loader' ),
        ) );
        $container->set( 'admin_entry_link_presenter', fn() => $services['admin_entry_link_presenter'] ?? new \Racketmanager\Admin\Presenters\Admin_Entry_Link_Presenter() );
        $container->set( 'league_team_repository', fn() => $league_team_repo );
        $container->set( 'league_repository', fn() => $league_repo );
        $container->set( 'team_repository', fn() => $team_repo );
        $container->set( 'club_repository', fn() => $club_repo );
        $container->set( 'league_query_service', fn( Simple_Container $c ) => $services['league_query_service'] ?? new \Racketmanager\Services\Competition\League_Query_Service(
            $c->get( 'league_repository' ),
            fn() => $c->get( 'fixture_repository' ),
            fn() => $services['rubber_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface::class ),
            fn() => $services['team_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Team_Repository_Interface::class ),
            fn() => $c->get( 'league_team_repository' ),
            fn() => $services['club_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Club_Repository_Interface::class ),
            fn() => $services['player_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Player_Repository_Interface::class ),
        ) );
        $container->set( 'event_query_service', fn( Simple_Container $c ) => $services['event_query_service'] ?? new \Racketmanager\Services\Competition\Event_Query_Service(
            $c->get( 'event_repository' ),
            fn() => $c->get( 'fixture_repository' ),
            fn() => $services['rubber_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface::class ),
            fn() => $c->get( 'league_repository' ),
            fn() => $services['team_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Team_Repository_Interface::class ),
            fn() => $services['club_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Club_Repository_Interface::class ),
            fn() => $services['player_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Player_Repository_Interface::class ),
        ) );
        $container->set( 'score_validation_service', fn() => $score_validation );
        $container->set( 'fixture_result_manager', fn() => $fixture_result_mgr );

        $container->set( 'admin_message_service', fn() => $message_service );
        $container->set( 'admin_action_adapter', fn() => $action_adapter );
        $container->set( 'admin_screen_presenter', fn() => $screen_presenter );
        $container->set( 'action_guard', fn() => $guard_instance );
        $container->set( 'admin_competition_page_presenter', fn() => $services['admin_competition_page_presenter'] ?? new Admin_Competition_Page_Presenter( $competition_service, $club_service, $season_service, $finance_service ) );
        if ( ! array_key_exists( 'admin_results_match_presenter', $services ) || false !== $services['admin_results_match_presenter'] ) {
            $container->set( 'admin_results_match_presenter', fn() => $services['admin_results_match_presenter'] ?? new \Racketmanager\Admin\Presenters\Admin_Results_Match_Presenter( new \Racketmanager\Admin\Presenters\Admin_Date_Presenter(), $this->createStub( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class ) ) );
        }

        $container->set( 'league_contact_action_dispatcher', fn() => $services['league_contact_action_dispatcher'] ?? new League_Contact_Action_Dispatcher( $league_service, $competition_service, $guard_instance, $services['competition_notification_service'] ?? $this->createStub( \Racketmanager\Services\Competition\Competition_Notification_Service::class ) ) );
        $container->set( 'league_setup_action_dispatcher', fn() => $services['league_setup_action_dispatcher'] ?? new League_Setup_Action_Dispatcher( $competition_service, $guard_instance, $services['event_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class ) ) );
        $container->set( 'event_setup_action_dispatcher', fn() => $services['event_setup_action_dispatcher'] ?? new Event_Setup_Action_Dispatcher( $competition_service, $guard_instance, $services['event_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class ) ) );
        $container->set( 'season_delete_action_dispatcher', fn() => $services['season_delete_action_dispatcher'] ?? new Season_Delete_Action_Dispatcher( $competition_service, $guard_instance ) );
        $container->set( 'league_team_action_dispatcher', fn( Simple_Container $c ) => $services['league_team_action_dispatcher'] ?? new League_Team_Action_Dispatcher( $league_service, $guard_instance, $standings_service, team_repository: $c->get( 'team_repository' ) ) );
        $container->set( 'league_match_action_dispatcher', fn( Simple_Container $c ) => $services['league_match_action_dispatcher'] ?? new League_Match_Action_Dispatcher( $fixture_repository, $fixture_maintenance, $guard_instance, $fixture_result_mgr, $fixture_service, league_query_service: $c->get( 'league_query_service' ), league_repository: $league_repo ) );
        $container->set( 'league_constitution_action_dispatcher', fn() => $services['league_constitution_action_dispatcher'] ?? new League_Constitution_Action_Dispatcher( $guard_instance, $league_team_repo, $league_service, $services['competition_league_schedule_service'] ?? $this->createStub( \Racketmanager\Services\Competition\League_Schedule_Service::class ), $league_repo, $services['event_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class ), $services['team_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Team_Repository_Interface::class ), $services['promotion_relegation_service'] ?? $this->createStub( \Racketmanager\Services\Competition\Promotion_Relegation_Service::class ) ) );
        $container->set( 'league_event_action_dispatcher', fn( Simple_Container $c ) => $services['league_event_action_dispatcher'] ?? new League_Event_Action_Dispatcher( $league_service, $guard_instance, $c->get( 'league_query_service' ) ) );
        $container->set( 'league_schedule_action_dispatcher', fn( Simple_Container $c ) => $services['league_schedule_action_dispatcher'] ?? new League_Schedule_Action_Dispatcher(
            $services['league_schedule_service'] ?? new League_Schedule_Service(
                $fixture_maintenance,
                $services['competition_schedule_service'] ?? $this->createStub( \Racketmanager\Services\Competition\Competition_Schedule_Service::class ),
                $event_repo,
                $league_repo,
                $league_team_repo,
                $services['event_query_service'] ?? $this->createStub( \Racketmanager\Services\Competition\Event_Query_Service::class ),
                $c->get( 'league_query_service' )
            ),
            $guard_instance
        ) );

        $container->set( 'club_action_dispatcher', fn() => $services['club_action_dispatcher'] ?? new Club_Action_Dispatcher( $club_service, $player_service, $guard_instance ) );
        $container->set( 'club_player_action_dispatcher', fn() => $services['club_player_action_dispatcher'] ?? new Club_Player_Action_Dispatcher( $registration_service, $player_service, $guard_instance ) );
        $container->set( 'club_team_action_dispatcher', fn() => $services['club_team_action_dispatcher'] ?? new Club_Team_Action_Dispatcher( $club_service, $team_service, $guard_instance ) );
        $container->set( 'club_role_action_dispatcher', fn() => $services['club_role_action_dispatcher'] ?? new Club_Role_Action_Dispatcher( $club_service, $guard_instance ) );
        $container->set( 'season_action_dispatcher', fn() => $services['season_action_dispatcher'] ?? new Season_Action_Dispatcher( $season_service, $guard_instance ) );

        $container->set( 'competition_action_dispatcher', fn() => $services['competition_action_dispatcher'] ?? new Competition_Action_Dispatcher( $competition_service, $guard_instance, $services['event_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class ) ) );
        $container->set( 'competition_season_action_dispatcher', fn() => $services['competition_season_action_dispatcher'] ?? new Competition_Season_Action_Dispatcher( $competition_service, $finance_service, $guard_instance, $services['competition_season_service'] ?? new \Racketmanager\Services\Competition\Competition_Season_Service( $league_team_repo, $this->createStub( \Racketmanager\Repositories\Interfaces\Club_Repository_Interface::class ), $services['event_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class ), $services['league_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class ) ), $services['event_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class ) ) );
        $container->set( 'event_action_dispatcher', fn() => $services['event_action_dispatcher'] ?? new Event_Action_Dispatcher( $competition_service, $services['competition_season_service'] ?? $this->createStub( \Racketmanager\Services\Competition\Competition_Season_Service::class ), $guard_instance, $services['event_repository'] ?? $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class ), $league_service ) );
        $container->set( 'finance_charge_action_dispatcher', fn() => $services['finance_charge_action_dispatcher'] ?? new Finance_Charge_Action_Dispatcher( $finance_service, $guard_instance, $services['generate_charge_invoices_handler'] ?? new \Racketmanager\Application\Finance\Commands\Generate_Charge_Invoices_Handler( $finance_service ) ) );
        $container->set( 'finance_invoice_action_dispatcher', fn() => $services['finance_invoice_action_dispatcher'] ?? new Finance_Invoice_Action_Dispatcher( $finance_service, $guard_instance, $services['action_invoice_handler'] ?? new \Racketmanager\Application\Finance\Commands\Action_Invoice_Handler( $finance_service ) ) );
        $container->set( 'player_action_dispatcher', fn() => $services['player_action_dispatcher'] ?? new Player_Action_Dispatcher( $player_service, $guard_instance ) );
        $container->set( 'player_error_action_dispatcher', fn() => $services['player_error_action_dispatcher'] ?? new Player_Error_Action_Dispatcher( $player_service, $guard_instance ) );
        $container->set( 'player_request_action_dispatcher', fn() => $services['player_request_action_dispatcher'] ?? new Player_Request_Action_Dispatcher( $registration_service, $guard_instance ) );

        $container->set( 'cup_setup_action_dispatcher', fn() => $services['cup_setup_action_dispatcher'] ?? new Cup_Setup_Action_Dispatcher( $competition_service, $services['draw_action_handler'] ?? $this->createStub( \Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface::class ), $guard_instance ) );
        $container->set( 'cup_plan_action_dispatcher', fn() => $services['cup_plan_action_dispatcher'] ?? new Cup_Plan_Action_Dispatcher( $competition_service, $guard_instance ) );
        $container->set( 'draw_action_dispatcher', fn() => $services['draw_action_dispatcher'] ?? new Draw_Action_Dispatcher( $services['draw_action_handler'] ?? $this->createStub( \Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface::class ), $guard_instance ) );

        $container->set( 'tournament_contact_action_dispatcher', fn( Simple_Container $c ) => $services['tournament_contact_action_dispatcher'] ?? new \Racketmanager\Services\Admin\Tournament\Tournament_Contact_Action_Dispatcher(
            $tournament_service,
            $guard_instance
        ) );

        $container->set( 'import_action_dispatcher', fn( Simple_Container $c ) => $services['import_action_dispatcher'] ?? new Import_Action_Dispatcher(
            $guard_instance,
            $this->createStub( \Racketmanager\Services\Fixture_Service::class ),
            $player_service,
            $registration_service,
            $league_team_repo,
            $standings_service,
            $c->get( 'league_query_service' ),
            null,
            $league_repo
        ) );
        $container->set( 'index_action_dispatcher', fn() => $services['index_action_dispatcher'] ?? new Index_Action_Dispatcher( $competition_service, $guard_instance ) );
        $container->set( 'options_action_dispatcher', fn() => $services['options_action_dispatcher'] ?? new Options_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Options_Service::class ), $guard_instance ) );
        $container->set( 'results_checker_action_dispatcher', fn() => $services['results_checker_action_dispatcher'] ?? new Results_Checker_Action_Dispatcher( $this->createStub( \Racketmanager\Repositories\Results_Checker_Repository::class ), $this->createStub( \Racketmanager\Services\Results_Checker_Service::class ), $guard_instance ) );
        $app = $services['app'] ?? null;
        if ( null === $app ) {
            $app            = $this->createStub( \Racketmanager\RacketManager::class );
            $app->container = $container;
        }
        ( new Admin_Screens_Service_Provider( $app ) )->register( $container );
        if ( array_key_exists( 'admin_results_match_presenter', $services ) && false === $services['admin_results_match_presenter'] ) {
            $container->remove( 'admin_results_match_presenter' );
        }
        if ( isset( $services['admin_player_screen'] ) ) {
            $container->set( 'admin_player_screen', fn() => $services['admin_player_screen'] );
        }
        if ( ! array_key_exists( 'results_checker_presenter', $services ) || false !== $services['results_checker_presenter'] ) {
            $container->set( 'results_checker_presenter', fn() => $services['results_checker_presenter'] ?? $this->createStub( \Racketmanager\Presentation\Presenters\Results_Checker_Presenter::class ) );
        }

        foreach ( $services as $id => $service ) {
            if ( is_string( $id ) && is_object( $service ) ) {
                $container->set( $id, fn() => $service );
            }
        }

        return $container;
    }
}
