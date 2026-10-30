<?php
declare( strict_types=1 );
/**
 * Admin Screens Service Provider
 *
 * Registers admin screen controllers, view models, and bridges in the container.
 *
 * @package RacketManager
 * @subpackage Admin
 */

namespace Racketmanager\Admin;

use LogicException;
use Racketmanager\Admin;
use Racketmanager\Admin\Admin_Display;
use Racketmanager\Admin\Controllers\Clubs_Admin_Controller;
use Racketmanager\Admin\Controllers\Club_Admin_Controller;
use Racketmanager\Admin\Controllers\Club_Players_Admin_Controller;
use Racketmanager\Admin\Controllers\Club_Roles_Admin_Controller;
use Racketmanager\Admin\Controllers\Club_Team_Admin_Controller;
use Racketmanager\Admin\Controllers\Club_Teams_Admin_Controller;
use Racketmanager\Admin\Controllers\Competition_Config_Admin_Controller;
use Racketmanager\Admin\Controllers\Competition_Season_Modify_Admin_Controller;
use Racketmanager\Admin\Controllers\Competition_Seasons_Admin_Controller;
use Racketmanager\Admin\Controllers\Contact_Admin_Controller;
use Racketmanager\Admin\Controllers\Cup_Competitions_Admin_Controller;
use Racketmanager\Admin\Controllers\Cup_Draw_Admin_Controller;
use Racketmanager\Admin\Controllers\Cup_Match_Admin_Controller;
use Racketmanager\Admin\Controllers\Cup_Matches_Admin_Controller;
use Racketmanager\Admin\Controllers\Cup_Overview_Admin_Controller;
use Racketmanager\Admin\Controllers\Cup_Plan_Admin_Controller;
use Racketmanager\Admin\Controllers\Cup_Setup_Admin_Controller;
use Racketmanager\Admin\Controllers\Cup_Setup_Event_Admin_Controller;
use Racketmanager\Admin\Controllers\League_Competitions_Admin_Controller;
use Racketmanager\Admin\Controllers\League_Constitution_Admin_Controller;
use Racketmanager\Admin\Controllers\League_Event_Admin_Controller;
use Racketmanager\Admin\Controllers\League_Match_Admin_Controller;
use Racketmanager\Admin\Controllers\League_Matches_Admin_Controller;
use Racketmanager\Admin\Controllers\League_Overview_Admin_Controller;
use Racketmanager\Admin\Controllers\League_Schedule_Admin_Controller;
use Racketmanager\Admin\Controllers\League_Setup_Admin_Controller;
use Racketmanager\Admin\Controllers\League_Setup_Event_Admin_Controller;
use Racketmanager\Admin\Controllers\League_Standings_Admin_Controller;
use Racketmanager\Admin\Controllers\Teams_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Competition_Config_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Draw_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Event_Config_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Fixtures_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Information_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Overview_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Plan_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Setup_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Setup_Event_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Team_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Tournaments_Admin_Controller;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter;
use Racketmanager\Admin\Presenters\Admin_Competition_Page_Presenter;
use Racketmanager\Admin\Presenters\Admin_Date_Presenter;
use Racketmanager\Admin\Presenters\Admin_Entry_Link_Presenter;
use Racketmanager\Admin\Presenters\Admin_League_Matches_Presenter;
use Racketmanager\Admin\Presenters\Admin_League_Schedule_Presenter;
use Racketmanager\Admin\Presenters\Admin_Plan_Presenter;
use Racketmanager\Admin\Presenters\Admin_Results_Match_Presenter;
use Racketmanager\Admin\Presenters\Championship_Finals_Presenter_Interface;
use Racketmanager\Services\Admin\Flash_Message_Store_Interface;
use Racketmanager\Services\Admin\League_Team_Admin_Service;
use Racketmanager\Admin\Controllers\Finances_Charge_Admin_Controller;
use Racketmanager\Admin\Controllers\Finances_Charges_Admin_Controller;
use Racketmanager\Admin\Controllers\Finances_Home_Admin_Controller;
use Racketmanager\Admin\Controllers\Finances_Invoice_Admin_Controller;
use Racketmanager\Admin\Controllers\Finances_Invoices_Admin_Controller;
use Racketmanager\Admin\Controllers\Player_Admin_Controller;
use Racketmanager\Admin\Controllers\Player_Errors_Admin_Controller;
use Racketmanager\Admin\Controllers\Player_Requests_Admin_Controller;
use Racketmanager\Admin\Controllers\Players_Admin_Controller;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Providers\Service_Provider_Interface;
use Racketmanager\Services\Container\Simple_Container;

