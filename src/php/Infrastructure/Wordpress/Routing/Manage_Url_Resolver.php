<?php
declare( strict_types=1 );
/**
 * Manage URL Resolver
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Routing
 */

namespace Racketmanager\Infrastructure\Wordpress\Routing;

use Racketmanager\Application\Ports\Manage_Url_Resolver_Interface;
use Racketmanager\Application\Ports\Query_Vars_Interface;

/**
 * Class Manage_Url_Resolver
 *
 * Resolves URLs for management sections and subviews, adapting output based on whether
 * the caller is executing in the frontend management portal or the legacy wp-admin dashboard.
 */
final readonly class Manage_Url_Resolver implements Manage_Url_Resolver_Interface {

    /**
     * @param Manage_Route_Mapper       $route_mapper         Route mapper for section and page slugs.
     * @param Query_Vars_Interface|null $query_vars           WordPress query variable accessor.
     * @param bool|null                 $force_portal_context Explicit override for portal context (useful in tests).
     */
    public function __construct(
        private Manage_Route_Mapper $route_mapper,
        private ?Query_Vars_Interface $query_vars = null,
        private ?bool $force_portal_context = null,
    ) {
    }

    /**
     * Gets the URL for the specified section and view in the current context.
     *
     * @param string $section Section identifier to include in the generated URL.
     * @param string $view Optional view identifier to include in the generated URL.
     * @param array  $query_args Optional query arguments to append to the generated URL.
     *
     * @return string URL for the requested section and view.
     */
    public function get_url( string $section, string $view = '', array $query_args = [] ): string {
        if ( $this->is_portal_context() ) {
            return $this->get_portal_url( $section, $view, $query_args );
        }

        return $this->get_admin_url( $section, $view, $query_args );
    }

    /**
     * Generate a frontend portal permalink (/manage/:section/:view).
     *
     * @param string               $section    Section slug.
     * @param string               $view       Optional subview.
     * @param array<string, mixed> $query_args Optional additional query parameters.
     */
    public function get_portal_url( string $section, string $view = '', array $query_args = [] ): string {
        $section_slug = $this->route_mapper->normalize_section( $section );
        $path         = 'manage/' . $section_slug;

        if ( '' !== trim( $view ) ) {
            $path .= '/' . ltrim( trim( $view ), '/' );
        }

        $url = function_exists( 'home_url' )
            ? home_url( '/' . ltrim( $path, '/' ) )
            : '/' . ltrim( $path, '/' );

        if ( ! empty( $query_args ) && function_exists( 'add_query_arg' ) ) {
            $url = add_query_arg( $query_args, $url );
        }

        return $url;
    }

    /**
     * Generate a legacy wp-admin dashboard URL (admin.php?page=racketmanager-...&view=...).
     *
     * @param string               $section    Section slug.
     * @param string               $view       Optional subview.
     * @param array<string, mixed> $query_args Optional additional query parameters.
     */
    public function get_admin_url( string $section, string $view = '', array $query_args = [] ): string {
        $admin_page = $this->route_mapper->section_to_admin_page( $section );
        $args       = [ 'page' => $admin_page ];

        if ( '' !== trim( $view ) ) {
            $args['view'] = trim( $view );
        }

        $args = array_merge( $args, $query_args );

        if ( function_exists( 'admin_url' ) && function_exists( 'add_query_arg' ) ) {
            return admin_url( add_query_arg( $args, 'admin.php' ) );
        }

        return '/wp-admin/admin.php?' . http_build_query( $args );
    }

    /**
     * Determines whether the current request is being handled in the portal context.
     *
     * @return bool True when portal context is forced or detected from the current query variables, false otherwise.
     */
    public function is_portal_context(): bool {
        if ( null !== $this->force_portal_context ) {
            return $this->force_portal_context;
        }

        $portal_context = false;

        if ( null !== $this->query_vars && ! empty( $this->query_vars->get( 'rm_manage' ) ) ) {
            $portal_context = true;
        }

        if ( function_exists( 'get_query_var' ) && ! empty( get_query_var( 'rm_manage' ) ) ) {
            $portal_context = true;
        }

        return $portal_context;
    }

    /**
     * Creates a new instance with the portal context explicitly set.
     *
     * @param bool $is_portal Whether the new instance should operate in portal context.
     *
     * @return self New instance configured with the specified portal context.
     */
    public function with_portal_context( bool $is_portal ): self {
        return new self( $this->route_mapper, $this->query_vars, $is_portal );
    }
}
