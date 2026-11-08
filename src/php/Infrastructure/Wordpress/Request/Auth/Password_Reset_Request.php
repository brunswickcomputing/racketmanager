<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Request\Auth;

/**
 * Immutable request command object representing a password reset submission.
 */
final readonly class Password_Reset_Request {
    /**
     * @param string|null $http_method HTTP request method.
     * @param string|null $nonce       Security nonce.
     * @param string|null $rp_key      Password reset key.
     * @param string|null $rp_login    User login name.
     * @param string|null $password    New password.
     * @param string|null $re_password New password confirmation.
     */
    public function __construct(
        public ?string $http_method = null,
        public ?string $nonce = null,
        public ?string $rp_key = null,
        public ?string $rp_login = null,
        public ?string $password = null,
        public ?string $re_password = null
    ) {
    }

    /**
     * Factory method creating request object from superglobals.
     *
     * @param array<string, mixed>|null $server Server superglobal array ($_SERVER).
     * @param array<string, mixed>|null $post   Post superglobal array ($_POST).
     * @return self
     */
    public static function from_globals( ?array $server = null, ?array $post = null ): self {
        $server ??= $_SERVER;
        $post   ??= $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        $http_method = isset( $server['REQUEST_METHOD'] ) && is_string( $server['REQUEST_METHOD'] )
            ? strtoupper( trim( $server['REQUEST_METHOD'] ) )
            : null;

        if ( '' === $http_method ) {
            $http_method = null;
        }

        $nonce = isset( $post['racketmanager_nonce'] ) && is_string( $post['racketmanager_nonce'] )
            ? sanitize_text_field( wp_unslash( $post['racketmanager_nonce'] ) )
            : null;

        $rp_key = isset( $post['rp_key'] ) && is_string( $post['rp_key'] )
            ? sanitize_text_field( wp_unslash( $post['rp_key'] ) )
            : null;

        $rp_login = isset( $post['rp_login'] ) && is_string( $post['rp_login'] )
            ? sanitize_text_field( wp_unslash( $post['rp_login'] ) )
            : null;

        $password = isset( $post['password'] ) && is_string( $post['password'] )
            ? sanitize_text_field( wp_unslash( $post['password'] ) )
            : null;

        $re_password = isset( $post['rePassword'] ) && is_string( $post['rePassword'] )
            ? sanitize_text_field( wp_unslash( $post['rePassword'] ) )
            : null;

        return new self(
            $http_method,
            $nonce,
            $rp_key,
            $rp_login,
            $password,
            $re_password
        );
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
     * Check if the HTTP method is POST.
     *
     * @return bool
     */
    public function is_post(): bool {
        return 'POST' === $this->http_method;
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
     * Get the HTTP method.
     *
     * @return string|null
     */
    public function get_http_method(): ?string {
        return $this->http_method;
    }

    /**
     * Get the nonce.
     *
     * @return string|null
     */
    public function get_nonce(): ?string {
        return $this->nonce;
    }

    /**
     * Get the password reset key.
     *
     * @return string|null
     */
    public function get_rp_key(): ?string {
        return $this->rp_key;
    }

    /**
     * Get the user login.
     *
     * @return string|null
     */
    public function get_rp_login(): ?string {
        return $this->rp_login;
    }

    /**
     * Check if reset key is non-empty.
     *
     * @return bool
     */
    public function has_rp_key(): bool {
        return ! empty( $this->rp_key );
    }

    /**
     * Check if user login is non-empty.
     *
     * @return bool
     */
    public function has_rp_login(): bool {
        return ! empty( $this->rp_login );
    }

    /**
     * Get the submitted new password.
     *
     * @return string|null
     */
    public function get_password(): ?string {
        return $this->password;
    }

    /**
     * Get the submitted confirmation password.
     *
     * @return string|null
     */
    public function get_re_password(): ?string {
        return $this->re_password;
    }

    /**
     * Verify the reset password nonce.
     *
     * @param string $action Nonce action name.
     * @return bool
     */
    public function verify_nonce( string $action = 'racketmanager_reset-password' ): bool {
        if ( empty( $this->nonce ) ) {
            return false;
        }

        return (bool) wp_verify_nonce( $this->nonce, $action );
    }

    /**
     * Check whether passwords match.
     *
     * @return bool
     */
    public function passwords_match(): bool {
        return $this->password === $this->re_password;
    }

    /**
     * Check whether the password field is empty.
     *
     * @return bool
     */
    public function is_password_empty(): bool {
        return empty( $this->password );
    }

    /**
     * Verify the password reset key with WordPress core.
     *
     * @return mixed WP_User on success, WP_Error on failure, false if inputs are missing.
     */
    public function check_key(): mixed {
        if ( empty( $this->rp_key ) || empty( $this->rp_login ) ) {
            return false;
        }

        return check_password_reset_key( $this->rp_key, $this->rp_login );
    }
}
