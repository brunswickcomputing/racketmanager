<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Container\Providers;

use Racketmanager\Application\Competition\Ports\Event_Query_Service_Interface;
use Racketmanager\Application\League\Ports\League_Query_Service_Interface;
use Racketmanager\Application\General\Queries\Get_Alert_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Search_Handler;
use Racketmanager\Application\General\Queries\Get_Players_Handler;
use Racketmanager\Application\Mail\Mailer_Interface;
use Racketmanager\Application\Player\Commands\Create_Player_Handler;
use Racketmanager\Application\Player\Commands\Update_Player_Handler;
use Racketmanager\Application\Player\Ports\Player_Query_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Stats_Service_Interface;
use Racketmanager\Application\Player\Ports\Registration_Service_Interface;
use Racketmanager\Application\Player\Queries\Get_Player_Details_Handler;
use Racketmanager\Application\Player\Queries\Search_Players_Handler;
use Racketmanager\Application\Tournament\Ports\Tournament_Service_Interface;
use Racketmanager\Domain\Ports\Rating_Gateway_Interface;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Error_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Statistics_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Repositories\Player_Error_Repository;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Repositories\Player_Statistics_Repository;
use Racketmanager\Repositories\Interfaces\Registration_Repository_Interface;
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
    /**
     * Initialises the instance with an optional racket manager.
     *
     * @param RacketManager|null $app Optional racket manager instance.
     *
     * @return void
     */
    public function __construct(
        private ?RacketManager $app = null
    ) {}

    /**
     * Registers player-related repositories, services, validators, and handlers in the container.
     *
     * @param Simple_Container $container Container used to define services and aliases.
     *
     * @return void
     */
    public function register( Simple_Container $container ): void {
        $app = $this->app;

        // Repositories.
        $container->set( Player_Repository_Interface::class, fn(): Player_Repository_Interface => new Player_Repository() );
        $container->alias( Player_Repository::class, Player_Repository_Interface::class );
        $container->alias( 'player_repository', Player_Repository_Interface::class );

        $container->set( Player_Error_Repository_Interface::class, fn(): Player_Error_Repository_Interface => new Player_Error_Repository() );
        $container->alias( Player_Error_Repository::class, Player_Error_Repository_Interface::class );
        $container->alias( 'player_error_repository', Player_Error_Repository_Interface::class );

        $container->set( Player_Statistics_Repository_Interface::class, fn(): Player_Statistics_Repository_Interface => new Player_Statistics_Repository() );
        $container->alias( Player_Statistics_Repository::class, Player_Statistics_Repository_Interface::class );
        $container->alias( 'player_statistics_repository', Player_Statistics_Repository_Interface::class );

        // Services.
        $container->set(
            Player_Query_Service_Interface::class,
            fn( Simple_Container $c ): Player_Query_Service_Interface => new Player_Query_Service(
                $c->get( Player_Statistics_Repository_Interface::class ),
                $c->get( Competition_Repository_Interface::class ),
                $c->get( Fixture_Repository_Interface::class ),
                $c->get( Event_Repository_Interface::class ),
                $c->get( Rubber_Repository_Interface::class ),
                fn() => $c->get( Event_Query_Service_Interface::class ),
                fn() => $c->get( League_Query_Service_Interface::class )
            )
        );
        $container->alias( Player_Query_Service::class, Player_Query_Service_Interface::class );
        $container->alias( 'player_query_service', Player_Query_Service_Interface::class );

        $container->set(
            Player_Stats_Service_Interface::class,
            fn( Simple_Container $c ): Player_Stats_Service_Interface => new Player_Stats_Service(
                $c->get( Player_Query_Service_Interface::class )
            )
        );
        $container->alias( Player_Stats_Service::class, Player_Stats_Service_Interface::class );
        $container->alias( 'player_stats_service', Player_Stats_Service_Interface::class );

        $container->set(
            Player_Details_Validator::class,
            fn( Simple_Container $c ): Player_Details_Validator => new Player_Details_Validator(
                $c->get( Player_Repository_Interface::class )
            )
        );
        $container->alias( 'player_details_validator', Player_Details_Validator::class );

        $container->set(
            Player_Service_Interface::class,
            fn( Simple_Container $c ): Player_Service_Interface => new Player_Service(
                $app,
                $c->get( Player_Repository_Interface::class ),
                $c->get( Player_Error_Repository_Interface::class ),
                $c->get( Club_Role_Repository_Interface::class ),
                $c->get( Rating_Gateway_Interface::class ),
                $c->get( League_Team_Repository_Interface::class ),
                $c->get( Club_Repository_Interface::class ),
                $c->get( Registration_Repository_Interface::class ),
                $c->get( Mailer_Interface::class ),
                $c->get( Player_Details_Validator::class )
            )
        );
        $container->alias( Player_Service::class, Player_Service_Interface::class );
        $container->alias( 'player_service', Player_Service_Interface::class );

        $container->set(
            Player_Validation_Service::class,
            fn( Simple_Container $c ): Player_Validation_Service => new Player_Validation_Service(
                $c->get( Registration_Service_Interface::class ),
                $c->get( Results_Checker_Repository_Interface::class ),
                $c->get( Fixture_Repository_Interface::class ),
                $c->get( Player_Repository_Interface::class ),
                $c->get( Player_Details_Validator::class )
            )
        );
        $container->alias( 'player_validation_service', Player_Validation_Service::class );

        // Query Handlers.
        $container->set(
            Get_Players_Handler::class,
            fn( Simple_Container $c ): Get_Players_Handler => new Get_Players_Handler(
                $c->get( Player_Repository_Interface::class ),
                $c->get( User_Repository_Interface::class )
            )
        );
        $container->alias( 'get_players_handler', Get_Players_Handler::class );

        $container->set(
            Get_Player_Handler::class,
            fn( Simple_Container $c ): Get_Player_Handler => new Get_Player_Handler(
                $c->get( Player_Query_Service_Interface::class ),
                $c->get( Player_Stats_Service_Interface::class ),
                $c->get( Player_Service_Interface::class ),
                $c->get( Registration_Service_Interface::class ),
                $c->get( Tournament_Service_Interface::class )
            )
        );
        $container->alias( 'get_player_handler', Get_Player_Handler::class );

        $container->set(
            Get_Player_Search_Handler::class,
            fn( Simple_Container $c ): Get_Player_Search_Handler => new Get_Player_Search_Handler(
                $c->get( Player_Repository_Interface::class )
            )
        );
        $container->alias( 'get_player_search_handler', Get_Player_Search_Handler::class );

        // Application Command Handlers.
        $container->set(
            Update_Player_Handler::class,
            fn( Simple_Container $c ): Update_Player_Handler => new Update_Player_Handler(
                $c->get( Player_Service_Interface::class )
            )
        );
        $container->alias( 'update_player_handler', Update_Player_Handler::class );

        $container->set(
            Create_Player_Handler::class,
            fn( Simple_Container $c ): Create_Player_Handler => new Create_Player_Handler(
                $c->get( Player_Service_Interface::class )
            )
        );
        $container->alias( 'create_player_handler', Create_Player_Handler::class );

        // Application Query Handlers.
        $container->set(
            Get_Player_Details_Handler::class,
            fn( Simple_Container $c ): Get_Player_Details_Handler => new Get_Player_Details_Handler(
                $c->get( Player_Repository_Interface::class )
            )
        );
        $container->alias( 'get_player_details_handler', Get_Player_Details_Handler::class );

        $container->set(
            Search_Players_Handler::class,
            fn( Simple_Container $c ): Search_Players_Handler => new Search_Players_Handler(
                player_search_handler: $c->get( Get_Player_Search_Handler::class ),
                alert_handler: $c->get( Get_Alert_Handler::class )
            )
        );
        $container->alias( 'search_players_handler', Search_Players_Handler::class );
    }
}
