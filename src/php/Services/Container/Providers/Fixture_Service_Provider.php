<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Container\Providers;

use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Application\Club\Ports\Team_Service_Interface;
use Racketmanager\Application\Competition\Ports\Competition_Service_Interface;
use Racketmanager\Application\League\Ports\League_Service_Interface;
use Racketmanager\Application\Fixture\Listeners\Schedule_Fixture_Report_Listener;
use Racketmanager\Application\Fixture\Ports\Fixture_Detail_Service_Interface;
use Racketmanager\Application\Fixture\Ports\Fixture_Link_Service_Interface;
use Racketmanager\Application\Fixture\Ports\Fixture_Maintenance_Service_Interface;
use Racketmanager\Application\Fixture\Ports\Fixture_Permission_Service_Interface;
use Racketmanager\Application\Fixture\Ports\Fixture_Result_Manager_Interface;
use Racketmanager\Application\Fixture\Ports\Fixture_Service_Interface;
use Racketmanager\Application\Fixture\Ports\Result_Reporting_Service_Interface;
use Racketmanager\Application\Fixture\Ports\Results_Checker_Manager_Interface;
use Racketmanager\Application\Fixture\Queries\Get_Fixture_Details_Handler;
use Racketmanager\Application\Fixture\Services\Fixture_Details_Assembler;
use Racketmanager\Application\League\Ports\Standings_Service_Interface;
use Racketmanager\Application\Mail\Mailer_Interface;
use Racketmanager\Application\Matches\Ports\Fixture_Listing_Finder_Interface;
use Racketmanager\Application\Matches\Queries\Get_Daily_Matches_Handler;
use Racketmanager\Application\Matches\Queries\Get_Latest_Results_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Detail_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Header_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Score_Handler;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Application\Player\Ports\Registration_Service_Interface;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Application\Ports\Settings_Service_Interface;
use Racketmanager\Application\Ports\Template_Renderer_Interface;
use Racketmanager\Domain\Events\Domain_Event_Dispatcher_Interface;
use Racketmanager\Domain\Fixture\Events\Fixture_Result_Finalized_Event;
use Racketmanager\Infrastructure\Persistence\Legacy_Fixture_Listing_Finder;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Logging_Json_Response_Factory;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Fixture_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Fixture_Shortcode_Controller;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Matches_Shortcode_Adapter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Result_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Report_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Repositories\Result_Repository;
use Racketmanager\Repositories\Results_Checker_Repository;
use Racketmanager\Repositories\Results_Report_Repository;
use Racketmanager\Repositories\Rubber_Repository;
use Racketmanager\Services\Competition\Knockout_Progression_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Finalization_Service;
use Racketmanager\Services\Fixture\Fixture_Lifecycle_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Fixture\Lazy_Fixture_Team_Resolver;
use Racketmanager\Services\Fixture\Pending_Result_Orchestration_Service;
use Racketmanager\Services\Fixture\Pending_Result_Orchestrator_Interface;
use Racketmanager\Services\Fixture\Service_Provider as Internal_Fixture_Service_Provider;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Result\Result_Reporting_Service;
use Racketmanager\Services\Result\Results_Checker_Manager;
use Racketmanager\Services\Result\Rubber_Result_Manager;
use Racketmanager\Services\Result_Service;
use Racketmanager\Services\Validator\Player_Validation_Service;
use Racketmanager\Services\Validator\Score_Validation_Service;

/**
 * Service provider for Fixture, Match, Rubber, and Scoring bounded context.
 */
