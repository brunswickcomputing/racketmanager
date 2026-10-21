<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Assets;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin;
use Racketmanager\Infrastructure\Wordpress\Assets\Asset_Manager;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionClass;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Asset_Manager_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_get_asset_url_resolves_standard_url_and_fallback(): void {
        $manager = new Asset_Manager(
            'https://example.org/wp-content/plugins/racketmanager/',
            sys_get_temp_dir() . '/nonexistent_dir',
            '10.0.0'
        );

        $url = $manager->get_asset_url( 'dist/js/racketmanager.js', true );
        self::assertSame( 'https://example.org/wp-content/plugins/racketmanager/dist/js/racketmanager.js', $url );
    }

    public function test_get_asset_url_prefers_minified_file_when_present(): void {
        $temp_dir = sys_get_temp_dir() . '/rm_asset_test_' . uniqid();
        mkdir( $temp_dir . '/dist/js', 0777, true );
        file_put_contents( $temp_dir . '/dist/js/test.js', 'console.log("unminified");' );
        file_put_contents( $temp_dir . '/dist/js/test.min.js', 'console.log("minified");' );

        try {
            $manager = new Asset_Manager(
                'https://example.org/plugin/',
                $temp_dir,
                '10.0.0'
            );

            $min_url   = $manager->get_asset_url( 'dist/js/test.js', true );
            $plain_url = $manager->get_asset_url( 'dist/js/test.js', false );

            self::assertSame( 'https://example.org/plugin/dist/js/test.min.js', $min_url );
            self::assertSame( 'https://example.org/plugin/dist/js/test.js', $plain_url );
        } finally {
            if ( file_exists( $temp_dir . '/dist/js/test.min.js' ) ) {
                unlink( $temp_dir . '/dist/js/test.min.js' );
            }
            if ( file_exists( $temp_dir . '/dist/js/test.js' ) ) {
                unlink( $temp_dir . '/dist/js/test.js' );
            }
            if ( is_dir( $temp_dir . '/dist/js' ) ) {
                rmdir( $temp_dir . '/dist/js' );
            }
            if ( is_dir( $temp_dir . '/dist' ) ) {
                rmdir( $temp_dir . '/dist' );
            }
            if ( is_dir( $temp_dir ) ) {
                rmdir( $temp_dir );
            }
        }
    }

    public function test_load_frontend_scripts_registers_and_enqueues_dependencies(): void {
        $manager = new Asset_Manager(
            'https://example.org/plugin/',
            sys_get_temp_dir(),
            '10.0.0'
        );

        ob_start();
        $manager->load_frontend_scripts( 'EUR' );
        $output = ob_get_clean();

        // Enqueued scripts
        self::assertArrayHasKey( 'jquery', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayHasKey( 'jquery-ui-core', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayHasKey( 'jquery-ui-autocomplete', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayHasKey( 'jquery-ui-datepicker', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayHasKey( 'racketmanager-config', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayHasKey( 'password-strength-meter', $GLOBALS['wp_stubs_enqueued_scripts'] );

        // Script module
        self::assertArrayHasKey( 'racketmanager-module', $GLOBALS['wp_stubs_registered_script_modules'] );
        self::assertArrayHasKey( 'racketmanager-module', $GLOBALS['wp_stubs_enqueued_script_modules'] );

        // Config script registration version and args
        self::assertArrayHasKey( 'racketmanager-config', $GLOBALS['wp_stubs_registered_scripts'] );
        self::assertSame( '10.0.0', $GLOBALS['wp_stubs_registered_scripts']['racketmanager-config']['ver'] );

        // Inline scripts config
        self::assertArrayHasKey( 'racketmanager-config', $GLOBALS['wp_stubs_inline_scripts'] );
        $inline_entries = $GLOBALS['wp_stubs_inline_scripts']['racketmanager-config'];
        self::assertCount( 2, $inline_entries );
        self::assertStringContainsString( 'window.ajax_var', $inline_entries[0]['data'] );
        self::assertStringContainsString( '"currency":"EUR"', $inline_entries[1]['data'] );

        // Localization
        self::assertArrayHasKey( 'password-strength-meter', $GLOBALS['wp_stubs_localized_scripts'] );
        self::assertArrayHasKey( 'pwsL10n', $GLOBALS['wp_stubs_localized_scripts']['password-strength-meter'] );

        // Legacy inline script HTML output
        self::assertStringContainsString( 'RacketManagerAjaxL10n =', $output );
        self::assertStringContainsString( 'pluginUrl: "https://example.org/plugin/"', $output );
    }

    public function test_load_frontend_styles_enqueues_and_registers_styles(): void {
        $manager = new Asset_Manager(
            'https://example.org/plugin/',
            sys_get_temp_dir(),
            '10.0.0'
        );

        $manager->load_frontend_styles();

        self::assertArrayHasKey( 'racketmanager-print', $GLOBALS['wp_stubs_enqueued_styles'] );
        self::assertArrayHasKey( 'racketmanager-modal', $GLOBALS['wp_stubs_enqueued_styles'] );
        self::assertArrayHasKey( 'racketmanager', $GLOBALS['wp_stubs_enqueued_styles'] );
        self::assertArrayHasKey( 'jquery-ui-structure', $GLOBALS['wp_stubs_enqueued_styles'] );
        self::assertArrayHasKey( 'jquery-ui-theme', $GLOBALS['wp_stubs_enqueued_styles'] );

        self::assertArrayHasKey( 'jquery-ui', $GLOBALS['wp_stubs_registered_styles'] );
        self::assertArrayHasKey( 'jquery-ui-autocomplete', $GLOBALS['wp_stubs_registered_styles'] );

        // Media and version checks on enqueued styles
        self::assertSame( 'print', $GLOBALS['wp_stubs_enqueued_styles']['racketmanager-print']['media'] );
        self::assertSame( 'screen', $GLOBALS['wp_stubs_enqueued_styles']['racketmanager']['media'] );
        self::assertSame( 'screen', $GLOBALS['wp_stubs_enqueued_styles']['racketmanager-modal']['media'] );
        self::assertSame( '10.0.0', $GLOBALS['wp_stubs_enqueued_styles']['racketmanager']['ver'] );
    }

    public function test_load_admin_scripts_ignores_non_racketmanager_screens(): void {
        $manager = new Asset_Manager(
            'https://example.org/plugin/',
            sys_get_temp_dir(),
            '10.0.0'
        );

        $_GET['page'] = 'other-plugin-page';
        $GLOBALS['wp_stubs_current_screen'] = (object) [ 'base' => 'toplevel_page_other' ];

        ob_start();
        $manager->load_admin_scripts();
        $output = ob_get_clean();

        self::assertEmpty( $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertSame( '', $output );
    }

    public function test_load_admin_scripts_loads_on_racketmanager_pages(): void {
        $manager = new Asset_Manager(
            'https://example.org/plugin/',
            sys_get_temp_dir(),
            '10.0.0'
        );

        $_GET['page'] = 'racketmanager-tournaments';
        $GLOBALS['wp_stubs_current_screen'] = (object) [ 'base' => 'toplevel_page_racketmanager' ];

        ob_start();
        $manager->load_admin_scripts();
        $output = ob_get_clean();

        self::assertArrayHasKey( 'jquery', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayHasKey( 'racketmanager-bootstrap', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayHasKey( 'racketmanager-admin-ajax', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayHasKey( 'racketmanager-admin-functions', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayHasKey( 'racketmanager-admin-utils', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayHasKey( 'racketmanager-admin-draggable', $GLOBALS['wp_stubs_enqueued_scripts'] );

        // Dependencies and version on registered admin functions script
        self::assertArrayHasKey( 'racketmanager-admin-functions', $GLOBALS['wp_stubs_registered_scripts'] );
        self::assertContains(
            'racketmanager-admin-ajax',
            $GLOBALS['wp_stubs_registered_scripts']['racketmanager-admin-functions']['deps']
        );
        self::assertSame( '10.0.0', $GLOBALS['wp_stubs_registered_scripts']['racketmanager-admin-functions']['ver'] );

        self::assertArrayHasKey( 'racketmanager-admin-ajax', $GLOBALS['wp_stubs_localized_scripts'] );
        self::assertArrayHasKey( 'racketmanager-admin-functions', $GLOBALS['wp_stubs_localized_scripts'] );

        self::assertStringContainsString( 'window.RacketManagerAjaxL10n =', $output );

        // Exact pluginUrl check: must output base /assets without trailing /images so JS can append /images/loading.gif
        self::assertMatchesRegularExpression( '/pluginUrl:\s*"https:\/\/example\.org\/plugin\/assets"/', $output );
        preg_match( '/pluginUrl:\s*"([^"]+)"/', $output, $matches );
        self::assertNotEmpty( $matches );
        self::assertSame( 'https://example.org/plugin/assets', $matches[1] );
        self::assertSame( 'https://example.org/plugin/assets/images/loading.gif', $matches[1] . '/images/loading.gif' );
    }

    public function test_load_admin_scripts_handles_page_gating_and_arguments(): void {
        $manager = new Asset_Manager( 'https://example.org/plugin/', sys_get_temp_dir(), '10.0.0' );

        // Case 1: Post editor (metabox)
        unset( $_GET['page'] );
        $GLOBALS['wp_stubs_current_screen']   = (object) [ 'base' => 'post' ];
        $GLOBALS['wp_stubs_enqueued_scripts'] = [];
        $GLOBALS['wp_stubs_enqueued_scripts_order'] = [];
        $GLOBALS['wp_stubs_localized_scripts'] = [];

        ob_start();
        $manager->load_admin_scripts();
        $output_post = ob_get_clean();

        self::assertArrayHasKey( 'racketmanager-admin-ajax', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayNotHasKey( 'racketmanager-bootstrap', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayNotHasKey( 'racketmanager-admin-functions', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayNotHasKey( 'racketmanager-admin-utils', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayNotHasKey( 'racketmanager-admin-draggable', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayHasKey( 'racketmanager-admin-ajax', $GLOBALS['wp_stubs_localized_scripts'] );
        self::assertArrayHasKey( 'ajax_var', $GLOBALS['wp_stubs_localized_scripts']['racketmanager-admin-ajax'] );
        self::assertStringContainsString( 'window.RacketManagerAjaxL10n =', $output_post );

        // Case 2: Non-tournament RacketManager page (e.g. leagues)
        $_GET['page'] = 'racketmanager-leagues';
        $GLOBALS['wp_stubs_current_screen']   = (object) [ 'base' => 'toplevel_page_racketmanager' ];
        $GLOBALS['wp_stubs_enqueued_scripts'] = [];
        $GLOBALS['wp_stubs_enqueued_scripts_order'] = [];

        ob_start();
        $manager->load_admin_scripts();
        $output_leagues = ob_get_clean();

        self::assertArrayHasKey( 'racketmanager-admin-ajax', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayHasKey( 'racketmanager-admin-functions', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayHasKey( 'racketmanager-admin-utils', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertArrayNotHasKey( 'racketmanager-admin-draggable', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertStringContainsString( 'window.RacketManagerAjaxL10n =', $output_leagues );

        // Case 3: Tournament RacketManager page (draggable)
        $_GET['page'] = 'racketmanager-tournaments';
        $GLOBALS['wp_stubs_current_screen']   = (object) [ 'base' => 'toplevel_page_racketmanager' ];
        $GLOBALS['wp_stubs_enqueued_scripts'] = [];
        $GLOBALS['wp_stubs_enqueued_scripts_order'] = [];

        ob_start();
        $manager->load_admin_scripts();
        $output_tournaments = ob_get_clean();

        self::assertArrayHasKey( 'racketmanager-admin-draggable', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertTrue( $GLOBALS['wp_stubs_enqueued_scripts']['racketmanager-admin-draggable']['in_footer'] );

        // Strict order assertion: racketmanager-admin-ajax before racketmanager-admin-functions
        $order = $GLOBALS['wp_stubs_enqueued_scripts_order'];
        $ajax_pos = array_search( 'racketmanager-admin-ajax', $order, true );
        $func_pos = array_search( 'racketmanager-admin-functions', $order, true );
        self::assertNotFalse( $ajax_pos );
        self::assertNotFalse( $func_pos );
        self::assertLessThan( $func_pos, $ajax_pos );
    }

    public function test_load_admin_styles_enqueues_expected_stylesheets(): void {
        $manager = new Asset_Manager(
            'https://example.org/plugin/',
            sys_get_temp_dir(),
            '10.0.0'
        );

        $manager->load_admin_styles();

        self::assertArrayHasKey( 'racketmanager-bootstrap', $GLOBALS['wp_stubs_enqueued_styles'] );
        self::assertArrayHasKey( 'racketmanager', $GLOBALS['wp_stubs_enqueued_styles'] );
        self::assertArrayHasKey( 'racketmanager-modal', $GLOBALS['wp_stubs_enqueued_styles'] );
        self::assertArrayHasKey( 'jquery-ui-structure', $GLOBALS['wp_stubs_enqueued_styles'] );
        self::assertArrayHasKey( 'jquery-ui-theme', $GLOBALS['wp_stubs_enqueued_styles'] );
        self::assertArrayHasKey( 'thickbox', $GLOBALS['wp_stubs_enqueued_styles'] );
    }

    public function test_container_wiring_and_resolution(): void {
        $container = Container_Bootstrap::boot();

        self::assertTrue( $container->has( 'asset_manager' ) );
        self::assertTrue( $container->has( Asset_Manager::class ) );

        $asset_mgr1 = $container->get( 'asset_manager' );
        $asset_mgr2 = $container->get( Asset_Manager::class );

        self::assertInstanceOf( Asset_Manager::class, $asset_mgr1 );
        self::assertSame( $asset_mgr1, $asset_mgr2 );
    }

    public function test_container_reuses_app_asset_manager_when_provided(): void {
        $custom_manager = new Asset_Manager( 'https://custom.test/assets/', '/tmp/custom', '2.5.0' );

        $reflection = new ReflectionClass( RacketManager::class );
        /** @var RacketManager $app */
        $app = $reflection->newInstanceWithoutConstructor();
        $app->asset_manager = $custom_manager;

        $container = Container_Bootstrap::boot( $app );

        self::assertSame( $custom_manager, $container->get( 'asset_manager' ) );
        self::assertSame( $custom_manager, $container->get( Asset_Manager::class ) );
    }

    public function test_racketmanager_delegation_to_asset_manager(): void {
        $custom_manager = new Asset_Manager( 'https://custom.test/plugin/', '/tmp/custom', '3.0.0' );

        $reflection = new ReflectionClass( RacketManager::class );
        /** @var RacketManager $app */
        $app = $reflection->newInstanceWithoutConstructor();
        $app->asset_manager  = $custom_manager;
        $app->currency_code  = 'USD';

        $method = $reflection->getMethod( 'get_asset_url' );
        $url = $method->invoke( $app, 'dist/js/app.js', false );

        self::assertSame( 'https://custom.test/plugin/dist/js/app.js', $url );

        $app->load_styles();
        self::assertArrayHasKey( 'racketmanager', $GLOBALS['wp_stubs_enqueued_styles'] );
        self::assertSame( 'https://custom.test/plugin/dist/css/style.css', $GLOBALS['wp_stubs_enqueued_styles']['racketmanager']['src'] );
        self::assertSame( '3.0.0', $GLOBALS['wp_stubs_enqueued_styles']['racketmanager']['ver'] );

        ob_start();
        $app->load_scripts();
        $output = ob_get_clean();

        self::assertNotEmpty( $output );
        self::assertArrayHasKey( 'racketmanager-module', $GLOBALS['wp_stubs_enqueued_script_modules'] );
        self::assertStringContainsString( '"currency":"USD"', $GLOBALS['wp_stubs_inline_scripts']['racketmanager-config'][1]['data'] );
    }

    public function test_admin_delegates_load_scripts_and_styles_to_asset_manager(): void {
        $custom_manager = new Asset_Manager( 'https://custom.test/plugin/', '/tmp/custom', '4.0.0' );

        $reflection = new ReflectionClass( Admin::class );
        /** @var Admin $admin */
        $admin = $reflection->newInstanceWithoutConstructor();
        $admin->asset_manager = $custom_manager;

        $_GET['page'] = 'racketmanager-clubs';
        $GLOBALS['wp_stubs_current_screen'] = (object) [ 'base' => 'toplevel_page_racketmanager' ];

        $admin->load_styles();
        self::assertArrayHasKey( 'racketmanager', $GLOBALS['wp_stubs_enqueued_styles'] );
        self::assertArrayHasKey( 'thickbox', $GLOBALS['wp_stubs_enqueued_styles'] );
        self::assertSame( 'https://custom.test/plugin/dist/css/admin.css', $GLOBALS['wp_stubs_enqueued_styles']['racketmanager']['src'] );
        self::assertSame( '4.0.0', $GLOBALS['wp_stubs_enqueued_styles']['racketmanager']['ver'] );

        ob_start();
        $admin->load_scripts();
        $output = ob_get_clean();

        self::assertArrayHasKey( 'racketmanager-admin-ajax', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertStringStartsWith( 'https://custom.test/plugin/', $GLOBALS['wp_stubs_registered_scripts']['racketmanager-admin-ajax']['src'] );
        self::assertSame( '4.0.0', $GLOBALS['wp_stubs_registered_scripts']['racketmanager-admin-ajax']['ver'] );
        self::assertStringContainsString( 'https://custom.test/plugin/assets', $output );
        self::assertStringContainsString( 'window.RacketManagerAjaxL10n =', $output );
    }

    public function test_admin_resolves_asset_manager_from_container_when_property_not_set(): void {
        $custom_manager = new Asset_Manager( 'https://container.test/plugin/', '/tmp/container', '5.0.0' );
        $container = new Simple_Container();
        $container->set( 'asset_manager', fn() => $custom_manager );

        $reflection = new ReflectionClass( Admin::class );
        /** @var Admin $admin */
        $admin = $reflection->newInstanceWithoutConstructor();
        $admin->container = $container;

        $_GET['page'] = 'racketmanager-clubs';
        $GLOBALS['wp_stubs_current_screen'] = (object) [ 'base' => 'toplevel_page_racketmanager' ];

        $admin->load_styles();
        self::assertArrayHasKey( 'racketmanager', $GLOBALS['wp_stubs_enqueued_styles'] );
        self::assertArrayHasKey( 'thickbox', $GLOBALS['wp_stubs_enqueued_styles'] );
        self::assertSame( 'https://container.test/plugin/dist/css/admin.css', $GLOBALS['wp_stubs_enqueued_styles']['racketmanager']['src'] );
        self::assertSame( '5.0.0', $GLOBALS['wp_stubs_enqueued_styles']['racketmanager']['ver'] );

        ob_start();
        $admin->load_scripts();
        $output = ob_get_clean();

        self::assertArrayHasKey( 'racketmanager-admin-ajax', $GLOBALS['wp_stubs_enqueued_scripts'] );
        self::assertStringStartsWith( 'https://container.test/plugin/', $GLOBALS['wp_stubs_registered_scripts']['racketmanager-admin-ajax']['src'] );
        self::assertSame( '5.0.0', $GLOBALS['wp_stubs_registered_scripts']['racketmanager-admin-ajax']['ver'] );
        self::assertStringContainsString( 'https://container.test/plugin/assets', $output );
        self::assertStringContainsString( 'window.RacketManagerAjaxL10n =', $output );
    }
}
