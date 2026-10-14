<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use function is_array;
use function sanitize_text_field;
use function shortcode_atts;
use function wp_unslash;

class Get_Player_Search_Query {
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $template = null
    ) {}

    public static function from_atts(
        array|string $atts = [],
        array $query_vars = [],
        array $get_params = []
    ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $defaults   = [
            'search'   => null,
            'template' => '',
        ];
        $parsed     = shortcode_atts( $defaults, $atts_array );

        $search   = ! empty( $parsed['search'] ) ? sanitize_text_field( wp_unslash( (string) $parsed['search'] ) ) : null;
        $template = ! empty( $parsed['template'] ) ? sanitize_text_field( wp_unslash( (string) $parsed['template'] ) ) : null;

        return new self(
            search: $search,
            template: $template
        );
    }
}
