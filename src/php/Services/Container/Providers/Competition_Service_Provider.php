<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Container\Providers;

use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Application\Club\Ports\Team_Service_Interface;
use Racketmanager\Application\Competition\Commands\Submit_Cup_Entry_Handler;
use Racketmanager\Application\Competition\Commands\Submit_League_Entry_Handler;
use Racketmanager\Application\Competition\Ports\Competition_Entry_Service_Interface;
use Racketmanager\Application\Competition\Ports\Competition_Season_Service_Interface;
use Racketmanager\Application\Competition\Ports\Competition_Service_Interface;
use Racketmanager\Application\Competition\Ports\Event_Query_Service_Interface;
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
use Racketmanager\Application\Fixture\Ports\Fixture_Detail_Service_Interface;
use Racketmanager\Application\Fixture\Ports\Fixture_Link_Service_Interface;
use Racketmanager\Application\Fixture\Ports\Fixture_Result_Manager_Interface;
use Racketmanager\Application\Fixture\Ports\Fixture_Service_Interface;
use Racketmanager\Application\League\Ports\League_Query_Service_Interface;
use Racketmanager\Application\League\Ports\League_Schedule_Service_Interface;
use Racketmanager\Application\League\Ports\League_Service_Interface;
use Racketmanager\Application\League\Ports\Promotion_Relegation_Service_Interface;
use Racketmanager\Application\League\Ports\Standings_Service_Interface;
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
use Racketmanager\Application\Mail\Mailer_Interface;
use Racketmanager\Application\Player\Ports\Player_Query_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Stats_Service_Interface;
use Racketmanager\Application\Player\Ports\Registration_Service_Interface;
use Racketmanager\Application\Ports\Finance_Service_Interface;
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Application\Ports\Settings_Service_Interface;
use Racketmanager\Application\Tournament\Commands\Create_Tournament_Payment_Handler;
use Racketmanager\Application\Tournament\Commands\Generate_Tournament_Bracket_Handler;
use Racketmanager\Application\Tournament\Commands\Submit_Tournament_Entry_Handler;
use Racketmanager\Application\Tournament\Commands\Submit_Tournament_Partner_Handler;
use Racketmanager\Application\Tournament\Commands\Withdraw_Tournament_Entry_Handler;
use Racketmanager\Application\Tournament\Ports\Tournament_Service_Interface;
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
use Racketmanager\Domain\Events\Domain_Event_Dispatcher_Interface;
use Racketmanager\Application\Ports\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Tournament_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Tournament_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Competition_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\League_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Tournament_Shortcode_Adapter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Interfaces\Charge_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Season_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Entry_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Tournament_Entry_Repository;
use Racketmanager\Repositories\Tournament_Repository;
use Racketmanager\Services\Admin\Championship_Admin_Service;
use Racketmanager\Services\Championship_Manager;
use Racketmanager\Services\Competition\Competition_Notification_Service;
use Racketmanager\Services\Competition\Competition_Schedule_Service;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\Knockout_Progression_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition\League_Schedule_Service as Competition_League_Schedule_Service;
use Racketmanager\Services\Competition\Promotion_Relegation_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Notify_Service;
use Racketmanager\Services\Result_Service;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Services\Tournament_Service;

/**
 * Service provider for Competition, Event, League, and Tournament bounded context.
 */
