<?php
/**
 * WordPress admin redirector
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Admin
 */

declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Admin;

/**
 * Redirects via wp_safe_redirect(), falling back to a JS/meta redirect once headers are sent.
 *
 * WordPress admin screen callbacks run after the admin header has been output, so the fallback is
 * the normal path for admin menu page handlers.
 */
final class Wp_Admin_Redirector implements Admin_Redirector_Interface {

    /**
     * @inheritDoc
     */
    public function redirect( string $url ): void {
        if ( headers_sent() ) {
            $js_url   = esc_url_raw( $url );
            $html_url = esc_url( $url );

            echo '<script>window.location.replace(' . wp_json_encode( $js_url ) . ');</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_attr( $html_url ) . '"></noscript>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit;
        }

        wp_safe_redirect( $url );
        exit;
    }
}
