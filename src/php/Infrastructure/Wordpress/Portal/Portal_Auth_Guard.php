<?php
declare( strict_types=1 );
/**
 * Portal Auth Guard
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Portal
 */

namespace Racketmanager\Infrastructure\Wordpress\Portal;

/**
 * Class Portal_Auth_Guard
 *
 * Encapsulates authentication and capability authorization checks for portal requests.
 */
readonly class Portal_Auth_Guard {

    /**
     * Check whether the current user is authenticated.
     */
    public function is_authenticated(): bool {
        return function_exists( 'is_user_logged_in' ) && is_user_logged_in();
    }

    /**
     * Check whether the current user has the required capability.
     */
    public function user_can( string $capability ): bool {
        if ( '' === $capability ) {
            return true;
        }

        return function_exists( 'current_user_can' ) && current_user_can( $capability );
    }

    /**
     * Build login redirect URL.
     */
    public function get_login_url( string $redirect_to ): string {
        if ( function_exists( 'wp_login_url' ) ) {
            return wp_login_url( $redirect_to );
        }

        return '/wp-login.php?redirect_to=' . rawurlencode( $redirect_to );
    }
}
