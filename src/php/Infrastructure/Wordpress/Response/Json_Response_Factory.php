<?php

namespace Racketmanager\Infrastructure\Wordpress\Response;

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
}
