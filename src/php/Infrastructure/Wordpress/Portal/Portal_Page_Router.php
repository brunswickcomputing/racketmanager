<?php
declare( strict_types=1 );
/**
 * Portal Page Router
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Portal
 */

namespace Racketmanager\Infrastructure\Wordpress\Portal;

use Racketmanager\Admin\Admin_Index;
use Racketmanager\Admin\View_Models\Documentation_Page_View_Model;
use Racketmanager\Admin\View_Models\Overview_Page_View_Model;
use Racketmanager\Application\Ports\Manage_Url_Resolver_Interface;
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Route_Mapper;
use Racketmanager\Services\Container\Simple_Container;

/**
 * Class Portal_Page_Router
 *
 * Intercepts frontend /manage requests, enforces authentication and capability authorization,
 * seamlessly redirects unported sections to wp-admin, and renders ported sections in the portal shell.
 */
class Portal_Page_Router {

    /**
     * @param Simple_Container              $container        DI container.
     * @param Portal_Section_Registry       $section_registry Registered sections and rollout status.
     * @param Manage_Route_Mapper           $route_mapper     Route mapper for sections.
     * @param Manage_Url_Resolver_Interface $url_resolver     Contextual URL generator.
     * @param Portal_Auth_Guard             $auth_guard       Authentication and capability guard.
     * @param Portal_Screen_Presenter       $screen_presenter Layout and view presenter.
     * @param Query_Vars_Interface          $query_vars       Query variables accessor.
     * @param bool                          $auto_terminate   Whether to terminate execution on completion.
     */
    public function __construct(
        private readonly Simple_Container $container,
        private readonly Portal_Section_Registry $section_registry,
        private readonly Manage_Route_Mapper $route_mapper,
        private readonly Manage_Url_Resolver_Interface $url_resolver,
        private readonly Portal_Auth_Guard $auth_guard,
        private readonly Portal_Screen_Presenter $screen_presenter,
        private readonly Query_Vars_Interface $query_vars,
        private readonly bool $auto_terminate = true,
    ) {
    }

    /**
     * Register WordPress action and filter hooks.
     */
    public function register_hooks(): void {
        if ( function_exists( 'add_action' ) ) {
            add_action( 'template_redirect', [ $this, 'handle_template_redirect' ] );
        }
        if ( function_exists( 'add_filter' ) ) {
            add_filter( 'template_include', [ $this, 'handle_template_include' ] );
        }
    }

    /**
     * Check whether the current request targets the frontend management portal.
     */
    public function is_portal_request(): bool {
        $rm_manage = $this->query_vars->get( 'rm_manage' );
        return ! empty( $rm_manage );
    }

