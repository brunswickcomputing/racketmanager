<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Portal;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Cup;
use Racketmanager\Admin\Admin_League;
use Racketmanager\Admin\Admin_Tournament;
use Racketmanager\Admin\Controllers\Admin_Redirect_Url_Builder;
use Racketmanager\Application\Admin\Ports\Flash_Message_Store_Interface;
use Racketmanager\Application\Ports\Manage_Url_Resolver_Interface;
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

final class Competitive_Play_Portal_Integration_Test extends TestCase {

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

    public function test_competitive_play_sections_are_enabled_in_section_registry(): void {
        $this->assertTrue( $this->registry->is_ported( 'leagues' ) );
        $this->assertTrue( $this->registry->is_ported( 'cups' ) );
        $this->assertTrue( $this->registry->is_ported( 'tournaments' ) );

        $ported = $this->registry->get_ported_sections();
        $this->assertArrayHasKey( 'leagues', $ported );
        $this->assertArrayHasKey( 'cups', $ported );
        $this->assertArrayHasKey( 'tournaments', $ported );
    }

    public function test_dispatches_leagues_subviews_to_admin_league_screen(): void {
        $subviews = [ 'leagues', 'league', 'seasons', 'overview', 'event', 'constitution', 'schedule', 'matches' ];

        foreach ( $subviews as $subview ) {
            $query_vars = $this->createStub( Query_Vars_Interface::class );
            $query_vars->method( 'get' )->willReturnCallback(
                fn( string $key ) => match ( $key ) {
                    'rm_manage'  => '1',
                    'rm_section' => 'leagues',
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
            $container->set( Admin_League::class, fn() => $dummy_screen );

            $presenter = $this->create_presenter();
            $router = $this->create_router( $query_vars, $auth_guard, $presenter, $container );
            $router->handle_template_redirect();

            $this->assertSame( $subview, $invoked_view, "Failed asserting view {$subview} dispatched to Admin_League" );
        }
    }

    public function test_dispatches_cups_subviews_to_admin_cup_screen(): void {
        $subviews = [ 'cups', 'draw', 'seasons', 'overview', 'plan', 'setup' ];

        foreach ( $subviews as $subview ) {
            $query_vars = $this->createStub( Query_Vars_Interface::class );
            $query_vars->method( 'get' )->willReturnCallback(
                fn( string $key ) => match ( $key ) {
                    'rm_manage'  => '1',
                    'rm_section' => 'cups',
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
            $container->set( Admin_Cup::class, fn() => $dummy_screen );

            $presenter = $this->create_presenter();
            $router = $this->create_router( $query_vars, $auth_guard, $presenter, $container );
            $router->handle_template_redirect();

            $this->assertSame( $subview, $invoked_view, "Failed asserting view {$subview} dispatched to Admin_Cup" );
        }
    }

    public function test_dispatches_tournaments_subviews_to_admin_tournament_screen(): void {
        $subviews = [ 'tournaments', 'tournament', 'draw', 'fixtures', 'plan', 'setup', 'information', 'modify', 'competitions' ];

        foreach ( $subviews as $subview ) {
            $query_vars = $this->createStub( Query_Vars_Interface::class );
            $query_vars->method( 'get' )->willReturnCallback(
                fn( string $key ) => match ( $key ) {
                    'rm_manage'  => '1',
                    'rm_section' => 'tournaments',
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
            $container->set( Admin_Tournament::class, fn() => $dummy_screen );

            $presenter = $this->create_presenter();
            $router = $this->create_router( $query_vars, $auth_guard, $presenter, $container );
            $router->handle_template_redirect();

            $this->assertSame( $subview, $invoked_view, "Failed asserting view {$subview} dispatched to Admin_Tournament" );
        }
    }

    public function test_portal_navigation_links_leagues_cups_and_tournaments_to_portal_routes(): void {
        $auth_guard = $this->createStub( Portal_Auth_Guard::class );
        $auth_guard->method( 'user_can' )->willReturn( true );

        $presenter = $this->create_presenter( $auth_guard );
        $nav       = $presenter->build_navigation( 'leagues' );

        $items_by_slug = [];
        foreach ( $nav->items as $item ) {
            $items_by_slug[ $item->slug ] = $item;
        }

        $this->assertArrayHasKey( 'leagues', $items_by_slug );
        $this->assertArrayHasKey( 'cups', $items_by_slug );
        $this->assertArrayHasKey( 'tournaments', $items_by_slug );
        $this->assertArrayHasKey( 'seasons', $items_by_slug );

        $this->assertTrue( $items_by_slug['leagues']->is_ported );
        $this->assertSame( home_url( '/manage/leagues' ), $items_by_slug['leagues']->url );
        $this->assertTrue( $items_by_slug['leagues']->is_active );

        $this->assertTrue( $items_by_slug['cups']->is_ported );
        $this->assertSame( home_url( '/manage/cups' ), $items_by_slug['cups']->url );
        $this->assertFalse( $items_by_slug['cups']->is_active );

        $this->assertTrue( $items_by_slug['tournaments']->is_ported );
        $this->assertSame( home_url( '/manage/tournaments' ), $items_by_slug['tournaments']->url );
        $this->assertFalse( $items_by_slug['tournaments']->is_active );

        // Seasons is ported in final cutover
        $this->assertTrue( $items_by_slug['seasons']->is_ported );
        $this->assertSame( home_url( '/manage/seasons' ), $items_by_slug['seasons']->url );
    }

    public function test_redirect_url_builder_preserves_portal_routes_for_competitive_play(): void {
        $league_redirect = Admin_Redirect_Url_Builder::league_screen(
            [ 'league_id' => '10', 'season' => '2026', 'view' => 'overview' ]
        );
        $this->assertSame(
            home_url( '/manage/leagues/overview?league_id=10&season=2026' ),
            $league_redirect
        );

        $cup_redirect = Admin_Redirect_Url_Builder::cup_screen(
            [ 'competition_id' => '3', 'league' => '7', 'season' => '2026', 'view' => 'draw' ]
        );
        $this->assertSame(
            home_url( '/manage/cups/draw?competition_id=3&league=7&season=2026' ),
            $cup_redirect
        );

        $tourn_redirect = Admin_Redirect_Url_Builder::tournament_draw_view(
            [],
            [],
            'draw',
            14,
            28,
            'fixtures'
        );
        $this->assertSame(
            home_url( '/manage/tournaments/draw?tournament=14&league=28&tab=fixtures' ),
            $tourn_redirect
        );
    }
}
