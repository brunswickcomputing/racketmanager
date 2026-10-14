<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use function is_array;
use function sanitize_text_field;
use function shortcode_atts;
use function wp_unslash;

class Get_Alert_Query {
    public function __construct(
        public readonly string $msg = '',
        public readonly string $type = '',
        public readonly ?string $template = null
    ) {}

    public static function from_atts(
        array|string $atts = [],
        array $query_vars = [],
        array $get_params = []
    ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $defaults   = [
            'msg'      => '',
            'type'     => '',
            'template' => null,
        ];
        $parsed     = shortcode_atts( $defaults, $atts_array );

        $msg      = isset( $parsed['msg'] ) ? (string) $parsed['msg'] : '';
        $type     = isset( $parsed['type'] ) ? sanitize_text_field( wp_unslash( (string) $parsed['type'] ) ) : '';
        $template = ! empty( $parsed['template'] ) ? sanitize_text_field( wp_unslash( (string) $parsed['template'] ) ) : null;

        return new self(
            msg: $msg,
            type: $type,
            template: $template
        );
    }
}
