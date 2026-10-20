<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Player;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Player_Errors_Page_View_Model;
use Racketmanager\Admin\View_Models\Player_Page_View_Model;
use Racketmanager\Admin\View_Models\Player_Requests_Page_View_Model;
use Racketmanager\Admin\View_Models\Players_Page_View_Model;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Player\Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Player\Player_Error_Action_Dispatcher;
use Racketmanager\Services\Admin\Player\Player_Request_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Player_Test_Factory;

#[AllowMockObjectsWithoutExpectations]
final class Admin_Player_Delivery_Test extends TestCase {
    use Creates_Admin_Delivery_Container;
    use Manages_Wordpress_Globals;

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

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_current_user_can'] = true;

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
        $actions         = new Admin_Action_Adapter( $message_service, $this->redirector );
        $screens         = new Admin_Screen_Presenter( $this->renderer, $message_service );

        $this->container = $this->create_admin_delivery_container(
            services: array(
                'admin_message_service'            => $message_service,
                'admin_action_adapter'            => $actions,
                'admin_screen_presenter'          => $screens,
                'player_service'                  => $this->player_service,
                'club_service'                    => $this->club_service,
                'registration_service'            => $this->registration_service,
                'player_action_dispatcher'         => $this->player_action_dispatcher,
                'player_error_action_dispatcher'   => $this->player_error_action_dispatcher,
                'player_request_action_dispatcher' => $this->player_request_action_dispatcher,
            ),
            guard: $this->guard,
            redirector: $this->redirector,
            renderer: $this->renderer,
            flash_store: $this->flash_store,
        );

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
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_display_player_page_get_renders_view_model(): void {
        $_GET['player_id'] = '10';
        $_GET['club_id']   = '2';

        $player = Player_Test_Factory::create_sparse( [
            'id'           => 10,
            'display_name' => 'John Doe',
            'btm'          => '123456',
            'firstname'    => 'John',
            'surname'      => 'Doe',
            'email'        => 'john@example.com',
            'gender'       => 'M',
            'contactno'    => '0123456789',
            'locked'       => true,
        ] );

        $club = Club_Test_Factory::create( id: 2, name: 'Test Club' );

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
        self::assertSame( 'Errors', $captured_vm->tab_title );
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
        self::assertSame( 'Requests', $captured_vm->tab_title );
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
        self::assertSame( 'Players', $captured_vm->tab_title );
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
