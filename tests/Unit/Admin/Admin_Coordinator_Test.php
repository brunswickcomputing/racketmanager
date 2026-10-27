<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin;
use Racketmanager\Admin\Admin_Display;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Admin_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Assets\Asset_Manager;
use Racketmanager\Infrastructure\Wordpress\Bootstrap\Plugin_Bootstrap;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Player_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionClass;
use stdClass;
use WP_Error;

require_once __DIR__ . '/../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Admin_Coordinator_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_added_actions'] = [];
        $GLOBALS['wp_stubs_added_filters'] = [];
        Admin::reset_instance();
    }

    protected function tearDown(): void {
        Admin::reset_instance();
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_admin_does_not_extend_racketmanager(): void {
        self::assertFalse(
            is_subclass_of( Admin::class, RacketManager::class ),
            'Admin must NOT extend RacketManager; it should be a standalone admin presentation coordinator.'
        );
    }

    public function test_constructor_accepts_injected_dependencies(): void {
        $container     = new Simple_Container();
        $racketmanager = ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        $asset_manager = new Asset_Manager( 'https://example.test/', '/tmp/test', '1.2.3' );
        $admin_display = $this->createStub( Admin_Display::class );
        $ajax_admin    = $this->createStub( Admin_Ajax_Controller::class );

        $admin = new Admin(
            $container,
            $racketmanager,
            $admin_display,
            $asset_manager,
            $ajax_admin
        );

        self::assertSame( $container, $admin->container );
        self::assertSame( $racketmanager, $admin->racketmanager );
        self::assertSame( $admin_display, $admin->admin_display );
        self::assertSame( $asset_manager, $admin->asset_manager );
        self::assertSame( $ajax_admin, $admin->ajax_admin );
        self::assertSame( $admin, Admin::get_instance() );
    }

    public function test_constructor_registers_expected_wordpress_admin_hooks(): void {
        $GLOBALS['wp_stubs_added_actions'] = [];
        $container     = new Simple_Container();
        $racketmanager = ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        $asset_manager = new Asset_Manager();
        $admin_display = $this->createStub( Admin_Display::class );

        $admin = new Admin( $container, $racketmanager, $admin_display, $asset_manager );

        $actions = $GLOBALS['wp_stubs_added_actions'] ?? [];

        self::assertArrayHasKey( 'admin_enqueue_scripts', $actions );
        self::assertCount( 2, $actions['admin_enqueue_scripts'] );
        self::assertSame( [ $admin, 'load_scripts' ], $actions['admin_enqueue_scripts'][0]['callback'] );
        self::assertSame( [ $admin, 'load_styles' ], $actions['admin_enqueue_scripts'][1]['callback'] );

        self::assertArrayHasKey( 'admin_menu', $actions );
        self::assertArrayHasKey( 'admin_footer', $actions );
        self::assertArrayHasKey( 'show_user_profile', $actions );
        self::assertArrayHasKey( 'edit_user_profile', $actions );
        self::assertArrayHasKey( 'personal_options_update', $actions );
        self::assertArrayHasKey( 'edit_user_profile_update', $actions );
        self::assertArrayHasKey( 'publish_post', $actions );
        self::assertArrayHasKey( 'edit_post', $actions );
        self::assertArrayHasKey( 'add_meta_boxes', $actions );

        // Verify callbacks reference the Admin instance
        self::assertSame( [ $admin, 'menu' ], $actions['admin_menu'][0]['callback'] );
        self::assertSame( [ $admin, 'scroll_top' ], $actions['admin_footer'][0]['callback'] );
        self::assertSame( [ $admin, 'custom_user_profile_fields' ], $actions['show_user_profile'][0]['callback'] );
        self::assertSame( [ $admin, 'custom_user_profile_fields' ], $actions['edit_user_profile'][0]['callback'] );
        self::assertSame( [ $admin, 'update_extra_profile_fields' ], $actions['personal_options_update'][0]['callback'] );
        self::assertSame( [ $admin, 'update_extra_profile_fields' ], $actions['edit_user_profile_update'][0]['callback'] );
        self::assertSame( [ $admin, 'edit_match_report' ], $actions['publish_post'][0]['callback'] );
        self::assertSame( [ $admin, 'edit_match_report' ], $actions['edit_post'][0]['callback'] );
        self::assertSame( [ $admin, 'metaboxes' ], $actions['add_meta_boxes'][0]['callback'] );
    }

    public function test_singleton_lifecycle_and_reset(): void {
        Admin::reset_instance();

        $app       = ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        $container = new Simple_Container();
        $display   = $this->createStub( Admin_Display::class );
        $container->set( 'admin_display', fn() => $display );
        $container->set( 'admin', fn( Simple_Container $c ) => new Admin( $c, $app, $display, new Asset_Manager() ) );

        $admin1 = Admin::get_instance( $container, $app );
        $admin2 = Admin::get_instance();

        self::assertInstanceOf( Admin::class, $admin1 );
        self::assertSame( $admin1, $admin2 );

        Admin::reset_instance();
        self::assertNull( Admin::get_current_instance() );

        // With a fresh container, get_instance creates and caches a fresh admin instance
        $container2 = new Simple_Container();
        $container2->set( 'admin_display', fn() => $display );
        $container2->set( 'admin', fn( Simple_Container $c ) => new Admin( $c, $app, $display, new Asset_Manager() ) );
        $admin3 = Admin::get_instance( $container2, $app );

        self::assertInstanceOf( Admin::class, $admin3 );
        self::assertNotSame( $admin1, $admin3 );
        self::assertSame( $admin3, Admin::get_instance() );
    }

    public function test_get_instance_throws_exception_when_no_registered_admin(): void {
        Admin::reset_instance();
        unset( $GLOBALS['racketmanager'] );

        $this->expectException( \LogicException::class );
        $this->expectExceptionMessage( 'Admin instance has not been initialized' );

        Admin::get_instance();
    }

    public function test_legacy_option_and_asset_delegations(): void {
        $racketmanager = ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        $racketmanager->options = [ 'currency' => 'EUR', 'title' => 'Tennis' ];
        $gateway = $this->createStub( Option_Gateway_Interface::class );
        $gateway->method( 'get_option' )->willReturnCallback(
            fn( string $key, mixed $default = false ) => 'racketmanager' === $key ? [ 'currency' => 'EUR', 'title' => 'Tennis' ] : $default
        );
        $racketmanager->option_service = new Option_Service( $gateway );

        $asset_manager = new Asset_Manager( 'https://test.local/plugin/', '/tmp/test', '2.0.0' );

        $admin = new Admin(
            new Simple_Container(),
            $racketmanager,
            $this->createStub( Admin_Display::class ),
            $asset_manager
        );

        self::assertSame( [ 'currency' => 'EUR', 'title' => 'Tennis' ], $admin->get_options() );
        self::assertSame( 'EUR', $admin->get_options( 'currency' ) );
        self::assertSame( [ 'currency' => 'EUR', 'title' => 'Tennis' ], $admin->options );
        self::assertTrue( isset( $admin->options ) );

        $url = $admin->get_asset_url( 'dist/js/admin/ajax.js' );
        self::assertSame( 'https://test.local/plugin/dist/js/admin/ajax.js', $url );
    }

    public function test_schedule_player_ratings_delegates_to_player_service(): void {
        $container      = new Simple_Container();
        $player_service = $this->createMock( Player_Service::class );
        $player_service->expects( self::once() )
            ->method( 'schedule_global_player_ratings' )
            ->willReturn( true );

        $container->set( 'player_service', $player_service );

        $admin = new Admin(
            $container,
            ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor(),
            $this->createStub( Admin_Display::class ),
            new Asset_Manager()
        );

        $result = $admin->schedule_player_ratings();
        self::assertTrue( $result );
    }

    public function test_plugin_actions_filters_links_array(): void {
        $admin = new Admin(
            new Simple_Container(),
            ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor(),
            $this->createStub( Admin_Display::class ),
            new Asset_Manager()
        );

        $links = [ 'deactivate' => '<a href="#">Deactivate</a>' ];
        $filtered = $admin->plugin_actions( $links );

        self::assertIsArray( $filtered );
        self::assertCount( 3, $filtered );
        self::assertArrayHasKey( 'settings', $filtered );
        self::assertArrayHasKey( 'documentation', $filtered );
        self::assertStringContainsString( 'admin.php?page=racketmanager-settings', $filtered['settings'] );
        self::assertStringContainsString( 'admin.php?page=racketmanager-doc', $filtered['documentation'] );
    }

    public function test_admin_service_provider_wires_admin_and_admin_display(): void {
        $app = ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        $app->options = [];
        $app->site_url = 'https://tennis.local';
        $app->site_name = 'Leigh & Westcliff';
        $app->option_service = new Option_Service( new \Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway() );

        $container = Container_Bootstrap::boot( $app );
        $app->container = $container;

        self::assertTrue( $container->has( 'admin' ) );
        self::assertTrue( $container->has( Admin::class ) );
        self::assertTrue( $container->has( 'admin_display' ) );
        self::assertTrue( $container->has( Admin_Display::class ) );

        $admin1 = $container->get( 'admin' );
        $admin2 = $container->get( Admin::class );
        $display1 = $container->get( 'admin_display' );
        $display2 = $container->get( Admin_Display::class );

        self::assertInstanceOf( Admin::class, $admin1 );
        self::assertSame( $admin1, $admin2 );
        self::assertInstanceOf( Admin_Display::class, $display1 );
        self::assertSame( $display1, $display2 );

        // Injected dependencies on Admin
        self::assertSame( $container, $admin1->container );
        self::assertSame( $app, $admin1->racketmanager );
        self::assertSame( $display1, $admin1->admin_display );
        self::assertSame( $container->get( 'asset_manager' ), $admin1->asset_manager );
        self::assertSame( $container->get( 'admin_ajax_controller' ), $admin1->ajax_admin );
    }

    public function test_plugin_bootstrap_launch_in_dashboard_returns_admin_and_preserves_racketmanager_global(): void {
        Admin::reset_instance();

        $app = ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        $app->options   = [];
        $app->site_url  = 'https://tennis.local';
        $app->site_name = 'Leigh & Westcliff';
        $app->option_service = new Option_Service( new \Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway() );

        $container      = Container_Bootstrap::boot( $app );
        $app->container = $container;

        $result = Plugin_Bootstrap::launch( true, static fn() => $app );

        self::assertInstanceOf( Admin::class, $result );
        self::assertSame( $result, Admin::get_instance() );

        // Legacy $GLOBALS['racketmanager'] remains the RacketManager core instance
        self::assertInstanceOf( RacketManager::class, $GLOBALS['racketmanager'] );
        self::assertSame( $app, $GLOBALS['racketmanager'] );
        self::assertSame( $app, $result->racketmanager );
        self::assertSame( $container, $result->container );
        self::assertSame( $container->get( 'admin' ), $result );

        // Second launch returns the same admin instance
        $second_launch = Plugin_Bootstrap::launch( true, static fn() => $app );
        self::assertSame( $result, $second_launch );
    }

    public function test_early_admin_get_instance_then_launch_shares_single_instance_and_hooks(): void {
        Admin::reset_instance();

        $app = ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        $app->options   = [];
        $app->site_url  = 'https://tennis.local';
        $app->site_name = 'Leigh & Westcliff';
        $app->option_service = new Option_Service( new \Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway() );

        $container      = Container_Bootstrap::boot( $app );
        $app->container = $container;
        $GLOBALS['racketmanager'] = $app;

        $GLOBALS['wp_stubs_added_actions'] = [];

        // 1. Call Admin::get_instance() with no args
        $a1 = Admin::get_instance();
        self::assertInstanceOf( Admin::class, $a1 );
        self::assertCount( 1, $GLOBALS['wp_stubs_added_actions']['admin_menu'] ?? [] );

        // 2. Call launch(true)
        $a2 = Plugin_Bootstrap::launch( true, static fn() => $app );

        // Must be the exact same instance
        self::assertSame( $a1, $a2 );
        self::assertSame( $a1, $container->get( 'admin' ) );
        // admin_menu action must not be duplicated
        self::assertCount( 1, $GLOBALS['wp_stubs_added_actions']['admin_menu'] ?? [] );
    }

    public function test_admin_registers_ajax_hooks_on_ajax_registry(): void {
        $container     = new Simple_Container();
        $registry      = new \Racketmanager\Infrastructure\Wordpress\Ajax\Ajax_Registry();
        $container->set( 'ajax_registry', fn() => $registry );

        $ajax_controller = $this->createMock( Admin_Ajax_Controller::class );
        $ajax_controller->expects( self::once() )
            ->method( 'register_hooks' )
            ->with( $registry );

        $admin = new Admin(
            $container,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager(),
            $ajax_controller
        );

        self::assertInstanceOf( Admin::class, $admin );
        self::assertSame( $ajax_controller, $admin->ajax_admin );
    }

    public function test_add_meta_box_resolves_current_league_from_container_league_repository(): void {
        $competition = \Racketmanager\Tests\Helpers\Competition_Test_Factory::create( id: 1, type: \Racketmanager\Domain\Competition\Competition_Type::LEAGUE );
        $event       = new \Racketmanager\Domain\Competition\Event( (object) array( 'id' => 9, 'name' => 'Event', 'seasons' => array( '2026' => array( 'name' => '2026' ) ) ), $competition );
        $league      = new \Racketmanager\Domain\Competition\League( (object) array( 'id' => 42, 'title' => 'Division 1' ), $event );
        $league->set_season( '2026' );

        $league_repository = $this->createMock( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class );
        $league_repository->expects( self::once() )->method( 'find_by_id' )->with( 42 )->willReturn( $league );

        $container = new Simple_Container();
        $container->set( 'league_repository', fn() => $league_repository );

        $wpdb                       = new \wpdb();
        $wpdb->get_results_callback = static fn() => array( (object) array( 'id' => 42, 'title' => 'Division 1' ) );
        $wpdb->get_row_callback     = static fn() => (object) array( 'id' => 77, 'league_id' => '42', 'season' => '2026' );
        $GLOBALS['wpdb']            = $wpdb;
        $GLOBALS['post']            = (object) array( 'ID' => 5 );

        $admin = new Admin(
            $container,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager()
        );

        ob_start();
        try {
            $admin->add_meta_box( $GLOBALS['post'] );
        } finally {
            $html = (string) ob_get_clean();
        }

        self::assertStringContainsString( "name='curr_match_id' value=\"77\"", $html );
        self::assertStringContainsString( "<select name='season'></select>", $html );
    }

    public function test_add_meta_box_without_league_repository_renders_without_current_league(): void {
        $wpdb                       = new \wpdb();
        $wpdb->get_results_callback = static fn() => array( (object) array( 'id' => 42, 'title' => 'Division 1' ) );
        $wpdb->get_row_callback     = static fn() => null;
        $GLOBALS['wpdb']            = $wpdb;
        $GLOBALS['post']            = (object) array( 'ID' => 5 );

        $admin = new Admin(
            new Simple_Container(),
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager()
        );

        ob_start();
        try {
            $admin->add_meta_box( $GLOBALS['post'] );
        } finally {
            $html = (string) ob_get_clean();
        }

        self::assertStringContainsString( 'Division 1', $html );
        self::assertStringNotContainsString( "<select name='season'></select>", $html );
    }

    public function test_register_admin_hooks_is_idempotent(): void {
        $GLOBALS['wp_stubs_added_actions'] = [];
        $container = new Simple_Container();
        $admin = new Admin(
            $container,
            null,
            $this->createStub( Admin_Display::class ),
            new Asset_Manager()
        );

        $initial_count = count( $GLOBALS['wp_stubs_added_actions']['admin_menu'] ?? [] );
        self::assertSame( 1, $initial_count );

        $admin->register_admin_hooks();
        $admin->register_admin_hooks();

        self::assertCount( 1, $GLOBALS['wp_stubs_added_actions']['admin_menu'] ?? [] );
    }

    public function test_resolving_admin_or_admin_display_from_null_app_container_throws_exception(): void {
        $container = Container_Bootstrap::boot( null );

        $this->expectException( \LogicException::class );
        $container->get( 'admin' );
    }

    public function test_resolving_admin_display_from_null_app_container_throws_exception(): void {
        $container = Container_Bootstrap::boot( null );

        $this->expectException( \LogicException::class );
        $container->get( 'admin_display' );
    }
}
