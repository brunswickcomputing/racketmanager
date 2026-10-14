<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use function is_array;
use function is_numeric;
use function str_replace;

final readonly class Get_Event_Partner_Query {
    public function __construct(
        public ?int $event_id = null,
        public ?string $event_name = null,
        public ?string $season = null,
        public int|string|null $player_id = null,
        public ?string $gender = null,
        public ?string $date_end = null,
        public ?string $modal = null,
        public int|string|null $partner_id = null,
        public int|string|null $tournament_id = null,
        public string $template = ''
    ) {}

    private static function to_int_or_string( mixed $value ): int|string|null {
        if ( null === $value || '' === $value ) {
            return null;
        }
        if ( is_numeric( $value ) ) {
            return (int) $value;
        }
        return (string) $value;
    }

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

        return new self(
            event_id: $event_id,
            event_name: $event_name,
            season: ! empty( $atts_array['season'] ) ? (string) $atts_array['season'] : null,
            player_id: self::to_int_or_string( $atts_array['player'] ?? ( $atts_array['player_id'] ?? null ) ),
            gender: ! empty( $atts_array['gender'] ) ? (string) $atts_array['gender'] : null,
            date_end: ! empty( $atts_array['date_end'] ) ? (string) $atts_array['date_end'] : null,
            modal: ! empty( $atts_array['modal'] ) ? (string) $atts_array['modal'] : null,
            partner_id: self::to_int_or_string( $atts_array['partner_id'] ?? null ),
            tournament_id: self::to_int_or_string( $atts_array['tournament_id'] ?? null ),
            template: ! empty( $atts_array['template'] ) ? (string) $atts_array['template'] : ''
        );
    }
}
