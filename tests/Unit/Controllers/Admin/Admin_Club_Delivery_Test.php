<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Club;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Player_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Player_Test_Factory;

#[AllowMockObjectsWithoutExpectations]
final class Admin_Club_Delivery_Test extends TestCase {
    use Creates_Admin_Delivery_Container;
    use Manages_Wordpress_Globals;

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

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->redirector           = $this->createMock( Admin_Redirector_Interface::class );
        $this->renderer             = $this->createMock( View_Renderer_Interface::class );
        $this->flash_store          = $this->createMock( Admin_Flash_Message_Store::class );
        $this->guard                = $this->createMock( Action_Guard_Interface::class );
        $this->club_service         = $this->createMock( Club_Service::class );
        $this->player_service       = $this->createMock( Player_Service::class );
        $this->registration_service = $this->createMock( Registration_Service::class );
        $this->team_service         = $this->createMock( Team_Service::class );

        $this->container = $this->create_admin_delivery_container(
            services: array(
                'club_service'         => $this->club_service,
                'player_service'       => $this->player_service,
                'registration_service' => $this->registration_service,
                'team_service'         => $this->team_service,
            ),
            guard: $this->guard,
            redirector: $this->redirector,
            renderer: $this->renderer,
            flash_store: $this->flash_store,
        );

        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->container = $this->container;

        $this->screen = $this->container->get( 'admin_club_screen' );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_player_screen_resolves_with_an_application_instance(): void {
        self::assertInstanceOf( \Racketmanager\Admin\Admin_Player::class, $this->container->get( 'admin_player_screen' ) );
    }

    public function test_display_player_page_delegates_to_player_screen_and_renders_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array(
            'player_id' => '10',
            'club_id'   => '2',
        );
        $_POST                     = array();

        $player = Player_Test_Factory::create_sparse( array(
            'id'           => 10,
            'display_name' => 'John Doe',
            'firstname'    => 'John',
            'surname'      => 'Doe',
            'wtn'          => '10.5',
        ) );

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
            ->willReturn( array() );

        $captured_vm = null;
        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/players/show-player',
                self::callback( function( $vm ) use ( &$captured_vm ): bool {
                    $captured_vm = $vm;
                    return $vm instanceof Player_Page_View_Model;
                } )
            );

        $this->screen->handle_display( 'player' );

        self::assertInstanceOf( Player_Page_View_Model::class, $captured_vm );
        self::assertSame( 10, $captured_vm->player_id );
        self::assertSame( 2, $captured_vm->club_id );
        self::assertSame( $club, $captured_vm->club );
    }

    public function test_display_player_page_not_found_sets_message_and_never_renders(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array(
            'player_id' => '999',
            'club_id'   => '2',
        );
        $_POST                     = array();

        $this->player_service->expects( self::once() )
            ->method( 'get_player' )
            ->with( 999 )
            ->willThrowException( new Player_Not_Found_Exception( 'Player 999 not found' ) );

        $this->renderer->expects( self::never() )
            ->method( 'render' );

        ob_start();
        $this->screen->handle_display( 'player' );
        $output = ob_get_clean();

        self::assertStringContainsString( 'Player 999 not found', $output );
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

    public function test_display_clubs_page_renders_clubs_table(): void {
        $GLOBALS['wp_stubs_current_user_can'] = true;
        $_SERVER['REQUEST_METHOD']            = 'GET';
        $_POST                                = array();

        $club1 = Club_Test_Factory::create( id: 1, name: 'Leigh LTC', shortcode: 'LLTC' );
        $club2 = Club_Test_Factory::create( id: 2, name: 'Kent Elms', shortcode: 'KE' );

        $this->club_service->expects( self::once() )
            ->method( 'get_clubs' )
            ->willReturn( array( $club1, $club2 ) );

        $renderer = new \Racketmanager\Services\View\Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $this->container->set( 'admin_screen_presenter', fn( $c ) => new \Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter( $renderer, $c->get( 'admin_message_service' ) ) );
        $this->container->set( 'admin_club_screen', fn( $c ) => new Admin_Club( $c, $c->get( 'admin_message_service' ), $c->get( 'admin_action_adapter' ), $c->get( 'admin_screen_presenter' ) ) );
        $this->screen = $this->container->get( 'admin_club_screen' );

        $warnings = array();
        set_error_handler(
            static function ( int $severity, string $message ) use ( &$warnings ): bool {
                $warnings[] = $message;
                return true;
            }
        );
        ob_start();
        try {
            $this->screen->display_clubs_page();
        } finally {
            $output = (string) ob_get_clean();
            restore_error_handler();
        }

        $this->assertSame( array(), $warnings );
        $this->assertStringContainsString( 'Leigh LTC', $output );
        $this->assertStringContainsString( 'Kent Elms', $output );
        $this->assertStringContainsString( 'page=racketmanager-clubs&amp;view=club&amp;club_id=1', $output );
        $this->assertStringContainsString( 'page=racketmanager-clubs&amp;view=club&amp;club_id=2', $output );
    }

    public function test_display_team_page_renders_with_normalized_template_path(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'edit' => '10' );
        $_POST                     = array();

        $team = $this->createStub( \Racketmanager\Domain\Team::class );
        $this->team_service->expects( self::once() )
            ->method( 'get_team_by_id' )
            ->with( 10 )
            ->willReturn( $team );

        $this->club_service->expects( self::once() )
            ->method( 'get_clubs' )
            ->willReturn( array() );

        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/includes/teams/team',
                self::isInstanceOf( \Racketmanager\Admin\View_Models\Tournament_Team_Page_View_Model::class )
            );

        $this->screen->display_team_page();
    }
}
