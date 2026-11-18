<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Portal;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Finances;
use Racketmanager\Admin\Admin_Import;
use Racketmanager\Admin\Admin_Options;
use Racketmanager\Admin\Admin_Season;
use Racketmanager\Admin\Controllers\Admin_Redirect_Url_Builder;
use Racketmanager\Admin\View_Models\Documentation_Page_View_Model;
use Racketmanager\Admin\View_Models\Overview_Page_View_Model;
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

final class Administration_And_Final_Cutover_Portal_Integration_Test extends TestCase {

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

    private function create_presenter( ?Portal_Auth_Guard $auth_guard = null, ?View_Renderer_Interface $renderer = null ): Portal_Screen_Presenter {
        return new Portal_Screen_Presenter(
            $renderer ?? $this->createStub( View_Renderer_Interface::class ),
            new Admin_Message_Service( $this->createStub( Flash_Message_Store_Interface::class ) ),
            $this->registry,
            $this->resolver,
            $auth_guard ?? $this->createStub( Portal_Auth_Guard::class )
        );
    }

    public function test_all_thirteen_sections_are_enabled_in_section_registry(): void {
        $expected = [
            'overview',
            'competitions',
            'leagues',
            'cups',
            'tournaments',
            'clubs',
            'players',
            'results',
            'seasons',
            'finances',
            'settings',
            'import',
            'documentation',
        ];

        $this->assertCount( 13, $this->registry->all() );
        $this->assertSame( $expected, array_keys( $this->registry->all() ) );
        foreach ( $expected as $slug ) {
            $this->assertTrue( $this->registry->is_ported( $slug ), "Expected section {$slug} to be ported" );
        }

        $ported = $this->registry->get_ported_sections();
        $this->assertCount( 13, $ported );
    }

