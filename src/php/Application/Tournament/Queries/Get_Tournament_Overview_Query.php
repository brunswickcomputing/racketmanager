<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

final class Get_Tournament_Overview_Query {
    public function __construct(
        public readonly int|string|null $tournament_id = null,
        public readonly string $template = ''
    ) {}

    public static function from_atts( array|string $atts, array $query_vars = [], array $get = [] ): self {
        $atts_array = is_array( $atts ) ? $atts : [];

        $id = $atts_array['id'] ?? null;
        if ( ! $id ) {
            if ( ! empty( $get['tournament_id'] ) ) {
                $id = $get['tournament_id'];
            } elseif ( isset( $query_vars['tournament_id'] ) ) {
                $id = $query_vars['tournament_id'];
            } elseif ( ! empty( $get['id'] ) ) {
                $id = $get['id'];
            } elseif ( isset( $query_vars['id'] ) ) {
                $id = $query_vars['id'];
            }
        }

        if ( is_numeric( $id ) ) {
            $id = (int) $id;
        } elseif ( is_string( $id ) && '' !== trim( $id ) ) {
            $id = trim( $id );
        } else {
            $id = null;
        }

        return new self(
            $id,
            isset( $atts_array['template'] ) ? (string) $atts_array['template'] : ''
        );
    }
}
