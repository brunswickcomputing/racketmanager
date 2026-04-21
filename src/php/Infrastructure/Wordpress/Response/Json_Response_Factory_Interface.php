<?php

namespace Racketmanager\Infrastructure\Wordpress\Response;

/**
 * Interface for JSON response factories
 */
interface Json_Response_Factory_Interface {
    /**
     * Send a success response and die
     *
     * @param mixed|null $data
     * @param int|null $status_code
     */
    public function send_success( mixed $data = null, ?int $status_code = null ): void;

    /**
     * Send an error response and die
     *
     * @param mixed|null $data
     * @param int|null $status_code
     */
    public function send_error( mixed $data = null, ?int $status_code = null ): void;

    /**
     * Send raw content and die
     *
     * @param string $content
     * @param int|null $status_code
     */
    public function send_raw( string $content, ?int $status_code = null ): void;

    /**
     * Log an error and send a response
     *
     * @param mixed|null $data
     * @param int|null $status_code
     */
    public function log_and_send_error( mixed $data = null, ?int $status_code = null ): void;
}
