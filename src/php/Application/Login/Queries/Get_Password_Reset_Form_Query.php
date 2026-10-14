<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Login\Queries;

use function filter_var;
use function is_array;
use function sanitize_text_field;
use function shortcode_atts;
use function wp_unslash;

class Get_Password_Reset_Form_Query {
    public function __construct(
        public readonly bool $show_title = false,
        public readonly ?string $login = null,
        public readonly ?string $key = null,
        public readonly ?string $error = null
    ) {}

    public static function from_atts(
        array|string $atts = [],
        array $query_vars = [],
        array $get_params = [],
        array $request_params = []
    ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $defaults   = [ 'show_title' => false ];
        $parsed     = shortcode_atts( $defaults, $atts_array );

        $show_title = filter_var( $parsed['show_title'] ?? false, FILTER_VALIDATE_BOOLEAN );

        $login = isset( $request_params['login'] ) ? sanitize_text_field( wp_unslash( (string) $request_params['login'] ) ) : null;
        $key   = isset( $request_params['key'] ) ? sanitize_text_field( wp_unslash( (string) $request_params['key'] ) ) : null;
        $error = isset( $request_params['error'] ) ? sanitize_text_field( wp_unslash( (string) $request_params['error'] ) ) : null;

        return new self(
            show_title: $show_title,
            login: $login,
            key: $key,
            error: $error
        );
    }
}
