<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use function is_array;
use function Racketmanager\un_seo_url;
use function shortcode_atts;

final readonly class Get_Competition_Entry_Query {
    public function __construct(
        public ?string $competition_name = null,
        public ?string $tournament_name = null,
        public ?string $competition_type = null,
        public ?string $season = null,
        public ?string $club_name = null,
        public ?string $player_id = null,
        public int $user_id = 0,
        public bool $is_admin = false,
        public ?string $entry_level = null,
        public string $template = ''
    ) {}

    public static function from_atts(
        array|string $atts = [],
        array $query_vars = [],
        int $user_id = 0,
        bool $is_admin = false,
        ?string $entry_level = null
    ): self {
        $args = shortcode_atts(
            [
                'template' => '',
            ],
            is_array( $atts ) ? $atts : []
        );

        $competition_name = $query_vars['competition_name'] ?? null;
        if ( $competition_name ) {
            $competition_name = un_seo_url( (string) $competition_name );
        }

        $type = isset( $query_vars['competition_type'] ) ? (string) $query_vars['competition_type'] : null;

        $tournament_name = null;
        if ( $competition_name && 'tournament' === $type ) {
            $tournament_name = $competition_name;
        } elseif ( ! empty( $query_vars['tournament'] ) ) {
            $tournament_name = un_seo_url( (string) $query_vars['tournament'] );
        }

        $season    = isset( $query_vars['season'] ) ? (string) $query_vars['season'] : null;
        $club_name = ! empty( $query_vars['club_name'] ) ? un_seo_url( (string) $query_vars['club_name'] ) : null;
        $player_id = ! empty( $query_vars['player_id'] ) ? un_seo_url( (string) $query_vars['player_id'] ) : null;

        return new self(
            competition_name: $competition_name,
            tournament_name: $tournament_name,
            competition_type: $type,
            season: $season,
            club_name: $club_name,
            player_id: $player_id,
            user_id: $user_id,
            is_admin: $is_admin,
            entry_level: $entry_level,
            template: (string) $args['template']
        );
    }

    public function with_entry_level( ?string $entry_level ): self {
        return new self(
            competition_name: $this->competition_name,
            tournament_name: $this->tournament_name,
            competition_type: $this->competition_type,
            season: $this->season,
            club_name: $this->club_name,
            player_id: $this->player_id,
            user_id: $this->user_id,
            is_admin: $this->is_admin,
            entry_level: $entry_level,
            template: $this->template
        );
    }
}
