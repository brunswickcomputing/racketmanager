<?php

namespace Racketmanager\Services\Container;

use Racketmanager\Exceptions\Interface_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Admin\Controllers\Tournament_Contact_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Teams_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Draw_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Information_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Matches_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Setup_Event_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Setup_Admin_Controller;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Championship_Admin_Service;
use Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Wp_Action_Guard;
use Racketmanager\Services\Admin\Tournament\Tournament_Contact_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Admin\Tournament\Tournament_Action_Dispatcher;
use Racketmanager\Services\Admin\Tournament\Tournament_Information_Action_Dispatcher;
use Racketmanager\Repositories\Charge_Repository;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Invoice_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Registration_Repository;
use Racketmanager\Repositories\Club_Role_Repository;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Repositories\Player_Error_Repository;
use Racketmanager\Repositories\Rubber_Repository;
use Racketmanager\Repositories\Season_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Repositories\Tournament_Entry_Repository;
use Racketmanager\Repositories\Tournament_Repository;
use Racketmanager\Admin\Controllers\Tournament_Plan_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Tournaments_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Overview_Admin_Controller;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\External\Wtn_Api_Client;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Notify_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Admin\Controllers\Tournament_Admin_Controller;
use Racketmanager\Services\View\Php_View_Renderer;

/**
 * Registers core services in the Simple_Container.
 */
final class Container_Bootstrap {
    public static function boot( RacketManager $app ): Simple_Container {
        $c = new Simple_Container();

        self::register_repositories( $c );
        self::register_external_clients( $c );
        self::register_services( $c, $app );
        self::register_admin_controllers( $c );

        return $c;
    }

    private static function register_repositories( Simple_Container $c ): void {
        $c->set( 'club_repository', fn() => new Club_Repository() );
        $c->set( 'registration_repository', fn() => new Registration_Repository() );
        $c->set( 'club_role_repository', fn() => new Club_Role_Repository() );
        $c->set( 'player_repository', fn() => new Player_Repository() );
        $c->set( 'player_error_repository', fn() => new Player_Error_Repository() );
        $c->set( 'team_repository', fn() => new Team_Repository() );
        $c->set( 'event_repository', fn() => new Event_Repository() );
        $c->set( 'fixture_repository', fn() => new Fixture_Repository() );
        $c->set( 'league_repository', fn() => new League_Repository() );
        $c->set( 'league_team_repository', fn() => new League_Team_Repository() );
        $c->set( 'competition_repository', fn() => new Competition_Repository() );
        $c->set( 'charge_repository', fn() => new Charge_Repository() );
        $c->set( 'invoice_repository', fn() => new Invoice_Repository() );
        $c->set( 'tournament_repository', fn() => new Tournament_Repository() );
        $c->set( 'tournament_entry_repository', fn() => new Tournament_Entry_Repository() );
        $c->set( 'season_repository', fn() => new Season_Repository() );
        $c->set( 'rubber_repository', fn() => new Rubber_Repository() );
    }

    private static function register_external_clients( Simple_Container $c ): void {
        $c->set( 'wtn_api_client', fn() => new Wtn_Api_Client() );
    }

