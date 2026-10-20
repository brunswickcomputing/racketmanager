<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
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
        $league_service       = $services['league_service'] ?? $this->createStub( League_Service::class );
        $competition_service  = $services['competition_service'] ?? $this->createStub( Competition_Service::class );
        $finance_service      = $services['finance_service'] ?? $this->createStub( Finance_Service::class );
        $season_service       = $services['season_service'] ?? $this->createStub( Season_Service::class );
        $tournament_service   = $services['tournament_service'] ?? $this->createStub( Tournament_Service::class );
        $fixture_service      = $services['fixture_service'] ?? $this->createStub( Fixture_Service::class );
        $fixture_detail_serv  = $services['fixture_detail_service'] ?? $this->createStub( Fixture_Detail_Service::class );
        $fixture_maintenance  = $services['fixture_maintenance_service'] ?? $this->createStub( Fixture_Maintenance_Service::class );
        $fixture_repository   = $services['fixture_repository'] ?? $this->createStub( Fixture_Repository_Interface::class );
        $league_team_repo     = $services['league_team_repository'] ?? $this->createStub( League_Team_Repository_Interface::class );
        $score_validation     = $services['score_validation_service'] ?? $this->createStub( Score_Validation_Service::class );

        $message_service      = $services['admin_message_service'] ?? new Admin_Message_Service( $flash_store_instance );
        $action_adapter       = $services['admin_action_adapter'] ?? new Admin_Action_Adapter( $message_service, $redirector_instance );
        $screen_presenter     = $services['admin_screen_presenter'] ?? new Admin_Screen_Presenter( $renderer_instance, $message_service );

        $container->set( 'club_service', fn() => $club_service );
        $container->set( 'player_service', fn() => $player_service );
        $container->set( 'registration_service', fn() => $registration_service );
        $container->set( 'team_service', fn() => $team_service );
        $container->set( 'league_service', fn() => $league_service );
        $container->set( 'competition_service', fn() => $competition_service );
        $container->set( 'finance_service', fn() => $finance_service );
        $container->set( 'season_service', fn() => $season_service );
        $container->set( 'tournament_service', fn() => $tournament_service );
        $container->set( 'fixture_service', fn() => $fixture_service );
        $container->set( 'fixture_detail_service', fn() => $fixture_detail_serv );
        $container->set( 'fixture_maintenance_service', fn() => $fixture_maintenance );
        $container->set( 'fixture_repository', fn() => $fixture_repository );
        $container->set( 'admin_league_schedule_presenter', fn() => $services['admin_league_schedule_presenter'] ?? new \Racketmanager\Admin\Presenters\Admin_League_Schedule_Presenter() );
        $container->set( 'admin_entry_link_presenter', fn() => $services['admin_entry_link_presenter'] ?? new \Racketmanager\Admin\Presenters\Admin_Entry_Link_Presenter() );
        $container->set( 'league_team_repository', fn() => $league_team_repo );
        $container->set( 'score_validation_service', fn() => $score_validation );

        $container->set( 'admin_message_service', fn() => $message_service );
        $container->set( 'admin_action_adapter', fn() => $action_adapter );
        $container->set( 'admin_screen_presenter', fn() => $screen_presenter );
        $container->set( 'admin_results_match_presenter', fn() => $services['admin_results_match_presenter'] ?? new \Racketmanager\Admin\Presenters\Admin_Results_Match_Presenter( new \Racketmanager\Admin\Presenters\Admin_Date_Presenter(), $this->createStub( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class ) ) );

        $container->set( 'league_contact_action_dispatcher', fn() => $services['league_contact_action_dispatcher'] ?? new League_Contact_Action_Dispatcher( $league_service, $competition_service, $guard_instance ) );
        $container->set( 'league_setup_action_dispatcher', fn() => $services['league_setup_action_dispatcher'] ?? new League_Setup_Action_Dispatcher( $competition_service, $guard_instance ) );
        $container->set( 'event_setup_action_dispatcher', fn() => $services['event_setup_action_dispatcher'] ?? new Event_Setup_Action_Dispatcher( $competition_service, $guard_instance ) );
        $container->set( 'season_delete_action_dispatcher', fn() => $services['season_delete_action_dispatcher'] ?? new Season_Delete_Action_Dispatcher( $competition_service, $guard_instance ) );
        $container->set( 'league_team_action_dispatcher', fn() => $services['league_team_action_dispatcher'] ?? new League_Team_Action_Dispatcher( $league_service, $guard_instance ) );
        $container->set( 'league_match_action_dispatcher', fn() => $services['league_match_action_dispatcher'] ?? new League_Match_Action_Dispatcher( $fixture_repository, $fixture_maintenance, $guard_instance ) );
        $container->set( 'league_constitution_action_dispatcher', fn() => $services['league_constitution_action_dispatcher'] ?? new League_Constitution_Action_Dispatcher( $guard_instance ) );
        $container->set( 'league_event_action_dispatcher', fn() => $services['league_event_action_dispatcher'] ?? new League_Event_Action_Dispatcher( $league_service, $guard_instance ) );
        $container->set( 'league_schedule_action_dispatcher', fn() => $services['league_schedule_action_dispatcher'] ?? new League_Schedule_Action_Dispatcher( new League_Schedule_Service( $fixture_maintenance ), $guard_instance ) );

        $container->set( 'club_action_dispatcher', fn() => $services['club_action_dispatcher'] ?? new Club_Action_Dispatcher( $club_service, $player_service, $guard_instance ) );
        $container->set( 'club_player_action_dispatcher', fn() => $services['club_player_action_dispatcher'] ?? new Club_Player_Action_Dispatcher( $registration_service, $player_service, $guard_instance ) );
        $container->set( 'club_team_action_dispatcher', fn() => $services['club_team_action_dispatcher'] ?? new Club_Team_Action_Dispatcher( $club_service, $team_service, $guard_instance ) );
        $container->set( 'club_role_action_dispatcher', fn() => $services['club_role_action_dispatcher'] ?? new Club_Role_Action_Dispatcher( $club_service, $guard_instance ) );
        $container->set( 'season_action_dispatcher', fn() => $services['season_action_dispatcher'] ?? new Season_Action_Dispatcher( $season_service, $guard_instance ) );

        $container->set( 'competition_action_dispatcher', fn() => $services['competition_action_dispatcher'] ?? new Competition_Action_Dispatcher( $competition_service, $guard_instance ) );
        $container->set( 'competition_season_action_dispatcher', fn() => $services['competition_season_action_dispatcher'] ?? new Competition_Season_Action_Dispatcher( $competition_service, $finance_service, $guard_instance ) );
        $container->set( 'event_action_dispatcher', fn() => $services['event_action_dispatcher'] ?? new Event_Action_Dispatcher( $competition_service, $guard_instance ) );
        $container->set( 'finance_charge_action_dispatcher', fn() => $services['finance_charge_action_dispatcher'] ?? new Finance_Charge_Action_Dispatcher( $finance_service, $guard_instance ) );
        $container->set( 'finance_invoice_action_dispatcher', fn() => $services['finance_invoice_action_dispatcher'] ?? new Finance_Invoice_Action_Dispatcher( $finance_service, $guard_instance ) );
        $container->set( 'player_action_dispatcher', fn() => $services['player_action_dispatcher'] ?? new Player_Action_Dispatcher( $player_service, $guard_instance ) );
        $container->set( 'player_error_action_dispatcher', fn() => $services['player_error_action_dispatcher'] ?? new Player_Error_Action_Dispatcher( $player_service, $guard_instance ) );
        $container->set( 'player_request_action_dispatcher', fn() => $services['player_request_action_dispatcher'] ?? new Player_Request_Action_Dispatcher( $registration_service, $guard_instance ) );

        $container->set( 'cup_setup_action_dispatcher', fn() => $services['cup_setup_action_dispatcher'] ?? new Cup_Setup_Action_Dispatcher( $competition_service, $guard_instance ) );
        $container->set( 'cup_plan_action_dispatcher', fn() => $services['cup_plan_action_dispatcher'] ?? new Cup_Plan_Action_Dispatcher( $competition_service, $guard_instance ) );
        $container->set( 'draw_action_dispatcher', fn() => $services['draw_action_dispatcher'] ?? new Draw_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Admin\Championship\Draw_Action_Resolver::class ), $this->createStub( \Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface::class ), $guard_instance ) );

        $container->set( 'import_action_dispatcher', fn() => $services['import_action_dispatcher'] ?? new Import_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Import\Import_Service_Interface::class ), $guard_instance ) );
        $container->set( 'index_action_dispatcher', fn() => $services['index_action_dispatcher'] ?? new Index_Action_Dispatcher( $competition_service, $guard_instance ) );
        $container->set( 'options_action_dispatcher', fn() => $services['options_action_dispatcher'] ?? new Options_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Options_Service::class ), $guard_instance ) );
        $container->set( 'results_checker_action_dispatcher', fn() => $services['results_checker_action_dispatcher'] ?? new Results_Checker_Action_Dispatcher( $this->createStub( \Racketmanager\Repositories\Results_Checker_Repository::class ), $this->createStub( \Racketmanager\Services\Results_Checker_Service::class ), $guard_instance ) );
        $container->set( 'results_checker_presenter', fn() => $services['results_checker_presenter'] ?? $this->createStub( \Racketmanager\Presentation\Presenters\Results_Checker_Presenter::class ) );

        foreach ( $services as $id => $service ) {
            if ( is_string( $id ) && is_object( $service ) && ! $container->has( $id ) ) {
                $container->set( $id, fn() => $service );
            }
        }

        return $container;
    }
}
