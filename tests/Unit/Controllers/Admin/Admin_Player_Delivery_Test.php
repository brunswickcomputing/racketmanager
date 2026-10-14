<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Player;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Player_Errors_Page_View_Model;
use Racketmanager\Admin\View_Models\Player_Page_View_Model;
use Racketmanager\Admin\View_Models\Player_Requests_Page_View_Model;
use Racketmanager\Admin\View_Models\Players_Page_View_Model;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Player_Factory;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Player\Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Player\Player_Error_Action_Dispatcher;
use Racketmanager\Services\Admin\Player\Player_Request_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use stdClass;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Admin_Player_Delivery_Test extends TestCase {

    private Simple_Container $container;
    private Admin_Redirector_Interface $redirector;
    private View_Renderer_Interface $renderer;
    private Admin_Flash_Message_Store $flash_store;
    private Action_Guard_Interface $guard;
    private Player_Service $player_service;
    private Club_Service $club_service;
    private Registration_Service $registration_service;
    private Player_Action_Dispatcher $player_action_dispatcher;
    private Player_Error_Action_Dispatcher $player_error_action_dispatcher;
    private Player_Request_Action_Dispatcher $player_request_action_dispatcher;
    private Admin_Player $screen;
    private array $original_superglobals;
    private array $original_globals;

    protected function setUp(): void {
        parent::setUp();
        $this->original_superglobals = array(
            'get'                => $_GET,
            'post'               => $_POST,
            'request_method_set' => array_key_exists( 'REQUEST_METHOD', $_SERVER ),
            'request_method'     => $_SERVER['REQUEST_METHOD'] ?? null,
        );
        $this->original_globals     = array(
            'racketmanager_set' => array_key_exists( 'racketmanager', $GLOBALS ),
            'racketmanager'     => $GLOBALS['racketmanager'] ?? null,
            'capability_set'    => array_key_exists( 'wp_stubs_current_user_can', $GLOBALS ),
            'capability'        => $GLOBALS['wp_stubs_current_user_can'] ?? null,
        );
        $GLOBALS['wp_stubs_current_user_can'] = true;
        $_GET  = array();
        $_POST = array();

        $this->redirector           = $this->createMock( Admin_Redirector_Interface::class );
        $this->renderer             = $this->createMock( View_Renderer_Interface::class );
        $this->flash_store          = $this->createMock( Admin_Flash_Message_Store::class );
        $this->guard                = $this->createMock( Action_Guard_Interface::class );
        $this->player_service       = $this->createMock( Player_Service::class );
        $this->club_service         = $this->createMock( Club_Service::class );
        $this->registration_service = $this->createMock( Registration_Service::class );

        $this->player_action_dispatcher         = new Player_Action_Dispatcher( $this->player_service, $this->guard );
        $this->player_error_action_dispatcher   = new Player_Error_Action_Dispatcher( $this->player_service, $this->guard );
        $this->player_request_action_dispatcher = new Player_Request_Action_Dispatcher( $this->registration_service, $this->guard );

        $message_service = new Admin_Message_Service( $this->flash_store );

        $this->container = new Simple_Container();
        $this->container->set( 'admin_message_service', fn() => $message_service );
        $this->container->set( 'player_service', fn() => $this->player_service );
        $this->container->set( 'club_service', fn() => $this->club_service );
        $this->container->set( 'registration_service', fn() => $this->registration_service );
        $this->container->set( 'finance_service', fn() => $this->createStub( Finance_Service::class ) );
        $this->container->set( 'competition_service', fn() => $this->createStub( Competition_Service::class ) );
        $this->container->set( 'season_service', fn() => $this->createStub( Season_Service::class ) );
        $this->container->set( 'team_service', fn() => $this->createStub( Team_Service::class ) );
        $this->container->set( 'league_contact_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Contact_Action_Dispatcher( $this->createStub( League_Service::class ), $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'league_setup_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Setup_Action_Dispatcher( $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'event_setup_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\Event_Setup_Action_Dispatcher( $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'season_delete_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\Season_Delete_Action_Dispatcher( $this->createStub( Competition_Service::class ), $this->guard ) );
        $this->container->set( 'league_team_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Team_Action_Dispatcher( $this->createStub( League_Service::class ), $this->guard ) );
        $this->container->set( 'league_match_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Match_Action_Dispatcher( $this->createStub( Fixture_Repository_Interface::class ), $this->createStub( \Racketmanager\Services\Fixture\Fixture_Maintenance_Service::class ), $this->guard ) );
        $this->container->set( 'league_constitution_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Constitution_Action_Dispatcher( $this->guard ) );
        $this->container->set( 'league_event_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Event_Action_Dispatcher( $this->createStub( League_Service::class ), $this->guard ) );
        $this->container->set( 'league_schedule_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Schedule_Action_Dispatcher( new \Racketmanager\Services\Admin\League\League_Schedule_Service( $this->createStub( \Racketmanager\Services\Fixture\Fixture_Maintenance_Service::class ) ), $this->guard ) );
        $this->container->set( 'club_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Action_Dispatcher( $this->createStub( Club_Service::class ), $this->createStub( Player_Service::class ), $this->guard ) );
        $this->container->set( 'club_player_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Player_Action_Dispatcher( $this->createStub( Registration_Service::class ), $this->createStub( Player_Service::class ), $this->guard ) );
        $this->container->set( 'club_team_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Team_Action_Dispatcher( $this->createStub( Club_Service::class ), $this->createStub( Team_Service::class ), $this->guard ) );
        $this->container->set( 'club_role_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Role_Action_Dispatcher( $this->createStub( Club_Service::class ), $this->guard ) );
        $this->container->set( 'season_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Season\Season_Action_Dispatcher( $this->createStub( Season_Service::class ), $this->guard ) );
        $this->container->set( 'league_service', fn() => $this->createStub( League_Service::class ) );
        $this->container->set( 'tournament_service', fn() => $this->createStub( Tournament_Service::class ) );
        $this->container->set( 'fixture_repository', fn() => $this->createStub( Fixture_Repository_Interface::class ) );

        $actions = new Admin_Action_Adapter( $message_service, $this->redirector );
        $screens = new Admin_Screen_Presenter( $this->renderer, $message_service );

        $this->container->set( 'admin_action_adapter', fn() => $actions );
        $this->container->set( 'admin_screen_presenter', fn() => $screens );
        $this->container->set( 'player_action_dispatcher', fn() => $this->player_action_dispatcher );
        $this->container->set( 'player_error_action_dispatcher', fn() => $this->player_error_action_dispatcher );
        $this->container->set( 'player_request_action_dispatcher', fn() => $this->player_request_action_dispatcher );

        $racketmanager            = $this->createMock( \Racketmanager\RacketManager::class );
        $racketmanager->container = $this->container;
        $GLOBALS['racketmanager'] = $racketmanager;

        $this->screen = new Admin_Player(
            $racketmanager,
            $actions,
            $screens,
            $this->player_action_dispatcher,
            $this->player_error_action_dispatcher,
            $this->player_request_action_dispatcher
        );
    }

    protected function tearDown(): void {
        $_GET  = $this->original_superglobals['get'];
        $_POST = $this->original_superglobals['post'];
        if ( $this->original_superglobals['request_method_set'] ) {
            $_SERVER['REQUEST_METHOD'] = $this->original_superglobals['request_method'];
        } else {
            unset( $_SERVER['REQUEST_METHOD'] );
        }

        if ( $this->original_globals['racketmanager_set'] ) {
            $GLOBALS['racketmanager'] = $this->original_globals['racketmanager'];
        } else {
            unset( $GLOBALS['racketmanager'] );
        }
        if ( $this->original_globals['capability_set'] ) {
            $GLOBALS['wp_stubs_current_user_can'] = $this->original_globals['capability'];
        } else {
            unset( $GLOBALS['wp_stubs_current_user_can'] );
        }

        parent::tearDown();
    }

    public function test_display_player_page_get_renders_view_model(): void {
        $_GET['player_id'] = '10';
        $_GET['club_id']   = '2';

        $player = Player_Factory::from_object( (object) array(
            'id'           => 10,
            'firstname'    => 'John',
            'surname'      => 'Doe',
            'display_name' => 'John Doe',
            'gender'       => 'M',
            'btm'          => '123456',
            'email'        => 'john@example.com',
            'contactno'    => '0123456789',
            'locked'       => true,
        ) );

        $club = new Club( (object) array( 'id' => 2, 'name' => 'Test Club' ) );

        $this->player_service->expects( self::once() )
            ->method( 'get_player' )
            ->with( 10 )
            ->willReturn( $player );

        $this->club_service->expects( self::once() )
            ->method( 'get_club' )
            ->with( 2 )
            ->willReturn( $club );

        $this->flash_store->expects( self::once() )
            ->method( 'pop' )
            ->willReturn( array(
                'flash_data' => array(
                    'errors'    => array( 'firstname' => 'First name error' ),
                    'form_data' => array(
                        'firstname' => 'Johnny',
                    ),
                ),
            ) );

        $captured_vm = null;
        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/players/show-player',
                self::callback( function( $vm ) use ( &$captured_vm ) {
                    $captured_vm = $vm;
                    return $vm instanceof Player_Page_View_Model;
                } )
            );

        $this->screen->display_player_page();

        self::assertInstanceOf( Player_Page_View_Model::class, $captured_vm );
        self::assertSame( 10, $captured_vm->player_id );
        self::assertSame( 2, $captured_vm->club_id );
        self::assertSame( 'Johnny', $captured_vm->player->firstname );
        self::assertSame( 'Doe', $captured_vm->player->surname );
        self::assertSame( '', $captured_vm->player->locked );
        self::assertFalse( $captured_vm->form_valid );
        self::assertTrue( $captured_vm->errors->has( 'firstname' ) );
    }

    public function test_display_player_page_not_found_sets_message(): void {
        $_GET['player_id'] = '999';

        $this->player_service->expects( self::once() )
            ->method( 'get_player' )
            ->with( 999 )
            ->willThrowException( new Player_Not_Found_Exception( 'Player 999 not found' ) );

        $this->renderer->expects( self::never() )->method( 'render' );

        ob_start();
        $this->screen->display_player_page();
        $output = ob_get_clean();

        self::assertStringContainsString( 'Player 999 not found', $output );
    }

    public function test_display_player_page_post_dispatches_action_and_delivers_prg(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['view']              = 'player';
        $_POST['updatePlayer']     = '1';
        $_POST['playerId']         = '10';
        $_POST['firstname']        = 'John';

        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->player_service->expects( self::once() )
            ->method( 'amend_player_details' )
            ->willReturn( $this->createMock( Player::class ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( self::callback( function( string $message ): bool {
                return 'Player updated' === $message;
            } ) );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function( string $url ): bool {
                return str_contains( $url, 'page=racketmanager-players' )
                    && str_contains( $url, 'view=player' )
                    && str_contains( $url, 'player_id=10' );
            } ) );

        $this->screen->display_player_page();
    }

    public function test_display_errors_page_get_renders_view_model(): void {
        $_GET['status'] = 'no_player';

        $errors_list = array(
            (object) array( 'id' => 1, 'player_id' => 10, 'player_name' => 'John', 'btm' => '123', 'message' => 'No player' ),
        );

        $this->player_service->expects( self::once() )
            ->method( 'get_player_errors' )
            ->with( 'no_player' )
            ->willReturn( $errors_list );

        $this->flash_store->expects( self::once() )
            ->method( 'pop' )
            ->willReturn( array() );

        $captured_vm = null;
        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/players/show-errors',
                self::callback( function( $vm ) use ( &$captured_vm ) {
                    $captured_vm = $vm;
                    return $vm instanceof Player_Errors_Page_View_Model;
                } )
            );

        $this->screen->display_errors_page();

        self::assertInstanceOf( Player_Errors_Page_View_Model::class, $captured_vm );
        self::assertSame( 'no_player', $captured_vm->status );
        self::assertCount( 1, $captured_vm->player_errors );
    }

    public function test_display_errors_page_post_dispatches_action_and_delivers_prg(): void {
        $_SERVER['REQUEST_METHOD']     = 'POST';
        $_GET['view']                  = 'errors';
        $_POST['doPlayerErrorBulk']    = '1';
        $_POST['action']               = 'delete';
        $_POST['playerError']          = array( '1' );

        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->player_service->expects( self::once() )
            ->method( 'remove_player_error' )
            ->with( 1 );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( self::callback( function( string $message ): bool {
                return str_contains( $message, 'Player error has been removed' );
            } ) );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function( string $url ): bool {
                return str_contains( $url, 'page=racketmanager-players' )
                    && str_contains( $url, 'view=errors' );
            } ) );

        $this->screen->display_errors_page();
    }

    public function test_display_requests_page_get_renders_view_model(): void {
        $_GET['status'] = 'outstanding';
        $_GET['club']   = '5';

        $clubs = array( (object) array( 'id' => 5, 'shortcode' => 'TC' ) );
        $this->club_service->expects( self::once() )
            ->method( 'get_clubs' )
            ->willReturn( $clubs );

        $requests = array(
            (object) array( 'registration_id' => 1, 'user_id' => 10, 'club_name' => 'TC', 'display_name' => 'John' ),
        );

        $this->registration_service->expects( self::once() )
            ->method( 'get_registered_players_list' )
            ->with( null, 'outstanding', 5 )
            ->willReturn( $requests );

        $captured_vm = null;
        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/players/show-requests',
                self::callback( function( $vm ) use ( &$captured_vm ) {
                    $captured_vm = $vm;
                    return $vm instanceof Player_Requests_Page_View_Model;
                } )
            );

        $this->screen->display_requests_page();

        self::assertInstanceOf( Player_Requests_Page_View_Model::class, $captured_vm );
        self::assertSame( 5, $captured_vm->club_id );
        self::assertSame( 'outstanding', $captured_vm->status );
        self::assertCount( 1, $captured_vm->player_requests );
    }

    public function test_display_requests_page_post_dispatches_action_and_delivers_prg(): void {
        $_SERVER['REQUEST_METHOD']   = 'POST';
        $_GET['view']                = 'requests';
        $_POST['doPlayerRequest']    = '1';
        $_POST['action']             = 'approve';
        $_POST['playerRequest']      = array( '3' );

        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->registration_service->expects( self::once() )
            ->method( 'approve_registration' )
            ->with( 3, self::anything() );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( self::callback( function( string $message ): bool {
                return str_contains( $message, 'Player has been approved' );
            } ) );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function( string $url ): bool {
                return str_contains( $url, 'page=racketmanager-players' )
                    && str_contains( $url, 'view=requests' );
            } ) );

        $this->screen->display_requests_page();
    }

    public function test_display_players_page_get_renders_view_model(): void {
        $players = array(
            (object) array( 'id' => 1, 'fullname' => 'John Doe' ),
        );

        $this->player_service->expects( self::once() )
            ->method( 'get_all_players' )
            ->willReturn( $players );

        $captured_vm = null;
        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/players/show-players',
                self::callback( function( $vm ) use ( &$captured_vm ) {
                    $captured_vm = $vm;
                    return $vm instanceof Players_Page_View_Model;
                } )
            );

        $this->screen->display_players_page();

        self::assertInstanceOf( Players_Page_View_Model::class, $captured_vm );
        self::assertCount( 1, $captured_vm->players );
        self::assertTrue( $captured_vm->form_valid );
    }

    public function test_display_players_page_search_with_empty_name_shows_notice(): void {
        $_GET['doPlayerSearch'] = '1';
        $_GET['name']           = '';

        $players = array(
            (object) array( 'id' => 1, 'fullname' => 'John Doe' ),
        );

        $this->player_service->expects( self::once() )
            ->method( 'get_all_players' )
            ->willReturn( $players );

        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with( 'admin/players/show-players', self::isInstanceOf( Players_Page_View_Model::class ) );

        $this->flash_store->expects( self::once() )
            ->method( 'pop' )
            ->willReturn( array() );

        ob_start();
        $this->screen->display_players_page();
        $output = ob_get_clean();

        self::assertStringContainsString( 'No search term specified', $output );
    }

    public function test_display_players_page_post_dispatches_action_and_delivers_prg(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['view']              = 'players';
        $_POST['addPlayer']        = '1';
        $_POST['firstname']        = 'Alice';
        $_POST['surname']          = 'Smith';

        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->player_service->expects( self::once() )
            ->method( 'add_new_player' )
            ->willReturn( $this->createMock( Player::class ) );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( self::callback( function( string $message ): bool {
                return 'Player added' === $message;
            } ) );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( function( string $url ): bool {
                return str_contains( $url, 'page=racketmanager-players' )
                    && str_contains( $url, 'view=players' );
            } ) );

        $this->screen->display_players_page();
    }

    public function test_display_players_section_delegates_to_errors_page(): void {
        $this->player_service->expects( self::once() )
            ->method( 'get_player_errors' )
            ->willReturn( array() );

        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with( 'admin/players/show-errors', self::isInstanceOf( Player_Errors_Page_View_Model::class ) );

        $this->screen->display_players_section();
    }

    public function test_handle_display_routes_correctly(): void {
        $this->player_service->expects( self::once() )
            ->method( 'get_player_errors' )
            ->willReturn( array() );

        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with( 'admin/players/show-errors', self::isInstanceOf( Player_Errors_Page_View_Model::class ) );

        $this->screen->handle_display( 'errors' );
    }

    public function test_permission_denied_stops_execution(): void {
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $this->renderer->expects( self::never() )->method( 'render' );

        ob_start();
        $this->screen->display_player_page();
        $output = ob_get_clean();

        self::assertNotEmpty( $output );
    }
}
