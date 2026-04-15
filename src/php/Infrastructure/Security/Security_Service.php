<?php

namespace Racketmanager\Infrastructure\Security;

/**
 * Service for handling security checks
 */
final class Security_Service implements Security_Service_Interface {
    /**
     * Verify WordPress nonce
     *
     * @param string $nonce The nonce value from request
     * @param string $nonce_action The nonce action name
     * @return bool
     */
    public function verify_nonce( string $nonce, string $nonce_action ): bool {
        return (bool) wp_verify_nonce( $nonce, $nonce_action );
    }

    /**
     * Check if the current user has a specific capability
     *
     * @param string $capability
     * @return bool
     */
    public function current_user_can( string $capability ): bool {
        return current_user_can( $capability );
    }
}
