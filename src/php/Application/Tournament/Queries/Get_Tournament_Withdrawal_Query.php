<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

final class Get_Tournament_Withdrawal_Query {
    public function __construct(
        public readonly int|string|null $tournament_id = null,
        public readonly int|string|null $player_id = null,
        public readonly ?string $modal = null
    ) {}

    public static function from_atts( array|string $atts ): self {
        $atts_array = is_array( $atts ) ? $atts : [];

        $id = $atts_array['id'] ?? null;
        if ( is_numeric( $id ) ) {
            $id = (int) $id;
        } elseif ( is_string( $id ) && '' !== trim( $id ) ) {
            $id = trim( $id );
        } else {
            $id = null;
        }

        $player_id = $atts_array['player_id'] ?? null;
        if ( is_numeric( $player_id ) ) {
            $player_id = (int) $player_id;
        } elseif ( is_string( $player_id ) && '' !== trim( $player_id ) ) {
            $player_id = trim( $player_id );
        } else {
            $player_id = null;
        }

        $modal = isset( $atts_array['modal'] ) && '' !== trim( (string) $atts_array['modal'] )
            ? (string) $atts_array['modal']
            : null;

        return new self( $id, $player_id, $modal );
    }
}
