<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Bootstrap;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Bootstrap\Plugin_Bootstrap;
use Racketmanager\RacketManager;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionClass;
use ReflectionMethod;
use stdClass;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Plugin_Bootstrap_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private const array SERVICE_CLASSES = [
        'tournament_service'        => Tournament_Service::class,
        'competition_service'       => Competition_Service::class,
        'competition_entry_service' => Competition_Entry_Service::class,
        'finance_service'           => Finance_Service::class,
    ];

    private string $temp_dir = '';

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_added_actions'] = [];
        $GLOBALS['wp_stubs_added_filters'] = [];
    }

    protected function tearDown(): void {
        if ( '' !== $this->temp_dir ) {
            $this->remove_directory( $this->temp_dir );
        }
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_container_wiring_and_resolution(): void {
        $container = Container_Bootstrap::boot();

        self::assertTrue( $container->has( 'plugin_bootstrap' ) );
        self::assertTrue( $container->has( Plugin_Bootstrap::class ) );
        $bootstrap = $container->get( 'plugin_bootstrap' );
        self::assertInstanceOf( Plugin_Bootstrap::class, $bootstrap );
        self::assertSame( $bootstrap, $container->get( Plugin_Bootstrap::class ) );
    }

    public function test_hook_definitions_match_legacy_racketmanager_constructor_wiring(): void {
        [ $app, $container, $services ] = $this->create_app_with_services();
        $bootstrap = new Plugin_Bootstrap( $container );

        $expected = [
            [ 'action', 'widgets_init', [ $app, 'register_widget' ], 10, 1 ],
            [ 'action', 'init', [ $app, 'racketmanager_locale' ], 10, 1 ],
            [ 'action', 'init', [ $app, 'init_components' ], 10, 1 ],
            [ 'action', 'init', [ $app, 'load_shortcodes' ], 10, 1 ],
            [ 'action', 'wp_enqueue_scripts', [ $app, 'load_styles' ], 5, 1 ],
            [ 'action', 'wp_enqueue_scripts', [ $app, 'load_scripts' ], 10, 1 ],
            [ 'action', 'wp_loaded', [ $app, 'add_racketmanager_templates' ], 10, 1 ],
            [ 'action', 'template_redirect', [ $app, 'redirect_to_login' ], 10, 1 ],
            [ 'filter', 'wp_privacy_personal_data_exporters', [ $app, 'racketmanager_register_exporter' ], 10, 1 ],
            [ 'filter', 'wp_mail', [ $app, 'racketmanager_mail' ], 10, 1 ],
            [ 'filter', 'email_change_email', [ $app, 'racketmanager_change_email_address' ], 10, 3 ],
            [ 'filter', 'pre_get_document_title', [ $app, 'set_page_title' ], 999, 1 ],
            [ 'action', 'rm_resultPending', [ $app, 'chase_pending_results' ], 1, 1 ],
            [ 'action', 'rm_confirmationPending', [ $app, 'chase_pending_approvals' ], 1, 1 ],
            [ 'action', 'rm_calculate_player_ratings', [ $app, 'calculate_player_ratings' ], 1, 1 ],
            [ 'action', 'racketmanager_report_fixture_result', [ $app, 'report_fixture_result' ], 10, 2 ],
            [ 'action', 'rm_calculate_tournament_ratings', [ $services['tournament_service'], 'calculate_player_team_rating_for_tournament' ], 1, 1 ],
            [ 'action', 'rm_calculate_team_ratings', [ $services['competition_service'], 'calculate_team_ratings' ], 10, 3 ],
            [ 'action', 'rm_notify_team_entry_open', [ $services['competition_entry_service'], 'notify_team_entry_open' ], 10, 2 ],
            [ 'action', 'rm_notify_team_entry_reminder', [ $services['competition_entry_service'], 'notify_team_entry_reminder' ], 10, 2 ],
            [ 'action', 'rm_notify_tournament_entry_open', [ $services['competition_entry_service'], 'notify_tournament_entry_open' ], 10, 1 ],
            [ 'action', 'rm_notify_tournament_entry_reminder', [ $services['competition_entry_service'], 'notify_tournament_entry_open_reminder' ], 10, 1 ],
            [ 'action', 'rm_notify_tournament_finalists', [ $services['tournament_service'], 'notify_finalists_for_tournament' ], 10, 1 ],
            [ 'action', 'rm_send_invoices', [ $services['finance_service'], 'send_invoices_for_charge' ], 10, 1 ],
        ];

        $actual = array_map(
            static fn( array $d ): array => [ $d['type'], $d['hook'], $d['callback'], $d['priority'], $d['accepted_args'] ],
            $bootstrap->get_hook_definitions( $app )
        );

        self::assertSame( $expected, $actual );
    }

    public function test_register_hooks_adds_actions_and_filters_with_priorities_and_callback_identity(): void {
        [ $app, $container, $services ] = $this->create_app_with_services();
        $bootstrap = new Plugin_Bootstrap( $container );

        $bootstrap->register_hooks( $app );

        $actions = $GLOBALS['wp_stubs_added_actions'];
        $filters = $GLOBALS['wp_stubs_added_filters'];

        self::assertSame(
            [
                [ 'callback' => [ $app, 'racketmanager_locale' ], 'priority' => 10, 'accepted_args' => 1 ],
                [ 'callback' => [ $app, 'init_components' ], 'priority' => 10, 'accepted_args' => 1 ],
                [ 'callback' => [ $app, 'load_shortcodes' ], 'priority' => 10, 'accepted_args' => 1 ],
            ],
            $actions['init']
        );
        self::assertSame(
            [
                [ 'callback' => [ $app, 'load_styles' ], 'priority' => 5, 'accepted_args' => 1 ],
                [ 'callback' => [ $app, 'load_scripts' ], 'priority' => 10, 'accepted_args' => 1 ],
            ],
            $actions['wp_enqueue_scripts']
        );
        self::assertSame( 999, $filters['pre_get_document_title'][0]['priority'] );
        self::assertSame( 3, $filters['email_change_email'][0]['accepted_args'] );
        self::assertArrayNotHasKey( 'wp_mail', $actions, 'Filters must not be registered as actions' );
        self::assertArrayNotHasKey( 'init', $filters, 'Actions must not be registered as filters' );

        $team_ratings = $actions['rm_calculate_team_ratings'][0];
        self::assertSame( $services['competition_service'], $team_ratings['callback'][0] );
        self::assertSame( [ 10, 3 ], [ $team_ratings['priority'], $team_ratings['accepted_args'] ] );
        self::assertSame( $services['finance_service'], $actions['rm_send_invoices'][0]['callback'][0] );
        self::assertSame( [ $app, 'report_fixture_result' ], $actions['racketmanager_report_fixture_result'][0]['callback'] );
        self::assertSame( 2, $actions['racketmanager_report_fixture_result'][0]['accepted_args'] );

        $total = array_sum( array_map( 'count', $actions ) ) + array_sum( array_map( 'count', $filters ) );
        self::assertSame( 24, $total );
        self::assertTrue( $bootstrap->is_booted( $app ) );
    }

    public function test_register_hooks_is_idempotent_per_application_instance(): void {
        [ $app, $container ] = $this->create_app_with_services();
        $bootstrap = new Plugin_Bootstrap( $container );

        $bootstrap->register_hooks( $app );
        $bootstrap->register_hooks( $app );

        self::assertCount( 3, $GLOBALS['wp_stubs_added_actions']['init'] );
        self::assertCount( 1, $GLOBALS['wp_stubs_added_actions']['rm_send_invoices'] );
        self::assertCount( 1, $GLOBALS['wp_stubs_added_filters']['wp_mail'] );

        $other_app = ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        self::assertFalse( $bootstrap->is_booted( $other_app ) );
    }

    public function test_service_task_hooks_are_skipped_when_service_is_not_defined(): void {
        $app       = ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        $bootstrap = new Plugin_Bootstrap( new Simple_Container() );

        $hooks = array_column( $bootstrap->get_scheduled_task_hook_definitions( $app ), 'hook' );

        self::assertSame(
            [ 'rm_resultPending', 'rm_confirmationPending', 'rm_calculate_player_ratings', 'racketmanager_report_fixture_result' ],
            $hooks
        );
    }

    public function test_service_task_hooks_resolve_services_lazily_at_registration(): void {
        $app       = ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        $container = new Simple_Container();
        $resolved  = 0;
        $container->set(
            'finance_service',
            function () use ( &$resolved ): stdClass {
                ++$resolved;
                return new stdClass();
            }
        );

        $bootstrap = new Plugin_Bootstrap( $container );
        self::assertSame( 0, $resolved, 'Constructing the bootstrap must not resolve services' );

        $bootstrap->get_lifecycle_hook_definitions( $app );
        self::assertSame( 0, $resolved, 'Lifecycle hooks must not resolve services' );

        $bootstrap->register_hooks( $app );
        self::assertSame( 1, $resolved );
        self::assertSame( $container->get( 'finance_service' ), $GLOBALS['wp_stubs_added_actions']['rm_send_invoices'][0]['callback'][0] );
    }

    public function test_app_hook_callbacks_are_public_racketmanager_methods_accepting_declared_args(): void {
        foreach ( Plugin_Bootstrap::APP_HOOKS as [ , $hook, $method, , $accepted_args ] ) {
            $this->assert_callable_method( RacketManager::class, $method, $accepted_args, $hook );
        }
        foreach ( Plugin_Bootstrap::APP_TASK_HOOKS as [ $hook, $method, , $accepted_args ] ) {
            $this->assert_callable_method( RacketManager::class, $method, $accepted_args, $hook );
        }
    }

    public function test_service_hook_callbacks_exist_on_registered_service_classes(): void {
        foreach ( Plugin_Bootstrap::SERVICE_TASK_HOOKS as [ $hook, $service_id, $method, , $accepted_args ] ) {
            self::assertArrayHasKey( $service_id, self::SERVICE_CLASSES, "Unknown service '$service_id' for [$hook]" );
            $this->assert_callable_method( self::SERVICE_CLASSES[ $service_id ], $method, $accepted_args, $hook );
        }
    }

    public function test_discover_sport_registrars_includes_only_lowercase_registrar_scripts(): void {
        $plugin_dir = $this->create_temp_dir();
        $theme_dir  = $plugin_dir . '/theme';
        mkdir( $plugin_dir . '/src/php/sports', 0777, true );
        mkdir( $theme_dir . '/Sports', 0777, true );
        file_put_contents( $plugin_dir . '/src/php/sports/tennis.php', '<?php' );
        file_put_contents( $plugin_dir . '/src/php/sports/League_Tennis.php', '<?php' );
        file_put_contents( $plugin_dir . '/src/php/sports/.hidden.php', '<?php' );
        file_put_contents( $plugin_dir . '/src/php/sports/readme.txt', 'x' );
        mkdir( $plugin_dir . '/src/php/sports/nested.php' );
        file_put_contents( $theme_dir . '/Sports/padel.php', '<?php' );
        file_put_contents( $theme_dir . '/Sports/Competition_Padel.php', '<?php' );
        $GLOBALS['wp_stubs_stylesheet_directory'] = $theme_dir;

        $bootstrap = new Plugin_Bootstrap( new Simple_Container(), $plugin_dir . '/' );

        self::assertSame(
            [ $plugin_dir . '/src/php/sports/tennis.php', $theme_dir . '/Sports/padel.php' ],
            $bootstrap->discover_sport_registrars()
        );
    }

    public function test_load_libraries_requires_registrars_template_tags_and_functions_once(): void {
        $plugin_dir = $this->create_temp_dir();
        mkdir( $plugin_dir . '/src/php/sports', 0777, true );
        $counter = '$GLOBALS["plugin_bootstrap_test_loaded"][] = basename( __FILE__ );';
        file_put_contents( $plugin_dir . '/src/php/sports/squash.php', '<?php ' . $counter );
        file_put_contents( $plugin_dir . '/template-tags.php', '<?php ' . $counter );
        file_put_contents( $plugin_dir . '/functions.php', '<?php ' . $counter );
        $GLOBALS['wp_stubs_stylesheet_directory'] = $plugin_dir . '/missing-theme';
        $GLOBALS['plugin_bootstrap_test_loaded']  = [];

        $bootstrap = new Plugin_Bootstrap( new Simple_Container(), $plugin_dir . '/' );
        try {
            $bootstrap->load_libraries();
            $bootstrap->load_libraries();

            self::assertSame( [ 'squash.php', 'template-tags.php', 'functions.php' ], $GLOBALS['plugin_bootstrap_test_loaded'] );
        } finally {
            unset( $GLOBALS['plugin_bootstrap_test_loaded'] );
        }
    }

    public function test_boot_loads_libraries_and_registers_hooks(): void {
        $plugin_dir = $this->create_temp_dir();
        file_put_contents( $plugin_dir . '/template-tags.php', '<?php $GLOBALS["plugin_bootstrap_test_boot"] = true;' );
        file_put_contents( $plugin_dir . '/functions.php', '<?php' );
        $GLOBALS['wp_stubs_stylesheet_directory'] = '';

        [ $app, $container ] = $this->create_app_with_services();
        $bootstrap = new Plugin_Bootstrap( $container, $plugin_dir . '/' );
        try {
            $bootstrap->boot( $app );

            self::assertTrue( $GLOBALS['plugin_bootstrap_test_boot'] ?? false );
            self::assertTrue( $bootstrap->is_booted( $app ) );
            self::assertCount( 3, $GLOBALS['wp_stubs_added_actions']['init'] );
        } finally {
            unset( $GLOBALS['plugin_bootstrap_test_boot'] );
        }
    }

    public function test_launch_uses_frontend_instance_outside_admin_and_sets_global(): void {
        $frontend = new stdClass();
        $admin    = new stdClass();

        $instance = Plugin_Bootstrap::launch( false, static fn() => $frontend, static fn() => $admin );

        self::assertSame( $frontend, $instance );
        self::assertSame( $frontend, $GLOBALS['racketmanager'] );
    }

    public function test_launch_uses_admin_instance_in_dashboard_and_sets_global(): void {
        $frontend = new stdClass();
        $admin    = new stdClass();

        $instance = Plugin_Bootstrap::launch( true, static fn() => $frontend, static fn() => $admin );

        self::assertSame( $admin, $instance );
        self::assertSame( $frontend, $GLOBALS['racketmanager'] );
    }

    public function test_launch_detects_admin_context_from_is_admin(): void {
        $frontend = new stdClass();
        $admin    = new stdClass();

        $GLOBALS['wp_stubs_is_admin'] = true;
        try {
            self::assertSame( $admin, Plugin_Bootstrap::launch( null, static fn() => $frontend, static fn() => $admin ) );
            $GLOBALS['wp_stubs_is_admin'] = false;
            self::assertSame( $frontend, Plugin_Bootstrap::launch( null, static fn() => $frontend, static fn() => $admin ) );
        } finally {
            unset( $GLOBALS['wp_stubs_is_admin'] );
        }
    }

    public function test_racketmanager_resolves_task_services_lazily_from_container(): void {
        $app       = ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        $container = new Simple_Container();
        $player    = $this->createMock( Player_Service::class );
        $player->expects( self::once() )->method( 'calculate_player_ratings' )->with( 42 );
        $container->set( 'player_service', $player );
        $app->container = $container;

        $app->calculate_player_ratings( 42 );

        $orchestrator = $this->createMock( \Racketmanager\Services\Fixture\Pending_Result_Orchestrator_Interface::class );
        $orchestrator->expects( self::once() )->method( 'chase_pending_results' )->with( 'league' );
        $container->set( 'pending_result_orchestration_service', $orchestrator );

        $app->chase_pending_results( 'league' );
    }

    public function test_racketmanager_constructor_delegates_hook_registration_to_bootstrap(): void {
        $source = (string) file_get_contents( dirname( __DIR__, 5 ) . '/src/php/RacketManager.php' );
        preg_match( '/function __construct\(\) \{(.*?)\n    \}/s', $source, $constructor );

        self::assertNotEmpty( $constructor[1] ?? '' );
        self::assertStringContainsString( "\$this->container->get( 'plugin_bootstrap' )", $constructor[1] );
        self::assertStringContainsString( '$this->plugin_bootstrap->boot( $this )', $constructor[1] );
        self::assertStringNotContainsString( 'add_action(', $constructor[1] );
        self::assertStringNotContainsString( 'add_filter(', $constructor[1] );
    }

    public function test_boot_calls_wpdb_show_errors(): void {
        $prev_wpdb = $GLOBALS['wpdb'] ?? null;
        try {
            $plugin_dir = $this->create_temp_dir();
            mkdir( $plugin_dir . '/src/php/sports', 0777, true );
            file_put_contents( $plugin_dir . '/template-tags.php', '<?php' );
            file_put_contents( $plugin_dir . '/functions.php', '<?php' );
            $GLOBALS['wp_stubs_stylesheet_directory'] = $plugin_dir . '/missing-theme';

            $recording_wpdb = new class {
                public bool $show_errors_called = false;
                public function show_errors(): void {
                    $this->show_errors_called = true;
                }
            };
            $GLOBALS['wpdb'] = $recording_wpdb;

            $bootstrap = new Plugin_Bootstrap( new Simple_Container(), $plugin_dir . '/' );
            /** @var RacketManager $app */
            $app = ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
            $bootstrap->boot( $app );

            self::assertTrue( $recording_wpdb->show_errors_called );
        } finally {
            $GLOBALS['wpdb'] = $prev_wpdb;
        }
    }

    /**
     * @return array{0: RacketManager, 1: Simple_Container, 2: array<string, object>}
     */
    private function create_app_with_services(): array {
        /** @var RacketManager $app */
        $app       = ( new ReflectionClass( RacketManager::class ) )->newInstanceWithoutConstructor();
        $container = new Simple_Container();
        $services  = [];
        foreach ( array_keys( self::SERVICE_CLASSES ) as $id ) {
            $services[ $id ] = new stdClass();
            $container->set( $id, $services[ $id ] );
        }

        return [ $app, $container, $services ];
    }

    private function assert_callable_method( string $class, string $method, int $accepted_args, string $hook ): void {
        self::assertTrue( method_exists( $class, $method ), "[$hook] {$class}::{$method}() does not exist" );
        $reflection = new ReflectionMethod( $class, $method );
        self::assertTrue( $reflection->isPublic(), "[$hook] {$class}::{$method}() must be public" );
        self::assertFalse( $reflection->isStatic(), "[$hook] {$class}::{$method}() must be an instance method" );
        // WordPress passes at most accepted_args arguments, so required parameters must not exceed it.
        self::assertLessThanOrEqual(
            $accepted_args,
            $reflection->getNumberOfRequiredParameters(),
            "[$hook] {$class}::{$method}() requires more parameters than accepted_args"
        );
    }

    private function create_temp_dir(): string {
        $this->temp_dir = sys_get_temp_dir() . '/rm_plugin_bootstrap_' . uniqid( '', true );
        mkdir( $this->temp_dir, 0777, true );

        return $this->temp_dir;
    }

    private function remove_directory( string $dir ): void {
        if ( ! is_dir( $dir ) ) {
            return;
        }
        foreach ( array_diff( (array) scandir( $dir ), [ '.', '..' ] ) as $entry ) {
            $path = $dir . '/' . $entry;
            is_dir( $path ) ? $this->remove_directory( $path ) : unlink( $path );
        }
        rmdir( $dir );
    }
}
