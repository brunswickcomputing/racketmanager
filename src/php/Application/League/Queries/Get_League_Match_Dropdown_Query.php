<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use function is_array;
use function preg_replace;
use function trim;

/**
 * Query for retrieving match dropdown for [match-dropdown].
 */
final readonly class Get_League_Match_Dropdown_Query {
    public ?int $league_id;
    public ?string $season;
    public ?int $match_id;
    public string $template;

    public function __construct(
        ?int $league_id = null,
        ?string $season = null,
        ?int $match_id = null,
        string $template = ''
    ) {
        $this->league_id = ( null !== $league_id && $league_id > 0 ) ? $league_id : null;
        $this->season    = ( null !== $season && '' !== trim( $season ) && false !== $season ) ? trim( (string) $season ) : null;
        $this->match_id  = ( null !== $match_id && $match_id > 0 ) ? $match_id : null;
        $this->template  = preg_replace( '/[^A-Za-z0-9_-]/', '', $template ) ?: '';
    }

    public static function from_atts( array|string $atts, ?int $default_league_id = null ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $league_id  = isset( $atts_array['id'] ) ? (int) $atts_array['id'] : ( isset( $atts_array['league_id'] ) ? (int) $atts_array['league_id'] : $default_league_id );
        $season     = isset( $atts_array['season'] ) && false !== $atts_array['season'] && '' !== $atts_array['season'] ? (string) $atts_array['season'] : null;
        $match_id   = isset( $atts_array['match_id'] ) ? (int) $atts_array['match_id'] : null;
        $template   = isset( $atts_array['template'] ) ? (string) $atts_array['template'] : '';

        return new self(
            league_id: $league_id,
            season: $season,
            match_id: $match_id,
            template: $template
        );
    }
}
