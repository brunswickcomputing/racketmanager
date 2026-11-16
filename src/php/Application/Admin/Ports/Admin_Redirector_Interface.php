<?php
declare( strict_types=1 );

/**
 * Admin redirector port
 *
 * @package RacketManager
 * @subpackage Application/Admin/Ports
 */

namespace Racketmanager\Application\Admin\Ports;

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
