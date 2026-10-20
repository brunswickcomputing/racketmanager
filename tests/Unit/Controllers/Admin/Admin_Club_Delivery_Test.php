<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Club;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Exceptions\Invalid_Status_Exception;
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

        $this->screen = new Admin_Club( $racketmanager );
    }

    protected function tearDown(): void {
        $this->restore_globals();
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

    public function test_display_clubs_page_renders_clubs_table(): void {
        $GLOBALS['wp_stubs_current_user_can'] = true;
        $_SERVER['REQUEST_METHOD']            = 'GET';
        $_POST                                = array();

        $club1 = Club_Test_Factory::create( id: 1, name: 'Leigh LTC', shortcode: 'LLTC' );
        $club2 = Club_Test_Factory::create( id: 2, name: 'Kent Elms', shortcode: 'KE' );

        $this->club_service->expects( self::once() )
            ->method( 'get_clubs' )
            ->willReturn( array( $club1, $club2 ) );

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
}
