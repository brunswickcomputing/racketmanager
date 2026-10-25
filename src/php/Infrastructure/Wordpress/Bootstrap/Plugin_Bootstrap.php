<?php
/**
 * Plugin Bootstrap
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Bootstrap
 */

declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Bootstrap;

use Racketmanager\Admin;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Simple_Container;

/**
 * Orchestrates the WordPress lifecycle for the plugin: loads procedural libraries
 * and registers the core action/filter hooks and scheduled task hooks.
 *
 * Callbacks keep their historical identity (`[ $racketmanager, 'method' ]` or
 * `[ $service, 'method' ]` with the shared container instance) so that third-party
 * `remove_action()` / `has_action()` calls keep working.
 */
final class Plugin_Bootstrap {

    public const string TYPE_ACTION = 'action';
    public const string TYPE_FILTER = 'filter';

    /**
     * Lifecycle hooks handled by the RacketManager application instance.
     * Format: [ type, hook, method, priority, accepted_args ].
     */
    public const array APP_HOOKS = [
        [ self::TYPE_ACTION, 'widgets_init', 'register_widget', 10, 1 ],
        [ self::TYPE_ACTION, 'init', 'racketmanager_locale', 10, 1 ],
        [ self::TYPE_ACTION, 'init', 'init_components', 10, 1 ],
        [ self::TYPE_ACTION, 'init', 'load_shortcodes', 10, 1 ],
        [ self::TYPE_ACTION, 'wp_enqueue_scripts', 'load_styles', 5, 1 ],
        [ self::TYPE_ACTION, 'wp_enqueue_scripts', 'load_scripts', 10, 1 ],
        [ self::TYPE_ACTION, 'wp_loaded', 'add_racketmanager_templates', 10, 1 ],
        [ self::TYPE_ACTION, 'template_redirect', 'redirect_to_login', 10, 1 ],
        [ self::TYPE_FILTER, 'wp_privacy_personal_data_exporters', 'racketmanager_register_exporter', 10, 1 ],
        [ self::TYPE_FILTER, 'wp_mail', 'racketmanager_mail', 10, 1 ],
        [ self::TYPE_FILTER, 'email_change_email', 'racketmanager_change_email_address', 10, 3 ],
        [ self::TYPE_FILTER, 'pre_get_document_title', 'set_page_title', 999, 1 ],
    ];

    /**
     * Scheduled (cron) task hooks handled by the RacketManager application instance.
     * Format: [ hook, method, priority, accepted_args ].
     */
    public const array APP_TASK_HOOKS = [
        [ 'rm_resultPending', 'chase_pending_results', 1, 1 ],
        [ 'rm_confirmationPending', 'chase_pending_approvals', 1, 1 ],
        [ 'rm_calculate_player_ratings', 'calculate_player_ratings', 1, 1 ],
        [ 'racketmanager_report_fixture_result', 'report_fixture_result', 10, 2 ],
    ];

    /**
     * Scheduled (cron) task hooks handled by container services.
     * Format: [ hook, service id, method, priority, accepted_args ].
     */
    public const array SERVICE_TASK_HOOKS = [
        [ 'rm_calculate_tournament_ratings', 'tournament_service', 'calculate_player_team_rating_for_tournament', 1, 1 ],
        [ 'rm_calculate_team_ratings', 'competition_service', 'calculate_team_ratings', 10, 3 ],
        [ 'rm_notify_team_entry_open', 'competition_entry_service', 'notify_team_entry_open', 10, 2 ],
        [ 'rm_notify_team_entry_reminder', 'competition_entry_service', 'notify_team_entry_reminder', 10, 2 ],
        [ 'rm_notify_tournament_entry_open', 'competition_entry_service', 'notify_tournament_entry_open', 10, 1 ],
        [ 'rm_notify_tournament_entry_reminder', 'competition_entry_service', 'notify_tournament_entry_open_reminder', 10, 1 ],
        [ 'rm_notify_tournament_finalists', 'tournament_service', 'notify_finalists_for_tournament', 10, 1 ],
        [ 'rm_send_invoices', 'finance_service', 'send_invoices_for_charge', 10, 1 ],
    ];

    private string $plugin_path;
    private bool $libraries_loaded = false;
    /** @var array<int, true> Object ids of application instances whose hooks are registered. */
    private array $booted = [];

    public function __construct(
        private readonly Simple_Container $container,
        ?string $plugin_path = null
    ) {
        $this->plugin_path = $plugin_path ?? ( defined( 'RACKETMANAGER_PATH' ) ? RACKETMANAGER_PATH : dirname( __DIR__, 5 ) . '/' );
    }

    /**
     * Launch the plugin runtime: instantiates RacketManager, exposes it via the legacy `$racketmanager` global,
     * and in the dashboard resolves the decoupled Admin coordinator.
     *
     * @param bool|null     $is_admin         Override for is_admin() detection.
     * @param callable|null $frontend_factory Factory for the frontend instance.
     * @param callable|null $admin_factory    Factory for the admin instance.
     * @return object
     */
    public static function launch( ?bool $is_admin = null, ?callable $frontend_factory = null, ?callable $admin_factory = null ): object {
        $app = $frontend_factory ? $frontend_factory() : RacketManager::get_instance();
        $GLOBALS['racketmanager'] = $app;

        $is_admin ??= function_exists( 'is_admin' ) && is_admin();
        if ( $is_admin ) {
            $admin = $admin_factory
                ? $admin_factory()
                : Admin::get_instance( $app instanceof RacketManager ? $app->container : null, $app instanceof RacketManager ? $app : null );
            return $admin;
        }

        return $app;
    }

