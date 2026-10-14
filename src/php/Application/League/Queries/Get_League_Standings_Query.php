<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

/**
 * Query for retrieving league standings for the [standings] shortcode.
 */
final readonly class Get_League_Standings_Query {
    public ?int $league_id;
    public ?string $season;
    public ?string $group;
    public string $template;
    public bool $widget;

    public function __construct(
        ?int $league_id = null,
        ?string $season = null,
        ?string $group = null,
        string $template = '',
        bool $widget = false
    ) {
        $this->league_id = ( null !== $league_id && $league_id > 0 ) ? $league_id : null;
        $this->season    = ( null !== $season && '' !== trim( $season ) && false !== $season ) ? trim( (string) $season ) : null;
        $this->group     = ( null !== $group && '' !== trim( $group ) && false !== $group ) ? trim( (string) $group ) : null;
        $this->template  = preg_replace( '/[^A-Za-z0-9_-]/', '', $template ) ?: '';
        $this->widget    = $widget;
    }

    public static function from_atts( array|string $atts, bool $widget = false, ?int $default_league_id = null ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $league_id  = isset( $atts_array['league_id'] ) ? (int) $atts_array['league_id'] : ( isset( $atts_array['id'] ) ? (int) $atts_array['id'] : $default_league_id );
        $season     = $atts_array['season'] ?? null;
        $group      = $atts_array['group'] ?? null;
        $template   = $atts_array['template'] ?? '';

        return new self(
            league_id: $league_id,
            season: $season ? (string) $season : null,
            group: $group ? (string) $group : null,
            template: (string) $template,
            widget: $widget
        );
    }
}