    /**
     * Handle template redirect logic for portal routing.
     */
    public function handle_template_redirect(): void {
        if ( ! $this->is_portal_request() ) {
            return;
        }

        // 1. Check user authentication.
        if ( ! $this->auth_guard->is_authenticated() ) {
            $current_url = function_exists( 'home_url' ) && function_exists( 'add_query_arg' )
                ? home_url( add_query_arg( [] ) )
                : '/manage';
            $login_url   = $this->auth_guard->get_login_url( $current_url );

            if ( function_exists( 'wp_safe_redirect' ) ) {
                wp_safe_redirect( $login_url );
            }
            $this->terminate();
            return;
        }

        // 2. Resolve section.
        $raw_section = $this->query_vars->get( 'rm_section' );
        $section     = ! empty( $raw_section ) ? strval( $raw_section ) : 'overview';
        $normalized  = $this->route_mapper->normalize_section( $section );

        if ( 'competitions' === $normalized ) {
            $passthrough_args = $this->extract_passthrough_query_args();
            $target_url       = $this->url_resolver->get_url( 'leagues', '', $passthrough_args );
            if ( function_exists( 'wp_redirect' ) ) {
                wp_redirect( $target_url, 302 );
            }
            $this->terminate();
            return;
        }

        if ( ! $this->section_registry->has( $normalized ) ) {
            if ( function_exists( 'status_header' ) ) {
                status_header( 404 );
            }
            global $wp_query;
            if ( isset( $wp_query ) && is_object( $wp_query ) && method_exists( $wp_query, 'set_404' ) ) {
                $wp_query->set_404();
            }
            return;
        }

        $config = $this->section_registry->get( $normalized );
        if ( null === $config ) {
            return;
        }

        // 3. Authorize user capability.
        if ( ! $this->auth_guard->user_can( $config->capability ) ) {
            if ( function_exists( 'status_header' ) ) {
                status_header( 403 );
            }
            $this->screen_presenter->present_unauthorized( $normalized );
            $this->terminate();
            return;
        }

        // 4. Section fallback: redirect to wp-admin if section is not yet ported.
        if ( ! $config->is_ported ) {
            $raw_view  = $this->query_vars->get( 'rm_view' );
            $view_name = ! empty( $raw_view ) ? strval( $raw_view ) : '';

            $passthrough_args = $this->extract_passthrough_query_args();
            $admin_url        = $this->url_resolver->get_admin_url( $config->slug, $view_name, $passthrough_args );

            if ( function_exists( 'wp_redirect' ) ) {
                wp_redirect( $admin_url, 302 );
            }
            $this->terminate();
            return;
        }

        // 5. Section is ported: dispatch to screen bridge and render portal shell.
        $this->dispatch_screen( $config );
        $this->terminate();
    }

    /**
     * Dispatch request to screen bridge and render output within the portal shell.
     *
     * @param Portal_Section_Config $config Section configuration.
     */
    public function dispatch_screen( Portal_Section_Config $config ): void {
        $raw_view  = $this->query_vars->get( 'rm_view' );
        $view_name = ! empty( $raw_view ) ? strval( $raw_view ) : $config->default_view;

        if ( 'overview' === $config->slug || Overview_Page_View_Model::class === $config->screen_class ) {
            $this->screen_presenter->present(
                [ 'view_model' => new Overview_Page_View_Model() ],
                Overview_Page_View_Model::class,
                'portal/overview',
                $config->slug,
                $config->title
            );
            return;
        }

        if ( 'documentation' === $config->slug || Documentation_Page_View_Model::class === $config->screen_class ) {
            $this->screen_presenter->present(
                [ 'view_model' => new Documentation_Page_View_Model() ],
                Documentation_Page_View_Model::class,
                'admin/documentation',
                $config->slug,
                $config->title
            );
            return;
        }

        $screen = $this->container->get( $config->screen_class );

        ob_start();
        if ( $screen instanceof Admin_Index ) {
            $screen->display_index_page();
        } elseif ( is_object( $screen ) && method_exists( $screen, 'handle_display' ) ) {
            $screen->handle_display( $view_name );
        }
        $content = ob_get_clean();

        $this->screen_presenter->render_layout( $config->title, is_string( $content ) ? $content : '', $config->slug );
    }

    /**
     * Template include filter hook fallback.
     *
     * @param string $template Current template file.
     *
     * @return string
     */
    public function handle_template_include( string $template ): string {
        if ( ! $this->is_portal_request() ) {
            return $template;
        }

        if ( function_exists( 'is_404' ) && is_404() ) {
            return $template;
        }

        return $template;
    }

    /**
     * Extract query arguments that should be passed through on redirects.
     *
     * @return array<string, mixed>
     */
    public function extract_passthrough_query_args(): array {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $query_params = isset( $_GET ) && is_array( $_GET ) ? $_GET : [];
        $excluded     = [ 'rm_manage', 'rm_section', 'rm_view', 'pagename' ];

        return array_filter(
            $query_params,
            static fn( string $key ): bool => ! in_array( $key, $excluded, true ),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Terminate request execution when auto-terminate is enabled.
     */
    protected function terminate(): void {
        if ( $this->auto_terminate ) {
            exit;
        }
    }
}
