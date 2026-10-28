<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Club;
use Racketmanager\Admin\Admin_Competition;
use Racketmanager\Admin\Admin_Screens_Service_Provider;
use Racketmanager\Admin\Controllers\Clubs_Admin_Controller;
use Racketmanager\Admin\Controllers\Club_Admin_Controller;
use Racketmanager\Admin\Controllers\Club_Players_Admin_Controller;
use Racketmanager\Admin\Controllers\Club_Roles_Admin_Controller;
use Racketmanager\Admin\Controllers\Club_Team_Admin_Controller;
use Racketmanager\Admin\Controllers\Club_Teams_Admin_Controller;
use Racketmanager\Admin\Controllers\Competition_Config_Admin_Controller;
use Racketmanager\Admin\Controllers\Competition_Season_Modify_Admin_Controller;
use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Container\Providers\Admin_Service_Provider;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;

final class Club_Competition_Service_Registration_Test extends TestCase {
    public function test_runtime_provider_registers_and_resolves_migrated_screens(): void {
        $container = new Simple_Container();
        $app       = $this->createStub( RacketManager::class );
        $app->container = $container;

        $container->set( 'view_renderer', fn() => $this->createStub( View_Renderer_Interface::class ) );
        $container->set( 'club_service', fn() => $this->createStub( Club_Service::class ) );
        $container->set( 'competition_service', fn() => $this->createStub( Competition_Service::class ) );
        $container->set( 'competition_season_service', fn() => $this->createStub( Competition_Season_Service::class ) );
        $container->set( 'league_service', fn() => $this->createStub( League_Service::class ) );
        $container->set( 'league_query_service', fn() => $this->createStub( League_Query_Service::class ) );
        $container->set( 'finance_service', fn() => $this->createStub( Finance_Service::class ) );
        $container->set( 'season_service', fn() => $this->createStub( Season_Service::class ) );
        $container->set( 'registration_service', fn() => $this->createStub( Registration_Service::class ) );
        $container->set( 'event_repository', fn() => $this->createStub( Event_Repository_Interface::class ) );
        $container->set( 'player_service', fn() => $this->createStub( Player_Service::class ) );
        $container->set( 'team_service', fn() => $this->createStub( Team_Service::class ) );
        $container->set( 'tournament_service', fn() => $this->createStub( Tournament_Service::class ) );

        ( new Admin_Service_Provider( $app ) )->register( $container );
        ( new Admin_Screens_Service_Provider( $app ) )->register( $container );

        self::assertInstanceOf( Admin_Club::class, $container->get( 'admin_club_screen' ) );
        self::assertInstanceOf( Admin_Competition::class, $container->get( 'admin_competition_screen' ) );
        self::assertTrue( $container->has( 'admin_player_screen' ) );
        self::assertInstanceOf( Club_Admin_Controller::class, $container->get( 'club_admin_controller' ) );
        self::assertInstanceOf( Club_Team_Admin_Controller::class, $container->get( 'club_team_admin_controller' ) );
        self::assertInstanceOf( Clubs_Admin_Controller::class, $container->get( 'clubs_admin_controller' ) );
        self::assertInstanceOf( Club_Players_Admin_Controller::class, $container->get( 'club_players_admin_controller' ) );
        self::assertInstanceOf( Club_Teams_Admin_Controller::class, $container->get( 'club_teams_admin_controller' ) );
        self::assertInstanceOf( Club_Roles_Admin_Controller::class, $container->get( 'club_roles_admin_controller' ) );
        self::assertInstanceOf( Competition_Config_Admin_Controller::class, $container->get( 'competition_config_admin_controller' ) );
        self::assertInstanceOf( Competition_Season_Modify_Admin_Controller::class, $container->get( 'competition_season_modify_admin_controller' ) );
    }
}