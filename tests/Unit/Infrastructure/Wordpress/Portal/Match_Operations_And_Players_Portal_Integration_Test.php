<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Portal;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Player;
use Racketmanager\Admin\Admin_Result;
use Racketmanager\Admin\Controllers\Admin_Redirect_Url_Builder;
use Racketmanager\Application\Admin\Ports\Flash_Message_Store_Interface;
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Application\Ports\View_Renderer_Interface;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Auth_Guard;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Page_Router;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Screen_Presenter;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Section_Registry;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Route_Mapper;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Url_Resolver;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Container\Simple_Container;

final class Match_Operations_And_Players_Portal_Integration_Test extends TestCase {

    private Portal_Section_Registry $registry;
    private Manage_Route_Mapper $route_mapper;
    private Manage_Url_Resolver $resolver;

    protected function setUp(): void {
        parent::setUp();
        $this->registry     = new Portal_Section_Registry();
        $this->route_mapper = new Manage_Route_Mapper();
        $this->resolver     = new Manage_Url_Resolver( $this->route_mapper, force_portal_context: true );
        Admin_Redirect_Url_Builder::set_resolver( $this->resolver );

        unset(
            $GLOBALS['wp_stubs_last_redirect'],
            $GLOBALS['wp_stubs_last_status_header'],
            $GLOBALS['wp_stubs_last_template']
        );
    }

    protected function tearDown(): void {
        Admin_Redirect_Url_Builder::set_resolver( null );
        unset(
            $GLOBALS['wp_stubs_last_redirect'],
            $GLOBALS['wp_stubs_last_status_header'],
            $GLOBALS['wp_stubs_last_template']
        );
        parent::tearDown();
    }

    private function create_router(
        Query_Vars_Interface $query_vars,
        Portal_Auth_Guard $auth_guard,
        Portal_Screen_Presenter $presenter,
        ?Simple_Container $container = null
    ): Portal_Page_Router {
        return new Portal_Page_Router(
            $container ?? new Simple_Container(),
            $this->registry,
            $this->route_mapper,
            $this->resolver,
            $auth_guard,
            $presenter,
            $query_vars,
            auto_terminate: false
        );
    }

    private function create_presenter( ?Portal_Auth_Guard $auth_guard = null ): Portal_Screen_Presenter {
        return new Portal_Screen_Presenter(
            $this->createStub( View_Renderer_Interface::class ),
            new Admin_Message_Service( $this->createStub( Flash_Message_Store_Interface::class ) ),
            $this->registry,
            $this->resolver,
            $auth_guard ?? $this->createStub( Portal_Auth_Guard::class )
        );
    }

    public function test_match_operations_and_players_sections_are_enabled_in_section_registry(): void {
        $this->assertTrue( $this->registry->is_ported( 'results' ) );
        $this->assertTrue( $this->registry->is_ported( 'players' ) );

        $ported = $this->registry->get_ported_sections();
        $this->assertArrayHasKey( 'results', $ported );
        $this->assertArrayHasKey( 'players', $ported );
    }

    public function test_dispatches_results_to_admin_result_screen(): void {
        $query_vars = $this->createStub( Query_Vars_Interface::class );
        $query_vars->method( 'get' )->willReturnCallback(
            fn( string $key ) => match ( $key ) {
                'rm_manage'  => '1',
                'rm_section' => 'results',
                'rm_view'    => 'results',
                default      => '',
            }
        );

        $auth_guard = $this->createStub( Portal_Auth_Guard::class );
        $auth_guard->method( 'is_authenticated' )->willReturn( true );
        $auth_guard->method( 'user_can' )->willReturn( true );

        $invoked_view = null;
        $dummy_screen = new class( $invoked_view ) {
            public function __construct( public ?string &$invoked_view ) {}
            public function handle_display( ?string $view = null ): void {
                $this->invoked_view = $view;
            }
        };

        $container = new Simple_Container();
        $container->set( Admin_Result::class, fn() => $dummy_screen );

        $presenter = $this->create_presenter( $auth_guard );
        $router    = $this->create_router( $query_vars, $auth_guard, $presenter, $container );

        $router->handle_template_redirect();

        $this->assertSame( 'results', $invoked_view );
    }

