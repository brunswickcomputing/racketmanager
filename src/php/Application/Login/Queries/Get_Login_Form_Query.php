<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Login\Queries;

use function is_array;
use function is_user_logged_in;
use function sanitize_text_field;
use function shortcode_atts;
use function wp_get_referer;
use function wp_unslash;

class Get_Login_Form_Query {
    public function __construct(
        public readonly bool $show_title = false,
        public readonly string $action = '',
        public readonly ?string $register_errors = null,
        public readonly bool $registered = false,
        public readonly ?string $redirect_to = null,
        public readonly ?string $referer = null,
        public readonly ?string $login_errors = null,
        public readonly ?string $checkemail = null,
        public readonly bool $password_updated = false,
        public readonly bool $logged_out = false,
        public readonly bool $is_logged_in = false,
        public readonly string $request_method = 'GET'
    ) {}

    public static function from_atts(
        array|string $atts = [],
        array $query_vars = [],
        array $get_params = [],
        array $request_params = [],
        array $server_params = [],
        ?string $action_override = null
    ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $defaults   = [ 'show_title' => false ];
        $parsed     = shortcode_atts( $defaults, $atts_array );

        $show_title = filter_var( $parsed['show_title'] ?? false, FILTER_VALIDATE_BOOLEAN );
        $action     = null !== $action_override
            ? sanitize_text_field( wp_unslash( $action_override ) )
            : ( isset( $get_params['action'] ) ? sanitize_text_field( wp_unslash( (string) $get_params['action'] ) ) : ( isset( $request_params['action'] ) ? sanitize_text_field( wp_unslash( (string) $request_params['action'] ) ) : '' ) );

        $register_errors = isset( $request_params['register-errors'] ) ? sanitize_text_field( wp_unslash( (string) $request_params['register-errors'] ) ) : null;
        $registered      = isset( $request_params['registered'] );

        $redirect_to = isset( $request_params['redirect_to'] ) ? (string) $request_params['redirect_to'] : null;
        $referer     = function_exists( 'wp_get_referer' ) ? wp_get_referer() : null;
        if ( false === $referer ) {
            $referer = null;
        }

        $login_errors     = isset( $request_params['login'] ) ? sanitize_text_field( wp_unslash( (string) $request_params['login'] ) ) : null;
        $checkemail       = isset( $request_params['checkemail'] ) ? sanitize_text_field( wp_unslash( (string) $request_params['checkemail'] ) ) : null;
        $password_updated = isset( $request_params['passwordUpdate'] ) && 'true' === (string) $request_params['passwordUpdate'];
        $logged_out       = isset( $request_params['logged_out'] ) && 'true' === (string) $request_params['logged_out'];

        $is_logged_in   = function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false;
        $request_method = isset( $server_params['REQUEST_METHOD'] ) ? (string) $server_params['REQUEST_METHOD'] : ( $_SERVER['REQUEST_METHOD'] ?? 'GET' );

        return new self(
            show_title: $show_title,
            action: $action,
            register_errors: $register_errors,
            registered: $registered,
            redirect_to: $redirect_to,
            referer: $referer,
            login_errors: $login_errors,
            checkemail: $checkemail,
            password_updated: $password_updated,
            logged_out: $logged_out,
            is_logged_in: $is_logged_in,
            request_method: $request_method
        );
    }
}
