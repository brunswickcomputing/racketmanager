<?php

namespace Racketmanager\Admin\Controllers;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\RacketManager;

/**
 * Class Export_Admin_Controller
 *
 * @package Racketmanager\Admin\Controllers
 */
class Export_Admin_Controller {

    private RacketManager $racketmanager;

    /**
     * Export_Admin_Controller constructor.
     *
     * @param RacketManager $racketmanager
     */
    public function __construct( RacketManager $racketmanager ) {
        $this->racketmanager = $racketmanager;
    }

    /**
     * Handle export request.
     */
    public function handle_export(): void {
        if ( ! isset( $_GET['racketmanager_export'] ) ) {
            return;
        }

        // Only enforce nonce for admin-side triggers if we are in admin.
        // Public calendar exports might not have nonce.
        if ( is_admin() ) {
            check_admin_referer( 'racketmanager_export_nonce' );
        }

        $type = isset( $_GET['racketmanager_export'] ) ? sanitize_text_field( wp_unslash( $_GET['racketmanager_export'] ) ) : '';

        switch ( $type ) {
            case 'calendar':
                $this->delegate_to_rest( 'calendar' );
                return;
            case 'fixtures':
                $this->delegate_to_rest( 'fixtures' );
                return;
            case 'results':
                $this->delegate_to_rest( 'results' );
                return;
            case 'report_results':
                $this->delegate_to_rest( 'report-results' );
                return;
            default:
                wp_die( esc_html__( 'Export function not found', 'racketmanager' ) );
        }
    }

    /**
     * Send response to browser.
     *
     * @param string $content
     * @param string $filename
     * @param string $content_type
     */
    #[NoReturn]
    protected function send_response( string $content, string $filename, string $content_type ): void {
        if ( ! headers_sent() ) {
            header( 'Content-Type: ' . $content_type );
            header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        }

        echo $content;
        $this->terminate();
    }

    /**
     * Terminate execution.
     */
    #[NoReturn]
    protected function terminate(): void {
        exit();
    }

    /**
     * Delegate the request to the REST API internally.
     *
     * @param string $path
     */
    protected function delegate_to_rest( string $path ): void {
        $request = new \WP_REST_Request( 'GET', '/racketmanager/v1/export/' . $path );
        $request->set_query_params( $_GET );
        $response = rest_do_request( $request );

        if ( is_wp_error( $response ) ) {
            wp_die( esc_html( $response->get_error_message() ) );
        }

        $headers      = $response->get_headers();
        $content_type = isset( $headers['Content-Type'] ) ? $headers['Content-Type'] : 'text/plain';
        $filename     = 'export';

        if ( isset( $headers['Content-Disposition'] ) ) {
            if ( preg_match( '/filename="([^"]+)"/', $headers['Content-Disposition'], $matches ) ) {
                $filename = $matches[1];
            }
        }

        $this->send_response( (string) $response->get_data(), $filename, $content_type );
    }
}
