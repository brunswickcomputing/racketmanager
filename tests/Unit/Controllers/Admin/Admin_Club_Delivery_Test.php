<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Club;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Club\Club_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Role_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Team_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Contact_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Constitution_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Event_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Match_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Schedule_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Schedule_Service;
use Racketmanager\Services\Admin\League\League_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Team_Action_Dispatcher;
use Racketmanager\Services\Admin\League\Event_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\League\Season_Delete_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\Validator\Score_Validation_Service;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Services\View\View_Renderer_Interface;
use stdClass;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Admin_Club_Delivery_Test extends TestCase {

    private Simple_Container $container;
    private Admin_Redirector_Interface $redirector;
    private View_Renderer_Interface $renderer;
    private Admin_Flash_Message_Store $flash_store;
    private Action_Guard_Interface $guard;
    private Club_Service $club_service;
    private Player_Service $player_service;
    private Registration_Service $registration_service;
    private Team_Service $team_service;
    private Admin_Club $screen;
    private ?string $original_method;

    protected function setUp(): void {
        parent::setUp();
        $this->original_method = $_SERVER['REQUEST_METHOD'] ?? null;
        $_GET                  = array();
        $_POST                 = array();

        $this->redirector           = $this->createMock( Admin_Redirector_Interface::class );
        $this->renderer             = $this->createMock( View_Renderer_Interface::class );
        $this->flash_store          = $this->createMock( Admin_Flash_Message_Store::class );
        $this->guard                = $this->createMock( Action_Guard_Interface::class );
        $this->club_service         = $this->createMock( Club_Service::class );
        $this->player_service       = $this->createMock( Player_Service::class );
        $this->registration_service = $this->createMock( Registration_Service::class );
        $this->team_service         = $this->createMock( Team_Service::class );

        $message_service = new Admin_Message_Service( $this->flash_store );

        $this->container = new Simple_Container();
        $this->container->set( 'club_service', fn() => $this->club_service );
        $this->container->set( 'player_service', fn() => $this->player_service );
        $this->container->set( 'registration_service', fn() => $this->registration_service );
        $this->container->set( 'team_service', fn() => $this->team_service );
        $this->container->set( 'league_service', fn() => $this->createStub( League_Service::class ) );
        $this->container->set( 'competition_service', fn() => $this->createStub( Competition_Service::class ) );
        $this->container->set( 'finance_service', fn() => $this->createStub( Finance_Service::class ) );
        $this->container->set( 'season_service', fn() => $this->createStub( Season_Service::class ) );
        $this->container->set( 'tournament_service', fn() => $this->createStub( Tournament_Service::class ) );
        $this->container->set( 'fixture_repository', fn() => $this->createStub( Fixture_Repository_Interface::class ) );
        $this->container->set( 'admin_action_adapter', fn() => new Admin_Action_Adapter( $message_service, $this->redirector ) );
        $this->container->set( 'admin_message_service', fn() => $message_service );

        $this->container->set( 'league_contact_action_dispatcher', fn() => new League_Contact_Action_Dispatcher( $this->createStub( League_Service::class ), $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'league_setup_action_dispatcher', fn() => new League_Setup_Action_Dispatcher( $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'event_setup_action_dispatcher', fn() => new Event_Setup_Action_Dispatcher( $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'season_delete_action_dispatcher', fn() => new Season_Delete_Action_Dispatcher( $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'league_team_action_dispatcher', fn() => new League_Team_Action_Dispatcher( $this->createStub( League_Service::class ), $this->guard ) );
        $this->container->set( 'league_match_action_dispatcher', fn() => new League_Match_Action_Dispatcher( $this->createStub( Fixture_Repository_Interface::class ), $this->createStub( Fixture_Maintenance_Service::class ), $this->guard ) );
        $this->container->set( 'league_constitution_action_dispatcher', fn() => new League_Constitution_Action_Dispatcher( $this->guard ) );
        $this->container->set( 'league_event_action_dispatcher', fn() => new League_Event_Action_Dispatcher( $this->createStub( League_Service::class ), $this->guard ) );
        $this->container->set( 'league_schedule_action_dispatcher', fn() => new League_Schedule_Action_Dispatcher( new League_Schedule_Service( $this->createStub( Fixture_Maintenance_Service::class ) ), $this->guard ) );

        $this->container->set( 'club_action_dispatcher', fn() => new Club_Action_Dispatcher( $this->club_service, $this->player_service, $this->guard ) );
        $this->container->set( 'club_player_action_dispatcher', fn() => new Club_Player_Action_Dispatcher( $this->registration_service, $this->player_service, $this->guard ) );
        $this->container->set( 'club_team_action_dispatcher', fn() => new Club_Team_Action_Dispatcher( $this->club_service, $this->team_service, $this->guard ) );
        $this->container->set( 'club_role_action_dispatcher', fn() => new Club_Role_Action_Dispatcher( $this->club_service, $this->guard ) );
        $this->container->set( 'season_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Season\Season_Action_Dispatcher( $this->createStub( Season_Service::class ), $this->guard ) );

        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->container = $this->container;
        global $racketmanager_instance;
        $racketmanager_instance = $racketmanager;

        $this->screen = new Admin_Club( $racketmanager );
    }

    protected function tearDown(): void {
        $_GET  = array();
        $_POST = array();
        if ( null === $this->original_method ) {
            unset( $_SERVER['REQUEST_METHOD'] );
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->original_method;
        }
        parent::tearDown();
    }

    public function test_post_mutation_delete_clubs_redirects_with_flash(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['doClubDel']        = '1';
        $_POST['action']           = 'delete';
        $_POST['club']             = array( 10 );

        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'clubs-bulk', 'del_teams' );

        $this->club_service->expects( self::once() )
            ->method( 'remove_club' )
            ->with( 10 );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( self::stringContains( '10 deleted' ), false );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::stringContains( 'page=racketmanager-clubs' ) );

        $this->screen->display_clubs_page();
    }

    public function test_post_mutation_add_team_redirects_with_flash(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['club_id']           = '5';
        $_POST['addTeam']          = '1';
        $_POST['club']             = '5';
        $_POST['team_type']        = 'Men';

        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-team', 'edit_teams' );

        $this->club_service->expects( self::once() )
            ->method( 'create_team' )
            ->with( 5, 'Men' );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( 'Team added', false );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::stringContains( 'club_id=5' ) );

        $this->screen->display_teams_page();
    }

    public function test_post_mutation_add_role_redirects_with_flash(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['club_id']           = '5';
        $_POST['addRole']          = '1';
        $_POST['club_id']          = '5';
        $_POST['role_id']          = '2';
        $_POST['user_id']          = '10';

        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-club-role', 'edit_teams' );

        $this->club_service->expects( self::once() )
            ->method( 'set_club_role' )
            ->with( 5, 2, 10 )
            ->willReturn( true );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( 'Role added', false );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::stringContains( 'club_id=5' ) );

        $this->screen->display_roles_page();
    }

    public function test_post_mutation_guard_failure_redirects_with_error_flash_and_calls_no_service_mutation(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['doClubDel']        = '1';
        $_POST['action']           = 'delete';
        $_POST['club']             = array( 10 );

        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->willThrowException( new Invalid_Status_Exception( 'Security token invalid' ) );

        $this->club_service->expects( self::never() )
            ->method( 'remove_club' );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( 'Security token invalid', true );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::stringContains( 'page=racketmanager-clubs' ) );

        $this->screen->display_clubs_page();
    }
}
