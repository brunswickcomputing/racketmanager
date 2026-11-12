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
use Racketmanager\Application\General\Queries\Get_Team_Order_Handler;
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Club_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Club_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Club_Shortcode_Adapter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Club_Role_Repository;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
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
        $container->set( 'club_repository', fn() => new Club_Repository() );
        $container->alias( Club_Repository_Interface::class, 'club_repository' );
        $container->alias( Club_Repository::class, 'club_repository' );

        $container->set( 'registration_repository', fn(): Registration_Repository_Interface => new Registration_Repository() );
        $container->alias( Registration_Repository_Interface::class, 'registration_repository' );
        $container->alias( Registration_Repository::class, 'registration_repository' );

        $container->set( 'club_role_repository', fn( Simple_Container $c ): Club_Role_Repository_Interface => new Club_Role_Repository( $c->get( User_Repository_Interface::class ) ) );
        $container->alias( Club_Role_Repository_Interface::class, 'club_role_repository' );
        $container->alias( Club_Role_Repository::class, 'club_role_repository' );

        $container->set(
            'team_repository',
            fn( Simple_Container $c ) => new Team_Repository(
                null,
                $c->get( 'player_repository' ),
                fn() => $c->get( Fixture_Repository_Interface::class )
            )
        );
        $container->alias( Team_Repository_Interface::class, 'team_repository' );
        $container->alias( Team_Repository::class, 'team_repository' );

        // Services.
        $container->set(
            'club_service',
            fn( Simple_Container $c ) => new Club_Service(
                $c->get( 'club_repository' ),
                $c->get( 'registration_repository' ),
                $c->get( 'club_role_repository' ),
                $c->get( 'player_repository' ),
                $c->get( 'team_repository' ),
                $c->get( 'player_service' ),
                $c->get( 'option_service' )
            )
        );
        $container->alias( Club_Service::class, 'club_service' );
        $container->alias( Club_Service_Interface::class, 'club_service' );

        $container->set(
            'registration_service',
            fn( Simple_Container $c ) => new Registration_Service(
                $app,
                $c->get( 'registration_repository' ),
                $c->get( 'player_repository' ),
                $c->get( 'club_repository' ),
                $c->get( 'player_service' ),
                $c->get( 'mailer' ),
                $c->get( 'clock' ),
                $c->get( 'domain_event_dispatcher' )
            )
        );
        $container->alias( Registration_Service::class, 'registration_service' );

        $container->set(
            'team_service',
            fn( Simple_Container $c ) => new Team_Service(
                $c->get( 'team_repository' ),
                $c->get( 'club_repository' ),
                $c->get( 'event_repository' ),
                $c->get( 'player_service' ),
                $c->get( 'fixture_repository' ),
                $c->get( 'event_query_service' )
            )
        );
        $container->alias( Team_Service::class, 'team_service' );
        $container->alias( Team_Service_Interface::class, 'team_service' );

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
            'get_club_handler',
            fn( Simple_Container $c ) => new Get_Club_Handler(
                $c->get( 'club_service' ),
                $c->get( 'settings_service' ),
                $c->get( 'security_service' )
            )
        );

        $container->set(
            'get_club_players_handler',
            fn( Simple_Container $c ) => new Get_Club_Players_Handler(
                $c->get( 'club_service' ),
                $c->get( 'player_service' ),
                $c->get( 'registration_service' ),
                $c->get( 'option_gateway' )
            )
        );

        $container->set(
            'get_club_roles_handler',
            fn( Simple_Container $c ) => new Get_Club_Roles_Handler(
                $c->get( 'club_service' )
            )
        );

        $container->set(
            'get_club_role_modal_handler',
            fn( Simple_Container $c ) => new Get_Club_Role_Modal_Handler(
                $c->get( 'club_service' )
            )
        );

        $container->set(
            'get_club_competitions_handler',
            fn( Simple_Container $c ) => new Get_Club_Competitions_Handler(
                $c->get( 'club_service' ),
                $c->get( 'competition_service' ),
                $c->get( 'competition_repository' ),
                $c->get( 'event_query_service' )
            )
        );

        $container->set(
            'get_club_invoices_handler',
            fn( Simple_Container $c ) => new Get_Club_Invoices_Handler(
                $c->get( 'club_service' ),
                $c->get( 'finance_service' ),
                $c->get( 'settings_service' ),
                $c->get( 'option_gateway' )
            )
        );

        $container->set(
            'get_team_order_handler',
            fn( Simple_Container $c ) => new Get_Team_Order_Handler(
                $c->get( 'club_service' ),
                $c->get( 'event_repository' )
            )
        );

        // Shortcode Adapter.
        $container->set(
            'club_shortcode_adapter',
            fn( Simple_Container $c ) => new Club_Shortcode_Adapter(
                club_service: $c->get( 'club_service' ),
                get_club_handler: $c->get( 'get_club_handler' ),
                club_shortcode_presenter: $c->get( 'club_shortcode_presenter' ),
                players_handler: $c->get( 'get_club_players_handler' ),
                roles_handler: $c->get( 'get_club_roles_handler' ),
                role_modal_handler: $c->get( 'get_club_role_modal_handler' ),
                competitions_handler: $c->get( 'get_club_competitions_handler' ),
                invoices_handler: $c->get( 'get_club_invoices_handler' ),
                view_presenter: $c->get( 'shortcode_view_presenter' ),
                competition_shortcode_presenter: $c->get( 'competition_shortcode_presenter' ),
                event_query_service: $c->get( 'event_query_service' ),
                event_repository: $c->get( 'event_repository' ),
                team_repository: $c->get( 'team_repository' ),
                club_presenter: $c->get( 'club_presenter' ),
                player_presenter: $c->get( 'player_presenter' ),
                invoice_presenter: $c->get( 'invoice_presenter' ),
                team_details_presenter: $c->get( 'team_details_presenter' ),
                query_vars: $c->get( Query_Vars_Interface::class )
            )
        );
        $container->alias( Club_Shortcode_Adapter::class, 'club_shortcode_adapter' );

        // AJAX Adapter & Controller.
        $container->set(
            'club_ajax_adapter',
            fn( Simple_Container $c ) => new Club_Ajax_Adapter(
                $c->get( 'security_service' ),
                $c->get( 'json_response_factory' ),
                $c->get( 'club_service' ),
                $c->get( 'registration_service' ),
                $c->get( 'user_repository' ),
                $c->get( 'club_role_repository' ),
                $c->get( 'club_repository' ),
                $c->get( 'player_repository' )
            )
        );
        $container->alias( Club_Ajax_Adapter::class, 'club_ajax_adapter' );
        $container->set( 'club_ajax_controller', fn( Simple_Container $c ) => new Club_Ajax_Controller( $app, $c->get( 'club_ajax_adapter' ) ) );
        $container->alias( Club_Ajax_Controller::class, 'club_ajax_controller' );
    }
}
