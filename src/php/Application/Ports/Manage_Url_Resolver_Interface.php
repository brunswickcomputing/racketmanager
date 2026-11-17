<?php
declare( strict_types=1 );
/**
 * Manage URL Resolver Interface
 *
 * @package RacketManager
 * @subpackage Application/Ports
 */

namespace Racketmanager\Application\Ports;

/**
 * Interface Manage_Url_Resolver_Interface
 *
 * Provides contextual URL generation for administrative and frontend management routes.
 */
interface Manage_Url_Resolver_Interface {

    /**
     * Resolve the target URL for a manage section and view.
     *
     * @param string               $section    Section identifier (e.g. 'clubs', 'competitions', 'leagues').
     * @param string               $view       Optional subview (e.g. 'club', 'draw').
     * @param array<string, mixed> $query_args Optional query parameters (e.g. ['club_id' => 12]).
     *
     * @return string Fully qualified or root-relative URL.
     */
    public function get_url( string $section, string $view = '', array $query_args = [] ): string;

    /**
     * Determine whether the current resolution context is the frontend portal.
     *
     * @return bool
     */
    public function is_portal_context(): bool;
}
