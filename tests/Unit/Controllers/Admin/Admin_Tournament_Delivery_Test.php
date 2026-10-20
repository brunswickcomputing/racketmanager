<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Tournament;
use Racketmanager\Admin\Controllers\Tournament_Information_Admin_Controller;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Information_Page_View_Model;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Admin\Tournament\Tournament_Information_Action_Dispatcher;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionClass;

interface Admin_Draw_Controller_Mock_Interface {
    public function draw_page( array $query, array $post ): array;
}

/**
 * Demonstrates the POST (guard -> dispatch -> flash -> PRG redirect) / GET (flash -> view model -> render)
 * separation of the Admin_Tournament screen seam using the real controller, dispatcher and delivery adapters.
 */
#[AllowMockObjectsWithoutExpectations]
final class Admin_Tournament_Delivery_Test extends TestCase {
    use Creates_Admin_Delivery_Container;
    use Manages_Wordpress_Globals;

    private Simple_Container $container;
    private Admin_Redirector_Interface $redirector;
    private View_Renderer_Interface $renderer;
    private Admin_Flash_Message_Store $flash_store;
    private Action_Guard_Interface $guard;
    private Tournament_Service $tournament_service;
    private Admin_Tournament $screen;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->redirector         = $this->createMock( Admin_Redirector_Interface::class );
        $this->renderer           = $this->createMock( View_Renderer_Interface::class );
        $this->flash_store        = $this->createMock( Admin_Flash_Message_Store::class );
        $this->guard              = $this->createMock( Action_Guard_Interface::class );
        $this->tournament_service = $this->createMock( Tournament_Service::class );

        $message_service = new Admin_Message_Service( $this->flash_store );

        $this->container = $this->create_admin_delivery_container(
            services: array(
                'tournament_service'                      => $this->tournament_service,
                'tournament_information_admin_controller' => new Tournament_Information_Admin_Controller(
                    $this->tournament_service,
                    new Tournament_Information_Action_Dispatcher( $this->tournament_service, $this->guard ),
                    $this->guard
                ),
            ),
            guard: $this->guard,
            redirector: $this->redirector,
            renderer: $this->renderer,
            flash_store: $this->flash_store,
        );

        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->container = $this->container;

        $this->screen = new Admin_Tournament(
            $racketmanager,
            $this->renderer,
            $message_service,
            new Admin_Action_Adapter( $message_service, $this->redirector ),
            new Admin_Screen_Presenter( $this->renderer, $message_service )
        );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function run_screen( string $view ): string {
        ob_start();
        try {
            $this->screen->handle_display( $view );
        } finally {
            $output = (string) ob_get_clean();
        }

        return $output;
    }

    public function test_post_mutation_redirects_with_flash_and_never_renders(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['tournament']        = '7';
        $_POST['notifyFinalists']  = '1';

        $this->guard->expects( self::once() )->method( 'assert_capability' )->with( 'edit_teams' );
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_tournament-information', 'edit_teams' );
        $this->tournament_service->expects( self::once() )->method( 'notify_finalists_for_tournament' )->with( 7 )->willReturn( true );
        $this->flash_store->expects( self::once() )->method( 'set' )->with( 'Finalists notified', false );
        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::callback( static fn( string $url ): bool => str_contains( $url, 'view=information' ) && str_contains( $url, 'tournament=7' ) ) );
        $this->renderer->expects( self::never() )->method( 'render' );

        $output = $this->run_screen( 'information' );

        self::assertSame( '', $output, 'A mutation request must not render the screen as well as redirecting.' );
    }

    public function test_post_with_invalid_nonce_neither_mutates_nor_redirects(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['tournament']        = '7';
        $_POST['notifyFinalists']  = '1';

        $this->guard->method( 'assert_allowed' )->willThrowException( new Invalid_Status_Exception( 'Security token invalid' ) );
        $this->tournament_service->expects( self::never() )->method( 'notify_finalists_for_tournament' );
        $this->flash_store->expects( self::never() )->method( 'set' );
        $this->redirector->expects( self::never() )->method( 'redirect' );
        $this->renderer->expects( self::never() )->method( 'render' );

        $output = $this->run_screen( 'information' );

        self::assertStringContainsString( 'Security token invalid', $output );
    }

    public function test_request_without_capability_is_refused_before_any_work(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['tournament']        = '7';

        $this->guard->method( 'assert_capability' )->willThrowException( new Invalid_Status_Exception( 'No permission' ) );
        $this->tournament_service->expects( self::never() )->method( 'get_tournament' );
        $this->redirector->expects( self::never() )->method( 'redirect' );
        $this->renderer->expects( self::never() )->method( 'render' );

        $output = $this->run_screen( 'information' );

        self::assertStringContainsString( 'No permission', $output );
    }

    public function test_get_renders_view_model_after_showing_flash_and_does_not_mutate(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['tournament']        = '7';

        $tournament = $this->createStub( Tournament::class );
        $this->tournament_service->expects( self::once() )->method( 'get_tournament' )->with( 7 )->willReturn( $tournament );
        $this->flash_store->expects( self::once() )->method( 'pop' )->willReturn( array( 'message' => 'Information updated', 'message_type' => false ) );

        $this->guard->expects( self::never() )->method( 'assert_allowed' );
        $this->tournament_service->expects( self::never() )->method( 'notify_finalists_for_tournament' );
        $this->redirector->expects( self::never() )->method( 'redirect' );
        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/tournament/information',
                self::callback( static fn( object $vm ): bool => $vm instanceof Tournament_Information_Page_View_Model && $vm->tournament === $tournament )
            );

        $output = $this->run_screen( 'information' );

        self::assertStringContainsString( 'Information updated', $output );
    }

    public function test_post_only_screen_ignores_redirect_on_get_and_renders(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $vm         = ( new ReflectionClass( Tournament_Draw_Page_View_Model::class ) )->newInstanceWithoutConstructor();
        $controller = $this->createStub( Admin_Draw_Controller_Mock_Interface::class );
        $controller->method( 'draw_page' )->willReturn( array( 'view_model' => $vm, 'redirect' => 'https://example.test/admin.php' ) );
        $this->container->set( 'tournament_draw_admin_controller', $controller );

        $this->redirector->expects( self::never() )->method( 'redirect' );
        $this->renderer->expects( self::once() )->method( 'render' )->with( 'admin/tournament/draw', $vm );

        $this->run_screen( 'draw' );
    }

    public function test_post_only_screen_redirects_on_post_and_does_not_render(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $vm         = ( new ReflectionClass( Tournament_Draw_Page_View_Model::class ) )->newInstanceWithoutConstructor();
        $controller = $this->createStub( Admin_Draw_Controller_Mock_Interface::class );
        $controller->method( 'draw_page' )->willReturn( array( 'view_model' => $vm, 'redirect' => 'https://example.test/admin.php', 'message' => 'Draw saved' ) );
        $this->container->set( 'tournament_draw_admin_controller', $controller );

        $this->flash_store->expects( self::once() )->method( 'set' )->with( 'Draw saved', false );
        $this->redirector->expects( self::once() )->method( 'redirect' )->with( 'https://example.test/admin.php' );
        $this->renderer->expects( self::never() )->method( 'render' );

        $this->run_screen( 'draw' );
    }
}
