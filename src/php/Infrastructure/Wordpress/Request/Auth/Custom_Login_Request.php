<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Request\Auth;

/**
 * Immutable request command object representing a custom login redirection request.
 */
final readonly class Custom_Login_Request {
    /**
     * @param string|null $http_method HTTP request method.
     * @param string|null $redirect_to Desired redirect URL, if any.
     */
    public function __construct(
        public ?string $http_method = null,
        public ?string $redirect_to = null
    ) {
    }

    /**
     * Factory method creating request object from superglobals.
     *
     * @param array<string, mixed>|null $server  Server superglobal array ($_SERVER).
     * @param array<string, mixed>|null $request Request superglobal array ($_REQUEST).
     * @return self
     */
    public static function from_globals( ?array $server = null, ?array $request = null ): self {
        $server  ??= $_SERVER;
        $request ??= $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $http_method = isset( $server['REQUEST_METHOD'] ) && is_string( $server['REQUEST_METHOD'] )
            ? strtoupper( trim( $server['REQUEST_METHOD'] ) )
            : null;

        if ( '' === $http_method ) {
            $http_method = null;
        }

        $redirect_to = null;
        if ( isset( $request['redirect_to'] ) && is_string( $request['redirect_to'] ) ) {
            $redirect_to = esc_url_raw( wp_unslash( $request['redirect_to'] ) );
        }

        return new self( $http_method, $redirect_to );
    }

    /**
     * Check if HTTP request method was specified.
     *
     * @return bool
     */
    public function has_http_method(): bool {
        return null !== $this->http_method && '' !== $this->http_method;
    }

    /**
     * Check if the HTTP method is GET.
     *
     * @return bool
     */
    public function is_get(): bool {
        return 'GET' === $this->http_method;
    }

    /**
     * Check if the HTTP method is POST.
     *
     * @return bool
     */
    public function is_post(): bool {
        return 'POST' === $this->http_method;
    }

    /**
     * Get the HTTP method.
     *
     * @return string|null
     */
    public function get_http_method(): ?string {
        return $this->http_method;
    }

    /**
     * Get redirect_to URL.
     *
     * @return string|null
     */
    public function get_redirect_to(): ?string {
        return $this->redirect_to;
    }

    /**
     * Check if redirect_to is present and non-empty.
     *
     * @return bool
     */
    public function has_redirect_to(): bool {
        return ! empty( $this->redirect_to );
    }
}
