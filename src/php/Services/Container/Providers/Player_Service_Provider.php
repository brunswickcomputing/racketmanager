<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Container\Providers;

use Racketmanager\Application\General\Queries\Get_Player_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Search_Handler;
use Racketmanager\Application\General\Queries\Get_Players_Handler;
use Racketmanager\Application\Player\Commands\Create_Player_Handler;
use Racketmanager\Application\Player\Commands\Update_Player_Handler;
use Racketmanager\Application\Player\Queries\Get_Player_Details_Handler;
use Racketmanager\Application\Player\Queries\Search_Players_Handler;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Player_Error_Repository_Interface;
use Racketmanager\Repositories\Player_Error_Repository;
use Racketmanager\Repositories\Interfaces\Player_Statistics_Repository_Interface;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Repositories\Player_Statistics_Repository;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Validator\Player_Details_Validator;
use Racketmanager\Services\Validator\Player_Validation_Service;

/**
 * Service provider for Player bounded context.
 */
final readonly class Player_Service_Provider implements Service_Provider_Interface {
    public function __construct(
        private ?RacketManager $app = null
    ) {}

    public function register( Simple_Container $container ): void {
        $app = $this->app;

        // Repositories
        $container->set( 'player_repository', fn() => new Player_Repository() );

        $container->set( 'player_error_repository', fn(): Player_Error_Repository_Interface => new Player_Error_Repository() );

        $container->set( 'player_statistics_repository', fn(): Player_Statistics_Repository_Interface => new Player_Statistics_Repository() );

        // Services
        $container->set( 'player_query_service', fn( Simple_Container $c ) => new Player_Query_Service(
            $c->get( 'player_statistics_repository' ),
            $c->get( 'competition_repository' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'event_repository' ),
            $c->get( 'rubber_repository' ),
            fn() => $c->get( 'event_query_service' )
        ) );

        $container->set( 'player_stats_service', fn( Simple_Container $c ) => new Player_Stats_Service(
            $c->get( 'player_query_service' )
        ) );

        $container->set( 'player_details_validator', fn( Simple_Container $c ) => new Player_Details_Validator(
            $c->get( 'player_repository' )
        ) );

        $container->set( 'player_service', fn( Simple_Container $c ) => new Player_Service(
            $app,
            $c->get( 'player_repository' ),
            $c->get( 'player_error_repository' ),
            $c->get( 'club_role_repository' ),
            $c->get( 'wtn_api_client' ),
            $c->get( 'league_team_repository' ),
            $c->get( 'club_repository' ),
            $c->get( 'registration_repository' ),
            $c->get( 'mailer' ),
            $c->get( 'player_details_validator' )
        ) );

        $container->set( 'player_validation_service', fn( Simple_Container $c ) => new Player_Validation_Service(
            $c->get( 'registration_service' ),
            $c->get( 'results_checker_repository' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'player_repository' ),
            $c->get( 'player_details_validator' )
        ) );

        // Presenters
        $container->set( 'player_presenter', fn( Simple_Container $c ) => new Player_Presenter(
            $c->get( 'competition_shortcode_presenter' ),
            $c->get( 'option_gateway' )
        ) );

        // Query Handlers
        $container->set( 'get_players_handler', fn( Simple_Container $c ) => new Get_Players_Handler(
            $c->get( 'player_repository' ),
            $c->get( 'user_repository' ),
            $c->get( 'shortcode_template_renderer' ),
            $c->get( 'player_presenter' )
        ) );

        $container->set( 'get_player_handler', fn( Simple_Container $c ) => new Get_Player_Handler(
            $c->get( 'player_presenter' ),
            $c->get( 'player_query_service' ),
            $c->get( 'player_stats_service' ),
            $c->get( 'player_service' ),
            $c->get( 'registration_service' ),
            $c->get( 'tournament_service' )
        ) );

        $container->set( 'get_player_search_handler', fn( Simple_Container $c ) => new Get_Player_Search_Handler( $c->get( 'player_repository' ) ) );

        // Application Command Handlers
        $container->set( 'update_player_handler', fn( Simple_Container $c ) => new Update_Player_Handler( $c->get( 'player_service' ) ) );
        $container->set( 'create_player_handler', fn( Simple_Container $c ) => new Create_Player_Handler( $c->get( 'player_service' ) ) );

        // Application Query Handlers
        $container->set( 'get_player_details_handler', fn( Simple_Container $c ) => new Get_Player_Details_Handler(
            $c->get( 'player_repository' )
        ) );
        $container->set( 'search_players_handler', fn( Simple_Container $c ) => new Search_Players_Handler(
            player_search_handler: $c->get( 'get_player_search_handler' ),
            alert_handler: $c->get( 'get_alert_handler' ),
            view_presenter: $c->get( 'shortcode_view_presenter' )
        ) );
    }
}
