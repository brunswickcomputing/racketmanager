<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Portal;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Club;
use Racketmanager\Admin\Admin_Index;
use Racketmanager\Admin\Controllers\Admin_Redirect_Url_Builder;
use Racketmanager\Application\Admin\Ports\Flash_Message_Store_Interface;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Auth_Guard;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Page_Router;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Screen_Presenter;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Section_Registry;
use Racketmanager\Application\Ports\Manage_Url_Resolver_Interface;
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Application\Ports\View_Renderer_Interface;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Route_Mapper;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Url_Resolver;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Container\Simple_Container;

final class Clubs_And_Competitions_Portal_Integration_Test extends TestCase {

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

    public function test_clubs_and_competitions_are_enabled_in_section_registry(): void {
        $this->assertTrue( $this->registry->is_ported( 'clubs' ) );
        $this->assertTrue( $this->registry->is_ported( 'competitions' ) );
        $this->assertTrue( $this->registry->is_ported( 'leagues' ) );
        $this->assertTrue( $this->registry->is_ported( 'cups' ) );
        $this->assertTrue( $this->registry->is_ported( 'tournaments' ) );
        $this->assertTrue( $this->registry->is_ported( 'results' ) );
        $this->assertTrue( $this->registry->is_ported( 'players' ) );
        $this->assertTrue( $this->registry->is_ported( 'seasons' ) );
        $this->assertTrue( $this->registry->is_ported( 'finances' ) );

        $ported = $this->registry->get_ported_sections();
        $this->assertArrayHasKey( 'clubs', $ported );
        $this->assertArrayHasKey( 'competitions', $ported );
        $this->assertArrayHasKey( 'leagues', $ported );
        $this->assertArrayHasKey( 'cups', $ported );
        $this->assertArrayHasKey( 'tournaments', $ported );
        $this->assertArrayHasKey( 'results', $ported );
        $this->assertArrayHasKey( 'players', $ported );
        $this->assertArrayHasKey( 'seasons', $ported );
        $this->assertArrayHasKey( 'finances', $ported );
        $this->assertCount( 12, $ported );
    }

