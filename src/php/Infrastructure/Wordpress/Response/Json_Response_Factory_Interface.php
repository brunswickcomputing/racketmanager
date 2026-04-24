<?php

namespace Racketmanager\Infrastructure\Wordpress\Response;

/**
 * Interface for JSON response factories
 */
interface Json_Response_Factory_Interface {
    /**
     * Create a success response
     *
     * @param mixed|null $data
     * @param int|null $status_code
     * @return Response
     */
    public function create_success_response( mixed $data = null, ?int $status_code = 200 ): Response;

    /**
     * Create an error response
     *
     * @param mixed|null $data
     * @param int|null $status_code
     * @return Response
     */
    public function create_error_response( mixed $data = null, ?int $status_code = null ): Response;

    /**
     * Create a raw response
     *
     * @param string $content
     * @param int|null $status_code
     * @return Response
     */
    public function create_raw_response( string $content, ?int $status_code = 200 ): Response;

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
     * Log an error and send a response
     *
     * @param mixed|null $data
     * @param int|null $status_code
     */
    public function log_and_send_error( mixed $data = null, ?int $status_code = null ): void;
}