    public function test_dispatches_players_subviews_to_admin_player_screen(): void {
        $subviews = [ 'players', 'player', 'errors', 'requests' ];

        foreach ( $subviews as $subview ) {
            $query_vars = $this->createStub( Query_Vars_Interface::class );
            $query_vars->method( 'get' )->willReturnCallback(
                fn( string $key ) => match ( $key ) {
                    'rm_manage'  => '1',
                    'rm_section' => 'players',
                    'rm_view'    => $subview,
                    default      => '',
                }
            );

            $auth_guard = $this->createStub( Portal_Auth_Guard::class );
            $auth_guard->method( 'is_authenticated' )->willReturn( true );
            $auth_guard->method( 'user_can' )->willReturn( true );

            $invoked_view = null;
            $dummy_screen = new class( $invoked_view ) {
                public function __construct( public ?string &$invoked_view ) {}
                public function handle_display( ?string $view = null ): void {
                    $this->invoked_view = $view;
                }
            };

            $container = new Simple_Container();
            $container->set( Admin_Player::class, fn() => $dummy_screen );

            $presenter = $this->create_presenter( $auth_guard );
            $router    = $this->create_router( $query_vars, $auth_guard, $presenter, $container );

            $router->handle_template_redirect();

            $this->assertSame( $subview, $invoked_view, "Failed asserting view {$subview} dispatched to Admin_Player" );
        }
    }

    public function test_renders_portal_navigation_with_results_and_players_links(): void {
        $auth_guard = $this->createStub( Portal_Auth_Guard::class );
        $auth_guard->method( 'user_can' )->willReturn( true );

        $presenter = $this->create_presenter( $auth_guard );
        $nav       = $presenter->build_navigation( 'results', 'results' );

        $items_by_slug = [];
        foreach ( $nav->items as $item ) {
            $items_by_slug[ $item->slug ] = $item;
        }

        $this->assertArrayHasKey( 'results', $items_by_slug );
        $this->assertTrue( $items_by_slug['results']->is_active );
        $this->assertTrue( $items_by_slug['results']->is_ported );
        $this->assertSame( home_url( '/manage/results' ), $items_by_slug['results']->url );

        $this->assertArrayHasKey( 'players', $items_by_slug );
        $this->assertFalse( $items_by_slug['players']->is_active );
        $this->assertTrue( $items_by_slug['players']->is_ported );
        $this->assertSame( home_url( '/manage/players' ), $items_by_slug['players']->url );
    }

    public function test_unauthenticated_request_to_results_redirects_to_login(): void {
        $query_vars = $this->createStub( Query_Vars_Interface::class );
        $query_vars->method( 'get' )->willReturnCallback(
            fn( string $key ) => match ( $key ) {
                'rm_manage'  => '1',
                'rm_section' => 'results',
                'rm_view'    => 'results',
                default      => '',
            }
        );

        $auth_guard = $this->createStub( Portal_Auth_Guard::class );
        $auth_guard->method( 'is_authenticated' )->willReturn( false );
        $auth_guard->method( 'get_login_url' )->willReturn( 'https://example.test/wp-login.php?redirect_to=manage' );

        $presenter = $this->createStub( Portal_Screen_Presenter::class );
        $router    = $this->create_router( $query_vars, $auth_guard, $presenter );

        $router->handle_template_redirect();

        $this->assertSame(
            'https://example.test/wp-login.php?redirect_to=manage',
            $GLOBALS['wp_stubs_last_safe_redirect']['location'] ?? null
        );
        $this->assertSame( 302, $GLOBALS['wp_stubs_last_safe_redirect']['status'] ?? null );
    }

    public function test_unauthorized_user_receives_403_status_in_portal_shell(): void {
        $query_vars = $this->createStub( Query_Vars_Interface::class );
        $query_vars->method( 'get' )->willReturnCallback(
            fn( string $key ) => match ( $key ) {
                'rm_manage'  => '1',
                'rm_section' => 'players',
                'rm_view'    => 'players',
                default      => '',
            }
        );

        $auth_guard = $this->createStub( Portal_Auth_Guard::class );
        $auth_guard->method( 'is_authenticated' )->willReturn( true );
        $auth_guard->method( 'user_can' )->willReturn( false );

        $presenter = $this->createMock( Portal_Screen_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'present_unauthorized' )
            ->with( 'players' );

        $router = $this->create_router( $query_vars, $auth_guard, $presenter );
        $router->handle_template_redirect();

        $this->assertSame( 403, $GLOBALS['wp_stubs_last_status_header'] ?? null );
    }
}