    public function test_dispatches_clubs_subviews_to_admin_club_screen(): void {
        $subviews = [ 'clubs', 'club', 'players', 'teams', 'team', 'roles' ];

        foreach ( $subviews as $subview ) {
            $query_vars = $this->createStub( Query_Vars_Interface::class );
            $query_vars->method( 'get' )->willReturnCallback(
                fn( string $key ) => match ( $key ) {
                    'rm_manage'  => '1',
                    'rm_section' => 'clubs',
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
            $container->set( Admin_Club::class, fn() => $dummy_screen );

            $presenter = $this->createStub( Portal_Screen_Presenter::class );

            $router = $this->create_router( $query_vars, $auth_guard, $presenter, $container );
            $router->handle_template_redirect();

            $this->assertSame( $subview, $invoked_view );
        }
    }

    public function test_dispatches_competitions_to_admin_index_screen(): void {
        $query_vars = $this->createStub( Query_Vars_Interface::class );
        $query_vars->method( 'get' )->willReturnCallback(
            fn( string $key ) => match ( $key ) {
                'rm_manage'  => '1',
                'rm_section' => 'competitions',
                'rm_view'    => '',
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
        $container->set( Admin_Index::class, fn() => $dummy_screen );

        $presenter = $this->createStub( Portal_Screen_Presenter::class );

        $router = $this->create_router( $query_vars, $auth_guard, $presenter, $container );
        $router->handle_template_redirect();

        $this->assertSame( 'competitions', $invoked_view );
    }

    public function test_requires_racket_manager_capability_for_clubs_and_competitions(): void {
        $query_vars = $this->createStub( Query_Vars_Interface::class );
        $query_vars->method( 'get' )->willReturnCallback(
            fn( string $key ) => match ( $key ) {
                'rm_manage'  => '1',
                'rm_section' => 'clubs',
                'rm_view'    => 'club',
                default      => '',
            }
        );

        $auth_guard = $this->createStub( Portal_Auth_Guard::class );
        $auth_guard->method( 'is_authenticated' )->willReturn( true );
        // Simulate missing racket_manager capability
        $auth_guard->method( 'user_can' )->willReturnCallback(
            fn( string $cap ) => 'racket_manager' !== $cap
        );

        $presenter = $this->createStub( Portal_Screen_Presenter::class );

        $router = $this->create_router( $query_vars, $auth_guard, $presenter );
        $router->handle_template_redirect();

        $this->assertSame( 403, $GLOBALS['wp_stubs_last_status_header'] ?? null );
    }

    public function test_portal_navigation_links_ported_sections_to_manage_and_unported_to_wp_admin(): void {
        $nav_vm = $this->presenter_build_navigation( 'clubs' );

        $items_by_slug = [];
        foreach ( $nav_vm->items as $item ) {
            $items_by_slug[ $item->slug ] = $item;
        }

        // Ported sections
        $this->assertTrue( $items_by_slug['clubs']->is_ported );
        $this->assertSame( home_url( '/manage/clubs' ), $items_by_slug['clubs']->url );
        $this->assertTrue( $items_by_slug['clubs']->is_active );

        $this->assertTrue( $items_by_slug['competitions']->is_ported );
        $this->assertSame( home_url( '/manage/competitions' ), $items_by_slug['competitions']->url );
        $this->assertFalse( $items_by_slug['competitions']->is_active );

        // All sections are ported in final cutover
        $this->assertTrue( $items_by_slug['seasons']->is_ported );
        $this->assertSame( home_url( '/manage/seasons' ), $items_by_slug['seasons']->url );

        $this->assertTrue( $items_by_slug['finances']->is_ported );
        $this->assertSame( home_url( '/manage/finances' ), $items_by_slug['finances']->url );
    }

    private function presenter_build_navigation( string $active_section ): \Racketmanager\Infrastructure\Wordpress\Portal\Portal_Navigation_View_Model {
        $auth_guard = $this->createStub( Portal_Auth_Guard::class );
        $auth_guard->method( 'user_can' )->willReturn( true );

        $flash_store     = $this->createStub( Flash_Message_Store_Interface::class );
        $flash_store->method( 'pop' )->willReturn( [] );
        $message_service = new Admin_Message_Service( $flash_store );

        $presenter = new Portal_Screen_Presenter(
            $this->createStub( View_Renderer_Interface::class ),
            $message_service,
            $this->registry,
            $this->resolver,
            $auth_guard
        );

        $reflection = new \ReflectionClass( $presenter );
        $method     = $reflection->getMethod( 'build_navigation' );

        return $method->invoke( $presenter, $active_section );
    }

    public function test_form_actions_target_portal_in_portal_context(): void {
        global $racketmanager;
        $container = new Simple_Container();
        $container->set( Manage_Url_Resolver_Interface::class, fn() => $this->resolver );
        $racketmanager = (object) [ 'container' => $container ];

        $renderer = new \Racketmanager\Services\View\Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );

        // 1. show-clubs form
        $vm_clubs = new \Racketmanager\Admin\View_Models\Clubs_Page_View_Model( [] );
        $html_clubs = $renderer->render_to_string( 'admin/show-clubs', $vm_clubs );
        $this->assertStringContainsString( 'action="' . esc_url( home_url( '/manage/clubs' ) ) . '"', $html_clubs );

        // 2. club edit form
        $vm_club = new \Racketmanager\Admin\View_Models\Club_Page_View_Model(
            club: null,
            edit: false,
            form_title: 'Add Club',
            form_action: 'Add Club',
            club_id: null
        );
        $html_club = $renderer->render_to_string( 'admin/includes/club', $vm_club );
        $this->assertStringContainsString( 'action="' . esc_url( home_url( '/manage/clubs/club' ) ) . '"', $html_club );

        // 3. competitions form
        $vm_index = new \Racketmanager\Admin\View_Models\Index_Page_View_Model(
            errors: new \Racketmanager\Admin\View_Models\Error_Bag(),
            competition_name: '',
            type: '',
            age_group: '',
            competitions: [],
            age_groups: [],
            age_group_select: '',
            page_name: 'racketmanager'
        );
        $html_index = $renderer->render_to_string( 'admin/index', $vm_index );
        $this->assertStringContainsString( 'action="' . esc_url( home_url( '/manage/competitions' ) ) . '"', $html_index );
    }
}
