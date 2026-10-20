<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Container\Providers;

use Racketmanager\Application\Club\Queries\Get_Club_Competitions_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Invoices_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Players_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Role_Modal_Handler;
use Racketmanager\Application\Club\Queries\Get_Club_Roles_Handler;
use Racketmanager\Application\General\Queries\Get_Team_Order_Handler;
use Racketmanager\Infrastructure\Wordpress\Ajax\Club_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Club_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Club_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Club_Shortcode_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Club_Role_Repository;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Registration_Repository_Interface;
use Racketmanager\Repositories\Registration_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;

/**
 * Service provider for Club and Team bounded context.
 */
final readonly class Club_Service_Provider implements Service_Provider_Interface {
    public function __construct(
        private ?RacketManager $app = null
    ) {}

    public function register( Simple_Container $container ): void {
        $app = $this->app;

        // Repositories
        $container->set( 'club_repository', fn() => new Club_Repository() );
        $container->set( 'registration_repository', fn(): Registration_Repository_Interface => new Registration_Repository() );
        $container->set( 'club_role_repository', fn(): Club_Role_Repository_Interface => new Club_Role_Repository() );
        $container->set( 'team_repository', fn( Simple_Container $c ) => new Team_Repository( null, $c->get( 'player_repository' ) ) );

        // Services
        $container->set( 'club_service', fn( Simple_Container $c ) => new Club_Service(
            $c->get( 'club_repository' ),
            $c->get( 'registration_repository' ),
            $c->get( 'club_role_repository' ),
            $c->get( 'player_repository' ),
            $c->get( 'team_repository' ),
            $c->get( 'player_service' )
        ) );

        $container->set( 'registration_service', fn( Simple_Container $c ) => new Registration_Service(
            $app,
            $c->get( 'registration_repository' ),
            $c->get( 'player_repository' ),
            $c->get( 'club_repository' ),
            $c->get( 'player_service' ),
            $c->get( 'mailer' )
        ) );

        $container->set( 'team_service', fn( Simple_Container $c ) => new Team_Service(
            $c->get( 'team_repository' ),
            $c->get( 'club_repository' ),
            $c->get( 'event_repository' ),
            $c->get( 'player_service' ),
            $c->get( 'fixture_repository' )
        ) );

        // Presenters
        $container->set( 'club_presenter', fn( Simple_Container $c ) => new Club_Presenter( $c->get( 'option_gateway' ) ) );
        $container->set( 'club_shortcode_presenter', fn( Simple_Container $c ) => new Club_Shortcode_Presenter(
            $c->get( 'view_renderer' ),
            $c->get( 'club_presenter' ),
            $c->get( 'player_presenter' ),
            $c->get( 'favourite_presenter' )
        ) );

        // Query Handlers
        $container->set( 'get_club_handler', fn( Simple_Container $c ) => new Get_Club_Handler(
            $c->get( 'club_service' ),
            $c->get( 'settings_service' ),
            $c->get( 'club_presenter' ),
            $c->get( 'security_service' )
        ) );

        $container->set( 'get_club_players_handler', fn( Simple_Container $c ) => new Get_Club_Players_Handler(
            $c->get( 'club_service' ),
            $c->get( 'player_service' ),
            $c->get( 'registration_service' ),
            $c->get( 'club_presenter' ),
            $c->get( 'option_gateway' ),
            $c->get( 'player_presenter' )
        ) );

        $container->set( 'get_club_roles_handler', fn( Simple_Container $c ) => new Get_Club_Roles_Handler(
            $c->get( 'club_service' )
        ) );

        $container->set( 'get_club_role_modal_handler', fn( Simple_Container $c ) => new Get_Club_Role_Modal_Handler(
            $c->get( 'club_service' )
        ) );

        $container->set( 'get_club_competitions_handler', fn( Simple_Container $c ) => new Get_Club_Competitions_Handler(
            $c->get( 'club_service' ),
            $c->get( 'competition_service' ),
            $c->get( 'competition_repository' ),
            $c->get( 'team_details_presenter' ),
            $c->get( 'club_presenter' )
        ) );

        $container->set( 'get_club_invoices_handler', fn( Simple_Container $c ) => new Get_Club_Invoices_Handler(
            $c->get( 'club_service' ),
            $c->get( 'finance_service' ),
            $c->get( 'invoice_presenter' ),
            $c->get( 'club_presenter' ),
            $c->get( 'settings_service' ),
            $c->get( 'option_gateway' )
        ) );

        $container->set( 'get_team_order_handler', fn( Simple_Container $c ) => new Get_Team_Order_Handler(
            $c->get( 'club_service' ),
            $c->get( 'event_repository' )
        ) );

        // Shortcode Adapter
        $container->set( 'club_shortcode_adapter', fn( Simple_Container $c ) => new Club_Shortcode_Adapter(
            club_service: $c->get( 'club_service' ),
            get_club_handler: $c->get( 'get_club_handler' ),
            club_shortcode_presenter: $c->get( 'club_shortcode_presenter' ),
            players_handler: $c->get( 'get_club_players_handler' ),
            roles_handler: $c->get( 'get_club_roles_handler' ),
            role_modal_handler: $c->get( 'get_club_role_modal_handler' ),
            competitions_handler: $c->get( 'get_club_competitions_handler' ),
            invoices_handler: $c->get( 'get_club_invoices_handler' ),
            view_presenter: $c->get( 'shortcode_view_presenter' ),
            competition_shortcode_presenter: $c->get( 'competition_shortcode_presenter' )
        ) );

        // AJAX Adapter & Controller
        $container->set( 'club_ajax_adapter', fn( Simple_Container $c ) => new Club_Ajax_Adapter(
            $c->get( 'security_service' ),
            $c->get( 'json_response_factory' ),
            $c->get( 'club_service' ),
            $c->get( 'registration_service' ),
            $c->get( 'user_repository' )
        ) );
        $container->set( 'club_ajax_controller', fn( Simple_Container $c ) => new Club_Ajax_Controller( $app, $c->get( 'club_ajax_adapter' ) ) );
    }
}
