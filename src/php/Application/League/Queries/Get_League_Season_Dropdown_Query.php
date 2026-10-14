<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use function is_array;
use function preg_replace;
use function trim;

/**
 * Query for retrieving season dropdown for [season-dropdown].
 */
final readonly class Get_League_Season_Dropdown_Query {
    public ?int $league_id;
    public ?string $season;
    public string $template;

    public function __construct(
        ?int $league_id = null,
        ?string $season = null,
        string $template = ''
    ) {
        $this->league_id = ( null !== $league_id && $league_id > 0 ) ? $league_id : null;
        $this->season    = ( null !== $season && '' !== trim( $season ) && false !== $season ) ? trim( (string) $season ) : null;
        $this->template  = preg_replace( '/[^A-Za-z0-9_-]/', '', $template ) ?: '';
    }

    public static function from_atts( array|string $atts, ?int $default_league_id = null ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $league_id  = isset( $atts_array['id'] ) ? (int) $atts_array['id'] : ( isset( $atts_array['league_id'] ) ? (int) $atts_array['league_id'] : $default_league_id );
        $season     = isset( $atts_array['season'] ) && false !== $atts_array['season'] && '' !== $atts_array['season'] ? (string) $atts_array['season'] : null;
        $template   = isset( $atts_array['template'] ) ? (string) $atts_array['template'] : '';

        return new self(
            league_id: $league_id,
            season: $season,
            template: $template
        );
    }
}
