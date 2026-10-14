<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use function function_exists;
use function get_query_var;
use function is_array;
use function is_numeric;
use function sanitize_text_field;
use function shortcode_atts;
use function wp_unslash;

class Get_Purchase_Order_Query {
    public function __construct(
        public readonly int $id = 0,
        public readonly ?string $modal = null,
        public readonly ?string $template = null
    ) {}

    public static function from_atts(
        array|string $atts = [],
        array $query_vars = [],
        array $get_params = []
    ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $defaults   = [
            'id'       => 0,
            'modal'    => null,
            'template' => null,
        ];
        $parsed     = shortcode_atts( $defaults, $atts_array );

        $raw_id = null;
        if ( ! empty( $parsed['id'] ) ) {
            $raw_id = $parsed['id'];
        } elseif ( ! empty( $query_vars['id'] ) ) {
            $raw_id = $query_vars['id'];
        } elseif ( ! empty( $get_params['id'] ) ) {
            $raw_id = $get_params['id'];
        } elseif ( function_exists( 'get_query_var' ) ) {
            $var = get_query_var( 'id' );
            if ( $var && '' !== $var ) {
                $raw_id = $var;
            }
        }

        $id       = ( null !== $raw_id && is_numeric( $raw_id ) ) ? (int) $raw_id : 0;
        $modal    = ! empty( $parsed['modal'] ) ? sanitize_text_field( wp_unslash( (string) $parsed['modal'] ) ) : null;
        $template = ! empty( $parsed['template'] ) ? sanitize_text_field( wp_unslash( (string) $parsed['template'] ) ) : null;

        return new self(
            id: $id,
            modal: $modal,
            template: $template
        );
    }
}
