<?php
declare( strict_types=1 );

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

    /**
     * Check if the current visitor is a logged-in user
     *
     * @return bool
     */
    public function is_user_logged_in(): bool {
        return function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false;
    }

    /**
     * Get the current user ID
     *
     * @return int
     */
    public function get_current_user_id(): int {
        return function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : 0;
    }
}
