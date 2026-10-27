<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Util\Util_Lookup;

use function htmlspecialchars;
use function is_array;
use function is_numeric;
use function is_string;
use function trim;
use function wp_strip_all_tags;
use function wp_unslash;

final class Get_Tournament_Players_Query {
    public function __construct(
        public readonly int|string|null $tournament_id = null,
        public readonly int|string|null $player_id = null
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

        $players = $atts_array['players'] ?? null;
        if ( ! $players ) {
            if ( ! empty( $get['player'] ) ) {
                $players = Seo_Slug::decode( htmlspecialchars( wp_strip_all_tags( wp_unslash( (string) $get['player'] ) ) ) );
            } elseif ( isset( $query_vars['player'] ) ) {
                $players = Seo_Slug::decode( (string) $query_vars['player'] );
            }
        }

        if ( is_numeric( $players ) ) {
            $players = (int) $players;
        } elseif ( is_string( $players ) && '' !== trim( $players ) ) {
            $players = trim( $players );
        } else {
            $players = null;
        }

        $player_id = Util_Lookup::get_player_id( $players );
        if ( is_numeric( $player_id ) ) {
            $player_id = (int) $player_id;
        }

        return new self( $id, $player_id );
    }
}