    private static function register_services( Simple_Container $c, RacketManager $app ): void {
        $c->set( 'racketmanager_app', fn() => $app );

        $c->set( 'player_service', function ( Simple_Container $c ) use ( $app ) {
            return new Player_Service( $app, $c->get( 'player_repository' ), $c->get( 'player_error_repository' ), $c->get( 'club_role_repository' ), $c->get( 'wtn_api_client' ), $c->get( 'league_team_repository' ), $c->get( 'club_repository' ), $c->get( 'registration_repository' ), );
        } );

        $c->set( 'competition_service', function ( Simple_Container $c ) use ( $app ) {
            return new Competition_Service( $app, $c->get( 'competition_repository' ), $c->get( 'club_repository' ), $c->get( 'event_repository' ), $c->get( 'league_repository' ), $c->get( 'league_team_repository' ), $c->get( 'season_repository' ), $c->get( 'team_repository' ), );
        } );

        $c->set( 'club_service', function ( Simple_Container $c ) {
            return new Club_Service( $c->get( 'club_repository' ), $c->get( 'registration_repository' ), $c->get( 'club_role_repository' ), $c->get( 'player_repository' ), $c->get( 'team_repository' ), $c->get( 'player_service' ), );
        } );

        $c->set( 'competition_entry_service', function ( Simple_Container $c ) use ( $app ) {
            return new Competition_Entry_Service( $app, $c->get( 'club_repository' ), $c->get( 'league_repository' ), $c->get( 'league_team_repository' ), $c->get( 'team_repository' ), $c->get( 'tournament_repository' ), $c->get( 'tournament_entry_repository' ), $c->get( 'club_service' ), $c->get( 'competition_service' ), $c->get( 'finance_service' ), $c->get( 'player_service' ), $c->get( 'tournament_service' ), $c->get( 'notify_service' ), );
        } );

        $c->set( 'team_service', function ( Simple_Container $c ) {
            return new Team_Service( $c->get( 'team_repository' ), $c->get( 'club_repository' ), $c->get( 'event_repository' ), $c->get( 'player_service' ), );
        } );

        $c->set( 'registration_service', function ( Simple_Container $c ) use ( $app ) {
            return new Registration_Service( $app, $c->get( 'registration_repository' ), $c->get( 'player_repository' ), $c->get( 'club_repository' ), $c->get( 'player_service' ) );
        } );

        $c->set( 'league_service', function ( Simple_Container $c ) use ( $app ) {
            return new League_Service( $app, $c->get( 'league_repository' ), $c->get( 'event_repository' ), $c->get( 'league_team_repository' ), $c->get( 'team_repository' ), );
        } );

        $c->set( 'fixture_service', function ( Simple_Container $c ) use ( $app ) {
            return new Fixture_Service( $app, $c->get( 'fixture_repository' ), $c->get( 'registration_service' ), $c->get( 'league_repository' ), $c->get( 'team_repository' ), $c->get( 'club_repository' ), $c->get( 'competition_service' ), $c->get( 'team_service' ), );
        } );

        $c->set( 'finance_service', function ( Simple_Container $c ) use ( $app ) {
            return new Finance_Service( $app, $c->get( 'charge_repository' ), $c->get( 'invoice_repository' ), $c->get( 'club_repository' ), $c->get( 'tournament_repository' ), $c->get( 'competition_service' ), $c->get( 'player_service' ) );
        } );

        $c->set( 'tournament_service', function ( Simple_Container $c ) use ( $app ) {
            return new Tournament_Service( $app, $c->get( 'tournament_repository' ), $c->get( 'charge_repository' ), $c->get( 'event_repository' ), $c->get( 'fixture_service' ), $c->get( 'league_team_repository' ), $c->get( 'tournament_entry_repository' ), $c->get( 'competition_service' ), $c->get( 'player_service' ), $c->get( 'club_service' ), $c->get( 'finance_service' ), );
        } );

        $c->set( 'season_service', function ( Simple_Container $c ) use ( $app ) {
            return new Season_Service( $app, $c->get( 'season_repository' ), );
        } );

        $c->set( 'view_renderer', function () {
            return new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        } );

        $c->set( 'admin_flash_message_store', fn() => new Admin_Flash_Message_Store() );

        $c->set( 'admin_message_service', function ( Simple_Container $c ) {
            return new Admin_Message_Service( $c->get( 'admin_flash_message_store' ) );
        } );

        $c->set( 'notify_service', fn() => new Notify_Service( $app ) );
    }

    private static function register_admin_controllers( Simple_Container $c ): void {
        $c->set( 'tournament_action_dispatcher', function ( Simple_Container $c ) {
            return new Tournament_Action_Dispatcher(
                $c->get( 'tournament_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'club_service' ),
                $c->get( 'competition_service' ),
                $c->get( 'season_service' ),
                $c->get( 'tournament_action_dispatcher' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_plan_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Plan_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_tournaments_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Tournaments_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'competition_service' ),
                $c->get( 'season_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_overview_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Overview_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_setup_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Setup_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_setup_event_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Setup_Event_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'draw_action_dispatcher' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'championship_admin_service', function ( Simple_Container $c ) {
            return new Championship_Admin_Service(
                $c->get( 'league_service' ),
            );
        } );

        // Register draw action handler under an interface-ish name for consumers.
        $c->set( 'draw_action_handler', function ( Simple_Container $c ) {
            $handler = $c->get( 'championship_admin_service' );
            if ( ! ( $handler instanceof Draw_Action_Handler_Interface ) ) {
                throw new Interface_Exception( __( 'Draw action handler must implement Draw_Action_Handler_Interface', 'racketmanager' ) );
            }
            return $handler;
        } );

        // Register the concrete implementation (optional alias).
        $c->set( 'wp_action_guard', fn () => new Wp_Action_Guard() );

        // Register under an interface-ish name for consumers.
        // (Simple_Container is string-keyed, so we keep it as a convention.)
        $c->set( 'action_guard', function ( Simple_Container $c ) {
            $guard = $c->get( 'wp_action_guard' );
            if ( ! ( $guard instanceof Action_Guard_Interface ) ) {
                throw new Interface_Exception( __( 'Action guard must implement Action_Guard_Interface', 'racketmanager' ) );
            }
            return $guard;
        } );

        $c->set( 'draw_action_dispatcher', function ( Simple_Container $c ) {
            return new Draw_Action_Dispatcher(
                $c->get( 'draw_action_handler' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_contact_action_dispatcher', function ( Simple_Container $c ) {
            return new Tournament_Contact_Action_Dispatcher(
                $c->get( 'tournament_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_contact_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Contact_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'tournament_contact_action_dispatcher' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_information_action_dispatcher', function ( Simple_Container $c ) {
            return new Tournament_Information_Action_Dispatcher(
                $c->get( 'tournament_service' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_information_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Information_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'tournament_information_action_dispatcher' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_matches_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Matches_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'draw_action_dispatcher' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_draw_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Draw_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'league_service' ),
                $c->get( 'draw_action_dispatcher' ),
                $c->get( 'action_guard' ),
            );
        } );

        $c->set( 'tournament_teams_admin_controller', function ( Simple_Container $c ) {
            return new Tournament_Teams_Admin_Controller(
                $c->get( 'tournament_service' ),
                $c->get( 'league_service' ),
                $c->get( 'team_service' ),
                $c->get( 'action_guard' ),
            );
        } );

    }

}
