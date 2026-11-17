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
     * @param Manage_Route_Mapper        $route_mapper         Route mapper for section and page slugs.
     * @param Query_Vars_Interface|null  $query_vars           WordPress query variable accessor.
     * @param bool|null                  $force_portal_context Explicit override for portal context (useful in tests).
     */
    public function __construct(
        private Manage_Route_Mapper $route_mapper,
        private ?Query_Vars_Interface $query_vars = null,
        private ?bool $force_portal_context = null,
    ) {
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function is_portal_context(): bool {
        if ( null !== $this->force_portal_context ) {
            return $this->force_portal_context;
        }

        if ( null !== $this->query_vars && ! empty( $this->query_vars->get( 'rm_manage' ) ) ) {
            return true;
        }

        if ( function_exists( 'is_admin' ) && is_admin() ) {
            return false;
        }

        return true;
    }

    /**
     * Return a new instance configured with an explicit portal context flag.
     */
    public function with_portal_context( bool $is_portal ): self {
        return new self( $this->route_mapper, $this->query_vars, $is_portal );
    }
}