final readonly class Fixture_Service_Provider implements Service_Provider_Interface {
    /**
     * Constructor.
     *
     * @param RacketManager|null $app Application instance.
     */
    public function __construct(
        private ?RacketManager $app = null
    ) {}

    /**
     * Registers fixture services in the container.
     *
     * @param Simple_Container $container The service container.
     * @return void
     */
    public function register( Simple_Container $container ): void {
        $app = $this->app;

        // Repositories.
        $container->set(
            Lazy_Fixture_Team_Resolver::class,
            fn( Simple_Container $c ): Lazy_Fixture_Team_Resolver => new Lazy_Fixture_Team_Resolver(
                fn(): \Racketmanager\Domain\Fixture\Fixture_Team_Resolver_Interface => $c->get( Team_Service_Interface::class )
            )
        );
        $container->alias( 'fixture_team_resolver', Lazy_Fixture_Team_Resolver::class );

        $container->set(
            Fixture_Relation_Loader::class,
            fn( Simple_Container $c ): Fixture_Relation_Loader => new Fixture_Relation_Loader(
                $c->get( League_Repository_Interface::class ),
                $c->get( Lazy_Fixture_Team_Resolver::class )
            )
        );
        $container->alias( 'fixture_relation_loader', Fixture_Relation_Loader::class );

        $container->set(
            Fixture_Repository_Interface::class,
            fn( Simple_Container $c ): Fixture_Repository_Interface => new Fixture_Repository(
                relation_loader: $c->get( Fixture_Relation_Loader::class ),
                rubber_repository: $c->get( Rubber_Repository_Interface::class ),
                league_repository: $c->get( League_Repository_Interface::class ),
                team_repository: $c->get( Team_Repository_Interface::class )
            )
        );
        $container->alias( Fixture_Repository::class, Fixture_Repository_Interface::class );
        $container->alias( 'fixture_repository', Fixture_Repository_Interface::class );

        $container->set(
            Rubber_Repository_Interface::class,
            fn( Simple_Container $c ): Rubber_Repository_Interface => new Rubber_Repository(
                $c->get( Registration_Service_Interface::class )
            )
        );
        $container->alias( Rubber_Repository::class, Rubber_Repository_Interface::class );
        $container->alias( 'rubber_repository', Rubber_Repository_Interface::class );

        $container->set( Results_Checker_Repository_Interface::class, fn(): Results_Checker_Repository_Interface => new Results_Checker_Repository() );
        $container->alias( Results_Checker_Repository::class, Results_Checker_Repository_Interface::class );
        $container->alias( 'results_checker_repository', Results_Checker_Repository_Interface::class );

        $container->set( Results_Report_Repository_Interface::class, fn(): Results_Report_Repository_Interface => new Results_Report_Repository() );
        $container->alias( Results_Report_Repository::class, Results_Report_Repository_Interface::class );
        $container->alias( 'results_report_repository', Results_Report_Repository_Interface::class );

        $container->set(
            Result_Repository_Interface::class,
            fn( Simple_Container $c ): Result_Repository_Interface => new Result_Repository(
                $c->get( Results_Report_Repository_Interface::class )
            )
        );
        $container->alias( Result_Repository::class, Result_Repository_Interface::class );
        $container->alias( 'result_repository', Result_Repository_Interface::class );

        // Services.
        $container->set( Fixture_Link_Service_Interface::class, fn(): Fixture_Link_Service_Interface => new Fixture_Link_Service() );
        $container->alias( Fixture_Link_Service::class, Fixture_Link_Service_Interface::class );
        $container->alias( 'fixture_link_service', Fixture_Link_Service_Interface::class );

        $container->set(
            Fixture_Permission_Service_Interface::class,
            fn( Simple_Container $c ): Fixture_Permission_Service_Interface => new Fixture_Permission_Service(
                $c->get( Repository_Provider::class ),
                $c->get( Registration_Service_Interface::class ),
                $app?->get_options() ?? []
            )
        );
        $container->alias( Fixture_Permission_Service::class, Fixture_Permission_Service_Interface::class );
        $container->alias( 'fixture_permission_service', Fixture_Permission_Service_Interface::class );

        $container->set(
            Fixture_Detail_Service_Interface::class,
            fn( Simple_Container $c ): Fixture_Detail_Service_Interface => new Fixture_Detail_Service(
                $c->get( Repository_Provider::class ),
                $c->get( Competition_Service_Interface::class ),
                $c->get( Team_Service_Interface::class ),
                $c->get( Fixture_Permission_Service_Interface::class ),
                $c->get( Fixture_Link_Service_Interface::class )
            )
        );
        $container->alias( Fixture_Detail_Service::class, Fixture_Detail_Service_Interface::class );
        $container->alias( 'fixture_detail_service', Fixture_Detail_Service_Interface::class );

        $container->set(
            Result_Reporting_Service_Interface::class,
            fn( Simple_Container $c ): Result_Reporting_Service_Interface => new Result_Reporting_Service(
                $c->get( Repository_Provider::class ),
                $c->get( Option_Gateway_Interface::class )
            )
        );
        $container->alias( Result_Reporting_Service::class, Result_Reporting_Service_Interface::class );
        $container->alias( 'result_reporting_service', Result_Reporting_Service_Interface::class );

        $container->set(
            Schedule_Fixture_Report_Listener::class,
            fn( Simple_Container $c ): Schedule_Fixture_Report_Listener => new Schedule_Fixture_Report_Listener(
                $c->get( Result_Reporting_Service_Interface::class )
            )
        );
        $container->alias( 'schedule_fixture_report_listener', Schedule_Fixture_Report_Listener::class );

        if ( $container->has( Domain_Event_Dispatcher_Interface::class ) ) {
            /** @var Domain_Event_Dispatcher_Interface $dispatcher */
            $dispatcher = $container->get( Domain_Event_Dispatcher_Interface::class );
            $dispatcher->subscribe(
                Fixture_Result_Finalized_Event::class,
                function ( Fixture_Result_Finalized_Event $event ) use ( $container ): void {
                    /** @var Schedule_Fixture_Report_Listener $listener */
                    $listener = $container->get( Schedule_Fixture_Report_Listener::class );
                    $listener( $event );
                }
            );
        }

        $container->set(
            Fixture_Finalization_Service::class,
            fn( Simple_Container $c ): Fixture_Finalization_Service => new Fixture_Finalization_Service(
                $c->get( Standings_Service_Interface::class ),
                $c->get( Knockout_Progression_Service::class ),
                $c->get( Result_Reporting_Service_Interface::class ),
                $c->get( Domain_Event_Dispatcher_Interface::class )
            )
        );
        $container->alias( 'fixture_finalization_service', Fixture_Finalization_Service::class );

        $container->set(
            Rubber_Result_Manager::class,
            fn( Simple_Container $c ): Rubber_Result_Manager => new Rubber_Result_Manager(
                $c->get( Score_Validation_Service::class ),
                $c->get( League_Service_Interface::class ),
                $c->get( Rubber_Repository_Interface::class ),
                $c->get( Player_Validation_Service::class )
            )
        );
        $container->alias( 'rubber_result_manager', Rubber_Result_Manager::class );

        $container->set(
            Internal_Fixture_Service_Provider::class,
            function ( Simple_Container $c ): Internal_Fixture_Service_Provider {
                $service_provider = new Internal_Fixture_Service_Provider(
                    $c->get( Result_Service::class ),
                    $c->get( Knockout_Progression_Service::class ),
                    $c->get( League_Service_Interface::class ),
                    $c->get( Score_Validation_Service::class ),
                    $c->get( Player_Validation_Service::class ),
                    $c->get( Notification_Service::class ),
                    $c->get( Registration_Service_Interface::class )
                );
                $service_provider->set_team_service( $c->get( Team_Service_Interface::class ) );
                $service_provider->set_competition_service( $c->get( Competition_Service_Interface::class ) );
                $service_provider->set_fixture_permission_service( $c->get( Fixture_Permission_Service_Interface::class ) );
                $service_provider->set_fixture_detail_service( $c->get( Fixture_Detail_Service_Interface::class ) );
                $service_provider->set_fixture_finalization_service( $c->get( Fixture_Finalization_Service::class ) );
                $service_provider->set_result_reporting_service( $c->get( Result_Reporting_Service_Interface::class ) );
                $service_provider->set_settings_service( $c->get( Settings_Service_Interface::class ) );
                $service_provider->set_rubber_manager( $c->get( Rubber_Result_Manager::class ) );

                return $service_provider;
            }
        );
        $container->alias( 'fixture_service_provider', Internal_Fixture_Service_Provider::class );

        $container->set(
            Fixture_Service_Interface::class,
            fn( Simple_Container $c ): Fixture_Service_Interface => new Fixture_Service(
                $c->get( Repository_Provider::class ),
                $c->get( Internal_Fixture_Service_Provider::class ),
                $c->get( Fixture_Permission_Service_Interface::class ),
                $c->get( Fixture_Detail_Service_Interface::class )
            )
        );
        $container->alias( Fixture_Service::class, Fixture_Service_Interface::class );
        $container->alias( 'fixture_service', Fixture_Service_Interface::class );

        $container->set(
            Fixture_Result_Manager_Interface::class,
            function ( Simple_Container $c ): Fixture_Result_Manager_Interface {
                $service_provider = $c->get( Internal_Fixture_Service_Provider::class );

                $result_manager      = new Fixture_Result_Manager( $service_provider, $c->get( Repository_Provider::class ), $c->get( Standings_Service_Interface::class ) );
                $maintenance_service = new Fixture_Maintenance_Service( $service_provider, $c->get( Repository_Provider::class ), $result_manager );
                $result_manager->set_fixture_maintenance_service( $maintenance_service );
                $service_provider->set_fixture_maintenance_service( $maintenance_service );

                return $result_manager;
            }
        );
        $container->alias( Fixture_Result_Manager::class, Fixture_Result_Manager_Interface::class );
        $container->alias( 'fixture_result_manager', Fixture_Result_Manager_Interface::class );

        $container->set(
            Fixture_Maintenance_Service_Interface::class,
            function ( Simple_Container $c ): Fixture_Maintenance_Service_Interface {
                /** @var Fixture_Result_Manager $result_manager */
                $result_manager = $c->get( Fixture_Result_Manager_Interface::class );

                return $result_manager->get_fixture_maintenance_service();
            }
        );
        $container->alias( Fixture_Maintenance_Service::class, Fixture_Maintenance_Service_Interface::class );
        $container->alias( 'fixture_maintenance_service', Fixture_Maintenance_Service_Interface::class );

        $container->set(
            Fixture_Lifecycle_Service::class,
            fn( Simple_Container $c ): Fixture_Lifecycle_Service => new Fixture_Lifecycle_Service(
                $c->get( Repository_Provider::class ),
                $c->get( Fixture_Maintenance_Service_Interface::class ),
                $c->get( Notification_Service::class )
            )
        );
        $container->alias( 'fixture_lifecycle_service', Fixture_Lifecycle_Service::class );

        $container->set(
            Pending_Result_Orchestrator_Interface::class,
            fn( Simple_Container $c ): Pending_Result_Orchestrator_Interface => new Pending_Result_Orchestration_Service(
                $c->get( Option_Service::class ),
                $c->get( Fixture_Repository_Interface::class ),
                $c->get( Fixture_Maintenance_Service_Interface::class )
            )
        );
        $container->alias( Pending_Result_Orchestration_Service::class, Pending_Result_Orchestrator_Interface::class );
        $container->alias( 'pending_result_orchestration_service', Pending_Result_Orchestrator_Interface::class );

        $container->set(
            Fixture_Details_Assembler::class,
            fn( Simple_Container $c ): Fixture_Details_Assembler => new Fixture_Details_Assembler(
                $c->get( League_Repository_Interface::class ),
                $c->get( Event_Repository_Interface::class ),
                $c->get( Competition_Repository_Interface::class ),
                $c->get( Team_Repository_Interface::class ),
                $c->get( Club_Repository_Interface::class ),
                $c->get( Rubber_Repository_Interface::class ),
                $c->get( League_Team_Repository_Interface::class ),
                $c->get( Player_Repository_Interface::class ),
                $c->get( Fixture_Link_Service_Interface::class ),
                $c->get( Fixture_Permission_Service_Interface::class ),
                $c->get( Club_Role_Repository_Interface::class )
            )
        );
        $container->alias( 'fixture_details_assembler', Fixture_Details_Assembler::class );
        // Deprecated 10.1.0: former keys kept for backward compatibility; removed in v11.0.
        $container->alias( 'application_fixture_detail_service', Fixture_Details_Assembler::class );
        $container->alias( 'domain_fixture_detail_service', Fixture_Details_Assembler::class );

        $container->set(
            Result_Service::class,
            fn( Simple_Container $c ): Result_Service => new Result_Service(
                $c->get( Fixture_Repository_Interface::class ),
                $c->get( Team_Repository_Interface::class ),
                $c->get( Option_Service::class ),
                $c->get( Template_Renderer_Interface::class ),
                $c->get( Mailer_Interface::class ),
                $c->get( League_Repository_Interface::class )
            )
        );
        $container->alias( 'result_service', Result_Service::class );

        $container->set( Score_Validation_Service::class, fn(): Score_Validation_Service => new Score_Validation_Service() );
        $container->alias( 'score_validation_service', Score_Validation_Service::class );

        $container->set(
            Results_Checker_Manager_Interface::class,
            function ( Simple_Container $c ): Results_Checker_Manager_Interface {
                $manager = new Results_Checker_Manager(
                    $c->get( Results_Checker_Repository_Interface::class ),
                    $c->get( Fixture_Result_Manager_Interface::class ),
                    $c->get( League_Repository_Interface::class ),
                    $c->get( Fixture_Repository_Interface::class )
                );
                $manager->set_notification_service( $c->get( Notification_Service::class ) );

                return $manager;
            }
        );
        $container->alias( Results_Checker_Manager::class, Results_Checker_Manager_Interface::class );
        $container->alias( 'results_checker_manager', Results_Checker_Manager_Interface::class );

        // Finder.
        $container->set(
            Fixture_Listing_Finder_Interface::class,
            fn( Simple_Container $c ): Fixture_Listing_Finder_Interface => new Legacy_Fixture_Listing_Finder(
                $c->get( Fixture_Repository_Interface::class )
            )
        );
        $container->alias( Legacy_Fixture_Listing_Finder::class, Fixture_Listing_Finder_Interface::class );
        $container->alias( 'fixture_listing_finder', Fixture_Listing_Finder_Interface::class );

        // Query Handlers.
        $container->set(
            Get_Fixture_Details_Handler::class,
            fn( Simple_Container $c ): Get_Fixture_Details_Handler => new Get_Fixture_Details_Handler(
                $c->get( Fixture_Repository_Interface::class ),
                $c->get( Fixture_Details_Assembler::class )
            )
        );
        $container->alias( 'get_fixture_details_handler', Get_Fixture_Details_Handler::class );

        $container->set(
            Get_Daily_Matches_Handler::class,
            fn( Simple_Container $c ): Get_Daily_Matches_Handler => new Get_Daily_Matches_Handler(
                $c->get( Fixture_Listing_Finder_Interface::class )
            )
        );
        $container->alias( 'get_daily_matches_handler', Get_Daily_Matches_Handler::class );

        $container->set(
            Get_Latest_Results_Handler::class,
            fn( Simple_Container $c ): Get_Latest_Results_Handler => new Get_Latest_Results_Handler(
                $c->get( Fixture_Listing_Finder_Interface::class ),
                $c->get( Club_Repository_Interface::class ),
                $c->get( Competition_Repository_Interface::class )
            )
        );
        $container->alias( 'get_latest_results_handler', Get_Latest_Results_Handler::class );

        $container->set(
            Get_Match_Score_Handler::class,
            fn( Simple_Container $c ): Get_Match_Score_Handler => new Get_Match_Score_Handler(
                $c->get( Fixture_Detail_Service_Interface::class )
            )
        );
        $container->alias( 'get_match_score_handler', Get_Match_Score_Handler::class );

        $container->set(
            Get_Match_Header_Handler::class,
            fn( Simple_Container $c ): Get_Match_Header_Handler => new Get_Match_Header_Handler(
                $c->get( Fixture_Detail_Service_Interface::class )
            )
        );
        $container->alias( 'get_match_header_handler', Get_Match_Header_Handler::class );

        $container->set(
            Get_Match_Detail_Handler::class,
            fn( Simple_Container $c ): Get_Match_Detail_Handler => new Get_Match_Detail_Handler(
                $c->get( Fixture_Detail_Service_Interface::class ),
                $c->get( Player_Service_Interface::class )
            )
        );
        $container->alias( 'get_match_detail_handler', Get_Match_Detail_Handler::class );

        // Shortcode Adapters & Controllers.
        $container->set( Fixture_Shortcode_Controller::class, fn(): Fixture_Shortcode_Controller => new Fixture_Shortcode_Controller( $app ) );
        $container->alias( 'fixture_shortcode_controller', Fixture_Shortcode_Controller::class );

        $container->alias( 'fixture_shortcode_adapter', Fixture_Shortcode_Adapter::class );
        $container->alias( 'matches_shortcode_adapter', Matches_Shortcode_Adapter::class );

        // AJAX Adapter & Controller.
        $container->alias( 'fixture_ajax_adapter', Fixture_Ajax_Adapter::class );

        $container->set(
            Fixture_Ajax_Controller::class,
            fn( Simple_Container $c ): Fixture_Ajax_Controller => new Fixture_Ajax_Controller(
                $app,
                $c->get( Fixture_Ajax_Adapter::class )
            )
        );
        $container->alias( 'fixture_ajax_controller', Fixture_Ajax_Controller::class );
    }
}
