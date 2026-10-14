<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use function is_array;
use function preg_replace;
use function trim;

/**
 * Query for retrieving crosstable data for [crosstable].
 */
final readonly class Get_League_Crosstable_Query {
    public ?int $league_id;
    public ?string $group;
    public string $template;
    public string $mode;
    public ?string $season;

    public function __construct(
        ?int $league_id = null,
        ?string $group = null,
        string $template = '',
        string $mode = '',
        ?string $season = null
    ) {
        $this->league_id = ( null !== $league_id && $league_id > 0 ) ? $league_id : null;
        $this->group     = ( null !== $group && '' !== trim( $group ) && false !== $group ) ? trim( (string) $group ) : null;
        $this->template  = preg_replace( '/[^A-Za-z0-9_-]/', '', $template ) ?: '';
        $this->mode      = preg_replace( '/[^A-Za-z0-9_-]/', '', $mode ) ?: '';
        $this->season    = ( null !== $season && '' !== trim( $season ) && false !== $season ) ? trim( (string) $season ) : null;
    }

    public static function from_atts( array|string $atts, ?int $default_league_id = null ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $league_id  = isset( $atts_array['league_id'] ) ? (int) $atts_array['league_id'] : ( isset( $atts_array['id'] ) ? (int) $atts_array['id'] : $default_league_id );
        $group      = $atts_array['group'] ?? null;
        $template   = $atts_array['template'] ?? '';
        $mode       = $atts_array['mode'] ?? '';
        $season     = $atts_array['season'] ?? null;

        return new self(
            league_id: $league_id,
            group: $group && false !== $group ? (string) $group : null,
            template: (string) $template,
            mode: (string) $mode,
            season: $season && false !== $season ? (string) $season : null
        );
    }
}
