<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Container\Providers;

use Racketmanager\Application\Competition\Queries\Club_Entry_Selection_Service;
use Racketmanager\Application\Competition\Queries\Get_Competition_Clubs_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Dropdown_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Events_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Overview_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Payment_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Players_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Teams_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Winners_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competitions_Handler;
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
use Racketmanager\Infrastructure\Wordpress\Ajax\Tournament_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Tournament_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Competition_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\League_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Tournament_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\League_Archive_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\Presenters\League_Standings_Presenter;
use Racketmanager\Presentation\Presenters\Order_Of_Play_Presenter;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Tournament_Entry_Repository;
use Racketmanager\Repositories\Tournament_Repository;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition\Competition_Notification_Service;
use Racketmanager\Services\Competition\Knockout_Progression_Service;
use Racketmanager\Services\Competition\League_Schedule_Service as Competition_League_Schedule_Service;
use Racketmanager\Services\Championship_Manager;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;

/**
 * Service provider for Competition, Event, League, and Tournament bounded context.
 */
final readonly class Competition_Service_Provider implements Service_Provider_Interface {
    public function __construct(
        private ?RacketManager $app = null
    ) {}

    public function register( Simple_Container $container ): void {
        $app = $this->app;

        // Repositories
        $container->set( 'competition_repository', fn() => new Competition_Repository() );
        $container->set( 'event_repository', fn() => new Event_Repository() );
        $container->set( 'league_team_repository', fn() => new League_Team_Repository() );
        $container->set( 'tournament_repository', fn( Simple_Container $c ) => new Tournament_Repository(
            competition_service: fn() => $c->get( 'competition_service' )
        ) );
        $container->set( 'tournament_entry_repository', fn() => new Tournament_Entry_Repository() );

        $container->set( 'league_repository', fn( Simple_Container $c ) => new League_Repository(
            fixture_repository: fn() => $c->get( 'fixture_repository' ),
            standings_service: fn() => $c->get( 'standings_service' ),
            league_schedule_service: fn() => new Competition_League_Schedule_Service( $c->get( 'fixture_service' ) ),
            competition_notification_service: fn() => $c->get( 'competition_notification_service' ),
            fixture_service: fn() => $c->get( 'fixture_service' ),
            fixture_result_manager: fn() => $c->get( 'fixture_result_manager' ),
            notification_service: fn() => $c->get( 'notification_service' ),
            rubber_repository: fn() => $c->get( 'rubber_repository' )
        ) );

        // Services
        $container->set( 'competition_season_service', fn( Simple_Container $c ) => new Competition_Season_Service(
            $c->get( 'league_team_repository' ),
            $c->get( 'club_repository' ),
            $c->get( 'event_repository' ),
            $c->get( 'domain_event_dispatcher' )
        ) );
        $container->set( 'competition_service', fn( Simple_Container $c ) => new Competition_Service(
            $app,
            $c->get( 'competition_repository' ),
            $c->get( 'club_repository' ),
            $c->get( 'event_repository' ),
            $c->get( 'league_repository' ),
            $c->get( 'league_team_repository' ),
            $c->get( 'season_repository' ),
            $c->get( 'team_repository' ),
            $c->get( 'fixture_repository' )
        ) );

        $container->set( 'competition_notification_service', fn( Simple_Container $c ) => new Competition_Notification_Service(
            $c->get( 'mailer' ),
            $c->get( 'event_repository' )
        ) );

        $container->set( 'league_service', fn( Simple_Container $c ) => new League_Service(
            $app,
            $c->get( 'league_repository' ),
            $c->get( 'event_repository' ),
            $c->get( 'league_team_repository' ),
            $c->get( 'team_repository' )
        ) );

        $container->set( 'standings_service', fn( Simple_Container $c ) => new Standings_Service(
            $c->get( 'league_repository' ),
            $c->get( 'league_team_repository' ),
            $c->get( 'league_service' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'rubber_repository' )
        ) );

        $container->set( 'knockout_progression_service', fn( Simple_Container $c ) => new Knockout_Progression_Service(
            $c->get( 'fixture_repository' ),
            $c->get( 'league_repository' ),
            $c->get( 'event_repository' )
        ) );

        $container->set( 'tournament_service', function ( Simple_Container $c ) use ( $app ) {
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
                $c->get( 'player_stats_service' ),
                $c->get( 'club_service' ),
                $c->get( 'finance_service' ),
                $c->get( 'league_service' ),
                $c->get( 'mailer' )
            );
            $c->get( 'fixture_link_service' )->set_tournament_service( $service );
            return $service;
        } );

        $container->set( 'competition_entry_service', fn( Simple_Container $c ) => new Competition_Entry_Service(
            $app,
            $c->get( 'club_repository' ),
            $c->get( 'league_repository' ),
            $c->get( 'league_team_repository' ),
            $c->get( 'team_repository' ),
            $c->get( 'tournament_repository' ),
            $c->get( 'tournament_entry_repository' ),
            $c->get( 'club_service' ),
            $c->get( 'competition_service' ),
            $c->get( 'finance_service' ),
            $c->get( 'player_service' ),
            $c->get( 'tournament_service' ),
            $c->get( 'notify_service' ),
            $c->get( 'mailer' ),
            $c->get( 'option_gateway' )
        ) );

        // Presenters
        $container->set( 'competition_shortcode_presenter', fn( Simple_Container $c ) => new Competition_Shortcode_Presenter(
            $c->get( 'view_renderer' ),
            $c->get( 'club_presenter' ),
            option_gateway: $c->get( 'option_gateway' )
        ) );

        $container->set( 'league_standings_presenter', fn( Simple_Container $c ) => new League_Standings_Presenter(
            $c->get( 'view_renderer' ),
            $c->get( 'club_presenter' ),
            $c->get( 'player_presenter' )
        ) );

        // Competition Queries
        $container->set( 'get_competition_handler', fn( Simple_Container $c ) => new Get_Competition_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'club_service' ),
            $c->get( 'player_service' ),
            $c->get( 'security_service' ),
            $c->get( 'settings_service' ),
            $c->get( 'competition_season_service' )
        ) );

        $container->set( 'get_competition_overview_handler', fn( Simple_Container $c ) => new Get_Competition_Overview_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'competition_shortcode_presenter' ),
            $c->get( 'competition_season_service' )
        ) );

        $container->set( 'get_competition_events_handler', fn( Simple_Container $c ) => new Get_Competition_Events_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'competition_shortcode_presenter' ),
            $c->get( 'competition_season_service' )
        ) );

        $container->set( 'get_competition_teams_handler', fn( Simple_Container $c ) => new Get_Competition_Teams_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'competition_shortcode_presenter' ),
            $c->get( 'competition_season_service' )
        ) );

        $container->set( 'get_competition_clubs_handler', fn( Simple_Container $c ) => new Get_Competition_Clubs_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'club_service' ),
            $c->get( 'player_stats_service' ),
            $c->get( 'tournament_presenter' ),
            $c->get( 'competition_shortcode_presenter' ),
            $c->get( 'competition_season_service' )
        ) );

        $container->set( 'get_competition_players_handler', fn( Simple_Container $c ) => new Get_Competition_Players_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'player_service' ),
            $c->get( 'player_query_service' ),
            $c->get( 'player_stats_service' ),
            $c->get( 'player_presenter' ),
            $c->get( 'competition_shortcode_presenter' ),
            $c->get( 'competition_season_service' )
        ) );

        $container->set( 'get_competition_winners_handler', fn( Simple_Container $c ) => new Get_Competition_Winners_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'competition_season_service' ),
            $c->get( 'competition_shortcode_presenter' )
        ) );

        $container->set( 'get_competition_dropdown_handler', fn( Simple_Container $c ) => new Get_Competition_Dropdown_Handler(
            $c->get( 'competition_service' )
        ) );

        $container->set( 'get_competitions_handler', fn( Simple_Container $c ) => new Get_Competitions_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'tournament_service' ),
            $c->get( 'player_service' ),
            $c->get( 'player_query_service' ),
            $c->get( 'competition_shortcode_presenter' )
        ) );

        $container->set( 'get_competition_payment_handler', fn( Simple_Container $c ) => new Get_Competition_Payment_Handler(
            $c->get( 'tournament_service' ),
            $c->get( 'player_service' ),
            $c->get( 'finance_service' ),
            $c->get( 'competition_entry_service' ),
            $c->get( 'tournament_presenter' )
        ) );

        $container->set( 'club_entry_selection_service', fn( Simple_Container $c ) => new Club_Entry_Selection_Service(
            $c->get( 'club_service' ),
            $c->get( 'player_service' )
        ) );

        $container->set( 'get_competition_entry_handler', fn( Simple_Container $c ) => new Get_Competition_Entry_Handler(
            club_entry_selection: $c->get( 'club_entry_selection_service' ),
            competition_service: $c->get( 'competition_service' ),
            tournament_service: $c->get( 'tournament_service' ),
            player_service: $c->get( 'player_service' ),
            club_service: $c->get( 'club_service' ),
            team_service: $c->get( 'team_service' ),
            registration_service: $c->get( 'registration_service' ),
            competition_entry_service: $c->get( 'competition_entry_service' ),
            finance_service: $c->get( 'finance_service' ),
            competition_presenter: $c->get( 'competition_shortcode_presenter' ),
            tournament_presenter: $c->get( 'tournament_presenter' )
        ) );

        // League Queries
        $container->set( 'get_league_standings_handler', fn( Simple_Container $c ) => new Get_League_Standings_Handler(
            $c->get( 'league_service' ),
            $c->get( 'league_repository' )
        ) );

        $container->set( 'get_league_championship_handler', fn( Simple_Container $c ) => new Get_League_Championship_Handler(
            $c->get( 'league_service' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'competition_service' ),
            $c->get( 'team_service' ),
            $c->get( 'fixture_link_service' ),
            $c->get( 'fixture_permission_service' ),
            $c->get( 'shortcode_template_renderer' ),
            $c->get( 'rubber_repository' ),
            $c->get( 'tournament_presenter' )
        ) );

        $container->set( 'league_archive_presenter', fn( Simple_Container $c ) => new League_Archive_Presenter(
            $c->get( 'option_gateway' )
        ) );

        $container->set( 'get_league_archive_handler', fn( Simple_Container $c ) => new Get_League_Archive_Handler(
            $c->get( 'league_service' ),
            $c->get( 'competition_service' ),
            $c->get( 'shortcode_template_renderer' ),
            $c->get( 'league_archive_presenter' )
        ) );

        $container->set( 'get_league_crosstable_handler', fn( Simple_Container $c ) => new Get_League_Crosstable_Handler(
            $c->get( 'league_service' ),
            $c->get( 'shortcode_template_renderer' )
        ) );

        $container->set( 'get_league_matches_handler', fn( Simple_Container $c ) => new Get_League_Matches_Handler(
            $c->get( 'league_service' ),
            $c->get( 'shortcode_template_renderer' ),
            $c->get( 'tournament_presenter' )
        ) );

        $container->set( 'team_details_presenter', fn( Simple_Container $c ) => new Team_Details_Presenter(
            $c->get( 'option_gateway' ),
            $c->get( 'settings_service' )
        ) );

        $container->set( 'get_league_teams_handler', fn( Simple_Container $c ) => new Get_League_Teams_Handler(
            $c->get( 'league_service' ),
            $c->get( 'team_service' ),
            $c->get( 'player_stats_service' ),
            $c->get( 'tournament_presenter' ),
            $c->get( 'team_details_presenter' )
        ) );

        $container->set( 'get_league_players_handler', fn( Simple_Container $c ) => new Get_League_Players_Handler(
            $c->get( 'league_service' ),
            $c->get( 'league_repository' ),
            $c->get( 'player_service' ),
            $c->get( 'shortcode_template_renderer' ),
            $c->get( 'player_query_service' ),
            $c->get( 'player_stats_service' ),
            $c->get( 'player_presenter' )
        ) );

        $container->set( 'get_league_season_dropdown_handler', fn( Simple_Container $c ) => new Get_League_Season_Dropdown_Handler(
            $c->get( 'league_service' ),
            $c->get( 'shortcode_template_renderer' )
        ) );

        $container->set( 'get_league_match_dropdown_handler', fn( Simple_Container $c ) => new Get_League_Match_Dropdown_Handler(
            $c->get( 'league_service' ),
            $c->get( 'shortcode_template_renderer' )
        ) );

        $container->set( 'get_league_last_5_handler', fn( Simple_Container $c ) => new Get_League_Last_5_Handler(
            $c->get( 'league_service' ),
            $c->get( 'shortcode_template_renderer' ),
            $c->get( 'fixture_presenter' )
        ) );

        // Event Queries
        $container->set( 'get_event_handler', fn( Simple_Container $c ) => new Get_Event_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'competition_shortcode_presenter' )
        ) );

        $container->set( 'get_event_standings_handler', fn( Simple_Container $c ) => new Get_Event_Standings_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'competition_shortcode_presenter' )
        ) );

        $container->set( 'get_event_draw_handler', fn( Simple_Container $c ) => new Get_Event_Draw_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'league_service' ),
            $c->get( 'competition_shortcode_presenter' ),
            $c->get( 'fixture_detail_service' )
        ) );

        $container->set( 'get_event_matches_handler', fn( Simple_Container $c ) => new Get_Event_Matches_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'tournament_presenter' ),
            $c->get( 'league_service' )
        ) );

        $container->set( 'get_event_clubs_handler', fn( Simple_Container $c ) => new Get_Event_Clubs_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'club_service' ),
            $c->get( 'player_stats_service' ),
            $c->get( 'tournament_presenter' ),
            $c->get( 'competition_shortcode_presenter' )
        ) );

        $container->set( 'get_event_teams_handler', fn( Simple_Container $c ) => new Get_Event_Teams_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'player_stats_service' ),
            $c->get( 'tournament_presenter' ),
            $c->get( 'team_service' ),
            $c->get( 'team_details_presenter' ),
            $c->get( 'competition_shortcode_presenter' )
        ) );

        $container->set( 'get_event_players_handler', fn( Simple_Container $c ) => new Get_Event_Players_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'player_service' ),
            $c->get( 'player_query_service' ),
            $c->get( 'player_stats_service' ),
            $c->get( 'player_presenter' ),
            $c->get( 'competition_shortcode_presenter' )
        ) );

        $container->set( 'get_event_partner_handler', fn( Simple_Container $c ) => new Get_Event_Partner_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'player_service' )
        ) );

        $container->set( 'get_event_team_matches_handler', fn( Simple_Container $c ) => new Get_Event_Team_Matches_Handler(
            $c->get( 'competition_service' )
        ) );

        $container->set( 'get_team_order_players_handler', fn( Simple_Container $c ) => new Get_Team_Order_Players_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'club_service' ),
            $c->get( 'registration_service' ),
            $c->get( 'settings_service' )
        ) );

        $container->set( 'championship_manager', fn( Simple_Container $c ) => new Championship_Manager(
            $c->get( 'result_service' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'league_service' ),
            $c->get( 'event_repository' ),
            $c->has( 'notification_service' ) ? $c->get( 'notification_service' ) : null
        ) );

        $container->set( 'get_event_dropdown_handler', fn( Simple_Container $c ) => new Get_Event_Dropdown_Handler(
            $c->get( 'competition_service' )
        ) );

        // Tournament Queries
        $container->set( 'get_tournament_handler', fn( Simple_Container $c ) => new Get_Tournament_Handler(
            $c->get( 'tournament_service' ),
            $c->get( 'tournament_presenter' ),
            $c->get( 'get_latest_tournament_handler' )
        ) );

        $container->set( 'get_tournament_overview_handler', fn( Simple_Container $c ) => new Get_Tournament_Overview_Handler(
            $c->get( 'tournament_service' ),
            $c->get( 'tournament_presenter' )
        ) );

        $container->set( 'get_tournament_events_handler', fn( Simple_Container $c ) => new Get_Tournament_Events_Handler(
            $c->get( 'tournament_service' ),
            $c->get( 'tournament_presenter' )
        ) );

        $container->set( 'tournament_presenter', fn( Simple_Container $c ) => new Tournament_Presenter(
            date_format: (string) $c->get( 'option_gateway' )->get_option( 'date_format', 'Y-m-d' ),
            fixture_detail_service: $c->get( 'fixture_detail_service' ),
            team_match_row_presenter: $c->get( 'team_match_row_presenter' )
        ) );

        $container->set( 'get_tournament_draws_handler', fn( Simple_Container $c ) => new Get_Tournament_Draws_Handler(
            $c->get( 'tournament_service' ),
            $c->get( 'tournament_presenter' )
        ) );

        $container->set( 'get_tournament_players_handler', fn( Simple_Container $c ) => new Get_Tournament_Players_Handler(
            $c->get( 'tournament_service' ),
            $c->get( 'tournament_presenter' ),
            $c->get( 'player_presenter' )
        ) );

        $container->set( 'get_tournament_winners_handler', fn( Simple_Container $c ) => new Get_Tournament_Winners_Handler(
            $c->get( 'tournament_service' )
        ) );

        $container->set( 'get_tournament_matches_handler', fn( Simple_Container $c ) => new Get_Tournament_Matches_Handler(
            $c->get( 'tournament_service' ),
            $c->get( 'tournament_presenter' )
        ) );

        $container->set( 'get_tournament_match_handler', fn( Simple_Container $c ) => new Get_Tournament_Match_Handler(
            $c->get( 'tournament_service' ),
            $c->get( 'fixture_service' ),
            $c->get( 'tournament_presenter' )
        ) );

        $container->set( 'order_of_play_presenter', fn( Simple_Container $c ) => new Order_Of_Play_Presenter(
            fixture_repository: $c->get( 'fixture_repository' ),
            competition_service: $c->get( 'competition_service' ),
            team_service: $c->get( 'team_service' ),
            option_gateway: $c->get( 'option_gateway' )
        ) );

        $container->set( 'get_order_of_play_handler', fn( Simple_Container $c ) => new Get_Order_Of_Play_Handler(
            tournament_service: $c->get( 'tournament_service' ),
            competition_repository: $c->get( 'competition_repository' ),
            order_of_play_presenter: $c->get( 'order_of_play_presenter' )
        ) );

        $container->set( 'get_latest_tournament_handler', fn( Simple_Container $c ) => new Get_Latest_Tournament_Handler(
            $c->get( 'tournament_service' )
        ) );

        $container->set( 'get_tournament_withdrawal_handler', fn( Simple_Container $c ) => new Get_Tournament_Withdrawal_Handler(
            $c->get( 'tournament_service' )
        ) );

        // Shortcode Adapters
        $container->set( 'competition_shortcode_adapter', fn( Simple_Container $c ) => new Competition_Shortcode_Adapter(
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
                $c->get( 'player_presenter' ),
                $c->get( 'favourite_presenter' )
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
            tournament_presenter: $c->get( 'tournament_presenter' ),
            view_presenter: $c->get( 'shortcode_view_presenter' ),
            racketmanager: $app
        ) );

        $container->set( 'league_shortcode_adapter', fn( Simple_Container $c ) => new League_Shortcode_Adapter(
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

        $container->set( 'event_shortcode_adapter', fn( Simple_Container $c ) => new Event_Shortcode_Adapter(
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

        $container->set( 'tournament_shortcode_adapter', fn( Simple_Container $c ) => new Tournament_Shortcode_Adapter(
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

        // AJAX
        $container->set( 'tournament_ajax_adapter', fn( Simple_Container $c ) => new Tournament_Ajax_Adapter(
            $c->get( 'security_service' ),
            $c->get( 'json_response_factory' ),
            $c->get( 'competition_entry_service' ),
            $c->get( 'finance_service' ),
            $c->get( 'tournament_service' )
        ) );
        $container->set( 'tournament_ajax_controller', fn( Simple_Container $c ) => new Tournament_Ajax_Controller( $app, $c->get( 'tournament_ajax_adapter' ) ) );
    }
}
