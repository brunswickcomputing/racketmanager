<?php

namespace Racketmanager\Infrastructure\Wordpress\Response;

/**
 * Encapsulate a response that can be sent to the browser
 */
class Response {
    /**
     * @var mixed
     */
    private mixed $content;

    /**
     * @var int|null
     */
    private ?int $status_code;

    /**
     * @var array
     */
    private array $headers;

    /**
     * Response constructor.
     *
     * @param mixed $content
     * @param int|null $status_code
     * @param array $headers
     */
    public function __construct( mixed $content, ?int $status_code = null, array $headers = [] ) {
        $this->content     = $content;
        $this->status_code = $status_code;
        $this->headers     = $headers;
    }

    /**
     * Send the response and headers
     *
     * @return void
     */
    public function send(): void {
        if ( is_array( $this->content ) && isset( $this->content['success'] ) ) {
            $data = $this->content['data'] ?? null;
            if ( $this->content['success'] ) {
                wp_send_json_success( $data, $this->status_code );
            } else {
                wp_send_json_error( $data, $this->status_code );
            }
        }

        foreach ( $this->headers as $name => $value ) {
            header( "$name: $value" );
        }

        if ( $this->status_code ) {
            status_header( $this->status_code );
        }

        if ( is_array( $this->content ) || is_object( $this->content ) ) {
            if ( ! headers_sent() ) {
                header( 'Content-Type: application/json; charset=UTF-8' );
            }
            echo wp_json_encode( $this->content );
        } else {
            echo $this->content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }

    /**
     * @return mixed
     */
    public function get_content(): mixed {
        return $this->content;
    }

    /**
     * @return int|null
     */
    public function get_status_code(): ?int {
        return $this->status_code;
    }

    /**
     * @return array
     */
    public function get_headers(): array {
        return $this->headers;
    }
}
