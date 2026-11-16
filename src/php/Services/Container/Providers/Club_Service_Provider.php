<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Container\Providers;

use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Application\Club\Ports\Club_Validator_Factory_Interface;
use Racketmanager\Application\Club\Ports\Team_Service_Interface;
use Racketmanager\Application\Club\Queries\Get_Club_Competitions_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Invoices_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Players_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Role_Modal_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Roles_Handler;
use Racketmanager\Application\Competition\Ports\Competition_Service_Interface;
use Racketmanager\Application\Competition\Ports\Event_Query_Service_Interface;
use Racketmanager\Application\General\Queries\Get_Team_Order_Handler;
use Racketmanager\Application\Mail\Mailer_Interface;
use Racketmanager\Application\Player\Ports\Player_Service_Interface;
use Racketmanager\Application\Player\Ports\Registration_Service_Interface;
use Racketmanager\Application\Ports\Finance_Service_Interface;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Application\Ports\Settings_Service_Interface;
use Racketmanager\Domain\Events\Domain_Event_Dispatcher_Interface;
use Racketmanager\Domain\Ports\Clock_Interface;
use Racketmanager\Application\Ports\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Club_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Club_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Application\Ports\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Club_Shortcode_Adapter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Club_Role_Repository;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Registration_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Repositories\Registration_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Validator\Club_Validator_Factory;

/**
 * Service provider for Club and Team bounded context.
 */
