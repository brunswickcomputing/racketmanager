<?php
declare( strict_types=1 );
/**
 * Admin action guard interface (nonce + capability)
 *
 * @package RacketManager
 * @subpackage Application/Admin/Ports
 */

namespace Racketmanager\Application\Admin\Ports;

use Racketmanager\Exceptions\Invalid_Status_Exception;

interface Action_Guard_Interface {
    /**
     * Nonce + capability guard (use for POST actions).
     *
     * @param string $nonce_field  Nonce form field name.
     * @param string $nonce_action Nonce action name.
     * @param string $capability   Required user capability.
     *
     * @throws Invalid_Status_Exception If verification fails.
     */
    public function assert_allowed( string $nonce_field, string $nonce_action, string $capability ): void;

    /**
     * Capability-only guard (useful for GET requests where no nonce is present).
     *
     * @param string $capability Required user capability.
     *
     * @throws Invalid_Status_Exception If verification fails.
     */
    public function assert_capability( string $capability ): void;
}
