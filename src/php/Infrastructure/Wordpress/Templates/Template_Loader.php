<?php
/**
 * Template Loader
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Templates
 */

declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Templates;

use function add_filter;
use function basename;
use function file_exists;
use function function_exists;
use function get_post_meta;
use function get_stylesheet;
use function get_stylesheet_directory;
use function get_template_directory;
use function get_theme_root;
use function is_array;
use function is_category;
use function is_numeric;
use function is_object;
use function is_string;
use function ltrim;
use function md5;
use function rtrim;
use function wp_cache_add;
use function wp_cache_delete;
use function wp_get_theme;

/**
 * Service for registering, discovering, and loading plugin page and archive templates.
 */
class Template_Loader {

    public const DEFAULT_TEMPLATES = [
        'templates/page_template/template_notitle.php'        => 'No Title',
        'templates/page_template/template-member-account.php' => 'Member Account',
    ];

    public const TEMPLATE_ALIASES = [
        'templates/template_notitle.php'                      => 'templates/page_template/template_notitle.php',
        'templates/template_member_account.php'               => 'templates/page_template/template-member-account.php',
        'templates/page_template/template_no_title.php'       => 'templates/page_template/template_notitle.php',
        'templates/page_template/template_member_account.php' => 'templates/page_template/template-member-account.php',
    ];

    private array $templates;
    private array $aliases;
    private string $plugin_path;

    /**
     * @param string|null $plugin_path Root directory of the plugin.
     * @param array|null  $templates   Mapping of template file path to template name.
     * @param array|null  $aliases     Mapping of legacy/alias slugs to canonical template paths.
     */
    public function __construct( ?string $plugin_path = null, ?array $templates = null, ?array $aliases = null ) {
        $this->plugin_path = $plugin_path ?? ( defined( 'RACKETMANAGER_PATH' ) ? (string) RACKETMANAGER_PATH : '' );
        $this->templates   = $templates ?? self::DEFAULT_TEMPLATES;
        $this->aliases     = $aliases ?? self::TEMPLATE_ALIASES;
    }

    /**
     * Registers WordPress filters for page and archive template handling.
     */
    public function register_hooks(): void {
        add_filter( 'theme_page_templates', [ $this, 'racketmanager_templates_as_option' ], 10, 3 );
        add_filter( 'wp_insert_post_data', [ $this, 'register_racketmanager_templates' ] );
        add_filter( 'template_include', [ $this, 'racketmanager_load_template' ] );
        add_filter( 'archive_template', [ $this, 'racketmanager_archive_template' ] );
    }

    /**
     * Backwards-compatible alias for registering template filters.
     */
    public function add_racketmanager_templates(): void {
        $this->register_hooks();
    }

    /**
     * Get tracked page templates.
     *
     * @return array<string, string>
     */
    public function get_templates(): array {
        return $this->templates;
    }

    /**
     * Set tracked page templates.
     *
     * @param array<string, string> $templates
     */
    public function set_templates( array $templates ): void {
        $this->templates = $templates;
    }

    /**
     * Add a single page template to tracked templates.
     *
     * @param string $path  Relative template file path.
     * @param string $label Template name in dropdown.
     */
    public function add_template( string $path, string $label ): void {
        $this->templates[ $path ] = $label;
    }

    /**
     * Resolves a template slug (including legacy aliases) to its canonical relative path.
     *
     * @param string $template_slug
     * @return string|null Resolved template relative path, or null if not a recognized plugin template.
     */
    public function resolve_template_slug( string $template_slug ): ?string {
        if ( isset( $this->templates[ $template_slug ] ) ) {
            return $template_slug;
        }

        if ( isset( $this->aliases[ $template_slug ] ) ) {
            return $this->aliases[ $template_slug ];
        }

        return null;
    }

    /**
     * Adds tracked templates to the page template dropdown.
     *
     * @param array $posts_templates Existing page templates list.
     * @param mixed $theme           Theme object (WP_Theme|null).
     * @param mixed $post            Post object (WP_Post|null) or ID being edited.
     * @return array
     */
    public function racketmanager_templates_as_option( array $posts_templates, mixed $theme = null, mixed $post = null ): array {
        $templates = array_merge( $posts_templates, $this->templates );

        $post_id = null;
        if ( is_object( $post ) && isset( $post->ID ) ) {
            $post_id = (int) $post->ID;
        } elseif ( is_numeric( $post ) && (int) $post > 0 ) {
            $post_id = (int) $post;
        }

        if ( null !== $post_id && function_exists( 'get_post_meta' ) ) {
            $current_slug = get_post_meta( $post_id, '_wp_page_template', true );
            if ( is_string( $current_slug ) && isset( $this->aliases[ $current_slug ] ) && ! isset( $templates[ $current_slug ] ) ) {
                $canonical_target = $this->aliases[ $current_slug ];
                $label            = $this->templates[ $canonical_target ] ?? 'Page Template';
                $templates[ $current_slug ] = $label;
            }
        }

        return $templates;
    }

