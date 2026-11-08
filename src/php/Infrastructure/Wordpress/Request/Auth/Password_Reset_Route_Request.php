<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Request\Auth;

/**
 * Immutable request command object representing a password reset route request.
 */
final readonly class Password_Reset_Route_Request {
    /**
     * @param string|null $http_method HTTP request method.
     * @param string      $key         Sanitized password reset key.
     * @param string      $login       Sanitized user login name.
     */
    public function __construct(
        public ?string $http_method = null,
        public string $key = '',
        public string $login = ''
    ) {
    }

    /**
     * Factory method creating request object from superglobals.
     *
     * @param array<string, mixed>|null $server Server superglobal array ($_SERVER).
     * @param array<string, mixed>|null $get    Get superglobal array ($_GET).
     * @return self
     */
    public static function from_globals( ?array $server = null, ?array $get = null ): self {
        $server ??= $_SERVER;
        $get    ??= $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $http_method = isset( $server['REQUEST_METHOD'] ) && is_string( $server['REQUEST_METHOD'] )
            ? strtoupper( trim( $server['REQUEST_METHOD'] ) )
            : null;

        if ( '' === $http_method ) {
            $http_method = null;
        }

        $key = isset( $get['key'] ) && is_string( $get['key'] )
            ? sanitize_text_field( wp_unslash( $get['key'] ) )
            : '';

        $login = isset( $get['login'] ) && is_string( $get['login'] )
            ? sanitize_text_field( wp_unslash( $get['login'] ) )
            : '';

        return new self( $http_method, $key, $login );
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
     * Get the password reset key.
     *
     * @return string
     */
    public function get_key(): string {
        return $this->key;
    }

    /**
     * Get the alphanumeric sanitised password reset key.
     *
     * @return string
     */
    public function get_clean_key(): string {
        return (string) preg_replace( '/[^a-z0-9]/i', '', $this->key );
    }

    /**
     * Get the user login.
     *
     * @return string
     */
    public function get_login(): string {
        return $this->login;
    }

    /**
     * Check if key is non-empty.
     *
     * @return bool
     */
    public function has_key(): bool {
        return '' !== $this->key;
    }

    /**
     * Check if user login is non-empty.
     *
     * @return bool
     */
    public function has_login(): bool {
        return '' !== $this->login;
    }

    /**
     * Verify the password reset key with WordPress core.
     *
     * @return mixed WP_User on success, WP_Error on failure, false if inputs are missing.
     */
    public function check_key(): mixed {
        if ( '' === $this->key || '' === $this->login ) {
            return false;
        }

        return check_password_reset_key( $this->key, $this->login );
    }
}
