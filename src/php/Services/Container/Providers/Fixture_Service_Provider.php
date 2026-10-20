<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Container\Providers;

use Racketmanager\Application\Fixture\Queries\Get_Fixture_Details_Handler;
use Racketmanager\Application\Matches\Queries\Get_Daily_Matches_Handler;
use Racketmanager\Application\Matches\Queries\Get_Latest_Results_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Detail_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Header_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Score_Handler;
use Racketmanager\Domain\Fixture\Services\Fixture_Detail_Service as Domain_Fixture_Detail_Service;
use Racketmanager\Infrastructure\Persistence\Legacy_Fixture_Listing_Finder;
use Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Response\Logging_Json_Response_Factory;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Fixture_Shortcode_Adapter;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Fixture_Shortcode_Controller;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Matches_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\Presentation\Presenters\Match_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Matches_List_Presenter;
use Racketmanager\Presentation\Presenters\Results_Checker_Presenter;
use Racketmanager\Presentation\Presenters\Team_Match_Row_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Interfaces\Result_Repository_Interface;
use Racketmanager\Repositories\Result_Repository;
use Racketmanager\Repositories\Results_Checker_Repository;
use Racketmanager\Repositories\Results_Report_Repository;
use Racketmanager\Repositories\Rubber_Repository;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Finalization_Service;
use Racketmanager\Services\Fixture\Fixture_Lifecycle_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Racketmanager\Services\Fixture\Lazy_Fixture_Team_Resolver;
use Racketmanager\Services\Fixture\Service_Provider as Internal_Fixture_Service_Provider;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Result\Result_Reporting_Service;
use Racketmanager\Services\Result\Results_Checker_Manager;
use Racketmanager\Services\Result_Service;
use Racketmanager\Services\Result\Rubber_Result_Manager;
use Racketmanager\Services\Validator\Score_Validation_Service;

/**
 * Service provider for Fixture, Match, Rubber, and Scoring bounded context.
 */
