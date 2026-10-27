<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use Racketmanager\Domain\Shared\Seo_Slug;
use function is_array;
use function preg_replace;
use function trim;

/**
 * Query for retrieving league teams for [teams].
 */
final readonly class Get_League_Teams_Query {
    public ?int $league_id;
    public string $template;
    public ?string $team_id;
    public ?string $group;

    public function __construct(
        ?int $league_id = null,
        string $template = '',
        ?string $team_id = null,
        ?string $group = null
    ) {
        $this->league_id = ( null !== $league_id && $league_id > 0 ) ? $league_id : null;
        $this->template  = preg_replace( '/[^A-Za-z0-9_-]/', '', $template ) ?: '';
        $this->team_id   = ( null !== $team_id && '' !== trim( $team_id ) && false !== $team_id ) ? trim( (string) $team_id ) : null;
        $this->group     = ( null !== $group && '' !== trim( $group ) && false !== $group ) ? trim( (string) $group ) : null;
    }

    public static function from_atts( array|string $atts, array $query_vars = [], ?int $default_league_id = null ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $league_id  = isset( $atts_array['league_id'] ) ? (int) $atts_array['league_id'] : ( isset( $atts_array['id'] ) ? (int) $atts_array['id'] : $default_league_id );
        $template   = isset( $atts_array['template'] ) ? (string) $atts_array['template'] : '';
        $group      = isset( $atts_array['group'] ) && '' !== $atts_array['group'] && false !== $atts_array['group'] ? (string) $atts_array['group'] : null;
        $team_id    = isset( $atts_array['teams'] ) && '' !== $atts_array['teams'] && null !== $atts_array['teams'] ? (string) $atts_array['teams'] : null;

        if ( ! $team_id && isset( $query_vars['team'] ) ) {
            $team_id = Seo_Slug::decode( (string) $query_vars['team'] );
        }

        return new self(
            league_id: $league_id,
            template: $template,
            team_id: $team_id,
            group: $group
        );
    }
}
