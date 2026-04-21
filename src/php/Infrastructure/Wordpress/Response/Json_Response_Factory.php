<?php

namespace Racketmanager\Infrastructure\Wordpress\Response;

use JetBrains\PhpStorm\NoReturn;

/**
 * Factory for creating and sending JSON responses for WordPress AJAX
 */
class Json_Response_Factory implements Json_Response_Factory_Interface {
    /**
     * Send a success response and die
     *
     * @param mixed|null $data
     * @param int|null $status_code
     */
    public function send_success( mixed $data = null, ?int $status_code = null ): void {
        wp_send_json_success( $data, $status_code );
    }

    /**
     * Send an error response and die
     *
     * @param mixed|null $data
     * @param int|null $status_code
     */
    public function send_error( mixed $data = null, ?int $status_code = null ): void {
        wp_send_json_error( $data, $status_code );
    }

    /**
     * Send raw content and die
     *
     * @param string $content
     * @param int|null $status_code
     */
    #[NoReturn]
    public function send_raw( string $content, ?int $status_code = null ): void {
        if ( $status_code ) {
            status_header( $status_code );
        }
        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        die();
    }

    /**
     * Log an error and send a response
     *
     * @param mixed|null $data
     * @param int|null $status_code
     */
    public function log_and_send_error( mixed $data = null, ?int $status_code = null ): void {
        if ( $data ) {
            error_log( 'AJAX Error: ' . wp_json_encode( $data ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
        $this->send_error( $data, $status_code );
    }
}