final readonly class Club_Service_Provider implements Service_Provider_Interface {
    /**
     * Constructor.
     *
     * @param RacketManager|null $app Racketmanager app.
     */
    public function __construct(
        private ?RacketManager $app = null
    ) {}

    /**
     * Register services.
     *
     * @param Simple_Container $container Simple container.
     *
     * @return void
     */
    public function register( Simple_Container $container ): void {
        $app = $this->app;

        // Repositories.
        $container->set( Club_Repository_Interface::class, fn(): Club_Repository_Interface => new Club_Repository() );
        $container->alias( Club_Repository::class, Club_Repository_Interface::class );
        $container->alias( 'club_repository', Club_Repository_Interface::class );

        $container->set( Registration_Repository_Interface::class, fn(): Registration_Repository_Interface => new Registration_Repository() );
        $container->alias( Registration_Repository::class, Registration_Repository_Interface::class );
        $container->alias( 'registration_repository', Registration_Repository_Interface::class );

        $container->set( Club_Role_Repository_Interface::class, fn( Simple_Container $c ): Club_Role_Repository_Interface => new Club_Role_Repository( $c->get( User_Repository_Interface::class ) ) );
        $container->alias( Club_Role_Repository::class, Club_Role_Repository_Interface::class );
        $container->alias( 'club_role_repository', Club_Role_Repository_Interface::class );

        $container->set(
            Team_Repository_Interface::class,
            fn( Simple_Container $c ): Team_Repository_Interface => new Team_Repository(
                null,
                $c->get( Player_Repository_Interface::class ),
                fn() => $c->get( Fixture_Repository_Interface::class )
            )
        );
        $container->alias( Team_Repository::class, Team_Repository_Interface::class );
        $container->alias( 'team_repository', Team_Repository_Interface::class );

        // Services.
        $container->set(
            Club_Service_Interface::class,
            fn( Simple_Container $c ): Club_Service_Interface => new Club_Service(
                $c->get( Club_Repository_Interface::class ),
                $c->get( Registration_Repository_Interface::class ),
                $c->get( Club_Role_Repository_Interface::class ),
                $c->get( Player_Repository_Interface::class ),
                $c->get( Team_Repository_Interface::class ),
                $c->get( Player_Service_Interface::class ),
                $c->get( Option_Service::class )
            )
        );
        $container->alias( Club_Service::class, Club_Service_Interface::class );
        $container->alias( 'club_service', Club_Service_Interface::class );

        $container->set(
            Registration_Service_Interface::class,
            fn( Simple_Container $c ): Registration_Service_Interface => new Registration_Service(
                $app,
                $c->get( Registration_Repository_Interface::class ),
                $c->get( Player_Repository_Interface::class ),
                $c->get( Club_Repository_Interface::class ),
                $c->get( Player_Service_Interface::class ),
                $c->get( Mailer_Interface::class ),
                $c->get( Clock_Interface::class ),
                $c->get( Domain_Event_Dispatcher_Interface::class )
            )
        );
        $container->alias( Registration_Service::class, Registration_Service_Interface::class );
        $container->alias( 'registration_service', Registration_Service_Interface::class );

        $container->set(
            Team_Service_Interface::class,
            fn( Simple_Container $c ): Team_Service_Interface => new Team_Service(
                $c->get( Team_Repository_Interface::class ),
                $c->get( Club_Repository_Interface::class ),
                $c->get( Event_Repository_Interface::class ),
                $c->get( Player_Service_Interface::class ),
                $c->get( Fixture_Repository_Interface::class ),
                $c->get( Event_Query_Service_Interface::class )
            )
        );
        $container->alias( Team_Service::class, Team_Service_Interface::class );
        $container->alias( 'team_service', Team_Service_Interface::class );

        // Validator Factory.
        $container->set(
            Club_Validator_Factory_Interface::class,
            fn( Simple_Container $c ): Club_Validator_Factory_Interface => new Club_Validator_Factory(
                club_role_repository: $c->has( Club_Role_Repository_Interface::class ) ? $c->get( Club_Role_Repository_Interface::class ) : null,
                player_repository: $c->has( Player_Repository_Interface::class ) ? $c->get( Player_Repository_Interface::class ) : null,
                user_repository: $c->has( User_Repository_Interface::class ) ? $c->get( User_Repository_Interface::class ) : null,
                club_repository: $c->has( Club_Repository_Interface::class ) ? $c->get( Club_Repository_Interface::class ) : null,
            )
        );
        $container->alias( Club_Validator_Factory::class, Club_Validator_Factory_Interface::class );

        // Query Handlers.
        $container->set(
            Get_Club_Handler::class,
            fn( Simple_Container $c ): Get_Club_Handler => new Get_Club_Handler(
                $c->get( Club_Service_Interface::class ),
                $c->get( Settings_Service_Interface::class ),
                $c->get( Security_Service_Interface::class )
            )
        );
        $container->alias( 'get_club_handler', Get_Club_Handler::class );

        $container->set(
            Get_Club_Players_Handler::class,
            fn( Simple_Container $c ): Get_Club_Players_Handler => new Get_Club_Players_Handler(
                $c->get( Club_Service_Interface::class ),
                $c->get( Player_Service_Interface::class ),
                $c->get( Registration_Service_Interface::class ),
                $c->get( Option_Gateway_Interface::class )
            )
        );
        $container->alias( 'get_club_players_handler', Get_Club_Players_Handler::class );

        $container->set(
            Get_Club_Roles_Handler::class,
            fn( Simple_Container $c ): Get_Club_Roles_Handler => new Get_Club_Roles_Handler(
                $c->get( Club_Service_Interface::class )
            )
        );
        $container->alias( 'get_club_roles_handler', Get_Club_Roles_Handler::class );

        $container->set(
            Get_Club_Role_Modal_Handler::class,
            fn( Simple_Container $c ): Get_Club_Role_Modal_Handler => new Get_Club_Role_Modal_Handler(
                $c->get( Club_Service_Interface::class )
            )
        );
        $container->alias( 'get_club_role_modal_handler', Get_Club_Role_Modal_Handler::class );

        $container->set(
            Get_Club_Competitions_Handler::class,
            fn( Simple_Container $c ): Get_Club_Competitions_Handler => new Get_Club_Competitions_Handler(
                $c->get( Club_Service_Interface::class ),
                $c->get( Competition_Service_Interface::class ),
                $c->get( Competition_Repository_Interface::class ),
                $c->get( Event_Query_Service_Interface::class )
            )
        );
        $container->alias( 'get_club_competitions_handler', Get_Club_Competitions_Handler::class );

        $container->set(
            Get_Club_Invoices_Handler::class,
            fn( Simple_Container $c ): Get_Club_Invoices_Handler => new Get_Club_Invoices_Handler(
                $c->get( Club_Service_Interface::class ),
                $c->get( Finance_Service_Interface::class ),
                $c->get( Settings_Service_Interface::class ),
                $c->get( Option_Gateway_Interface::class )
            )
        );
        $container->alias( 'get_club_invoices_handler', Get_Club_Invoices_Handler::class );

        $container->set(
            Get_Team_Order_Handler::class,
            fn( Simple_Container $c ): Get_Team_Order_Handler => new Get_Team_Order_Handler(
                $c->get( Club_Service_Interface::class ),
                $c->get( Event_Repository_Interface::class )
            )
        );
        $container->alias( 'get_team_order_handler', Get_Team_Order_Handler::class );

        // Shortcode Adapter (autowired via container class resolution).
        $container->alias( 'club_shortcode_adapter', Club_Shortcode_Adapter::class );

        // AJAX Adapter & Controller.
        $container->set(
            Club_Ajax_Adapter::class,
            fn( Simple_Container $c ): Club_Ajax_Adapter => new Club_Ajax_Adapter(
                $c->get( Security_Service_Interface::class ),
                $c->get( Json_Response_Factory_Interface::class ),
                $c->get( Club_Service_Interface::class ),
                $c->get( Registration_Service_Interface::class ),
                $c->get( User_Repository_Interface::class ),
                $c->get( Club_Role_Repository_Interface::class ),
                $c->get( Club_Repository_Interface::class ),
                $c->get( Player_Repository_Interface::class )
            )
        );
        $container->alias( 'club_ajax_adapter', Club_Ajax_Adapter::class );

        $container->set(
            Club_Ajax_Controller::class,
            fn( Simple_Container $c ): Club_Ajax_Controller => new Club_Ajax_Controller(
                $app,
                $c->get( Club_Ajax_Adapter::class )
            )
        );
        $container->alias( 'club_ajax_controller', Club_Ajax_Controller::class );
    }
}
