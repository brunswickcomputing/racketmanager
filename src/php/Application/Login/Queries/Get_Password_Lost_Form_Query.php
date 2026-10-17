<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Login\Queries;

use function filter_var;
use function is_array;
use function sanitize_text_field;
use function shortcode_atts;
use function wp_unslash;

class Get_Password_Lost_Form_Query {
    public function __construct(
        public readonly bool $show_title = true,
        public readonly ?string $errors = null,
        public readonly ?string $checkemail = null,
        public readonly bool $is_logged_in = false
    ) {}

    public static function from_atts(
        array|string $atts = [],
        array $query_vars = [],
        array $get_params = [],
        array $request_params = [],
        bool $is_logged_in = false
    ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $defaults   = [ 'show_title' => true ];
        $parsed     = shortcode_atts( $defaults, $atts_array );

        $show_title = filter_var( $parsed['show_title'] ?? true, FILTER_VALIDATE_BOOLEAN );

        $errors     = isset( $request_params['errors'] ) ? sanitize_text_field( wp_unslash( (string) $request_params['errors'] ) ) : null;
        $checkemail = isset( $request_params['checkemail'] ) ? sanitize_text_field( wp_unslash( (string) $request_params['checkemail'] ) ) : null;

        return new self(
            show_title: $show_title,
            errors: $errors,
            checkemail: $checkemail,
            is_logged_in: $is_logged_in
        );
    }
}