    public function test_dispatches_seasons_to_admin_season_screen(): void {
        $query_vars = $this->createStub( Query_Vars_Interface::class );
        $query_vars->method( 'get' )->willReturnCallback(
            fn( string $key ) => match ( $key ) {
                'rm_manage'  => '1',
                'rm_section' => 'seasons',
                'rm_view'    => 'seasons',
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
        $container->set( Admin_Season::class, fn() => $dummy_screen );

        $presenter = $this->create_presenter( $auth_guard );
        $router    = $this->create_router( $query_vars, $auth_guard, $presenter, $container );

        $router->handle_template_redirect();

        $this->assertSame( 'seasons', $invoked_view );
    }

    public function test_dispatches_finances_subviews_to_admin_finances_screen(): void {
        $subviews = [ 'finances', 'charges', 'charge', 'invoices', 'club-invoices', 'player-invoices', 'invoice' ];

        foreach ( $subviews as $subview ) {
            $query_vars = $this->createStub( Query_Vars_Interface::class );
            $query_vars->method( 'get' )->willReturnCallback(
                fn( string $key ) => match ( $key ) {
                    'rm_manage'  => '1',
                    'rm_section' => 'finances',
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
            $container->set( Admin_Finances::class, fn() => $dummy_screen );

            $presenter = $this->create_presenter( $auth_guard );
            $router    = $this->create_router( $query_vars, $auth_guard, $presenter, $container );

            $router->handle_template_redirect();

            $this->assertSame( $subview, $invoked_view, "Failed asserting view {$subview} dispatched to Admin_Finances" );
        }
    }

    public function test_dispatches_settings_to_admin_options_screen(): void {
        $query_vars = $this->createStub( Query_Vars_Interface::class );
        $query_vars->method( 'get' )->willReturnCallback(
            fn( string $key ) => match ( $key ) {
                'rm_manage'  => '1',
                'rm_section' => 'settings',
                'rm_view'    => 'settings',
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
        $container->set( Admin_Options::class, fn() => $dummy_screen );

        $presenter = $this->create_presenter( $auth_guard );
        $router    = $this->create_router( $query_vars, $auth_guard, $presenter, $container );

        $router->handle_template_redirect();

        $this->assertSame( 'settings', $invoked_view );
    }

    public function test_dispatches_import_to_admin_import_screen(): void {
        $query_vars = $this->createStub( Query_Vars_Interface::class );
        $query_vars->method( 'get' )->willReturnCallback(
            fn( string $key ) => match ( $key ) {
                'rm_manage'  => '1',
                'rm_section' => 'import',
                'rm_view'    => 'import',
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
        $container->set( Admin_Import::class, fn() => $dummy_screen );

        $presenter = $this->create_presenter( $auth_guard );
        $router    = $this->create_router( $query_vars, $auth_guard, $presenter, $container );

        $router->handle_template_redirect();

        $this->assertSame( 'import', $invoked_view );
    }

    public function test_dispatches_documentation_to_presenter_with_typed_view_model(): void {
        $query_vars = $this->createStub( Query_Vars_Interface::class );
        $query_vars->method( 'get' )->willReturnCallback(
            fn( string $key ) => match ( $key ) {
                'rm_manage'  => '1',
                'rm_section' => 'documentation',
                'rm_view'    => 'documentation',
                default      => '',
            }
        );

        $auth_guard = $this->createStub( Portal_Auth_Guard::class );
        $auth_guard->method( 'is_authenticated' )->willReturn( true );
        $auth_guard->method( 'user_can' )->willReturn( true );

        $rendered_template = null;
        $renderer          = $this->createStub( View_Renderer_Interface::class );
        $renderer->method( 'render_to_string' )->willReturnCallback(
            function ( string $template, object $vm ) use ( &$rendered_template ) {
                $rendered_template = $template;
                $this->assertInstanceOf( Documentation_Page_View_Model::class, $vm );
                return '<div>Documentation Content</div>';
            }
        );

        $presenter = $this->create_presenter( $auth_guard, $renderer );
        $router    = $this->create_router( $query_vars, $auth_guard, $presenter );

        $router->handle_template_redirect();

        $this->assertSame( 'admin/documentation', $rendered_template );
    }

    public function test_dispatches_overview_screen(): void {
        $query_vars = $this->createStub( Query_Vars_Interface::class );
        $query_vars->method( 'get' )->willReturnCallback(
            fn( string $key ) => match ( $key ) {
                'rm_manage'  => '1',
                'rm_section' => 'overview',
                'rm_view'    => 'overview',
                default      => '',
            }
        );

        $auth_guard = $this->createStub( Portal_Auth_Guard::class );
        $auth_guard->method( 'is_authenticated' )->willReturn( true );
        $auth_guard->method( 'user_can' )->willReturn( true );

        $rendered_template = null;
        $renderer          = $this->createStub( View_Renderer_Interface::class );
        $renderer->method( 'render_to_string' )->willReturnCallback(
            function ( string $template, object $vm ) use ( &$rendered_template ) {
                $rendered_template = $template;
                $this->assertInstanceOf( Overview_Page_View_Model::class, $vm );
                return '<div>Overview Content</div>';
            }
        );

        $presenter = $this->create_presenter( $auth_guard, $renderer );
        $router    = $this->create_router( $query_vars, $auth_guard, $presenter );

        $router->handle_template_redirect();

        $this->assertSame( 'portal/overview', $rendered_template );
    }

    public function test_portal_navigation_renders_all_twelve_navigation_sections_with_portal_routes(): void {
        $auth_guard = $this->createStub( Portal_Auth_Guard::class );
        $auth_guard->method( 'user_can' )->willReturn( true );

        $presenter = $this->create_presenter( $auth_guard );
        $nav       = $presenter->build_navigation( 'settings' );

        $items_by_slug = [];
        foreach ( $nav->items as $item ) {
            $items_by_slug[ $item->slug ] = $item;
        }

        $all_slugs = [
            'overview',
            'clubs',
            'leagues',
            'cups',
            'tournaments',
            'results',
            'players',
            'seasons',
            'finances',
            'settings',
            'import',
            'documentation',
        ];

        $this->assertCount( 12, $items_by_slug );

        foreach ( $all_slugs as $slug ) {
            $this->assertArrayHasKey( $slug, $items_by_slug );
            $this->assertTrue( $items_by_slug[ $slug ]->is_ported, "Section {$slug} should be ported" );
            $this->assertSame( home_url( '/manage/' . $slug ), $items_by_slug[ $slug ]->url );
        }

        $this->assertArrayNotHasKey( 'competitions', $items_by_slug );
        $this->assertTrue( $items_by_slug['settings']->is_active );
        $this->assertFalse( $items_by_slug['seasons']->is_active );
    }

    public function test_portal_navigation_filters_by_user_capabilities(): void {
        $auth_guard = $this->createStub( Portal_Auth_Guard::class );
        // Simulate user with racket_manager only (lacks racketmanager_settings and import_leagues)
        $auth_guard->method( 'user_can' )->willReturnCallback(
            fn( string $cap ) => match ( $cap ) {
                'racket_manager', 'view_leagues' => true,
                default                          => false,
            }
        );

        $presenter = $this->create_presenter( $auth_guard );
        $nav       = $presenter->build_navigation( 'clubs' );

        $items_by_slug = [];
        foreach ( $nav->items as $item ) {
            $items_by_slug[ $item->slug ] = $item;
        }

        $this->assertArrayHasKey( 'overview', $items_by_slug );
        $this->assertArrayHasKey( 'clubs', $items_by_slug );
        $this->assertArrayNotHasKey( 'competitions', $items_by_slug );
        $this->assertArrayHasKey( 'leagues', $items_by_slug );
        $this->assertArrayHasKey( 'cups', $items_by_slug );
        $this->assertArrayHasKey( 'tournaments', $items_by_slug );
        $this->assertArrayHasKey( 'results', $items_by_slug );
        $this->assertArrayHasKey( 'players', $items_by_slug );
        $this->assertArrayHasKey( 'finances', $items_by_slug );
        $this->assertArrayHasKey( 'documentation', $items_by_slug );

        // These require racketmanager_settings or import_leagues
        $this->assertArrayNotHasKey( 'seasons', $items_by_slug );
        $this->assertArrayNotHasKey( 'settings', $items_by_slug );
        $this->assertArrayNotHasKey( 'import', $items_by_slug );
    }

    public function test_redirect_url_builder_preserves_portal_routes_for_administration(): void {
        $season_redirect = Admin_Redirect_Url_Builder::season_screen();
        $this->assertSame( home_url( '/manage/seasons' ), $season_redirect );

        $finances_redirect = Admin_Redirect_Url_Builder::finances_screen(
            [ 'view' => 'charges', 'competition_id' => '5' ]
        );
        $this->assertSame( home_url( '/manage/finances/charges?competition_id=5' ), $finances_redirect );

        $options_redirect = Admin_Redirect_Url_Builder::options_screen(
            [ 'tab' => 'billing' ]
        );
        $this->assertSame( home_url( '/manage/settings?tab=billing' ), $options_redirect );

        $import_redirect = Admin_Redirect_Url_Builder::import_screen();
        $this->assertSame( home_url( '/manage/import' ), $import_redirect );
    }
}
