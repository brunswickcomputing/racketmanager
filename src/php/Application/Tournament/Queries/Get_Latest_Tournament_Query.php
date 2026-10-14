<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use function htmlspecialchars;
use function is_array;
use function is_string;
use function trim;
use function wp_strip_all_tags;
use function wp_unslash;

final class Get_Latest_Tournament_Query {
    public function __construct(
        public readonly ?string $age_group = null,
        public readonly string $template = ''
    ) {}

    public static function from_atts( array|string $atts, array $query_vars = [], array $get = [] ): self {
        $atts_array = is_array( $atts ) ? $atts : [];

        $age_group = $atts_array['age_group'] ?? null;
        if ( ! $age_group ) {
            if ( ! empty( $get['age_group'] ) ) {
                $age_group = htmlspecialchars( wp_strip_all_tags( wp_unslash( (string) $get['age_group'] ) ) );
            } elseif ( isset( $query_vars['age_group'] ) ) {
                $age_group = $query_vars['age_group'];
            }
        }

        $age_group = is_string( $age_group ) && '' !== trim( $age_group )
            ? trim( $age_group )
            : null;

        return new self(
            $age_group,
            isset( $atts_array['template'] ) ? (string) $atts_array['template'] : ''
        );
    }
}
