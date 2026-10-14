<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use function is_array;
use function preg_replace;

/**
 * Query for retrieving last 5 matches for [last-5].
 */
final readonly class Get_League_Last_5_Query {
    public ?int $league_id;
    public ?int $team_id;
    public string $template;

    public function __construct(
        ?int $league_id = null,
        ?int $team_id = null,
        string $template = ''
    ) {
        $this->league_id = ( null !== $league_id && $league_id > 0 ) ? $league_id : null;
        $this->team_id   = ( null !== $team_id && $team_id > 0 ) ? $team_id : null;
        $this->template  = preg_replace( '/[^A-Za-z0-9_-]/', '', $template ) ?: '';
    }

    public static function from_atts( array|string $atts, ?int $default_league_id = null ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $league_id  = isset( $atts_array['id'] ) ? (int) $atts_array['id'] : ( isset( $atts_array['league_id'] ) ? (int) $atts_array['league_id'] : $default_league_id );
        $team_id    = isset( $atts_array['team_id'] ) ? (int) $atts_array['team_id'] : null;
        $template   = isset( $atts_array['template'] ) ? (string) $atts_array['template'] : '';

        return new self(
            league_id: $league_id,
            team_id: $team_id,
            template: $template
        );
    }
}
