<?php

namespace Racketmanager\Infrastructure\Wordpress\Response;

use JetBrains\PhpStorm\NoReturn;

/**
 * Decorator for Json_Response_Factory that logs errors.
 */
class Logging_Json_Response_Factory implements Json_Response_Factory_Interface {
    /**
     * @var Json_Response_Factory_Interface
     */
    private Json_Response_Factory_Interface $factory;

    /**
     * @param Json_Response_Factory_Interface $factory
     */
    public function __construct( Json_Response_Factory_Interface $factory ) {
        $this->factory = $factory;
    }

    /**
     * @inheritDoc
     */
    public function create_success_response( mixed $data = null, ?int $status_code = 200 ): Response {
        return $this->factory->create_success_response( $data, $status_code );
    }

    /**
     * @inheritDoc
     */
    public function create_error_response( mixed $data = null, ?int $status_code = null ): Response {
        if ( $data ) {
            error_log( 'AJAX Fixture Error: ' . wp_json_encode( $data ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
        return $this->factory->create_error_response( $data, $status_code );
    }

    /**
     * @inheritDoc
     */
    public function create_raw_response( string $content, ?int $status_code = 200 ): Response {
        return $this->factory->create_raw_response( $content, $status_code );
    }

    /**
     * @inheritDoc
     */
    #[NoReturn]
    public function send_success( mixed $data = null, ?int $status_code = null ): void {
        $this->factory->send_success( $data, $status_code );
    }

    /**
     * @inheritDoc
     */
    #[NoReturn]
    public function send_error( mixed $data = null, ?int $status_code = null ): void {
        $this->log_and_send_error( $data, $status_code );
    }


    /**
     * @inheritDoc
     */
    #[NoReturn]
    public function log_and_send_error( mixed $data = null, ?int $status_code = null ): void {
        if ( $data ) {
            error_log( 'AJAX Fixture Error: ' . wp_json_encode( $data ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
        $this->factory->send_error( $data, $status_code );
    }
}
