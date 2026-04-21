<?php

namespace Racketmanager\Infrastructure\Wordpress\Response;

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
    public function send_success( mixed $data = null, ?int $status_code = null ): void {
        $this->factory->send_success( $data, $status_code );
    }

    /**
     * @inheritDoc
     */
    public function send_error( mixed $data = null, ?int $status_code = null ): void {
        $this->log_and_send_error( $data, $status_code );
    }

    /**
     * @inheritDoc
     */
    public function send_raw( string $content, ?int $status_code = null ): void {
        $this->factory->send_raw( $content, $status_code );
    }

    /**
     * @inheritDoc
     */
    public function log_and_send_error( mixed $data = null, ?int $status_code = null ): void {
        if ( $data ) {
            error_log( 'AJAX Fixture Error: ' . wp_json_encode( $data ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
        $this->factory->send_error( $data, $status_code );
    }
}
