<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use function is_array;
use function is_numeric;
use function str_replace;

final readonly class Get_Team_Order_Players_Query {
    public function __construct(
        public ?int $event_id = null,
        public ?string $event_name = null,
        public int|string|null $club_id = null,
        public ?string $season = null
    ) {}

    public static function from_atts( array|string $atts = [], array $query_vars = [] ): self {
        $atts_array = is_array( $atts ) ? $atts : [];

        $event_id = null;
        if ( isset( $atts_array['id'] ) && is_numeric( $atts_array['id'] ) && (int) $atts_array['id'] > 0 ) {
            $event_id = (int) $atts_array['id'];
        } elseif ( isset( $atts_array['event_id'] ) && is_numeric( $atts_array['event_id'] ) && (int) $atts_array['event_id'] > 0 ) {
            $event_id = (int) $atts_array['event_id'];
        }

        $event_name = null;
        if ( ! $event_id ) {
            if ( ! empty( $atts_array['event_name'] ) ) {
                $event_name = (string) $atts_array['event_name'];
            } elseif ( ! empty( $atts_array['event'] ) ) {
                $event_name = (string) $atts_array['event'];
            } elseif ( ! empty( $query_vars['event'] ) ) {
                $event_name = str_replace( '-', ' ', (string) $query_vars['event'] );
            }
        }

        $club_id = $atts_array['club_id'] ?? ( $atts_array['club'] ?? ( $atts_array['clubs'] ?? null ) );
        $season  = ! empty( $atts_array['season'] ) ? (string) $atts_array['season'] : null;

        return new self(
            event_id: $event_id,
            event_name: $event_name,
            club_id: $club_id,
            season: $season
        );
    }
}
