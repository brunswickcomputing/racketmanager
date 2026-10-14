<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use function is_array;
use function is_numeric;
use function is_string;
use function trim;

final class Get_Order_Of_Play_Query {
    public function __construct(
        public readonly int|string|null $tournament_id = null,
        public readonly int|string|null $competition_id = null,
        public readonly ?string $season = null,
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

        $competition_id = $atts_array['competition_id'] ?? null;
        if ( ! $competition_id ) {
            if ( ! empty( $get['competition_id'] ) ) {
                $competition_id = $get['competition_id'];
            } elseif ( isset( $query_vars['competition_id'] ) ) {
                $competition_id = $query_vars['competition_id'];
            }
        }

        if ( is_numeric( $competition_id ) ) {
            $competition_id = (int) $competition_id;
        } elseif ( is_string( $competition_id ) && '' !== trim( $competition_id ) ) {
            $competition_id = trim( $competition_id );
        } else {
            $competition_id = null;
        }

        $season = $atts_array['season'] ?? null;
        if ( ! $season ) {
            if ( ! empty( $get['season'] ) ) {
                $season = $get['season'];
            } elseif ( isset( $query_vars['season'] ) ) {
                $season = $query_vars['season'];
            }
        }

        $season = is_string( $season ) && '' !== trim( $season )
            ? trim( $season )
            : null;

        return new self(
            $id,
            $competition_id,
            $season,
            isset( $atts_array['template'] ) ? (string) $atts_array['template'] : ''
        );
    }
}
