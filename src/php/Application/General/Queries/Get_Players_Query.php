<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use function function_exists;
use function get_current_user_id;
use function is_array;
use function is_user_logged_in;
use function sanitize_text_field;
use function shortcode_atts;
use function wp_unslash;

class Get_Players_Query {
    public function __construct(
        public readonly ?string $template = null,
        public readonly ?string $search_string = null,
        public readonly bool $is_logged_in = false,
        public readonly ?int $user_id = null
    ) {}

    public static function from_atts(
        array|string $atts = [],
        array $query_vars = [],
        array $get_params = []
    ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $defaults   = [ 'template' => '' ];
        $parsed     = shortcode_atts( $defaults, $atts_array );

        $template = ! empty( $parsed['template'] ) ? sanitize_text_field( wp_unslash( (string) $parsed['template'] ) ) : null;

        $search_string = null;
        if ( isset( $get_params['q'] ) && '' !== $get_params['q'] ) {
            $search_string = sanitize_text_field( wp_unslash( (string) $get_params['q'] ) );
        } elseif ( isset( $query_vars['q'] ) && '' !== $query_vars['q'] ) {
            $search_string = sanitize_text_field( wp_unslash( (string) $query_vars['q'] ) );
        }

        $is_logged_in = function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false;
        $user_id      = $is_logged_in && function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : null;

        return new self(
            template: $template,
            search_string: $search_string,
            is_logged_in: $is_logged_in,
            user_id: $user_id
        );
    }
}
