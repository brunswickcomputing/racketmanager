<?php

namespace Racketmanager\Infrastructure\Wordpress\Response;

use JetBrains\PhpStorm\NoReturn;

/**
 * Factory for creating and sending JSON responses for WordPress AJAX
 */
class Json_Response_Factory implements Json_Response_Factory_Interface {
    /**
     * Create a success response
     *
     * @param mixed|null $data
     * @param int|null $status_code
     * @return Response
     */
    public function create_success_response( mixed $data = null, ?int $status_code = 200 ): Response {
        $response = [
            'success' => true,
        ];

        if ( isset( $data ) ) {
            $response['data'] = $data;
        }

        return new Response( $response, $status_code );
    }

    /**
     * Create an error response
     *
     * @param mixed|null $data
     * @param int|null $status_code
     * @return Response
     */
    public function create_error_response( mixed $data = null, ?int $status_code = null ): Response {
        $response = [
            'success' => false,
        ];

        if ( is_wp_error( $data ) ) {
            $error_data = [];
            foreach ( $data->get_error_codes() as $code ) {
                $error_data[ $code ] = $data->get_error_messages( $code );
            }
            $response['data'] = $error_data;
        } elseif ( isset( $data ) ) {
            $response['data'] = $data;
        }

        return new Response( $response, $status_code ?? 400 );
    }

    /**
     * Create a raw response
     *
     * @param string $content
     * @param int|null $status_code
     * @return Response
     */
    public function create_raw_response( string $content, ?int $status_code = 200 ): Response {
        return new Response( $content, $status_code );
    }

    /**
     * Send a success response and die
     *
     * @param mixed|null $data
     * @param int|null $status_code
     */
    #[NoReturn]
    public function send_success( mixed $data = null, ?int $status_code = null ): void {
        wp_send_json_success( $data, $status_code );
    }

    /**
     * Send an error response and die
     *
     * @param mixed|null $data
     * @param int|null $status_code
     */
    #[NoReturn]
    public function send_error( mixed $data = null, ?int $status_code = null ): void {
        wp_send_json_error( $data, $status_code );
    }


    /**
     * Log an error and send a response
     *
     * @param mixed|null $data
     * @param int|null $status_code
     */
    #[NoReturn]
    public function log_and_send_error( mixed $data = null, ?int $status_code = null ): void {
        if ( $data ) {
            error_log( 'AJAX Error: ' . wp_json_encode( $data ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
        $this->send_error( $data, $status_code );
    }
}
