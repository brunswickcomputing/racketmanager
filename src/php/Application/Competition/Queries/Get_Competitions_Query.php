<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

final readonly class Get_Competitions_Query {
    public function __construct(
        public ?string $type,
        public ?string $age_group = null,
        public ?int $user_id = null
    ) {}

    public static function from_atts( array|string $atts, array $query_vars = [], array $get = [], ?int $user_id = null ): self {
        $atts      = is_array( $atts ) ? $atts : [];
        $type      = $atts['type'] ?? null;
        $age_group = $atts['age_group'] ?? null;
        if ( ! $type ) {
            if ( isset( $get['competition_type'] ) && ! empty( $get['type'] ) ) {
                $type = htmlspecialchars( wp_strip_all_tags( wp_unslash( (string) $get['type'] ) ) );
            } else {
                $type = $query_vars['type'] ?? null;
            }
        }
        if ( isset( $query_vars['age_group'] ) ) {
            $age_group = $query_vars['age_group'];
        }

        return new self(
            is_scalar( $type ) && $type ? (string) $type : null,
            is_scalar( $age_group ) && $age_group ? (string) $age_group : null,
            $user_id && $user_id > 0 ? $user_id : null
        );
    }
}