    /**
     * Injects tracked templates into WordPress theme page templates cache.
     *
     * @param array $atts Insert post attributes.
     * @return array
     */
    public function register_racketmanager_templates( array $atts ): array {
        $theme_root = function_exists( 'get_theme_root' ) ? (string) get_theme_root() : '';
        $stylesheet = function_exists( 'get_stylesheet' ) ? (string) get_stylesheet() : '';
        $cache_key  = 'page_templates-' . md5( $theme_root . '/' . $stylesheet );

        $page_templates = [];
        if ( function_exists( 'wp_get_theme' ) ) {
            $theme = wp_get_theme();
            if ( is_object( $theme ) && method_exists( $theme, 'get_page_templates' ) ) {
                $raw_templates = $theme->get_page_templates();
                if ( is_array( $raw_templates ) ) {
                    $page_templates = $raw_templates;
                }
            }
        }

        if ( function_exists( 'wp_cache_delete' ) ) {
            wp_cache_delete( $cache_key, 'themes' );
        }

        $page_templates = array_merge( $page_templates, $this->templates );

        if ( function_exists( 'wp_cache_add' ) ) {
            wp_cache_add( $cache_key, $page_templates, 'themes', 1800 );
        }

        return $atts;
    }

    /**
     * Filters template_include to load the page template if assigned to current post.
     *
     * @param string $template Fallback template path.
     * @return string
     */
    public function racketmanager_load_template( string $template ): string {
        global $post;

        if ( ! $post || ! isset( $post->ID ) ) {
            return $template;
        }

        $template_slug = function_exists( 'get_post_meta' ) ? get_post_meta( $post->ID, '_wp_page_template', true ) : '';
        if ( ! is_string( $template_slug ) || '' === $template_slug ) {
            return $template;
        }

        $resolved_slug = $this->resolve_template_slug( $template_slug );
        if ( null === $resolved_slug ) {
            return $template;
        }

        // 1. Check for theme override first.
        $theme_override = $this->locate_theme_template( $template_slug );
        if ( null === $theme_override && $resolved_slug !== $template_slug ) {
            $theme_override = $this->locate_theme_template( $resolved_slug );
        }
        if ( null !== $theme_override ) {
            return $theme_override;
        }

        // 2. Plugin-provided template.
        $plugin_file = rtrim( $this->plugin_path, '/' ) . '/' . ltrim( $resolved_slug, '/' );
        if ( file_exists( $plugin_file ) ) {
            return $plugin_file;
        }

        return $template;
    }

    /**
     * Filters archive_template to route category archives.
     *
     * @param string $template Fallback archive template path.
     * @return string
     */
    public function racketmanager_archive_template( string $template ): string {
        if ( ! function_exists( 'is_category' ) ) {
            return $template;
        }

        if ( is_category( 'rules' ) ) {
            $override = $this->locate_theme_template( 'category-rules.php' )
                ?? $this->locate_theme_template( 'templates/pages/category-rules.php' );
            if ( null !== $override ) {
                return $override;
            }

            return rtrim( $this->plugin_path, '/' ) . '/templates/pages/category-rules.php';
        }

        if ( is_category( 'how-to' ) ) {
            $override = $this->locate_theme_template( 'category-how-to.php' )
                ?? $this->locate_theme_template( 'templates/pages/category-how-to.php' );
            if ( null !== $override ) {
                return $override;
            }

            return rtrim( $this->plugin_path, '/' ) . '/templates/pages/category-how-to.php';
        }

        return $template;
    }

    /**
     * Locates a template candidate in stylesheet or template theme directories.
     *
     * @param string $relative_path
     * @return string|null
     */
    private function locate_theme_template( string $relative_path ): ?string {
        $candidates = [];

        if ( function_exists( 'get_stylesheet_directory' ) ) {
            $sheet_dir = (string) get_stylesheet_directory();
            if ( '' !== $sheet_dir ) {
                $candidates[] = rtrim( $sheet_dir, '/' ) . '/' . ltrim( $relative_path, '/' );
                $candidates[] = rtrim( $sheet_dir, '/' ) . '/racketmanager/' . basename( $relative_path );
            }
        }

        if ( function_exists( 'get_template_directory' ) ) {
            $template_dir = (string) get_template_directory();
            if ( '' !== $template_dir ) {
                $candidates[] = rtrim( $template_dir, '/' ) . '/' . ltrim( $relative_path, '/' );
                $candidates[] = rtrim( $template_dir, '/' ) . '/racketmanager/' . basename( $relative_path );
            }
        }

        foreach ( $candidates as $candidate ) {
            if ( file_exists( $candidate ) ) {
                return $candidate;
            }
        }

        return null;
    }
}
