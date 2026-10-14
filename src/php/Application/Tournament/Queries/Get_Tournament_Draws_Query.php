<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use function htmlspecialchars;
use function is_array;
use function is_numeric;
use function is_string;
use function str_replace;
use function trim;
use function wp_strip_all_tags;
use function wp_unslash;

final class Get_Tournament_Draws_Query {
    public function __construct(
        public readonly int|string|null $tournament_id = null,
        public readonly int|string|null $draw_id = null,
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

        $draws = $atts_array['draws'] ?? null;
        if ( ! $draws ) {
            if ( ! empty( $get['draw'] ) ) {
                $draws = htmlspecialchars( wp_strip_all_tags( wp_unslash( (string) $get['draw'] ) ) );
            } elseif ( isset( $query_vars['draw'] ) ) {
                $draws = $query_vars['draw'];
            }
        }

        if ( is_numeric( $draws ) ) {
            $draws = (int) $draws;
        } elseif ( is_string( $draws ) && '' !== trim( $draws ) ) {
            $draws = str_replace( '-', ' ', trim( $draws ) );
        } else {
            $draws = null;
        }

        return new self(
            $id,
            $draws,
            isset( $atts_array['template'] ) ? (string) $atts_array['template'] : ''
        );
    }
}
