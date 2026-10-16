<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Container\Providers;

use Racketmanager\Application\General\Queries\Get_Player_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Search_Handler;
use Racketmanager\Application\General\Queries\Get_Players_Handler;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Player_Error_Repository_Interface;
use Racketmanager\Repositories\Player_Error_Repository;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Validator\Player_Validation_Service;

use RuntimeException;
use wpdb;

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
        $container->set( 'player_repository', fn( Simple_Container $c ) => new Player_Repository(
            player_query_service: fn() => $c->get( 'player_query_service' ),
            player_stats_service: fn() => $c->get( 'player_stats_service' )
        ) );

        $container->set( 'player_error_repository', fn(): Player_Error_Repository_Interface => new Player_Error_Repository() );

        // Services
        $container->set( 'player_query_service', function ( Simple_Container $c ) {
            global $wpdb;
            if ( ! $wpdb instanceof wpdb ) {
                throw new RuntimeException( 'Player_Query_Service requires a wpdb instance.' );
            }

            return new Player_Query_Service( $wpdb, $c->get( 'competition_repository' ) );
        } );

        $container->set( 'player_stats_service', fn( Simple_Container $c ) => new Player_Stats_Service(
            $c->get( 'player_query_service' )
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
            $c->get( 'mailer' )
        ) );

        $container->set( 'player_validation_service', fn( Simple_Container $c ) => new Player_Validation_Service(
            $c->get( 'registration_service' ),
            $c->get( 'results_checker_repository' ),
            $c->get( 'fixture_repository' )
        ) );

        // Presenters
        $container->set( 'player_presenter', fn() => new Player_Presenter() );

        // Query Handlers
        $container->set( 'get_players_handler', fn( Simple_Container $c ) => new Get_Players_Handler(
            $c->get( 'player_presenter' )
        ) );

        $container->set( 'get_player_handler', fn( Simple_Container $c ) => new Get_Player_Handler(
            $c->get( 'registration_service' ),
            $c->get( 'player_service' ),
            $c->get( 'tournament_service' ),
            $c->get( 'player_presenter' )
        ) );

        $container->set( 'get_player_search_handler', fn() => new Get_Player_Search_Handler( $app ) );
    }
}
