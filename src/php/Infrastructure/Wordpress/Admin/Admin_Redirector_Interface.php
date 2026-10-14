<?php
/**
 * Admin redirector port
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Admin
 */

declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Admin;

/**
 * Delivers a Post-Redirect-Get redirect for an admin request.
 *
 * The production implementation terminates the request; test doubles only record the URL.
 */
interface Admin_Redirector_Interface {

    /**
     * Redirect the browser to the given admin URL.
     *
     * @param string $url Target URL.
     */
    public function redirect( string $url ): void;
}