final readonly class Admin_Screens_Service_Provider implements Service_Provider_Interface {

    public function __construct(
        private ?RacketManager $app = null
    ) {
    }

    public function register( Simple_Container $container ): void {
        $app = $this->app;

        // Flash message store
        $container->set( 'admin_flash_message_store', fn(): Flash_Message_Store_Interface => new Admin_Flash_Message_Store() );
        $container->set( Flash_Message_Store_Interface::class, fn( Simple_Container $c ): Flash_Message_Store_Interface => $c->get( 'admin_flash_message_store' ) );

        // Admin Presenters
        $container->set( 'admin_date_presenter', fn() => new Admin_Date_Presenter() );
        $container->set( 'admin_entry_link_presenter', fn() => new Admin_Entry_Link_Presenter() );

        $container->set( 'admin_league_matches_presenter', fn( Simple_Container $c ) => new Admin_League_Matches_Presenter(
            $c->get( 'admin_date_presenter' )
        ) );

        $container->set( 'admin_championship_finals_presenter', fn( Simple_Container $c ): Championship_Finals_Presenter_Interface => new Admin_Championship_Finals_Presenter(
            $c->get( 'admin_date_presenter' )
        ) );
        $container->set( Championship_Finals_Presenter_Interface::class, fn( Simple_Container $c ) => $c->get( 'admin_championship_finals_presenter' ) );

        $container->set( 'admin_results_match_presenter', fn( Simple_Container $c ) => new Admin_Results_Match_Presenter(
            $c->get( 'admin_date_presenter' ),
            $c->get( 'league_repository' )
        ) );

        $container->set( 'admin_competition_page_presenter', fn( Simple_Container $c ) => new Admin_Competition_Page_Presenter(
            $c->get( 'competition_service' ),
            $c->get( 'club_service' ),
            $c->get( 'season_service' ),
            $c->get( 'finance_service' )
        ) );
        $container->set( Admin_Competition_Page_Presenter::class, fn( Simple_Container $c ) => $c->get( 'admin_competition_page_presenter' ) );

        $container->set( 'admin_plan_presenter', fn( Simple_Container $c ) => new Admin_Plan_Presenter(
            $c->get( 'fixture_detail_service' ),
            $c->get( 'player_repository' ),
            $c->get( 'fixture_relation_loader' ),
        ) );

        $container->set( 'admin_league_schedule_presenter', fn() => new Admin_League_Schedule_Presenter() );

        $container->set( 'competition_config_admin_controller', fn( Simple_Container $c ) => new Competition_Config_Admin_Controller(
            $c->get( 'competition_service' ),
            $c->get( 'tournament_service' ),
            $c->get( 'competition_action_dispatcher' ),
            $c->get( 'action_guard' ),
            $c->get( 'admin_competition_page_presenter' )
        ) );

        $container->set( 'competition_season_modify_admin_controller', fn( Simple_Container $c ) => new Competition_Season_Modify_Admin_Controller(
            $c->get( 'competition_service' ),
            $c->get( 'competition_season_action_dispatcher' ),
            $c->get( 'action_guard' ),
            $c->get( 'admin_competition_page_presenter' )
        ) );

        $container->set( 'clubs_admin_controller', fn( Simple_Container $c ) => new Clubs_Admin_Controller(
            $c->get( 'club_service' ),
            $c->get( 'club_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'club_admin_controller', fn( Simple_Container $c ) => new Club_Admin_Controller(
            $c->get( 'club_service' ),
            $c->get( 'club_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'club_players_admin_controller', fn( Simple_Container $c ) => new Club_Players_Admin_Controller(
            $c->get( 'club_service' ),
            $c->get( 'registration_service' ),
            $c->get( 'club_player_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'club_teams_admin_controller', fn( Simple_Container $c ) => new Club_Teams_Admin_Controller(
            $c->get( 'club_service' ),
            $c->get( 'team_service' ),
            $c->get( 'club_team_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'club_team_admin_controller', fn( Simple_Container $c ) => new Club_Team_Admin_Controller(
            $c->get( 'tournament_service' ),
            $c->get( 'league_service' ),
            $c->get( 'team_service' ),
            $c->get( 'club_service' ),
            $c->get( 'league_query_service' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'club_roles_admin_controller', fn( Simple_Container $c ) => new Club_Roles_Admin_Controller(
            $c->get( 'club_service' ),
            $c->get( 'registration_service' ),
            $c->get( 'club_role_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'finances_charges_admin_controller', fn( Simple_Container $c ) => new Finances_Charges_Admin_Controller(
            $c->get( 'finance_service' ),
            $c->get( 'competition_service' ),
            $c->get( 'season_service' ),
            $c->get( 'finance_charge_action_dispatcher' ),
            $c->get( 'action_guard' ),
            $c->get( 'invoice_presenter' )
        ) );

        $container->set( 'finances_home_admin_controller', fn( Simple_Container $c ) => new Finances_Home_Admin_Controller(
            $c->get( 'finances_charges_admin_controller' )
        ) );

        $container->set( 'finances_invoices_admin_controller', fn( Simple_Container $c ) => new Finances_Invoices_Admin_Controller(
            $c->get( 'finance_service' ),
            $c->get( 'club_service' ),
            $c->get( 'finance_invoice_action_dispatcher' ),
            $c->get( 'action_guard' ),
            $c->get( 'invoice_presenter' )
        ) );

        $container->set( 'finances_charge_admin_controller', fn( Simple_Container $c ) => new Finances_Charge_Admin_Controller(
            $c->get( 'finance_service' ),
            $c->get( 'competition_service' ),
            $c->get( 'season_service' ),
            $c->get( 'finance_charge_action_dispatcher' ),
            $c->get( 'action_guard' ),
            $c->get( 'invoice_presenter' )
        ) );

        $container->set( 'finances_invoice_admin_controller', fn( Simple_Container $c ) => new Finances_Invoice_Admin_Controller(
            $c->get( 'finance_service' ),
            $c->get( 'finance_invoice_action_dispatcher' ),
            $c->get( 'action_guard' ),
            $c->get( 'invoice_presenter' )
        ) );

        $container->set( 'admin_competition_screen', fn( Simple_Container $c ) => new Admin_Competition(
            $c,
            $c->get( 'admin_message_service' ),
            $c->get( 'admin_action_adapter' ),
            $c->get( 'admin_screen_presenter' )
        ) );

        $container->set( 'admin_club_screen', fn( Simple_Container $c ) => new Admin_Club(
            $c,
            $c->get( 'admin_message_service' ),
            $c->get( 'admin_action_adapter' ),
            $c->get( 'admin_screen_presenter' )
        ) );

        $container->set( 'admin_finances_screen', fn( Simple_Container $c ) => new Admin_Finances(
            $c,
            $c->get( 'admin_message_service' ),
            $c->get( 'admin_action_adapter' ),
            $c->get( 'admin_screen_presenter' )
        ) );

        $container->set( 'player_admin_controller', fn( Simple_Container $c ) => new Player_Admin_Controller(
            $c->get( 'player_service' ),
            $c->get( 'club_service' ),
            $c->get( 'player_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'player_errors_admin_controller', fn( Simple_Container $c ) => new Player_Errors_Admin_Controller(
            $c->get( 'player_service' ),
            $c->get( 'player_error_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'player_requests_admin_controller', fn( Simple_Container $c ) => new Player_Requests_Admin_Controller(
            $c->get( 'registration_service' ),
            $c->get( 'club_service' ),
            $c->get( 'player_request_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'players_admin_controller', fn( Simple_Container $c ) => new Players_Admin_Controller(
            $c->get( 'player_service' ),
            $c->get( 'player_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'admin_player_screen', fn( Simple_Container $c ) => new Admin_Player(
            $c,
            $c->get( 'admin_message_service' ),
            $c->get( 'admin_action_adapter' ),
            $c->get( 'admin_screen_presenter' )
        ) );

        $container->set( 'cup_competitions_admin_controller', fn( Simple_Container $c ) => new Cup_Competitions_Admin_Controller(
            $c->get( 'competition_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'index_action_dispatcher' )
        ) );

        $container->set( 'cup_overview_admin_controller', fn( Simple_Container $c ) => new Cup_Overview_Admin_Controller(
            $c->get( 'competition_service' ),
            $c->get( 'club_service' ),
            $c->get( 'event_query_service' ),
            $c->get( 'league_query_service' ),
            $c->get( 'admin_entry_link_presenter' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'cup_setup_admin_controller', fn( Simple_Container $c ) => new Cup_Setup_Admin_Controller(
            $c->get( 'competition_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'cup_setup_action_dispatcher' )
        ) );

        $container->set( 'cup_setup_event_admin_controller', fn( Simple_Container $c ) => new Cup_Setup_Event_Admin_Controller(
            $c->get( 'competition_service' ),
            $c->get( 'league_repository' ),
            $c->get( 'league_query_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'cup_setup_action_dispatcher' )
        ) );

        $container->set( 'cup_draw_admin_controller', fn( Simple_Container $c ) => new Cup_Draw_Admin_Controller(
            $c->get( 'competition_service' ),
            $c->get( 'league_repository' ),
            $c->get( 'league_query_service' ),
            $c->get( 'fixture_detail_service' ),
            $c->get( 'admin_championship_finals_presenter' ),
            $c->get( 'draw_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'cup_matches_admin_controller', fn( Simple_Container $c ) => new Cup_Matches_Admin_Controller(
            $c->get( 'competition_service' ),
            $c->get( 'league_repository' ),
            $c->get( 'league_query_service' ),
            $c->get( 'draw_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'cup_match_admin_controller', fn( Simple_Container $c ) => new Cup_Match_Admin_Controller(
            $c->get( 'competition_service' ),
            $c->get( 'league_repository' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'team_repository' ),
            $c->get( 'draw_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'cup_plan_admin_controller', fn( Simple_Container $c ) => new Cup_Plan_Admin_Controller(
            $c->get( 'competition_service' ),
            $c->get( 'club_repository' ),
            $c->get( 'admin_plan_presenter' ),
            $c->get( 'cup_plan_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'league_competitions_admin_controller', fn( Simple_Container $c ) => new League_Competitions_Admin_Controller(
            $c->get( 'competition_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'index_action_dispatcher' )
        ) );

        $container->set( 'league_overview_admin_controller', fn( Simple_Container $c ) => new League_Overview_Admin_Controller(
            $c->get( 'competition_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'admin_entry_link_presenter' ),
            $c->get( 'league_contact_action_dispatcher' )
        ) );

        $container->set( 'league_setup_admin_controller', fn( Simple_Container $c ) => new League_Setup_Admin_Controller(
            $c->get( 'competition_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'league_setup_action_dispatcher' )
        ) );

        $container->set( 'league_setup_event_admin_controller', fn( Simple_Container $c ) => new League_Setup_Event_Admin_Controller(
            $c->get( 'event_repository' ),
            $c->get( 'action_guard' ),
            $c->get( 'event_setup_action_dispatcher' )
        ) );

        $container->set( 'league_event_admin_controller', fn( Simple_Container $c ) => new League_Event_Admin_Controller(
            $c->get( 'event_repository' ),
            $c->get( 'league_service' ),
            $c->get( 'event_query_service' ),
            $c->get( 'league_query_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'league_event_action_dispatcher' )
        ) );

        $container->set( 'league_constitution_admin_controller', fn( Simple_Container $c ) => new League_Constitution_Admin_Controller(
            $c->get( 'event_repository' ),
            $c->get( 'season_service' ),
            $c->get( 'event_query_service' ),
            $c->get( 'league_query_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'league_constitution_action_dispatcher' )
        ) );

        $container->set( 'league_matches_admin_controller', fn( Simple_Container $c ) => new League_Matches_Admin_Controller(
            $c->get( 'event_repository' ),
            $c->get( 'event_query_service' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'league_match_admin_controller', fn( Simple_Container $c ) => new League_Match_Admin_Controller(
            $c->get( 'league_service' ),
            $c->get( 'league_query_service' ),
            $c->get( 'team_service' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'league_schedule_admin_controller', fn( Simple_Container $c ) => new League_Schedule_Admin_Controller(
            $c->get( 'competition_service' ),
            $c->get( 'event_repository' ),
            $c->get( 'admin_league_schedule_presenter' ),
            $c->get( 'league_schedule_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'league_standings_admin_controller', fn( Simple_Container $c ) => new League_Standings_Admin_Controller(
            $c->get( 'league_service' ),
            $c->get( 'league_query_service' ),
            $c->get( 'league_team_action_dispatcher' ),
            $c->get( 'league_match_action_dispatcher' ),
            $c->get( 'league_contact_action_dispatcher' ),
            $c->get( 'admin_championship_finals_presenter' ),
            $c->get( 'admin_league_matches_presenter' ),
            $c->get( 'fixture_detail_service' ),
            $c->get( 'standings_service' ),
            $c->get( 'league_team_repository' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'league_team_admin_service', fn( Simple_Container $c ) => new League_Team_Admin_Service(
            $c->get( 'league_service' ),
            $c->get( 'team_service' ),
            $c->get( 'team_repository' )
        ) );

        $container->set( 'teams_admin_controller', fn( Simple_Container $c ) => new Teams_Admin_Controller(
            $c->get( 'tournament_service' ),
            $c->get( 'league_service' ),
            $c->get( 'league_team_admin_service' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'contact_admin_controller', fn( Simple_Container $c ) => new Contact_Admin_Controller(
            $c->get( 'tournament_service' ),
            $c->get( 'league_service' ),
            $c->get( 'competition_service' ),
            $c->get( 'tournament_contact_action_dispatcher' ),
            $c->get( 'league_contact_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'competition_seasons_admin_controller', fn( Simple_Container $c ) => new Competition_Seasons_Admin_Controller(
            $c->get( 'competition_service' ),
            $c->get( 'season_delete_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'admin_event_screen', fn( Simple_Container $c ) => new Admin_Event(
            $app ?? throw new LogicException( 'RacketManager instance required for admin_event_screen' ),
            $c->get( 'admin_action_adapter' ),
            $c->get( 'admin_screen_presenter' ),
            $c->get( 'event_action_dispatcher' )
        ) );

        $container->set( 'admin_league_screen', fn( Simple_Container $c ) => new Admin_League(
            $c,
            $c->get( 'admin_message_service' ),
            $c->get( 'admin_action_adapter' ),
            $c->get( 'admin_screen_presenter' )
        ) );

        $container->set( 'admin_cup_screen', fn( Simple_Container $c ) => new Admin_Cup(
            $c,
            $c->get( 'admin_message_service' ),
            $c->get( 'admin_action_adapter' ),
            $c->get( 'admin_screen_presenter' )
        ) );

        $container->set( 'admin_tournament_screen', fn( Simple_Container $c ) => new Admin_Tournament(
            $app ?? throw new LogicException( 'RacketManager instance required for admin_tournament_screen' ),
            $c->get( 'view_renderer' ),
            $c->get( 'admin_message_service' ),
            $c->get( 'admin_action_adapter' ),
            $c->get( 'admin_screen_presenter' )
        ) );

        // Tournament Admin Controllers
        $container->set( 'tournament_admin_controller', fn( Simple_Container $c ) => new Tournament_Admin_Controller(
            $c->get( 'tournament_service' ),
            $c->get( 'club_service' ),
            $c->get( 'competition_service' ),
            $c->get( 'season_service' ),
            $c->get( 'tournament_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'tournament_tournaments_admin_controller', fn( Simple_Container $c ) => new Tournament_Tournaments_Admin_Controller(
            $c->get( 'tournament_service' ),
            $c->get( 'competition_service' ),
            $c->get( 'season_service' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'tournament_overview_admin_controller', fn( Simple_Container $c ) => new Tournament_Overview_Admin_Controller(
            $c->get( 'tournament_service' ),
            $c->get( 'tournament_overview_action_dispatcher' ),
            $c->get( 'action_guard' ),
            $c->get( 'admin_entry_link_presenter' )
        ) );

        $container->set( 'tournament_setup_admin_controller', fn( Simple_Container $c ) => new Tournament_Setup_Admin_Controller(
            $c->get( 'tournament_service' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'tournament_competition_config_admin_controller', fn( Simple_Container $c ) => new Tournament_Competition_Config_Admin_Controller(
            $c->get( 'tournament_service' ),
            $c->get( 'competition_service' ),
            $c->get( 'club_service' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'tournament_plan_admin_controller', fn( Simple_Container $c ) => new Tournament_Plan_Admin_Controller(
            $c->get( 'tournament_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'admin_plan_presenter' ),
        ) );

        $container->set( 'tournament_event_config_admin_controller', fn( Simple_Container $c ) => new Tournament_Event_Config_Admin_Controller(
            $c->get( 'tournament_service' ),
            $c->get( 'competition_service' ),
            $c->get( 'competition_season_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'event_repository' ),
            $c->get( 'league_service' )
        ) );

        $container->set( 'tournament_team_admin_controller', fn( Simple_Container $c ) => new Tournament_Team_Admin_Controller(
            $c->get( 'tournament_service' ),
            $c->get( 'league_service' ),
            $c->get( 'team_service' ),
            $c->get( 'club_service' ),
            $c->get( 'action_guard' ),
            $c->get( 'league_query_service' )
        ) );

        $container->set( 'tournament_setup_event_admin_controller', fn( Simple_Container $c ) => new Tournament_Setup_Event_Admin_Controller(
            $c->get( 'tournament_service' ),
            $c->get( 'league_service' ),
            $c->get( 'draw_action_dispatcher' ),
            $c->get( 'action_guard' ),
            $c->get( 'league_query_service' )
        ) );

        $container->set( 'tournament_contact_admin_controller', fn( Simple_Container $c ) => $c->get( 'contact_admin_controller' ) );

        $container->set( 'tournament_information_admin_controller', fn( Simple_Container $c ) => new Tournament_Information_Admin_Controller(
            $c->get( 'tournament_service' ),
            $c->get( 'tournament_information_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'tournament_fixtures_admin_controller', fn( Simple_Container $c ) => new Tournament_Fixtures_Admin_Controller(
            $c->get( 'tournament_matches_admin_service' ),
            $c->get( 'draw_action_dispatcher' ),
            $c->get( 'action_guard' )
        ) );

        $container->set( 'tournament_draw_admin_controller', fn( Simple_Container $c ) => new Tournament_Draw_Admin_Controller(
            $c->get( 'tournament_service' ),
            $c->get( 'league_service' ),
            $c->get( 'draw_action_dispatcher' ),
            $c->get( 'action_guard' ),
            $c->get( 'admin_championship_finals_presenter' )
        ) );

        // Admin application core
        $container->set( 'admin_display', function( Simple_Container $c ) use ( $app ) {
            if ( null === $app ) {
                throw new LogicException( 'admin_display service requires a valid RacketManager application instance.' );
            }
            return new Admin_Display( $app, $c );
        } );
        $container->set( Admin_Display::class, fn( Simple_Container $c ) => $c->get( 'admin_display' ) );

        $container->set( 'admin', function( Simple_Container $c ) use ( $app ) {
            if ( null !== Admin::get_current_instance() ) {
                return Admin::get_current_instance();
            }
            if ( null === $app ) {
                throw new LogicException( 'Admin service requires a valid RacketManager application instance.' );
            }
            return new Admin(
                $c,
                $app,
                $c->get( 'admin_display' ),
                $c->has( 'asset_manager' ) ? $c->get( 'asset_manager' ) : null,
                $c->has( 'admin_ajax_controller' ) ? $c->get( 'admin_ajax_controller' ) : null
            );
        } );
        $container->set( Admin::class, fn( Simple_Container $c ) => $c->get( 'admin' ) );
    }
}
