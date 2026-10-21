<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Templates;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Templates\Template_Loader;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Template_Loader_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private string $temp_dir;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->temp_dir = sys_get_temp_dir() . '/rm_template_loader_test_' . uniqid();
        mkdir( $this->temp_dir . '/templates/page_template', 0777, true );
        mkdir( $this->temp_dir . '/templates/pages', 0777, true );
    }

    protected function tearDown(): void {
        $this->remove_directory( $this->temp_dir );
        $this->restore_globals();
        parent::tearDown();
    }

    private function remove_directory( string $dir ): void {
        if ( ! is_dir( $dir ) ) {
            return;
        }
        $files = array_diff( scandir( $dir ) ?: [], [ '.', '..' ] );
        foreach ( $files as $file ) {
            $path = "$dir/$file";
            is_dir( $path ) ? $this->remove_directory( $path ) : unlink( $path );
        }
        rmdir( $dir );
    }

    public function test_default_and_custom_templates_configuration(): void {
        $loader = new Template_Loader( $this->temp_dir );

        self::assertSame( Template_Loader::DEFAULT_TEMPLATES, $loader->get_templates() );
        self::assertSame( 'templates/page_template/template_notitle.php', $loader->resolve_template_slug( 'templates/page_template/template_notitle.php' ) );
        self::assertSame( 'templates/page_template/template_notitle.php', $loader->resolve_template_slug( 'templates/template_notitle.php' ) );
        self::assertSame( 'templates/page_template/template_notitle.php', $loader->resolve_template_slug( 'templates/page_template/template_no_title.php' ) );
        self::assertSame( 'templates/page_template/template-member-account.php', $loader->resolve_template_slug( 'templates/page_template/template-member-account.php' ) );
        self::assertSame( 'templates/page_template/template-member-account.php', $loader->resolve_template_slug( 'templates/template_member_account.php' ) );
        self::assertSame( 'templates/page_template/template-member-account.php', $loader->resolve_template_slug( 'templates/page_template/template_member_account.php' ) );
        self::assertNull( $loader->resolve_template_slug( 'unknown-template.php' ) );
        self::assertNull( $loader->resolve_template_slug( 'template_notitle.php' ) ); // Bare short names should not resolve

        $custom_templates = [
            'custom/template.php' => 'Custom Template',
        ];
        $custom_aliases = [
            'old/legacy.php' => 'custom/template.php',
        ];
        $custom_loader = new Template_Loader( $this->temp_dir, $custom_templates, $custom_aliases );
        self::assertSame( $custom_templates, $custom_loader->get_templates() );
        self::assertSame( 'custom/template.php', $custom_loader->resolve_template_slug( 'custom/template.php' ) );
        self::assertSame( 'custom/template.php', $custom_loader->resolve_template_slug( 'old/legacy.php' ) );

        $loader->set_templates( $custom_templates );
        self::assertSame( $custom_templates, $loader->get_templates() );

        $loader->add_template( 'another/template.php', 'Another Template' );
        self::assertSame(
            [
                'custom/template.php'  => 'Custom Template',
                'another/template.php' => 'Another Template',
            ],
            $loader->get_templates()
        );
    }

    public function test_default_templates_point_to_real_plugin_files_on_disk(): void {
        $real_plugin_path = dirname( __DIR__, 5 );

        foreach ( Template_Loader::DEFAULT_TEMPLATES as $relative_path => $label ) {
            $full_path = $real_plugin_path . '/' . $relative_path;
            self::assertFileExists(
                $full_path,
                sprintf( 'Template "%s" (%s) must exist on disk at "%s"', $label, $relative_path, $full_path )
            );
        }
    }

    public function test_register_hooks_and_add_racketmanager_templates(): void {
        $GLOBALS['wp_stubs_added_filters'] = [];

        $loader = new Template_Loader( $this->temp_dir );
        $loader->register_hooks();

        self::assertArrayHasKey( 'theme_page_templates', $GLOBALS['wp_stubs_added_filters'] );
        $page_template_hook = $GLOBALS['wp_stubs_added_filters']['theme_page_templates'][0];
        self::assertSame( 10, $page_template_hook['priority'] );
        self::assertSame( 3, $page_template_hook['accepted_args'] );

        self::assertArrayHasKey( 'wp_insert_post_data', $GLOBALS['wp_stubs_added_filters'] );
        self::assertArrayHasKey( 'template_include', $GLOBALS['wp_stubs_added_filters'] );
        self::assertArrayHasKey( 'archive_template', $GLOBALS['wp_stubs_added_filters'] );

        $GLOBALS['wp_stubs_added_filters'] = [];
        $loader->add_racketmanager_templates();
        self::assertArrayHasKey( 'theme_page_templates', $GLOBALS['wp_stubs_added_filters'] );
        self::assertSame( 3, $GLOBALS['wp_stubs_added_filters']['theme_page_templates'][0]['accepted_args'] );
    }

    public function test_racketmanager_templates_as_option_merges_templates_and_preserves_legacy_post_meta_selection(): void {
        $loader = new Template_Loader( $this->temp_dir );
        $posts_templates = [
            'theme-template.php' => 'Theme Template',
        ];

        // 1. Global / default dropdown (no specific post being edited)
        $result_global = $loader->racketmanager_templates_as_option( $posts_templates );
        self::assertSame(
            [
                'theme-template.php'                                  => 'Theme Template',
                'templates/page_template/template_notitle.php'        => 'No Title',
                'templates/page_template/template-member-account.php' => 'Member Account',
            ],
            $result_global,
            'Global dropdown should only offer canonical templates'
        );

        // 2. Post being edited with canonical template
        $post_canonical     = new stdClass();
        $post_canonical->ID = 501;
        $GLOBALS['wp_stubs_post_meta'][501]['_wp_page_template'] = 'templates/page_template/template_notitle.php';

        $result_canonical = $loader->racketmanager_templates_as_option( $posts_templates, null, $post_canonical );
        self::assertSame(
            [
                'theme-template.php'                                  => 'Theme Template',
                'templates/page_template/template_notitle.php'        => 'No Title',
                'templates/page_template/template-member-account.php' => 'Member Account',
            ],
            $result_canonical
        );

        // 3. Post being edited with historical template: 'templates/template_notitle.php' (pre-2022)
        $post_pre2022_notitle     = new stdClass();
        $post_pre2022_notitle->ID = 502;
        $GLOBALS['wp_stubs_post_meta'][502]['_wp_page_template'] = 'templates/template_notitle.php';

        $result_pre2022_notitle = $loader->racketmanager_templates_as_option( $posts_templates, null, $post_pre2022_notitle );
        self::assertSame(
            [
                'theme-template.php'                                  => 'Theme Template',
                'templates/page_template/template_notitle.php'        => 'No Title',
                'templates/page_template/template-member-account.php' => 'Member Account',
                'templates/template_notitle.php'                      => 'No Title',
            ],
            $result_pre2022_notitle,
            'Dropdown for post with historical templates/template_notitle.php must include the slug to preserve selection'
        );

        // 4. Post being edited with historical template: 'templates/template_member_account.php' (pre-2022)
        $post_pre2022_account     = new stdClass();
        $post_pre2022_account->ID = 503;
        $GLOBALS['wp_stubs_post_meta'][503]['_wp_page_template'] = 'templates/template_member_account.php';

        $result_pre2022_account = $loader->racketmanager_templates_as_option( $posts_templates, null, $post_pre2022_account );
        self::assertSame(
            [
                'theme-template.php'                                  => 'Theme Template',
                'templates/page_template/template_notitle.php'        => 'No Title',
                'templates/page_template/template-member-account.php' => 'Member Account',
                'templates/template_member_account.php'               => 'Member Account',
            ],
            $result_pre2022_account
        );

        // 5. Post being edited with historical template: 'templates/page_template/template_no_title.php'
        $post_legacy_notitle     = new stdClass();
        $post_legacy_notitle->ID = 504;
        $GLOBALS['wp_stubs_post_meta'][504]['_wp_page_template'] = 'templates/page_template/template_no_title.php';

        $result_legacy_notitle = $loader->racketmanager_templates_as_option( $posts_templates, null, $post_legacy_notitle );
        self::assertSame(
            [
                'theme-template.php'                            => 'Theme Template',
                'templates/page_template/template_notitle.php'  => 'No Title',
                'templates/page_template/template-member-account.php' => 'Member Account',
                'templates/page_template/template_no_title.php' => 'No Title',
            ],
            $result_legacy_notitle
        );

        // 6. Post being edited with historical template: 'templates/page_template/template_member_account.php'
        $post_legacy_account     = new stdClass();
        $post_legacy_account->ID = 505;
        $GLOBALS['wp_stubs_post_meta'][505]['_wp_page_template'] = 'templates/page_template/template_member_account.php';

        $result_legacy_account = $loader->racketmanager_templates_as_option( $posts_templates, null, $post_legacy_account );
        self::assertSame(
            [
                'theme-template.php'                                  => 'Theme Template',
                'templates/page_template/template_notitle.php'        => 'No Title',
                'templates/page_template/template-member-account.php' => 'Member Account',
                'templates/page_template/template_member_account.php' => 'Member Account',
            ],
            $result_legacy_account
        );
    }

    public function test_register_racketmanager_templates_updates_theme_page_templates_cache(): void {
        $GLOBALS['wp_stubs_theme_root']            = '/var/www/themes';
        $GLOBALS['wp_stubs_stylesheet']            = 'test-theme';
        $GLOBALS['wp_stubs_theme_page_templates'] = [
            'theme-page.php' => 'Theme Page',
        ];
        $GLOBALS['wp_stubs_cache_deleted']         = [];
        $GLOBALS['wp_stubs_cache_added']           = [];

        $loader = new Template_Loader( $this->temp_dir );
        $atts   = [ 'post_title' => 'Test Page' ];
        $result = $loader->register_racketmanager_templates( $atts );

        self::assertSame( $atts, $result );

        $cache_key = 'page_templates-' . md5( '/var/www/themes/test-theme' );
        self::assertContains( [ $cache_key, 'themes' ], $GLOBALS['wp_stubs_cache_deleted'] );

        $expected_merged = [
            'theme-page.php'                                      => 'Theme Page',
            'templates/page_template/template_notitle.php'        => 'No Title',
            'templates/page_template/template-member-account.php' => 'Member Account',
        ];
        self::assertContains( [ $cache_key, $expected_merged, 'themes', 1800 ], $GLOBALS['wp_stubs_cache_added'] );
    }

    public function test_load_template_returns_fallback_when_post_not_set_or_no_matching_template(): void {
        $loader = new Template_Loader( $this->temp_dir );

        // No $post set
        unset( $GLOBALS['post'] );
        self::assertSame( '/fallback/page.php', $loader->racketmanager_load_template( '/fallback/page.php' ) );

        // $post set but no matching template
        $post     = new stdClass();
        $post->ID = 42;
        $GLOBALS['post'] = $post;
        $GLOBALS['wp_stubs_post_meta'][42]['_wp_page_template'] = 'default';

        self::assertSame( '/fallback/page.php', $loader->racketmanager_load_template( '/fallback/page.php' ) );
    }

    public function test_load_template_returns_plugin_file_when_template_assigned_and_exists(): void {
        $template_file = $this->temp_dir . '/templates/page_template/template_notitle.php';
        file_put_contents( $template_file, '<?php // no title' );

        $post     = new stdClass();
        $post->ID = 101;
        $GLOBALS['post'] = $post;
        $GLOBALS['wp_stubs_post_meta'][101]['_wp_page_template'] = 'templates/page_template/template_notitle.php';

        $loader = new Template_Loader( $this->temp_dir );
        $result = $loader->racketmanager_load_template( '/fallback/page.php' );

        self::assertSame( $template_file, $result );
    }

    public function test_load_template_prefers_theme_override_over_plugin_file(): void {
        $theme_dir = $this->temp_dir . '/theme';
        mkdir( $theme_dir . '/templates/page_template', 0777, true );
        $theme_file = $theme_dir . '/templates/page_template/template_notitle.php';
        file_put_contents( $theme_file, '<?php // theme override' );

        $plugin_file = $this->temp_dir . '/templates/page_template/template_notitle.php';
        file_put_contents( $plugin_file, '<?php // plugin original' );

        $GLOBALS['wp_stubs_stylesheet_directory'] = $theme_dir;

        $post     = new stdClass();
        $post->ID = 102;
        $GLOBALS['post'] = $post;
        $GLOBALS['wp_stubs_post_meta'][102]['_wp_page_template'] = 'templates/page_template/template_notitle.php';

        $loader = new Template_Loader( $this->temp_dir );
        $result = $loader->racketmanager_load_template( '/fallback/page.php' );

        self::assertSame( $theme_file, $result );
    }

    public function test_load_template_prefers_theme_racketmanager_subfolder_override(): void {
        $theme_dir = $this->temp_dir . '/theme-subfolder';
        mkdir( $theme_dir . '/racketmanager', 0777, true );
        $theme_file = $theme_dir . '/racketmanager/template_notitle.php';
        file_put_contents( $theme_file, '<?php // theme racketmanager subfolder' );

        $plugin_file = $this->temp_dir . '/templates/page_template/template_notitle.php';
        file_put_contents( $plugin_file, '<?php // plugin original' );

        $GLOBALS['wp_stubs_template_directory'] = $theme_dir;

        $post     = new stdClass();
        $post->ID = 103;
        $GLOBALS['post'] = $post;
        $GLOBALS['wp_stubs_post_meta'][103]['_wp_page_template'] = 'templates/page_template/template_notitle.php';

        $loader = new Template_Loader( $this->temp_dir );
        $result = $loader->racketmanager_load_template( '/fallback/page.php' );

        self::assertSame( $theme_file, $result );
    }

    public function test_load_template_resolves_historical_slugs_without_path_leakage(): void {
        $notitle_file = $this->temp_dir . '/templates/page_template/template_notitle.php';
        file_put_contents( $notitle_file, '<?php // notitle' );

        $account_file = $this->temp_dir . '/templates/page_template/template-member-account.php';
        file_put_contents( $account_file, '<?php // member account' );

        $loader = new Template_Loader( $this->temp_dir );

        $test_cases = [
            'templates/template_notitle.php'                      => $notitle_file,
            'templates/template_member_account.php'               => $account_file,
            'templates/page_template/template_no_title.php'       => $notitle_file,
            'templates/page_template/template_member_account.php' => $account_file,
            'templates/page_template/template_notitle.php'        => $notitle_file,
            'templates/page_template/template-member-account.php' => $account_file,
        ];

        $post_id = 110;
        foreach ( $test_cases as $saved_meta_slug => $expected_file ) {
            $post     = new stdClass();
            $post->ID = ++$post_id;
            $GLOBALS['post'] = $post;
            $GLOBALS['wp_stubs_post_meta'][ $post->ID ]['_wp_page_template'] = $saved_meta_slug;

            ob_start();
            $resolved = $loader->racketmanager_load_template( '/fallback/page.php' );
            $output   = ob_get_clean();

            self::assertSame( '', $output, sprintf( 'Resolution for slug "%s" must not produce any output / path leakage', $saved_meta_slug ) );
            self::assertSame( $expected_file, $resolved, sprintf( 'Slug "%s" should resolve to "%s"', $saved_meta_slug, $expected_file ) );
        }
    }

    public function test_load_template_returns_fallback_without_output_when_file_not_found(): void {
        $post     = new stdClass();
        $post->ID = 104;
        $GLOBALS['post'] = $post;
        $GLOBALS['wp_stubs_post_meta'][104]['_wp_page_template'] = 'templates/page_template/template-member-account.php';

        // Note: template-member-account.php has not been written to $this->temp_dir
        $loader = new Template_Loader( $this->temp_dir );

        ob_start();
        $result = $loader->racketmanager_load_template( '/fallback/page.php' );
        $output = ob_get_clean();

        self::assertSame( '', $output, 'Missing template resolution must not echo the missing file path' );
        self::assertSame( '/fallback/page.php', $result );
    }

    public function test_archive_template_routing_and_theme_overrides(): void {
        $loader = new Template_Loader( $this->temp_dir );

        // Normal archive (not rules or how-to)
        $GLOBALS['wp_stubs_is_category'] = false;
        self::assertSame( '/fallback/archive.php', $loader->racketmanager_archive_template( '/fallback/archive.php' ) );

        // Rules category with plugin default
        $GLOBALS['wp_stubs_is_category'] = 'rules';
        self::assertSame(
            $this->temp_dir . '/templates/pages/category-rules.php',
            $loader->racketmanager_archive_template( '/fallback/archive.php' )
        );

        // How-to category with plugin default
        $GLOBALS['wp_stubs_is_category'] = 'how-to';
        self::assertSame(
            $this->temp_dir . '/templates/pages/category-how-to.php',
            $loader->racketmanager_archive_template( '/fallback/archive.php' )
        );

        // How-to category with theme override
        $theme_dir = $this->temp_dir . '/theme-archive';
        mkdir( $theme_dir . '/racketmanager', 0777, true );
        $theme_rules_file = $theme_dir . '/racketmanager/category-rules.php';
        file_put_contents( $theme_rules_file, '<?php // theme rules' );
        $GLOBALS['wp_stubs_stylesheet_directory'] = $theme_dir;

        $GLOBALS['wp_stubs_is_category'] = 'rules';
        self::assertSame(
            $theme_rules_file,
            $loader->racketmanager_archive_template( '/fallback/archive.php' )
        );
    }

    public function test_container_wiring_and_resolution(): void {
        $container = Container_Bootstrap::boot();

        self::assertTrue( $container->has( 'template_loader' ) );
        self::assertTrue( $container->has( Template_Loader::class ) );

        $loader1 = $container->get( 'template_loader' );
        $loader2 = $container->get( Template_Loader::class );

        self::assertInstanceOf( Template_Loader::class, $loader1 );
        self::assertSame( $loader1, $loader2 );
    }

    public function test_racketmanager_delegation_to_template_loader(): void {
        $custom_templates = [
            'custom/page.php' => 'Custom Page',
        ];
        $loader = new Template_Loader( $this->temp_dir, $custom_templates );

        $reflection = new \ReflectionClass( RacketManager::class );
        /** @var RacketManager $app */
        $app = $reflection->newInstanceWithoutConstructor();
        $app->template_loader = $loader;

        // add_racketmanager_templates delegation
        $GLOBALS['wp_stubs_added_filters'] = [];
        $app->add_racketmanager_templates();
        self::assertArrayHasKey( 'theme_page_templates', $GLOBALS['wp_stubs_added_filters'] );

        // racketmanager_templates_as_option delegation
        $opts = $app->racketmanager_templates_as_option( [ 'orig.php' => 'Original' ] );
        self::assertSame(
            [
                'orig.php'        => 'Original',
                'custom/page.php' => 'Custom Page',
            ],
            $opts
        );

        // register_racketmanager_templates delegation
        $GLOBALS['wp_stubs_theme_root']            = '/theme/root';
        $GLOBALS['wp_stubs_stylesheet']            = 'child';
        $GLOBALS['wp_stubs_theme_page_templates'] = [];
        $GLOBALS['wp_stubs_cache_added']           = [];
        $app->register_racketmanager_templates( [ 'attr' => 'val' ] );
        $cache_key = 'page_templates-' . md5( '/theme/root/child' );
        self::assertContains( [ $cache_key, $custom_templates, 'themes', 1800 ], $GLOBALS['wp_stubs_cache_added'] );

        // racketmanager_load_template delegation
        $custom_file = $this->temp_dir . '/custom/page.php';
        mkdir( $this->temp_dir . '/custom', 0777, true );
        file_put_contents( $custom_file, '<?php // custom' );

        $post     = new stdClass();
        $post->ID = 200;
        $GLOBALS['post'] = $post;
        $GLOBALS['wp_stubs_post_meta'][200]['_wp_page_template'] = 'custom/page.php';

        self::assertSame( $custom_file, $app->racketmanager_load_template( '/fallback.php' ) );

        // racketmanager_archive_template delegation
        $GLOBALS['wp_stubs_is_category'] = 'rules';
        self::assertSame(
            $this->temp_dir . '/templates/pages/category-rules.php',
            $app->racketmanager_archive_template( '/fallback.php' )
        );
    }

    public function test_upgrade_v10_0_18_migrates_historical_page_template_slugs(): void {
        $this->expectOutputString( '<p>starting 10.0.18 upgrade</p>' );

        $executed_queries = $this->run_v10_0_18_upgrade( '10.0.17' );

        self::assertCount( 4, $executed_queries );
        self::assertStringContainsString( "UPDATE wp_postmeta SET `meta_value` = 'templates/page_template/template_notitle.php' WHERE `meta_key` = '_wp_page_template' AND `meta_value` = 'templates/template_notitle.php'", $executed_queries[0] );
        self::assertStringContainsString( "UPDATE wp_postmeta SET `meta_value` = 'templates/page_template/template-member-account.php' WHERE `meta_key` = '_wp_page_template' AND `meta_value` = 'templates/template_member_account.php'", $executed_queries[1] );
        self::assertStringContainsString( "UPDATE wp_postmeta SET `meta_value` = 'templates/page_template/template_notitle.php' WHERE `meta_key` = '_wp_page_template' AND `meta_value` = 'templates/page_template/template_no_title.php'", $executed_queries[2] );
        self::assertStringContainsString( "UPDATE wp_postmeta SET `meta_value` = 'templates/page_template/template-member-account.php' WHERE `meta_key` = '_wp_page_template' AND `meta_value` = 'templates/page_template/template_member_account.php'", $executed_queries[3] );
    }

    public function test_upgrade_v10_0_18_is_noop_when_already_installed(): void {
        $this->expectOutputString( '' );

        self::assertSame( [], $this->run_v10_0_18_upgrade( '10.0.18' ) );
        self::assertSame( [], $this->run_v10_0_18_upgrade( '10.1.0' ) );
    }

    /**
     * @return string[] Executed SQL queries.
     */
    private function run_v10_0_18_upgrade( string $installed_version ): array {
        global $wpdb;

        $executed_queries = [];
        $mock_wpdb = $this->createMock( \wpdb::class );
        $mock_wpdb->prefix   = 'wp_';
        $mock_wpdb->postmeta = 'wp_postmeta';
        $mock_wpdb->method( 'prepare' )->willReturnCallback(
            fn( string $query, ...$args ) => vsprintf( str_replace( '%s', "'%s'", $query ), $args )
        );
        $mock_wpdb->method( 'query' )->willReturnCallback(
            function( string $sql ) use ( &$executed_queries ) {
                $executed_queries[] = $sql;
                return 1;
            }
        );

        $saved_wpdb = $wpdb;
        $wpdb       = $mock_wpdb;

        try {
            $container = Container_Bootstrap::boot();
            $reflection = new \ReflectionClass( RacketManager::class );
            /** @var RacketManager $app */
            $app = $reflection->newInstanceWithoutConstructor();
            $app->options   = [ 'dbversion' => $installed_version ];
            $app->container = $container;

            $GLOBALS['racketmanager'] = $app;

            $upgrade = new \Racketmanager\Services\Upgrade( $app );
            $method  = new \ReflectionMethod( $upgrade, 'v10_0_18' );
            $method->invoke( $upgrade );
        } finally {
            $wpdb = $saved_wpdb;
            unset( $GLOBALS['racketmanager'] );
        }

        return $executed_queries;
    }
}