    /**
     * Boot the plugin lifecycle for an application instance: load procedural libraries
     * and register hooks exactly once.
     *
     * @param RacketManager $app Application instance.
     */
    public function boot( RacketManager $app ): void {
        global $wpdb;
        if ( isset( $wpdb ) && method_exists( $wpdb, 'show_errors' ) ) {
            $wpdb->show_errors();
        }
        $this->load_libraries();
        $this->register_hooks( $app );
    }

    /**
     * Whether hooks have been registered for the given application instance.
     */
    public function is_booted( RacketManager $app ): bool {
        return isset( $this->booted[ spl_object_id( $app ) ] );
    }

    /**
     * Register all core lifecycle and scheduled-task hooks (idempotent per instance).
     *
     * @param RacketManager $app Application instance.
     */
    public function register_hooks( RacketManager $app ): void {
        if ( $this->is_booted( $app ) ) {
            return;
        }
        $this->booted[ spl_object_id( $app ) ] = true;

        foreach ( $this->get_hook_definitions( $app ) as $definition ) {
            if ( self::TYPE_FILTER === $definition['type'] ) {
                add_filter( $definition['hook'], $definition['callback'], $definition['priority'], $definition['accepted_args'] );
            } else {
                add_action( $definition['hook'], $definition['callback'], $definition['priority'], $definition['accepted_args'] );
            }
        }
    }

    /**
     * Build the full list of hook definitions for the application instance.
     *
     * @param RacketManager $app Application instance.
     * @return array<int, array{type: string, hook: string, callback: callable, priority: int, accepted_args: int}>
     */
    public function get_hook_definitions( RacketManager $app ): array {
        return array_merge(
            $this->get_lifecycle_hook_definitions( $app ),
            $this->get_scheduled_task_hook_definitions( $app )
        );
    }

    /**
     * Frontend/core WordPress lifecycle hooks.
     *
     * @param RacketManager $app Application instance.
     * @return array<int, array{type: string, hook: string, callback: callable, priority: int, accepted_args: int}>
     */
    public function get_lifecycle_hook_definitions( RacketManager $app ): array {
        $definitions = [];
        foreach ( self::APP_HOOKS as [ $type, $hook, $method, $priority, $accepted_args ] ) {
            $definitions[] = $this->definition( $type, $hook, [ $app, $method ], $priority, $accepted_args );
        }

        return $definitions;
    }

    /**
     * Scheduled (cron) task hooks. Service callbacks are resolved from the shared container
     * at registration time; services that are not defined are skipped.
     *
     * @param RacketManager $app Application instance.
     * @return array<int, array{type: string, hook: string, callback: callable, priority: int, accepted_args: int}>
     */
    public function get_scheduled_task_hook_definitions( RacketManager $app ): array {
        $definitions = [];
        foreach ( self::APP_TASK_HOOKS as [ $hook, $method, $priority, $accepted_args ] ) {
            $definitions[] = $this->definition( self::TYPE_ACTION, $hook, [ $app, $method ], $priority, $accepted_args );
        }
        foreach ( self::SERVICE_TASK_HOOKS as [ $hook, $service_id, $method, $priority, $accepted_args ] ) {
            if ( ! $this->container->has( $service_id ) ) {
                continue;
            }
            $definitions[] = $this->definition( self::TYPE_ACTION, $hook, [ $this->container->get( $service_id ), $method ], $priority, $accepted_args );
        }

        return $definitions;
    }

    /**
     * Load procedural libraries: sport registrar scripts (plugin and theme), template tags and helper functions.
     */
    public function load_libraries(): void {
        if ( $this->libraries_loaded ) {
            return;
        }
        $this->libraries_loaded = true;

        foreach ( $this->discover_sport_registrars() as $file ) {
            require_once $file;
        }

        require_once $this->plugin_path . '/template-tags.php';
        require_once $this->plugin_path . '/functions.php';
        if ( defined( 'ABSPATH' ) && file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    }

    /**
     * Locate sport registrar scripts (lowercase non-class files such as tennis.php) in the plugin
     * and the active theme's `Sports` directory, excluding class files like League_Tennis.php.
     *
     * @return string[]
     */
    public function discover_sport_registrars(): array {
        $is_registrar = static fn( string $file ): bool => (bool) preg_match( '/^[a-z0-9\-]+\.php$/', basename( $file ) );

        $plugin_sports = array_filter( $this->read_directory( rtrim( $this->plugin_path, '/' ) . '/src/php/sports' ), $is_registrar );
        $theme_sports  = [];
        if ( function_exists( 'get_stylesheet_directory' ) ) {
            $theme_sports = array_filter( $this->read_directory( get_stylesheet_directory() . '/Sports' ), $is_registrar );
        }

        return array_values( array_unique( array_merge( $plugin_sports, $theme_sports ) ) );
    }

    /**
     * Read PHP files (non-hidden) in a directory.
     *
     * @param string $dir Directory name.
     * @return array<string, string> File name => full path.
     */
    private function read_directory( string $dir ): array {
        $files = [];
        if ( ! is_dir( $dir ) ) {
            return $files;
        }

        $entries = scandir( $dir );
        foreach ( false === $entries ? [] : $entries as $file ) {
            if ( str_starts_with( $file, '.' ) || is_dir( $dir . '/' . $file ) || 'php' !== pathinfo( $file, PATHINFO_EXTENSION ) ) {
                continue;
            }
            $files[ $file ] = $dir . '/' . $file;
        }

        return $files;
    }

    /**
     * @return array{type: string, hook: string, callback: callable, priority: int, accepted_args: int}
     */
    private function definition( string $type, string $hook, callable|array $callback, int $priority, int $accepted_args ): array {
        return [
            'type'          => $type,
            'hook'          => $hook,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        ];
    }
}