final readonly class Fixture_Service_Provider implements Service_Provider_Interface {
    public function __construct(
        private ?RacketManager $app = null
    ) {}

    public function register( Simple_Container $container ): void {
        $app = $this->app;

        // Repositories
        $container->set( 'fixture_team_resolver', fn( Simple_Container $c ) => new Lazy_Fixture_Team_Resolver(
                fn(): \Racketmanager\Domain\Fixture\Fixture_Team_Resolver_Interface => $c->get( 'team_service' )
        ) );
        $container->set( 'fixture_relation_loader', fn( Simple_Container $c ) => new Fixture_Relation_Loader(
            $c->get( 'league_repository' ),
            $c->get( 'fixture_team_resolver' )
        ) );
        $container->set( 'fixture_repository', fn( Simple_Container $c ) => new Fixture_Repository(
            relation_loader: $c->get( 'fixture_relation_loader' ),
            rubber_repository: $c->get( 'rubber_repository' )
        ) );

        $container->set( 'rubber_repository', fn( Simple_Container $c ) => new Rubber_Repository( $c->get( 'registration_service' ) ) );
        $container->set( 'results_checker_repository', fn() => new Results_Checker_Repository() );
        $container->set( 'results_report_repository', fn() => new Results_Report_Repository() );
        $container->set( 'result_repository', fn(): Result_Repository_Interface => new Result_Repository() );

        // Services
        $container->set( 'fixture_link_service', fn() => new Fixture_Link_Service() );

        $container->set( 'fixture_permission_service', fn( Simple_Container $c ) => new Fixture_Permission_Service(
            $c->get( 'repository_provider' ),
            $c->get( 'registration_service' ),
            $app?->get_options() ?? []
        ) );

        $container->set( 'fixture_detail_service', fn( Simple_Container $c ) => new Fixture_Detail_Service(
            $c->get( 'repository_provider' ),
            $c->get( 'competition_service' ),
            $c->get( 'team_service' ),
            $c->get( 'fixture_permission_service' ),
            $c->get( 'fixture_link_service' )
        ) );

        $container->set( 'fixture_finalization_service', fn( Simple_Container $c ) => new Fixture_Finalization_Service(
            $c->get( 'standings_service' ),
            $c->get( 'knockout_progression_service' ),
            $c->get( 'result_reporting_service' )
        ) );

        $container->set( 'rubber_result_manager', fn( Simple_Container $c ) => new Rubber_Result_Manager(
            $c->get( 'score_validation_service' ),
            $c->get( 'league_service' ),
            $c->get( 'rubber_repository' ),
            $c->get( 'player_validation_service' )
        ) );

        $container->set( 'fixture_service_provider', function ( Simple_Container $c ) {
            $service_provider = new Internal_Fixture_Service_Provider(
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
            $service_provider->set_result_reporting_service( $c->get( 'result_reporting_service' ) );
            $service_provider->set_settings_service( $c->get( 'settings_service' ) );
            $service_provider->set_rubber_manager( $c->get( 'rubber_result_manager' ) );

            return $service_provider;
        } );

        $container->set( 'fixture_service', fn( Simple_Container $c ) => new Fixture_Service(
            $c->get( 'repository_provider' ),
            $c->get( 'fixture_service_provider' ),
            $c->get( 'fixture_permission_service' ),
            $c->get( 'fixture_detail_service' )
        ) );

        $container->set( 'fixture_result_manager', function ( Simple_Container $c ) {
            $service_provider = $c->get( 'fixture_service_provider' );

            $result_manager      = new Fixture_Result_Manager( $service_provider, $c->get( 'repository_provider' ) );
            $maintenance_service = new Fixture_Maintenance_Service( $service_provider, $c->get( 'repository_provider' ), $result_manager );
            $result_manager->set_fixture_maintenance_service( $maintenance_service );
            $service_provider->set_fixture_maintenance_service( $maintenance_service );

            return $result_manager;
        } );

        $container->set( 'fixture_maintenance_service', function ( Simple_Container $c ) {
            $result_manager = $c->get( 'fixture_result_manager' );

            return $result_manager->get_fixture_maintenance_service();
        } );

        $container->set( 'fixture_lifecycle_service', fn( Simple_Container $c ) => new Fixture_Lifecycle_Service(
            $c->get( 'repository_provider' ),
            $c->get( 'fixture_maintenance_service' ),
            $c->get( 'notification_service' )
        ) );

        $container->set( 'domain_fixture_detail_service', fn( Simple_Container $c ) => new Domain_Fixture_Detail_Service(
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
        ) );

        $container->set( 'result_reporting_service', fn( Simple_Container $c ) => new Result_Reporting_Service(
            $c->get( 'repository_provider' ),
            $c->get( 'option_gateway' )
        ) );

        $container->set( 'result_service', fn( Simple_Container $c ) => new Result_Service(
            $c->get( 'fixture_repository' ),
            $c->get( 'team_repository' ),
            $c->get( 'mailer' )
        ) );

        $container->set( 'score_validation_service', fn() => new Score_Validation_Service() );

        $container->set( 'results_checker_manager', function ( Simple_Container $c ) {
            $manager = new Results_Checker_Manager(
                $c->get( 'results_checker_repository' ),
                $c->get( 'fixture_result_manager' ),
                $c->get( 'league_repository' ),
                $c->get( 'fixture_repository' )
            );
            $manager->set_notification_service( $c->get( 'notification_service' ) );

            return $manager;
        } );

        // Presenters
        $container->set( 'team_match_row_presenter', fn( Simple_Container $c ) => new Team_Match_Row_Presenter(
            $c->get( 'option_gateway' ),
            $c->get( 'fixture_relation_loader' ),
            $c->get( 'fixture_permission_service' ),
            $c->get( 'fixture_link_service' ),
            $c->get( 'club_repository' ),
            $c->get( 'league_repository' )
        ) );

        $container->set( 'fixture_presenter', fn( Simple_Container $c ) => new Fixture_Presenter(
            $c->get( 'fixture_link_service' ),
            $c->get( 'option_gateway' ),
            $c->get( 'team_match_row_presenter' )
        ) );

        $container->set( 'results_checker_presenter', fn( Simple_Container $c ) => new Results_Checker_Presenter(
            $c->get( 'fixture_repository' ),
            $c->get( 'player_repository' ),
            $c->get( 'team_repository' ),
            $c->get( 'league_repository' ),
            $c->get( 'fixture_detail_service' )
        ) );

        $container->set( 'matches_list_presenter', fn( Simple_Container $c ) => new Matches_List_Presenter(
            $c->get( 'competition_service' ),
            $c->get( 'team_service' ),
            $c->get( 'view_renderer' ),
            $c->get( 'tournament_presenter' ),
            $c->get( 'fixture_link_service' ),
            $c->get( 'fixture_permission_service' ),
            $c->get( 'club_presenter' ),
            $c->get( 'player_presenter' ),
            $c->get( 'rubber_repository' )
        ) );

        $container->set( 'match_shortcode_presenter', fn( Simple_Container $c ) => new Match_Shortcode_Presenter(
            $c->get( 'shortcode_view_presenter' ),
            $c->get( 'fixture_presenter' ),
            $c->get( 'player_service' )
        ) );

        // Finder
        $container->set( 'fixture_listing_finder', fn() => new Legacy_Fixture_Listing_Finder( $app ) );

        // Query Handlers
        $container->set( 'get_fixture_details_handler', fn( Simple_Container $c ) => new Get_Fixture_Details_Handler(
            $c->get( 'fixture_repository' ),
            $c->get( 'domain_fixture_detail_service' )
        ) );

        $container->set( 'get_daily_matches_handler', fn( Simple_Container $c ) => new Get_Daily_Matches_Handler(
            $c->get( 'fixture_listing_finder' )
        ) );

        $container->set( 'get_latest_results_handler', fn( Simple_Container $c ) => new Get_Latest_Results_Handler(
            $c->get( 'fixture_listing_finder' ),
            $c->get( 'club_repository' ),
            $c->get( 'competition_repository' )
        ) );

        $container->set( 'get_match_score_handler', fn( Simple_Container $c ) => new Get_Match_Score_Handler(
            $c->get( 'fixture_detail_service' )
        ) );

        $container->set( 'get_match_header_handler', fn( Simple_Container $c ) => new Get_Match_Header_Handler(
            $c->get( 'fixture_detail_service' )
        ) );

        $container->set( 'get_match_detail_handler', fn( Simple_Container $c ) => new Get_Match_Detail_Handler(
            $c->get( 'fixture_detail_service' ),
            $c->get( 'player_service' )
        ) );

        // Shortcode Adapters & Controllers
        $container->set( 'fixture_shortcode_controller', fn() => new Fixture_Shortcode_Controller( $app ) );

        $container->set( 'fixture_shortcode_adapter', fn( Simple_Container $c ) => new Fixture_Shortcode_Adapter(
            $c->get( 'get_fixture_details_handler' ),
            $c->get( 'fixture_presenter' )
        ) );

        $container->set( 'matches_shortcode_adapter', fn( Simple_Container $c ) => new Matches_Shortcode_Adapter(
            daily_matches_handler: $c->get( 'get_daily_matches_handler' ),
            latest_results_handler: $c->get( 'get_latest_results_handler' ),
            presenter: $c->get( 'matches_list_presenter' ),
            score_handler: $c->get( 'get_match_score_handler' ),
            header_handler: $c->get( 'get_match_header_handler' ),
            detail_handler: $c->get( 'get_match_detail_handler' ),
            match_presenter: $c->get( 'match_shortcode_presenter' )
        ) );

        // AJAX Adapter & Controller
        $container->set( 'fixture_ajax_adapter', fn( Simple_Container $c ) => new Fixture_Ajax_Adapter(
            $c,
            $c->get( 'security_service' ),
            new Logging_Json_Response_Factory( $c->get( 'json_response_factory' ) ),
            $c->get( 'fixture_detail_service' ),
            $c->get( 'view_renderer' ),
            $c->get( 'fixture_presenter' ),
            $c->get( 'get_fixture_details_handler' )
        ) );
        $container->set( 'fixture_ajax_controller', fn( Simple_Container $c ) => new Fixture_Ajax_Controller( $app, $c->get( 'fixture_ajax_adapter' ) ) );
    }
}
