<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Portal;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Auth_Guard;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Page_Router;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Screen_Presenter;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Section_Config;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Section_Registry;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Route_Mapper;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Url_Resolver;
use Racketmanager\Services\Container\Simple_Container;

final class Portal_Page_Router_Test extends TestCase {

    private Simple_Container $container;
    private Portal_Section_Registry $registry;
    private Manage_Route_Mapper $route_mapper;
    private Manage_Url_Resolver $url_resolver;

    protected function setUp(): void {
        parent::setUp();
        $this->container    = new Simple_Container();
        $this->registry    = new Portal_Section_Registry();
        $this->route_mapper = new Manage_Route_Mapper();
        $this->url_resolver = new Manage_Url_Resolver( $this->route_mapper, force_portal_context: true );
    }

    private function create_router(
        Query_Vars_Interface $query_vars,
        Portal_Auth_Guard $auth_guard,
        Portal_Screen_Presenter $screen_presenter,
    ): Portal_Page_Router {
        return new Portal_Page_Router(
            $this->container,
            $this->registry,
            $this->route_mapper,
            $this->url_resolver,
            $auth_guard,
            $screen_presenter,
            $query_vars,
            auto_terminate: false
        );
    }

    public function test_ignores_non_portal_requests(): void {
        $query_vars = $this->createStub( Query_Vars_Interface::class );
        $query_vars->method( 'get' )->willReturn( '' );

        $auth_guard = $this->createMock( Portal_Auth_Guard::class );
        $auth_guard->expects( $this->never() )->method( 'is_authenticated' );

        $presenter = $this->createMock( Portal_Screen_Presenter::class );
        $presenter->expects( $this->never() )->method( 'render_layout' );

        $router = $this->create_router( $query_vars, $auth_guard, $presenter );
        $router->handle_template_redirect();

        $this->assertFalse( $router->is_portal_request() );
    }

    public function test_redirects_unauthenticated_request_to_login(): void {
        $query_vars = $this->createStub( Query_Vars_Interface::class );
        $query_vars->method( 'get' )->willReturnCallback(
            fn( string $key ) => 'rm_manage' === $key ? '1' : ''
        );

        $auth_guard = $this->createStub( Portal_Auth_Guard::class );
        $auth_guard->method( 'is_authenticated' )->willReturn( false );
        $auth_guard->method( 'get_login_url' )->willReturn( 'https://example.test/wp-login.php?redirect_to=manage' );

        $presenter = $this->createStub( Portal_Screen_Presenter::class );

        $router = $this->create_router( $query_vars, $auth_guard, $presenter );
        $router->handle_template_redirect();

        $this->assertSame(
            'https://example.test/wp-login.php?redirect_to=manage',
            $GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? null
        );
    }

    public function test_handles_unknown_section_with_404(): void {
        $query_vars = $this->createStub( Query_Vars_Interface::class );
        $query_vars->method( 'get' )->willReturnCallback(
            fn( string $key ) => match ( $key ) {
                'rm_manage'  => '1',
                'rm_section' => 'invalid-section',
                default      => '',
            }
        );

        $auth_guard = $this->createStub( Portal_Auth_Guard::class );
        $auth_guard->method( 'is_authenticated' )->willReturn( true );

        $presenter = $this->createMock( Portal_Screen_Presenter::class );
        $presenter->expects( $this->never() )->method( 'render_layout' );

        $router = $this->create_router( $query_vars, $auth_guard, $presenter );
        $router->handle_template_redirect();

        $this->assertSame( 404, $GLOBALS['wp_stubs_last_status_header'] ?? null );
    }

    public function test_presents_unauthorized_when_capability_missing(): void {
        $query_vars = $this->createStub( Query_Vars_Interface::class );
        $query_vars->method( 'get' )->willReturnCallback(
            fn( string $key ) => match ( $key ) {
                'rm_manage'  => '1',
                'rm_section' => 'settings',
                default      => '',
            }
        );

        $auth_guard = $this->createStub( Portal_Auth_Guard::class );
        $auth_guard->method( 'is_authenticated' )->willReturn( true );
        $auth_guard->method( 'user_can' )->willReturn( false );

        $presenter = $this->createMock( Portal_Screen_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'present_unauthorized' )
            ->with( 'settings' );

        $router = $this->create_router( $query_vars, $auth_guard, $presenter );
        $router->handle_template_redirect();

        $this->assertSame( 403, $GLOBALS['wp_stubs_last_status_header'] ?? null );
    }

    public function test_seamlessly_redirects_unported_section_to_wp_admin(): void {
        $this->registry->set_ported( 'seasons', false );

        $query_vars = $this->createStub( Query_Vars_Interface::class );
        $query_vars->method( 'get' )->willReturnCallback(
            fn( string $key ) => match ( $key ) {
                'rm_manage'  => '1',
                'rm_section' => 'seasons',
                'rm_view'    => 'seasons',
                default      => '',
            }
        );

        $_GET['season_id'] = '42';

        $auth_guard = $this->createStub( Portal_Auth_Guard::class );
        $auth_guard->method( 'is_authenticated' )->willReturn( true );
        $auth_guard->method( 'user_can' )->willReturn( true );

        $presenter = $this->createStub( Portal_Screen_Presenter::class );

        $router = $this->create_router( $query_vars, $auth_guard, $presenter );
        $router->handle_template_redirect();

        $expected_redirect = admin_url( 'admin.php?page=racketmanager-seasons&view=seasons&season_id=42' );
        $this->assertSame(
            $expected_redirect,
            $GLOBALS['wp_stubs_last_redirect']['location'] ?? null
        );
        $this->assertSame( 302, $GLOBALS['wp_stubs_last_redirect']['status'] ?? null );

        unset( $_GET['season_id'] );
    }

    public function test_dispatches_ported_section_to_screen(): void {
        $this->registry->set_ported( 'clubs', true );

        $query_vars = $this->createStub( Query_Vars_Interface::class );
        $query_vars->method( 'get' )->willReturnCallback(
            fn( string $key ) => match ( $key ) {
                'rm_manage'  => '1',
                'rm_section' => 'clubs',
                'rm_view'    => 'clubs',
                default      => '',
            }
        );

        $auth_guard = $this->createStub( Portal_Auth_Guard::class );
        $auth_guard->method( 'is_authenticated' )->willReturn( true );
        $auth_guard->method( 'user_can' )->willReturn( true );

        $fake_screen = new class() {
            public bool $displayed = false;
            public function handle_display( string $view ): void {
                $this->displayed = true;
                echo '<div>Clubs Content</div>';
            }
        };

        $config = $this->registry->get( 'clubs' );
        $this->assertNotNull( $config );
        $this->container->set( $config->screen_class, fn() => $fake_screen );

        $presenter = $this->createMock( Portal_Screen_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render_layout' )
            ->with(
                'Clubs',
                '<div>Clubs Content</div>',
                'clubs'
            );

        $router = $this->create_router( $query_vars, $auth_guard, $presenter );
        $router->handle_template_redirect();

        $this->assertTrue( $fake_screen->displayed );
    }
}
