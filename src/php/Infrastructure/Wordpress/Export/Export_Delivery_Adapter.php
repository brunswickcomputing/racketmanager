<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Export;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\RacketManager;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Driving/Delivery adapter for exporting data formats (calendar, fixtures, results, report-results).
 *
 * Dispatches export queries received via HTTP GET / admin actions by delegating
 * to the REST API layer, where the Export_Controller and Rest_Controller_Adapter
 * enforce authentication and route-level capabilities before calling Data_Exporter_Interface.
 */
class Export_Delivery_Adapter {

    protected ?RacketManager $racketmanager;

    /**
     * Export_Delivery_Adapter constructor.
     *
     * @param RacketManager|null $racketmanager
     */
    public function __construct( ?RacketManager $racketmanager = null ) {
        $this->racketmanager = $racketmanager ?? ( $GLOBALS['racketmanager'] ?? null );
    }

    /**
     * Handle export request.
     *
     * @param array|null $query_params
     */
    public function handle_export( ?array $query_params = null ): void {
        $params = $query_params ?? $_GET;
        if ( ! isset( $params['racketmanager_export'] ) ) {
            return;
        }

        // Only enforce nonce for admin-side triggers if we are in admin.
        if ( is_admin() ) {
            check_admin_referer( 'racketmanager_export_nonce' );
        }

        $type = sanitize_text_field( wp_unslash( (string) $params['racketmanager_export'] ) );

        switch ( $type ) {
            case 'calendar':
                $this->delegate_to_rest( 'calendar', $params );
                return;
            case 'fixtures':
                $this->delegate_to_rest( 'fixtures', $params );
                return;
            case 'results':
                $this->delegate_to_rest( 'results', $params );
                return;
            case 'report_results':
                $this->delegate_to_rest( 'report-results', $params );
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
     * @param string     $path
     * @param array|null $query_params
     */
    protected function delegate_to_rest( string $path, ?array $query_params = null ): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/export/' . $path );
        $request->set_query_params( $query_params ?? $_GET );
        $response = rest_do_request( $request );

        if ( is_wp_error( $response ) ) {
            $status = (int) ( $response->get_error_data()['status'] ?? 500 );
            wp_die( esc_html( $response->get_error_message() ), '', array( 'response' => $status ) );
            return;
        }

        if ( $response instanceof WP_REST_Response && ( ( method_exists( $response, 'is_error' ) && $response->is_error() ) || $response->get_status() >= 400 ) ) {
            $data    = $response->get_data();
            $message = '';
            if ( is_array( $data ) && isset( $data['message'] ) ) {
                $message = (string) $data['message'];
            } elseif ( is_string( $data ) ) {
                $message = $data;
            } else {
                $message = __( 'Export request failed', 'racketmanager' );
            }
            $status = $response->get_status();
            wp_die( esc_html( $message ), '', array( 'response' => $status ) );
            return;
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
