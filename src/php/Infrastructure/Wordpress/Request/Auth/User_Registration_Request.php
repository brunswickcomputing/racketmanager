<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Request\Auth;

use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;

/**
 * Immutable request command object representing a new user registration submission.
 */
final readonly class User_Registration_Request {
    /**
     * @param string|null $http_method     HTTP request method.
     * @param string      $nonce           Registration security nonce.
     * @param string      $recaptcha_token Google reCAPTCHA response token.
     * @param string      $email           Sanitized email address.
     * @param string      $first_name      Sanitized first name.
     * @param string      $last_name       Sanitized last name.
     */
    public function __construct(
        public ?string $http_method = null,
        public string $nonce = '',
        public string $recaptcha_token = '',
        public string $email = '',
        public string $first_name = '',
        public string $last_name = ''
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

        $nonce = isset( $post['racketmanager_register_nonce'] ) && is_string( $post['racketmanager_register_nonce'] )
            ? sanitize_text_field( wp_unslash( $post['racketmanager_register_nonce'] ) )
            : '';

        $recaptcha_token = isset( $post['g-recaptcha-response'] ) && is_string( $post['g-recaptcha-response'] )
            ? sanitize_text_field( wp_unslash( $post['g-recaptcha-response'] ) )
            : '';

        $email = isset( $post['email'] ) && is_string( $post['email'] )
            ? sanitize_email( wp_unslash( $post['email'] ) )
            : '';

        $first_name = isset( $post['first_name'] ) && is_string( $post['first_name'] )
            ? sanitize_text_field( wp_unslash( $post['first_name'] ) )
            : '';

        $last_name = isset( $post['last_name'] ) && is_string( $post['last_name'] )
            ? sanitize_text_field( wp_unslash( $post['last_name'] ) )
            : '';

        return new self(
            $http_method,
            $nonce,
            $recaptcha_token,
            $email,
            $first_name,
            $last_name
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
     * Verify the registration nonce.
     *
     * @param string $action Nonce action name.
     * @return bool
     */
    public function verify_nonce( string $action = 'racketmanager_register' ): bool {
        if ( '' === $this->nonce ) {
            return false;
        }

        return (bool) wp_verify_nonce( $this->nonce, $action );
    }

    /**
     * Check if a reCAPTCHA token was supplied.
     *
     * @return bool
     */
    public function has_recaptcha_token(): bool {
        return '' !== $this->recaptcha_token;
    }

    /**
     * Verify the Google reCAPTCHA response token.
     *
     * @param string|Option_Service $secret_key_or_service Google reCAPTCHA secret key or Option_Service instance.
     * @return bool
     */
    public function verify_recaptcha( string|Option_Service $secret_key_or_service ): bool {
        $secret_key = $this->resolve_secret_key( $secret_key_or_service );
        if ( '' === $this->recaptcha_token || '' === $secret_key ) {
            return false;
        }

        $response = wp_remote_post(
            'https://www.google.com/recaptcha/api/siteverify',
            [
                'body' => [
                    'secret'   => $secret_key,
                    'response' => $this->recaptcha_token,
                ],
            ]
        );

        return $this->parse_recaptcha_response( $response );
    }

    /**
     * Resolve reCAPTCHA secret key from string or Option_Service.
     *
     * @param string|Option_Service $secret_key_or_service Google reCAPTCHA secret key or Option_Service instance.
     * @return string
     */
    private function resolve_secret_key( string|Option_Service $secret_key_or_service ): string {
        if ( $secret_key_or_service instanceof Option_Service ) {
            $keys = $secret_key_or_service->get_options( 'keys' );

            return isset( $keys['recaptchaSecretKey'] ) && is_string( $keys['recaptchaSecretKey'] )
                ? $keys['recaptchaSecretKey']
                : '';
        }

        return $secret_key_or_service;
    }

    /**
     * Parse and validate the Google reCAPTCHA verification response.
     *
     * @param mixed $response Response from wp_remote_post.
     * @return bool
     */
    private function parse_recaptcha_response( mixed $response ): bool {
        if ( is_wp_error( $response ) || ! is_array( $response ) || ! isset( $response['body'] ) || ! is_string( $response['body'] ) ) {
            return false;
        }

        $decoded = json_decode( $response['body'] );

        return is_object( $decoded ) && ! empty( $decoded->success );
    }

    /**
     * Get the nonce.
     *
     * @return string
     */
    public function get_nonce(): string {
        return $this->nonce;
    }

    /**
     * Get the reCAPTCHA token.
     *
     * @return string
     */
    public function get_recaptcha_token(): string {
        return $this->recaptcha_token;
    }

    /**
     * Get the sanitised email.
     *
     * @return string
     */
    public function get_email(): string {
        return $this->email;
    }

    /**
     * Get the sanitised first name.
     *
     * @return string
     */
    public function get_first_name(): string {
        return $this->first_name;
    }

    /**
     * Get the sanitised last name.
     *
     * @return string
     */
    public function get_last_name(): string {
        return $this->last_name;
    }

    /**
     * Check if email is non-empty.
     *
     * @return bool
     */
    public function has_email(): bool {
        return '' !== $this->email;
    }

    /**
     * Check if first name is non-empty.
     *
     * @return bool
     */
    public function has_first_name(): bool {
        return '' !== $this->first_name;
    }

    /**
     * Check if last name is non-empty.
     *
     * @return bool
     */
    public function has_last_name(): bool {
        return '' !== $this->last_name;
    }

    /**
     * Validate required registration fields and return list of missing field error keys.
     *
     * @return string[]
     */
    public function validate_field_errors(): array {
        $errors = [];
        if ( '' === $this->email ) {
            $errors[] = 'email';
        }
        if ( '' === $this->first_name ) {
            $errors[] = 'first_name';
        }
        if ( '' === $this->last_name ) {
            $errors[] = 'last_name';
        }

        return $errors;
    }
}
