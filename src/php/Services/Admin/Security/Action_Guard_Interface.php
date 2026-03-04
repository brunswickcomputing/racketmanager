<?php
/**
 * Admin action guard interface (nonce + capability)
 *
 * @package RacketManager
 * @subpackage Services/Admin/Security
 */

namespace Racketmanager\Services\Admin\Security;

use Racketmanager\Exceptions\Invalid_Status_Exception;

interface Action_Guard_Interface {
    /**
     * Nonce + capability guard (use for POST actions).
     *
     * @throws Invalid_Status_Exception
     */
    public function assert_allowed( string $nonce_field, string $nonce_action, string $capability ): void;

    /**
     * Capability-only guard (useful for GET requests where no nonce is present).
     *
     * @throws Invalid_Status_Exception
     */
    public function assert_capability( string $capability ): void;
}
