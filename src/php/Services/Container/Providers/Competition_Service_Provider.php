<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Container\Providers;

use Racketmanager\Application\Competition\Commands\Submit_Cup_Entry_Handler;
use Racketmanager\Application\Competition\Commands\Submit_League_Entry_Handler;
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
use Racketmanager\Application\Tournament\Commands\Create_Tournament_Payment_Handler;
use Racketmanager\Application\Tournament\Commands\Generate_Tournament_Bracket_Handler;
use Racketmanager\Application\Tournament\Commands\Submit_Tournament_Entry_Handler;
use Racketmanager\Application\Tournament\Commands\Submit_Tournament_Partner_Handler;
use Racketmanager\Application\Tournament\Commands\Withdraw_Tournament_Entry_Handler;
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
use Racketmanager\Domain\Competition\Scheduling\Competition_Scheduler;
use Racketmanager\Infrastructure\Wordpress\Ajax\Tournament_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Tournament_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Competition_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\League_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Tournament_Shortcode_Adapter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Tournament_Entry_Repository;
use Racketmanager\Repositories\Tournament_Repository;
use Racketmanager\Services\Competition\Competition_Schedule_Service;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition\Competition_Notification_Service;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\Knockout_Progression_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition\League_Schedule_Service as Competition_League_Schedule_Service;
use Racketmanager\Services\Competition\Promotion_Relegation_Service;
use Racketmanager\Services\Championship_Manager;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Services\Tournament_Service;

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
        $container->set( 'competition_repository', fn( Simple_Container $c ) => new Competition_Repository(
            static fn() => $c->has( 'fixture_repository' ) ? $c->get( 'fixture_repository' ) : null,
            static fn() => $c->has( 'fixture_relation_loader' ) ? $c->get( 'fixture_relation_loader' ) : null,
            static fn() => $c->get( 'club_repository' ),
            static fn() => $c->get( 'player_repository' ),
            static fn() => $c->get( 'event_repository' )
        ) );
        $container->set( 'event_repository', fn( Simple_Container $c ) => new Event_Repository( null, $c->get( 'competition_repository' ) ) );
        $container->set( 'league_team_repository', fn( Simple_Container $c ) => new League_Team_Repository( null, $c->get( 'club_repository' ), $c->get( 'team_repository' ) ) );
        $container->set( 'tournament_repository', fn( Simple_Container $c ) => new Tournament_Repository(
            competition_service: fn() => $c->get( 'competition_service' )
        ) );
        $container->set( 'tournament_entry_repository', fn() => new Tournament_Entry_Repository() );

        $container->set( 'league_repository', fn( Simple_Container $c ) => new League_Repository(
            $c->has( 'wpdb' ) ? $c->get( 'wpdb' ) : null,
            fn() => $c->get( 'event_repository' ),
            fn() => $c->get( 'league_query_service' )
        ) );

        $container->set( 'league_query_service', fn( Simple_Container $c ) => new League_Query_Service(
            $c->get( 'league_repository' ),
            fn() => $c->get( 'fixture_repository' ),
            fn() => $c->get( 'rubber_repository' ),
            fn() => $c->get( 'team_repository' ),
            fn() => $c->get( 'league_team_repository' ),
            fn() => $c->get( 'club_repository' ),
            fn() => $c->get( 'player_repository' )
        ) );

        $container->set( 'event_query_service', fn( Simple_Container $c ) => new Event_Query_Service(
            $c->get( 'event_repository' ),
            fn() => $c->get( 'fixture_repository' ),
            fn() => $c->get( 'rubber_repository' ),
            fn() => $c->get( 'league_repository' ),
            fn() => $c->get( 'team_repository' ),
            fn() => $c->get( 'club_repository' ),
            fn() => $c->get( 'player_repository' )
        ) );

        $container->set( 'competition_league_schedule_service', fn( Simple_Container $c ) => new Competition_League_Schedule_Service(
            $c->get( 'fixture_service' ),
            null,
            $c->get( 'league_repository' ),
            $c->get( 'event_query_service' ),
            fn() => $c->get( 'league_query_service' )
        ) );

        $container->set( 'competition_scheduler', fn() => new Competition_Scheduler() );
        $container->set( 'competition_schedule_service', fn( Simple_Container $c ) => new Competition_Schedule_Service(
            $c->get( 'event_repository' ),
            $c->get( 'league_repository' ),
            $c->get( 'event_query_service' ),
            $c->get( 'league_query_service' ),
            $c->get( 'fixture_service' ),
            $c->get( 'competition_scheduler' )
        ) );

        // Services
        $container->set( 'competition_season_service', fn( Simple_Container $c ) => new Competition_Season_Service(
            $c->get( 'league_team_repository' ),
            $c->get( 'club_repository' ),
            $c->get( 'event_repository' ),
            $c->get( 'league_repository' ),
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
            $c->get( 'fixture_repository' ),
            fn() => $c->get( 'league_service' )
        ) );

        $container->set( 'competition_notification_service', fn( Simple_Container $c ) => new Competition_Notification_Service(
            $c->get( 'mailer' ),
            $c->get( 'event_repository' ),
            $c->get( 'league_query_service' ),
            $c->get( 'league_repository' )
        ) );

        $container->set( 'promotion_relegation_service', fn( Simple_Container $c ) => new Promotion_Relegation_Service(
            $c->get( 'league_team_repository' ),
            $c->get( 'league_repository' ),
            $c->get( 'event_repository' ),
            $c->get( 'team_repository' )
        ) );
        $container->set( Promotion_Relegation_Service::class, fn( Simple_Container $c ) => $c->get( 'promotion_relegation_service' ) );

        $container->set( 'league_service', fn( Simple_Container $c ) => new League_Service(
            $app,
            $c->get( 'league_repository' ),
            $c->get( 'event_repository' ),
            $c->get( 'league_team_repository' ),
            $c->get( 'team_repository' ),
            fn() => $c->get( 'fixture_repository' ),
            fn() => $c->get( 'fixture_result_manager' ),
            fn() => $c->get( 'standings_service' ),
            $c->get( 'notification_service' ),
            $c->get( 'competition_notification_service' ),
            fn() => $c->get( 'league_query_service' )
        ) );

        $container->set( 'standings_service', fn( Simple_Container $c ) => new Standings_Service(
            $c->get( 'league_repository' ),
            $c->get( 'league_team_repository' ),
            $c->get( 'league_service' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'rubber_repository' ),
            fn() => $c->get( 'league_query_service' )
        ) );

        $container->set( 'knockout_progression_service', fn( Simple_Container $c ) => new Knockout_Progression_Service(
            $c->get( 'fixture_repository' ),
            $c->get( 'league_repository' ),
            $c->get( 'event_repository' ),
            null,
            fn() => $c->get( 'league_query_service' )
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
                $c->get( 'mailer' ),
                $c->get( 'league_repository' )
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
            $c->get( 'event_query_service' ),
            $c->get( 'mailer' ),
            $c->get( 'option_gateway' )
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
            $c->get( 'competition_season_service' )
        ) );

        $container->set( 'get_competition_events_handler', fn( Simple_Container $c ) => new Get_Competition_Events_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'competition_season_service' )
        ) );

        $container->set( 'get_competition_teams_handler', fn( Simple_Container $c ) => new Get_Competition_Teams_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'competition_season_service' )
        ) );

        $container->set( 'get_competition_clubs_handler', fn( Simple_Container $c ) => new Get_Competition_Clubs_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'club_service' ),
            $c->get( 'player_stats_service' ),
            $c->get( 'competition_season_service' )
        ) );

        $container->set( 'get_competition_players_handler', fn( Simple_Container $c ) => new Get_Competition_Players_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'player_service' ),
            $c->get( 'player_query_service' ),
            $c->get( 'player_stats_service' ),
            $c->get( 'competition_season_service' )
        ) );

        $container->set( 'get_competition_winners_handler', fn( Simple_Container $c ) => new Get_Competition_Winners_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'competition_season_service' )
        ) );

        $container->set( 'get_competition_dropdown_handler', fn( Simple_Container $c ) => new Get_Competition_Dropdown_Handler(
            $c->get( 'competition_service' )
        ) );

        $container->set( 'get_competitions_handler', fn( Simple_Container $c ) => new Get_Competitions_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'tournament_service' ),
            $c->get( 'player_service' ),
            $c->get( 'player_query_service' )
        ) );

        $container->set( 'get_competition_payment_handler', fn( Simple_Container $c ) => new Get_Competition_Payment_Handler(
            $c->get( 'tournament_service' ),
            $c->get( 'player_service' ),
            $c->get( 'finance_service' ),
            $c->get( 'competition_entry_service' )
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
            event_query_service: $c->get( 'event_query_service' )
        ) );

        // League Queries
        $container->set( 'get_league_standings_handler', fn( Simple_Container $c ) => new Get_League_Standings_Handler(
            $c->get( 'league_service' ),
            $c->get( 'league_repository' ),
            $c->get( 'league_query_service' )
        ) );

        $container->set( 'get_league_championship_handler', fn( Simple_Container $c ) => new Get_League_Championship_Handler(
            $c->get( 'league_service' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'rubber_repository' ),
            $c->get( 'league_query_service' )
        ) );


        $container->set( 'get_league_archive_handler', fn( Simple_Container $c ) => new Get_League_Archive_Handler(
            $c->get( 'league_service' ),
            $c->get( 'competition_service' ),
            $c->get( 'event_query_service' ),
            $c->get( 'league_query_service' )
        ) );

        $container->set( 'get_league_crosstable_handler', fn( Simple_Container $c ) => new Get_League_Crosstable_Handler(
            $c->get( 'league_service' ),
            $c->get( 'league_query_service' )
        ) );

        $container->set( 'get_league_matches_handler', fn( Simple_Container $c ) => new Get_League_Matches_Handler(
            $c->get( 'league_service' ),
            $c->get( 'league_query_service' )
        ) );


        $container->set( 'get_league_teams_handler', fn( Simple_Container $c ) => new Get_League_Teams_Handler(
            $c->get( 'league_service' ),
            $c->get( 'team_service' ),
            $c->get( 'player_stats_service' ),
            $c->get( 'league_query_service' )
        ) );

        $container->set( 'get_league_players_handler', fn( Simple_Container $c ) => new Get_League_Players_Handler(
            $c->get( 'league_service' ),
            $c->get( 'league_repository' ),
            $c->get( 'player_service' ),
            $c->get( 'player_query_service' ),
            $c->get( 'player_stats_service' ),
            $c->get( 'league_query_service' )
        ) );

        $container->set( 'get_league_season_dropdown_handler', fn( Simple_Container $c ) => new Get_League_Season_Dropdown_Handler(
            $c->get( 'league_service' )
        ) );

        $container->set( 'get_league_match_dropdown_handler', fn( Simple_Container $c ) => new Get_League_Match_Dropdown_Handler(
            $c->get( 'league_service' ),
            $c->get( 'league_query_service' )
        ) );

        $container->set( 'get_league_last_5_handler', fn( Simple_Container $c ) => new Get_League_Last_5_Handler(
            $c->get( 'league_service' ),
            $c->get( 'league_query_service' )
        ) );

        // Event Queries
        $container->set( 'get_event_handler', fn( Simple_Container $c ) => new Get_Event_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'event_query_service' )
        ) );

        $container->set( 'get_event_standings_handler', fn( Simple_Container $c ) => new Get_Event_Standings_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'event_query_service' )
        ) );

        $container->set( 'get_event_draw_handler', fn( Simple_Container $c ) => new Get_Event_Draw_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'league_service' ),
            $c->get( 'fixture_detail_service' ),
            $c->get( 'event_query_service' ),
            $c->get( 'league_query_service' )
        ) );

        $container->set( 'get_event_matches_handler', fn( Simple_Container $c ) => new Get_Event_Matches_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'league_service' ),
            $c->get( 'event_query_service' ),
            $c->get( 'league_query_service' )
        ) );

        $container->set( 'get_event_clubs_handler', fn( Simple_Container $c ) => new Get_Event_Clubs_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'club_service' ),
            $c->get( 'player_stats_service' ),
            $c->get( 'event_query_service' )
        ) );

        $container->set( 'get_event_teams_handler', fn( Simple_Container $c ) => new Get_Event_Teams_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'player_stats_service' ),
            $c->get( 'team_service' ),
            $c->get( 'event_query_service' )
        ) );

        $container->set( 'get_event_players_handler', fn( Simple_Container $c ) => new Get_Event_Players_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'player_service' ),
            $c->get( 'player_query_service' ),
            $c->get( 'player_stats_service' ),
            $c->get( 'event_query_service' )
        ) );

        $container->set( 'get_event_partner_handler', fn( Simple_Container $c ) => new Get_Event_Partner_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'player_service' ),
            $c->get( 'event_query_service' )
        ) );

        $container->set( 'get_event_team_matches_handler', fn( Simple_Container $c ) => new Get_Event_Team_Matches_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'event_query_service' )
        ) );

        $container->set( 'get_team_order_players_handler', fn( Simple_Container $c ) => new Get_Team_Order_Players_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'club_service' ),
            $c->get( 'registration_service' ),
            $c->get( 'settings_service' ),
            $c->get( 'event_query_service' )
        ) );

        $container->set( 'championship_manager', fn( Simple_Container $c ) => new Championship_Manager(
            $c->get( 'result_service' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'league_service' ),
            $c->get( 'event_repository' ),
            $c->get( 'league_query_service' ),
            $c->has( 'notification_service' ) ? $c->get( 'notification_service' ) : null
        ) );

        $container->set( 'get_event_dropdown_handler', fn( Simple_Container $c ) => new Get_Event_Dropdown_Handler(
            $c->get( 'competition_service' ),
            $c->get( 'event_query_service' )
        ) );

        // Tournament Queries
        $container->set( 'get_tournament_handler', fn( Simple_Container $c ) => new Get_Tournament_Handler(
            $c->get( 'tournament_service' ),
            $c->get( 'get_latest_tournament_handler' )
        ) );

        $container->set( 'get_tournament_overview_handler', fn( Simple_Container $c ) => new Get_Tournament_Overview_Handler(
            $c->get( 'tournament_service' )
        ) );

        $container->set( 'get_tournament_events_handler', fn( Simple_Container $c ) => new Get_Tournament_Events_Handler(
            $c->get( 'tournament_service' )
        ) );


        $container->set( 'get_tournament_draws_handler', fn( Simple_Container $c ) => new Get_Tournament_Draws_Handler(
            $c->get( 'tournament_service' )
        ) );

        $container->set( 'get_tournament_players_handler', fn( Simple_Container $c ) => new Get_Tournament_Players_Handler(
            $c->get( 'tournament_service' )
        ) );

        $container->set( 'get_tournament_winners_handler', fn( Simple_Container $c ) => new Get_Tournament_Winners_Handler(
            $c->get( 'tournament_service' )
        ) );

        $container->set( 'get_tournament_matches_handler', fn( Simple_Container $c ) => new Get_Tournament_Matches_Handler(
            $c->get( 'tournament_service' )
        ) );

        $container->set( 'get_tournament_match_handler', fn( Simple_Container $c ) => new Get_Tournament_Match_Handler(
            $c->get( 'tournament_service' ),
            $c->get( 'fixture_service' )
        ) );

        $container->set( 'get_order_of_play_handler', fn( Simple_Container $c ) => new Get_Order_Of_Play_Handler(
            tournament_service: $c->get( 'tournament_service' ),
            competition_repository: $c->get( 'competition_repository' )
        ) );

        $container->set( 'get_latest_tournament_handler', fn( Simple_Container $c ) => new Get_Latest_Tournament_Handler(
            $c->get( 'tournament_service' )
        ) );

        $container->set( 'get_tournament_withdrawal_handler', fn( Simple_Container $c ) => new Get_Tournament_Withdrawal_Handler(
            $c->get( 'tournament_service' )
        ) );

        // Command Handlers
        $container->set( 'submit_cup_entry_handler', fn( Simple_Container $c ) => new Submit_Cup_Entry_Handler(
            $c->get( 'competition_entry_service' )
        ) );
        $container->set( 'submit_league_entry_handler', fn( Simple_Container $c ) => new Submit_League_Entry_Handler(
            $c->get( 'competition_entry_service' )
        ) );
        $container->set( 'submit_tournament_entry_handler', fn( Simple_Container $c ) => new Submit_Tournament_Entry_Handler(
            $c->get( 'competition_entry_service' )
        ) );
        $container->set( 'withdraw_tournament_entry_handler', fn( Simple_Container $c ) => new Withdraw_Tournament_Entry_Handler(
            $c->get( 'competition_entry_service' )
        ) );
        $container->set( 'submit_tournament_partner_handler', fn( Simple_Container $c ) => new Submit_Tournament_Partner_Handler(
            $c->get( 'competition_entry_service' )
        ) );
        $container->set( 'create_tournament_payment_handler', fn( Simple_Container $c ) => new Create_Tournament_Payment_Handler(
            $c->get( 'finance_service' )
        ) );
        $container->set( 'generate_tournament_bracket_handler', fn( Simple_Container $c ) => new Generate_Tournament_Bracket_Handler(
            $c->get( 'championship_admin_service' )
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
            template_renderer: $c->get( 'shortcode_template_renderer' ),
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
            racketmanager: $app,
            event_query_service: $c->get( 'event_query_service' ),
            player_presenter: $c->get( 'player_presenter' )
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
            template_renderer: $c->get( 'shortcode_template_renderer' ),
            archive_presenter: $c->get( 'league_archive_presenter' ),
            tournament_presenter: $c->get( 'tournament_presenter' ),
            team_details_presenter: $c->get( 'team_details_presenter' ),
            player_presenter: $c->get( 'player_presenter' ),
            fixture_presenter: $c->get( 'fixture_presenter' ),
            competition_service: $c->get( 'competition_service' ),
            team_service: $c->get( 'team_service' ),
            fixture_link_service: $c->get( 'fixture_link_service' ),
            fixture_permission_service: $c->get( 'fixture_permission_service' )
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
            template_renderer: $c->get( 'shortcode_template_renderer' ),
            competition_shortcode_presenter: $c->get( 'competition_shortcode_presenter' ),
            tournament_presenter: $c->get( 'tournament_presenter' ),
            player_presenter: $c->get( 'player_presenter' ),
            team_details_presenter: $c->get( 'team_details_presenter' )
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
            template_renderer: $c->get( 'shortcode_template_renderer' ),
            competition_service: $c->get( 'competition_service' ),
            tournament_presenter: $c->get( 'tournament_presenter' ),
            player_presenter: $c->get( 'player_presenter' ),
            order_of_play_presenter: $c->get( 'order_of_play_presenter' )
        ) );

        // AJAX
        $container->set( 'tournament_ajax_adapter', fn( Simple_Container $c ) => new Tournament_Ajax_Adapter(
            $c->get( 'security_service' ),
            $c->get( 'json_response_factory' ),
            $c->get( 'competition_entry_service' ),
            $c->get( 'finance_service' ),
            $c->get( 'tournament_service' ),
            withdraw_tournament_entry_handler: $c->get( 'withdraw_tournament_entry_handler' ),
            create_tournament_payment_handler: $c->get( 'create_tournament_payment_handler' ),
            submit_tournament_partner_handler: $c->get( 'submit_tournament_partner_handler' ),
            submit_tournament_entry_handler: $c->get( 'submit_tournament_entry_handler' )
        ) );
        $container->set( 'tournament_ajax_controller', fn( Simple_Container $c ) => new Tournament_Ajax_Controller( $app, $c->get( 'tournament_ajax_adapter' ) ) );
    }
}