final readonly class Competition_Service_Provider implements Service_Provider_Interface {
    /**
     * Constructor.
     *
     * @param RacketManager|null $app Application instance.
     */
    public function __construct(
        private ?RacketManager $app = null
    ) {}

    /**
     * Registers services in the container.
     *
     * @param Simple_Container $container The service container.
     * @return void
     */
    public function register( Simple_Container $container ): void {
        $app = $this->app;

        // Repositories.
        $container->set(
            Competition_Repository_Interface::class,
            fn( Simple_Container $c ): Competition_Repository_Interface => new Competition_Repository(
                static fn(): Fixture_Repository_Interface => $c->get( Fixture_Repository_Interface::class ),
                static fn() => $c->has( Fixture_Relation_Loader::class ) ? $c->get( Fixture_Relation_Loader::class ) : null,
                static fn() => $c->get( Club_Repository_Interface::class ),
                static fn() => $c->get( Player_Repository_Interface::class ),
                static fn() => $c->get( Event_Repository_Interface::class ),
                static fn(): Rubber_Repository_Interface => $c->get( Rubber_Repository_Interface::class )
            )
        );
        $container->alias( Competition_Repository::class, Competition_Repository_Interface::class );
        $container->alias( 'competition_repository', Competition_Repository_Interface::class );

        $container->set(
            Event_Repository_Interface::class,
            fn( Simple_Container $c ): Event_Repository_Interface => new Event_Repository(
                null,
                $c->get( Competition_Repository_Interface::class )
            )
        );
        $container->alias( Event_Repository::class, Event_Repository_Interface::class );
        $container->alias( 'event_repository', Event_Repository_Interface::class );

        $container->set(
            League_Team_Repository_Interface::class,
            fn( Simple_Container $c ): League_Team_Repository_Interface => new League_Team_Repository(
                null,
                $c->get( Club_Repository_Interface::class ),
                $c->get( Team_Repository_Interface::class ),
                fn() => $c->get( Fixture_Repository_Interface::class )
            )
        );
        $container->alias( League_Team_Repository::class, League_Team_Repository_Interface::class );
        $container->alias( 'league_team_repository', League_Team_Repository_Interface::class );

        $container->set(
            Tournament_Repository_Interface::class,
            fn( Simple_Container $c ): Tournament_Repository_Interface => new Tournament_Repository(
                competition_service: fn() => $c->get( Competition_Service_Interface::class )
            )
        );
        $container->alias( Tournament_Repository::class, Tournament_Repository_Interface::class );
        $container->alias( 'tournament_repository', Tournament_Repository_Interface::class );

        $container->set(
            Tournament_Entry_Repository_Interface::class,
            fn(): Tournament_Entry_Repository_Interface => new Tournament_Entry_Repository()
        );
        $container->alias( Tournament_Entry_Repository::class, Tournament_Entry_Repository_Interface::class );
        $container->alias( 'tournament_entry_repository', Tournament_Entry_Repository_Interface::class );

        $container->set(
            League_Repository_Interface::class,
            fn( Simple_Container $c ): League_Repository_Interface => new League_Repository(
                $c->has_definition( 'wpdb' ) ? $c->get( 'wpdb' ) : null,
                fn() => $c->get( Event_Repository_Interface::class ),
                fn() => $c->get( League_Query_Service_Interface::class )
            )
        );
        $container->alias( League_Repository::class, League_Repository_Interface::class );
        $container->alias( 'league_repository', League_Repository_Interface::class );

        $container->set(
            League_Query_Service_Interface::class,
            fn( Simple_Container $c ): League_Query_Service_Interface => new League_Query_Service(
                $c->get( League_Repository_Interface::class ),
                fn() => $c->get( Fixture_Repository_Interface::class ),
                fn() => $c->get( Rubber_Repository_Interface::class ),
                fn() => $c->get( Team_Repository_Interface::class ),
                fn() => $c->get( League_Team_Repository_Interface::class ),
                fn() => $c->get( Club_Repository_Interface::class ),
                fn() => $c->get( Player_Repository_Interface::class )
            )
        );
        $container->alias( League_Query_Service::class, League_Query_Service_Interface::class );
        $container->alias( 'league_query_service', League_Query_Service_Interface::class );

        $container->set(
            Event_Query_Service_Interface::class,
            fn( Simple_Container $c ): Event_Query_Service_Interface => new Event_Query_Service(
                $c->get( Event_Repository_Interface::class ),
                fn() => $c->get( Fixture_Repository_Interface::class ),
                fn() => $c->get( Rubber_Repository_Interface::class ),
                fn() => $c->get( League_Repository_Interface::class ),
                fn() => $c->get( Team_Repository_Interface::class ),
                fn() => $c->get( Club_Repository_Interface::class ),
                fn() => $c->get( Player_Repository_Interface::class )
            )
        );
        $container->alias( Event_Query_Service::class, Event_Query_Service_Interface::class );
        $container->alias( 'event_query_service', Event_Query_Service_Interface::class );

        $container->set(
            League_Schedule_Service_Interface::class,
            fn( Simple_Container $c ): League_Schedule_Service_Interface => new Competition_League_Schedule_Service(
                $c->get( Fixture_Service_Interface::class ),
                null,
                $c->get( League_Repository_Interface::class ),
                $c->get( Event_Query_Service_Interface::class ),
                fn() => $c->get( League_Query_Service_Interface::class )
            )
        );
        $container->alias( Competition_League_Schedule_Service::class, League_Schedule_Service_Interface::class );
        $container->alias( 'competition_league_schedule_service', League_Schedule_Service_Interface::class );

        $container->set( Competition_Scheduler::class, fn(): Competition_Scheduler => new Competition_Scheduler() );
        $container->alias( 'competition_scheduler', Competition_Scheduler::class );

        $container->set(
            Competition_Schedule_Service::class,
            fn( Simple_Container $c ): Competition_Schedule_Service => new Competition_Schedule_Service(
                $c->get( Event_Repository_Interface::class ),
                $c->get( League_Repository_Interface::class ),
                $c->get( Event_Query_Service_Interface::class ),
                $c->get( League_Query_Service_Interface::class ),
                $c->get( Fixture_Service_Interface::class ),
                $c->get( Competition_Scheduler::class )
            )
        );
        $container->alias( 'competition_schedule_service', Competition_Schedule_Service::class );

        // Services.
        $container->set(
            Competition_Season_Service_Interface::class,
            fn( Simple_Container $c ): Competition_Season_Service_Interface => new Competition_Season_Service(
                $c->get( League_Team_Repository_Interface::class ),
                $c->get( Club_Repository_Interface::class ),
                $c->get( Event_Repository_Interface::class ),
                $c->get( League_Repository_Interface::class ),
                $c->get( Domain_Event_Dispatcher_Interface::class ),
                $c->get( Query_Vars_Interface::class )
            )
        );
        $container->alias( Competition_Season_Service::class, Competition_Season_Service_Interface::class );
        $container->alias( 'competition_season_service', Competition_Season_Service_Interface::class );

        $container->set(
            Competition_Service_Interface::class,
            fn( Simple_Container $c ): Competition_Service_Interface => new Competition_Service(
                $app,
                $c->get( Competition_Repository_Interface::class ),
                $c->get( Club_Repository_Interface::class ),
                $c->get( Event_Repository_Interface::class ),
                $c->get( League_Repository_Interface::class ),
                $c->get( League_Team_Repository_Interface::class ),
                $c->get( Season_Repository_Interface::class ),
                $c->get( Team_Repository_Interface::class ),
                $c->get( Fixture_Repository_Interface::class ),
                fn() => $c->get( League_Service_Interface::class ),
                $c->get( Option_Service::class )
            )
        );
        $container->alias( Competition_Service::class, Competition_Service_Interface::class );
        $container->alias( 'competition_service', Competition_Service_Interface::class );

        $container->set(
            Competition_Notification_Service::class,
            fn( Simple_Container $c ): Competition_Notification_Service => new Competition_Notification_Service(
                $c->get( Option_Service::class ),
                $c->get( Club_Repository_Interface::class ),
                $c->get( Mailer_Interface::class ),
                $c->get( Event_Repository_Interface::class ),
                $c->get( League_Query_Service_Interface::class ),
                $c->get( League_Repository_Interface::class )
            )
        );
        $container->alias( 'competition_notification_service', Competition_Notification_Service::class );

        $container->set(
            Promotion_Relegation_Service_Interface::class,
            fn( Simple_Container $c ): Promotion_Relegation_Service_Interface => new Promotion_Relegation_Service(
                $c->get( League_Team_Repository_Interface::class ),
                $c->get( League_Repository_Interface::class ),
                $c->get( Event_Repository_Interface::class ),
                $c->get( Team_Repository_Interface::class )
            )
        );
        $container->alias( Promotion_Relegation_Service::class, Promotion_Relegation_Service_Interface::class );
        $container->alias( 'promotion_relegation_service', Promotion_Relegation_Service_Interface::class );

        $container->set(
            League_Service_Interface::class,
            fn( Simple_Container $c ): League_Service_Interface => new League_Service(
                $app,
                $c->get( League_Repository_Interface::class ),
                $c->get( Event_Repository_Interface::class ),
                $c->get( League_Team_Repository_Interface::class ),
                $c->get( Team_Repository_Interface::class ),
                fn() => $c->get( Fixture_Repository_Interface::class ),
                fn() => $c->get( Fixture_Result_Manager_Interface::class ),
                fn() => $c->get( Standings_Service_Interface::class ),
                $c->get( Notification_Service::class ),
                $c->get( Competition_Notification_Service::class ),
                fn() => $c->get( League_Query_Service_Interface::class )
            )
        );
        $container->alias( League_Service::class, League_Service_Interface::class );
        $container->alias( 'league_service', League_Service_Interface::class );

        $container->set(
            Standings_Service_Interface::class,
            fn( Simple_Container $c ): Standings_Service_Interface => new Standings_Service(
                $c->get( League_Repository_Interface::class ),
                $c->get( League_Team_Repository_Interface::class ),
                $c->get( League_Service_Interface::class ),
                $c->get( Fixture_Repository_Interface::class ),
                $c->get( Rubber_Repository_Interface::class ),
                fn() => $c->get( League_Query_Service_Interface::class )
            )
        );
        $container->alias( Standings_Service::class, Standings_Service_Interface::class );
        $container->alias( 'standings_service', Standings_Service_Interface::class );

        $container->set(
            Knockout_Progression_Service::class,
            fn( Simple_Container $c ): Knockout_Progression_Service => new Knockout_Progression_Service(
                $c->get( Fixture_Repository_Interface::class ),
                $c->get( League_Repository_Interface::class ),
                $c->get( Event_Repository_Interface::class ),
                null,
                fn() => $c->get( League_Query_Service_Interface::class )
            )
        );
        $container->alias( 'knockout_progression_service', Knockout_Progression_Service::class );

        $container->set(
            Tournament_Service_Interface::class,
            function ( Simple_Container $c ) use ( $app ): Tournament_Service_Interface {
                $service = new Tournament_Service(
                    $app,
                    $c->get( Tournament_Repository_Interface::class ),
                    $c->get( Charge_Repository_Interface::class ),
                    $c->get( Event_Repository_Interface::class ),
                    $c->get( Fixture_Service_Interface::class ),
                    $c->get( League_Team_Repository_Interface::class ),
                    $c->get( Tournament_Entry_Repository_Interface::class ),
                    $c->get( Competition_Service_Interface::class ),
                    $c->get( Player_Service_Interface::class ),
                    $c->get( Player_Stats_Service_Interface::class ),
                    $c->get( Club_Service_Interface::class ),
                    $c->get( Finance_Service_Interface::class ),
                    $c->get( League_Service_Interface::class ),
                    $c->get( Mailer_Interface::class ),
                    $c->get( Option_Service::class ),
                    $c->get( League_Repository_Interface::class )
                );
                $c->get( Fixture_Link_Service_Interface::class )->set_tournament_service( $service );
                return $service;
            }
        );
        $container->alias( Tournament_Service::class, Tournament_Service_Interface::class );
        $container->alias( 'tournament_service', Tournament_Service_Interface::class );

        $container->set(
            Competition_Entry_Service_Interface::class,
            fn( Simple_Container $c ): Competition_Entry_Service_Interface => new Competition_Entry_Service(
                $app,
                $c->get( Club_Repository_Interface::class ),
                $c->get( League_Repository_Interface::class ),
                $c->get( League_Team_Repository_Interface::class ),
                $c->get( Team_Repository_Interface::class ),
                $c->get( Tournament_Repository_Interface::class ),
                $c->get( Tournament_Entry_Repository_Interface::class ),
                $c->get( Club_Service_Interface::class ),
                $c->get( Competition_Service_Interface::class ),
                $c->get( Finance_Service_Interface::class ),
                $c->get( Player_Service_Interface::class ),
                $c->get( Tournament_Service_Interface::class ),
                $c->get( Notify_Service::class ),
                $c->get( Event_Query_Service_Interface::class ),
                $c->get( Mailer_Interface::class ),
                $c->get( Option_Service::class ),
                $c->get( Player_Repository_Interface::class ),
                $c->get( Event_Repository_Interface::class )
            )
        );
        $container->alias( Competition_Entry_Service::class, Competition_Entry_Service_Interface::class );
        $container->alias( 'competition_entry_service', Competition_Entry_Service_Interface::class );

        // Competition Queries.
        $container->set(
            Get_Competition_Handler::class,
            fn( Simple_Container $c ): Get_Competition_Handler => new Get_Competition_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Club_Service_Interface::class ),
                $c->get( Player_Service_Interface::class ),
                $c->get( Security_Service_Interface::class ),
                $c->get( Settings_Service_Interface::class ),
                $c->get( Competition_Season_Service_Interface::class )
            )
        );
        $container->alias( 'get_competition_handler', Get_Competition_Handler::class );

        $container->set(
            Get_Competition_Overview_Handler::class,
            fn( Simple_Container $c ): Get_Competition_Overview_Handler => new Get_Competition_Overview_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Competition_Season_Service_Interface::class )
            )
        );
        $container->alias( 'get_competition_overview_handler', Get_Competition_Overview_Handler::class );

        $container->set(
            Get_Competition_Events_Handler::class,
            fn( Simple_Container $c ): Get_Competition_Events_Handler => new Get_Competition_Events_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Competition_Season_Service_Interface::class )
            )
        );
        $container->alias( 'get_competition_events_handler', Get_Competition_Events_Handler::class );

        $container->set(
            Get_Competition_Teams_Handler::class,
            fn( Simple_Container $c ): Get_Competition_Teams_Handler => new Get_Competition_Teams_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Competition_Season_Service_Interface::class )
            )
        );
        $container->alias( 'get_competition_teams_handler', Get_Competition_Teams_Handler::class );

        $container->set(
            Get_Competition_Clubs_Handler::class,
            fn( Simple_Container $c ): Get_Competition_Clubs_Handler => new Get_Competition_Clubs_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Club_Service_Interface::class ),
                $c->get( Player_Stats_Service_Interface::class ),
                $c->get( Competition_Season_Service_Interface::class )
            )
        );
        $container->alias( 'get_competition_clubs_handler', Get_Competition_Clubs_Handler::class );

        $container->set(
            Get_Competition_Players_Handler::class,
            fn( Simple_Container $c ): Get_Competition_Players_Handler => new Get_Competition_Players_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Player_Service_Interface::class ),
                $c->get( Player_Query_Service_Interface::class ),
                $c->get( Player_Stats_Service_Interface::class ),
                $c->get( Competition_Season_Service_Interface::class )
            )
        );
        $container->alias( 'get_competition_players_handler', Get_Competition_Players_Handler::class );

        $container->set(
            Get_Competition_Winners_Handler::class,
            fn( Simple_Container $c ): Get_Competition_Winners_Handler => new Get_Competition_Winners_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Competition_Season_Service_Interface::class )
            )
        );
        $container->alias( 'get_competition_winners_handler', Get_Competition_Winners_Handler::class );

        $container->set(
            Get_Competition_Dropdown_Handler::class,
            fn( Simple_Container $c ): Get_Competition_Dropdown_Handler => new Get_Competition_Dropdown_Handler(
                $c->get( Competition_Service_Interface::class )
            )
        );
        $container->alias( 'get_competition_dropdown_handler', Get_Competition_Dropdown_Handler::class );

        $container->set(
            Get_Competitions_Handler::class,
            fn( Simple_Container $c ): Get_Competitions_Handler => new Get_Competitions_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Tournament_Service_Interface::class ),
                $c->get( Player_Service_Interface::class ),
                $c->get( Player_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_competitions_handler', Get_Competitions_Handler::class );

        $container->set(
            Get_Competition_Payment_Handler::class,
            fn( Simple_Container $c ): Get_Competition_Payment_Handler => new Get_Competition_Payment_Handler(
                $c->get( Tournament_Service_Interface::class ),
                $c->get( Player_Service_Interface::class ),
                $c->get( Finance_Service_Interface::class ),
                $c->get( Competition_Entry_Service_Interface::class )
            )
        );
        $container->alias( 'get_competition_payment_handler', Get_Competition_Payment_Handler::class );

        $container->set(
            Club_Entry_Selection_Service::class,
            fn( Simple_Container $c ): Club_Entry_Selection_Service => new Club_Entry_Selection_Service(
                $c->get( Club_Service_Interface::class ),
                $c->get( Player_Service_Interface::class )
            )
        );
        $container->alias( 'club_entry_selection_service', Club_Entry_Selection_Service::class );

        $container->set(
            Get_Competition_Entry_Handler::class,
            fn( Simple_Container $c ): Get_Competition_Entry_Handler => new Get_Competition_Entry_Handler(
                club_entry_selection: $c->get( Club_Entry_Selection_Service::class ),
                competition_service: $c->get( Competition_Service_Interface::class ),
                tournament_service: $c->get( Tournament_Service_Interface::class ),
                player_service: $c->get( Player_Service_Interface::class ),
                club_service: $c->get( Club_Service_Interface::class ),
                team_service: $c->get( Team_Service_Interface::class ),
                registration_service: $c->get( Registration_Service_Interface::class ),
                competition_entry_service: $c->get( Competition_Entry_Service_Interface::class ),
                finance_service: $c->get( Finance_Service_Interface::class ),
                event_query_service: $c->get( Event_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_competition_entry_handler', Get_Competition_Entry_Handler::class );

        // League Queries.
        $container->set(
            Get_League_Standings_Handler::class,
            fn( Simple_Container $c ): Get_League_Standings_Handler => new Get_League_Standings_Handler(
                $c->get( League_Service_Interface::class ),
                $c->get( League_Repository_Interface::class ),
                $c->get( League_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_league_standings_handler', Get_League_Standings_Handler::class );

        $container->set(
            Get_League_Championship_Handler::class,
            fn( Simple_Container $c ): Get_League_Championship_Handler => new Get_League_Championship_Handler(
                $c->get( League_Service_Interface::class ),
                $c->get( Fixture_Repository_Interface::class ),
                $c->get( Rubber_Repository_Interface::class ),
                $c->get( League_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_league_championship_handler', Get_League_Championship_Handler::class );

        $container->set(
            Get_League_Archive_Handler::class,
            fn( Simple_Container $c ): Get_League_Archive_Handler => new Get_League_Archive_Handler(
                $c->get( League_Service_Interface::class ),
                $c->get( Competition_Service_Interface::class ),
                $c->get( Event_Query_Service_Interface::class ),
                $c->get( League_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_league_archive_handler', Get_League_Archive_Handler::class );

        $container->set(
            Get_League_Crosstable_Handler::class,
            fn( Simple_Container $c ): Get_League_Crosstable_Handler => new Get_League_Crosstable_Handler(
                $c->get( League_Service_Interface::class ),
                $c->get( League_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_league_crosstable_handler', Get_League_Crosstable_Handler::class );

        $container->set(
            Get_League_Matches_Handler::class,
            fn( Simple_Container $c ): Get_League_Matches_Handler => new Get_League_Matches_Handler(
                $c->get( League_Service_Interface::class ),
                $c->get( League_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_league_matches_handler', Get_League_Matches_Handler::class );

        $container->set(
            Get_League_Teams_Handler::class,
            fn( Simple_Container $c ): Get_League_Teams_Handler => new Get_League_Teams_Handler(
                $c->get( League_Service_Interface::class ),
                $c->get( Team_Service_Interface::class ),
                $c->get( Player_Stats_Service_Interface::class ),
                $c->get( League_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_league_teams_handler', Get_League_Teams_Handler::class );

        $container->set(
            Get_League_Players_Handler::class,
            fn( Simple_Container $c ): Get_League_Players_Handler => new Get_League_Players_Handler(
                $c->get( League_Service_Interface::class ),
                $c->get( League_Repository_Interface::class ),
                $c->get( Player_Service_Interface::class ),
                $c->get( Player_Query_Service_Interface::class ),
                $c->get( Player_Stats_Service_Interface::class ),
                $c->get( League_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_league_players_handler', Get_League_Players_Handler::class );

        $container->set(
            Get_League_Season_Dropdown_Handler::class,
            fn( Simple_Container $c ): Get_League_Season_Dropdown_Handler => new Get_League_Season_Dropdown_Handler(
                $c->get( League_Service_Interface::class )
            )
        );
        $container->alias( 'get_league_season_dropdown_handler', Get_League_Season_Dropdown_Handler::class );

        $container->set(
            Get_League_Match_Dropdown_Handler::class,
            fn( Simple_Container $c ): Get_League_Match_Dropdown_Handler => new Get_League_Match_Dropdown_Handler(
                $c->get( League_Service_Interface::class ),
                $c->get( League_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_league_match_dropdown_handler', Get_League_Match_Dropdown_Handler::class );

        $container->set(
            Get_League_Last_5_Handler::class,
            fn( Simple_Container $c ): Get_League_Last_5_Handler => new Get_League_Last_5_Handler(
                $c->get( League_Service_Interface::class ),
                $c->get( League_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_league_last_5_handler', Get_League_Last_5_Handler::class );

        // Event Queries.
        $container->set(
            Get_Event_Handler::class,
            fn( Simple_Container $c ): Get_Event_Handler => new Get_Event_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Event_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_event_handler', Get_Event_Handler::class );

        $container->set(
            Get_Event_Standings_Handler::class,
            fn( Simple_Container $c ): Get_Event_Standings_Handler => new Get_Event_Standings_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Event_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_event_standings_handler', Get_Event_Standings_Handler::class );

        $container->set(
            Get_Event_Draw_Handler::class,
            fn( Simple_Container $c ): Get_Event_Draw_Handler => new Get_Event_Draw_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( League_Service_Interface::class ),
                $c->get( Fixture_Detail_Service_Interface::class ),
                $c->get( Event_Query_Service_Interface::class ),
                $c->get( League_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_event_draw_handler', Get_Event_Draw_Handler::class );

        $container->set(
            Get_Event_Matches_Handler::class,
            fn( Simple_Container $c ): Get_Event_Matches_Handler => new Get_Event_Matches_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( League_Service_Interface::class ),
                $c->get( Event_Query_Service_Interface::class ),
                $c->get( League_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_event_matches_handler', Get_Event_Matches_Handler::class );

        $container->set(
            Get_Event_Clubs_Handler::class,
            fn( Simple_Container $c ): Get_Event_Clubs_Handler => new Get_Event_Clubs_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Club_Service_Interface::class ),
                $c->get( Player_Stats_Service_Interface::class ),
                $c->get( Event_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_event_clubs_handler', Get_Event_Clubs_Handler::class );

        $container->set(
            Get_Event_Teams_Handler::class,
            fn( Simple_Container $c ): Get_Event_Teams_Handler => new Get_Event_Teams_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Player_Stats_Service_Interface::class ),
                $c->get( Team_Service_Interface::class ),
                $c->get( Event_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_event_teams_handler', Get_Event_Teams_Handler::class );

        $container->set(
            Get_Event_Players_Handler::class,
            fn( Simple_Container $c ): Get_Event_Players_Handler => new Get_Event_Players_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Player_Service_Interface::class ),
                $c->get( Player_Query_Service_Interface::class ),
                $c->get( Player_Stats_Service_Interface::class ),
                $c->get( Event_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_event_players_handler', Get_Event_Players_Handler::class );

        $container->set(
            Get_Event_Partner_Handler::class,
            fn( Simple_Container $c ): Get_Event_Partner_Handler => new Get_Event_Partner_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Player_Service_Interface::class ),
                $c->get( Event_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_event_partner_handler', Get_Event_Partner_Handler::class );

        $container->set(
            Get_Event_Team_Matches_Handler::class,
            fn( Simple_Container $c ): Get_Event_Team_Matches_Handler => new Get_Event_Team_Matches_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Event_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_event_team_matches_handler', Get_Event_Team_Matches_Handler::class );

        $container->set(
            Get_Team_Order_Players_Handler::class,
            fn( Simple_Container $c ): Get_Team_Order_Players_Handler => new Get_Team_Order_Players_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Club_Service_Interface::class ),
                $c->get( Registration_Service_Interface::class ),
                $c->get( Settings_Service_Interface::class ),
                $c->get( Event_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_team_order_players_handler', Get_Team_Order_Players_Handler::class );

        $container->set(
            Championship_Manager::class,
            fn( Simple_Container $c ): Championship_Manager => new Championship_Manager(
                $c->get( Result_Service::class ),
                $c->get( Fixture_Repository_Interface::class ),
                $c->get( League_Service_Interface::class ),
                $c->get( Event_Repository_Interface::class ),
                $c->get( League_Query_Service_Interface::class ),
                $c->has( Notification_Service::class ) ? $c->get( Notification_Service::class ) : null
            )
        );
        $container->alias( 'championship_manager', Championship_Manager::class );

        $container->set(
            Get_Event_Dropdown_Handler::class,
            fn( Simple_Container $c ): Get_Event_Dropdown_Handler => new Get_Event_Dropdown_Handler(
                $c->get( Competition_Service_Interface::class ),
                $c->get( Event_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_event_dropdown_handler', Get_Event_Dropdown_Handler::class );

        // Tournament Queries.
        $container->set(
            Get_Tournament_Handler::class,
            fn( Simple_Container $c ): Get_Tournament_Handler => new Get_Tournament_Handler(
                $c->get( Tournament_Service_Interface::class ),
                $c->get( Get_Latest_Tournament_Handler::class ),
                $c->get( Query_Vars_Interface::class )
            )
        );
        $container->alias( 'get_tournament_handler', Get_Tournament_Handler::class );

        $container->set(
            Get_Tournament_Overview_Handler::class,
            fn( Simple_Container $c ): Get_Tournament_Overview_Handler => new Get_Tournament_Overview_Handler(
                $c->get( Tournament_Service_Interface::class )
            )
        );
        $container->alias( 'get_tournament_overview_handler', Get_Tournament_Overview_Handler::class );

        $container->set(
            Get_Tournament_Events_Handler::class,
            fn( Simple_Container $c ): Get_Tournament_Events_Handler => new Get_Tournament_Events_Handler(
                $c->get( Tournament_Service_Interface::class )
            )
        );
        $container->alias( 'get_tournament_events_handler', Get_Tournament_Events_Handler::class );

        $container->set(
            Get_Tournament_Draws_Handler::class,
            fn( Simple_Container $c ): Get_Tournament_Draws_Handler => new Get_Tournament_Draws_Handler(
                $c->get( Tournament_Service_Interface::class )
            )
        );
        $container->alias( 'get_tournament_draws_handler', Get_Tournament_Draws_Handler::class );

        $container->set(
            Get_Tournament_Players_Handler::class,
            fn( Simple_Container $c ): Get_Tournament_Players_Handler => new Get_Tournament_Players_Handler(
                $c->get( Tournament_Service_Interface::class )
            )
        );
        $container->alias( 'get_tournament_players_handler', Get_Tournament_Players_Handler::class );

        $container->set(
            Get_Tournament_Winners_Handler::class,
            fn( Simple_Container $c ): Get_Tournament_Winners_Handler => new Get_Tournament_Winners_Handler(
                $c->get( Tournament_Service_Interface::class )
            )
        );
        $container->alias( 'get_tournament_winners_handler', Get_Tournament_Winners_Handler::class );

        $container->set(
            Get_Tournament_Matches_Handler::class,
            fn( Simple_Container $c ): Get_Tournament_Matches_Handler => new Get_Tournament_Matches_Handler(
                $c->get( Tournament_Service_Interface::class )
            )
        );
        $container->alias( 'get_tournament_matches_handler', Get_Tournament_Matches_Handler::class );

        $container->set(
            Get_Tournament_Match_Handler::class,
            fn( Simple_Container $c ): Get_Tournament_Match_Handler => new Get_Tournament_Match_Handler(
                $c->get( Tournament_Service_Interface::class ),
                $c->get( Fixture_Service_Interface::class )
            )
        );
        $container->alias( 'get_tournament_match_handler', Get_Tournament_Match_Handler::class );

        $container->set(
            Get_Order_Of_Play_Handler::class,
            fn( Simple_Container $c ): Get_Order_Of_Play_Handler => new Get_Order_Of_Play_Handler(
                tournament_service: $c->get( Tournament_Service_Interface::class ),
                competition_repository: $c->get( Competition_Repository_Interface::class )
            )
        );
        $container->alias( 'get_order_of_play_handler', Get_Order_Of_Play_Handler::class );

        $container->set(
            Get_Latest_Tournament_Handler::class,
            fn( Simple_Container $c ): Get_Latest_Tournament_Handler => new Get_Latest_Tournament_Handler(
                $c->get( Tournament_Service_Interface::class )
            )
        );
        $container->alias( 'get_latest_tournament_handler', Get_Latest_Tournament_Handler::class );

        $container->set(
            Get_Tournament_Withdrawal_Handler::class,
            fn( Simple_Container $c ): Get_Tournament_Withdrawal_Handler => new Get_Tournament_Withdrawal_Handler(
                $c->get( Tournament_Service_Interface::class )
            )
        );
        $container->alias( 'get_tournament_withdrawal_handler', Get_Tournament_Withdrawal_Handler::class );

        // Command Handlers.
        $container->set(
            Submit_Cup_Entry_Handler::class,
            fn( Simple_Container $c ): Submit_Cup_Entry_Handler => new Submit_Cup_Entry_Handler(
                $c->get( Competition_Entry_Service_Interface::class )
            )
        );
        $container->alias( 'submit_cup_entry_handler', Submit_Cup_Entry_Handler::class );

        $container->set(
            Submit_League_Entry_Handler::class,
            fn( Simple_Container $c ): Submit_League_Entry_Handler => new Submit_League_Entry_Handler(
                $c->get( Competition_Entry_Service_Interface::class )
            )
        );
        $container->alias( 'submit_league_entry_handler', Submit_League_Entry_Handler::class );

        $container->set(
            Submit_Tournament_Entry_Handler::class,
            fn( Simple_Container $c ): Submit_Tournament_Entry_Handler => new Submit_Tournament_Entry_Handler(
                $c->get( Competition_Entry_Service_Interface::class )
            )
        );
        $container->alias( 'submit_tournament_entry_handler', Submit_Tournament_Entry_Handler::class );

        $container->set(
            Withdraw_Tournament_Entry_Handler::class,
            fn( Simple_Container $c ): Withdraw_Tournament_Entry_Handler => new Withdraw_Tournament_Entry_Handler(
                $c->get( Competition_Entry_Service_Interface::class )
            )
        );
        $container->alias( 'withdraw_tournament_entry_handler', Withdraw_Tournament_Entry_Handler::class );

        $container->set(
            Submit_Tournament_Partner_Handler::class,
            fn( Simple_Container $c ): Submit_Tournament_Partner_Handler => new Submit_Tournament_Partner_Handler(
                $c->get( Competition_Entry_Service_Interface::class )
            )
        );
        $container->alias( 'submit_tournament_partner_handler', Submit_Tournament_Partner_Handler::class );

        $container->set(
            Create_Tournament_Payment_Handler::class,
            fn( Simple_Container $c ): Create_Tournament_Payment_Handler => new Create_Tournament_Payment_Handler(
                $c->get( Finance_Service_Interface::class )
            )
        );
        $container->alias( 'create_tournament_payment_handler', Create_Tournament_Payment_Handler::class );

        $container->set(
            Generate_Tournament_Bracket_Handler::class,
            fn( Simple_Container $c ): Generate_Tournament_Bracket_Handler => new Generate_Tournament_Bracket_Handler(
                $c->get( Championship_Admin_Service::class )
            )
        );
        $container->alias( 'generate_tournament_bracket_handler', Generate_Tournament_Bracket_Handler::class );

        // Shortcode Adapters (autowired via container class resolution).
        $container->alias( 'competition_shortcode_adapter', Competition_Shortcode_Adapter::class );
        $container->alias( 'league_shortcode_adapter', League_Shortcode_Adapter::class );
        $container->alias( 'event_shortcode_adapter', Event_Shortcode_Adapter::class );
        $container->alias( 'tournament_shortcode_adapter', Tournament_Shortcode_Adapter::class );

        // AJAX.
        $container->alias( 'tournament_ajax_adapter', Tournament_Ajax_Adapter::class );

        $container->set(
            Tournament_Ajax_Controller::class,
            fn( Simple_Container $c ): Tournament_Ajax_Controller => new Tournament_Ajax_Controller(
                $app,
                $c->get( Tournament_Ajax_Adapter::class )
            )
        );
        $container->alias( 'tournament_ajax_controller', Tournament_Ajax_Controller::class );
    }
}
