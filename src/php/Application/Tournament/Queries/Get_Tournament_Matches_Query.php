<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use function htmlspecialchars;
use function is_array;
use function is_numeric;
use function is_string;
use function trim;
use function wp_strip_all_tags;
use function wp_unslash;

final class Get_Tournament_Matches_Query {
    public function __construct(
        public readonly int|string|null $tournament_id = null,
        public readonly ?string $match_date = null,
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

        $match_date = $atts_array['match_date'] ?? null;
        if ( ! $match_date ) {
            if ( ! empty( $get['match_date'] ) ) {
                $match_date = htmlspecialchars( wp_strip_all_tags( wp_unslash( (string) $get['match_date'] ) ) );
            } elseif ( isset( $query_vars['match_date'] ) ) {
                $match_date = $query_vars['match_date'];
            }
        }

        $match_date = is_string( $match_date ) && '' !== trim( $match_date )
            ? trim( $match_date )
            : null;

        return new self(
            $id,
            $match_date,
            isset( $atts_array['template'] ) ? (string) $atts_array['template'] : ''
        );
    }
}
