<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use function is_array;
use function preg_replace;
use function trim;

/**
 * Query for retrieving league matches for [matches].
 */
final readonly class Get_League_Matches_Query {
    public ?int $league_id;
    public int $team;
    public string $template;
    public string $template_type;
    public ?string $season;
    public string $limit;
    public string $matches;
    public string $home_only;
    public ?string $group;
    public string $time;
    public string $show_match_day_selection;

    public function __construct(
        ?int $league_id = null,
        int $team = 0,
        string $template = 'daily',
        string $template_type = 'tabs',
        ?string $season = null,
        string $limit = 'true',
        string $matches = 'current',
        string $home_only = 'false',
        ?string $group = null,
        string $time = '',
        string $show_match_day_selection = ''
    ) {
        $this->league_id                = ( null !== $league_id && $league_id > 0 ) ? $league_id : null;
        $this->team                     = $team;
        $this->template                 = (string) preg_replace( '/[^A-Za-z0-9_-]/', '', $template );
        $this->template_type            = preg_replace( '/[^A-Za-z0-9_-]/', '', $template_type ) ?: 'tabs';
        $this->season                   = ( null !== $season && '' !== trim( $season ) && false !== $season ) ? trim( (string) $season ) : null;
        $this->limit                    = (string) $limit;
        $this->matches                  = (string) $matches;
        $this->home_only                = (string) $home_only;
        $this->group                    = ( null !== $group && '' !== trim( $group ) && false !== $group ) ? trim( (string) $group ) : null;
        $this->time                     = preg_replace( '/[^A-Za-z0-9_-]/', '', $time ) ?: '';
        $this->show_match_day_selection = (string) $show_match_day_selection;
    }

    public static function from_atts( array|string $atts, ?int $default_league_id = null ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $league_id  = isset( $atts_array['league_id'] ) ? (int) $atts_array['league_id'] : ( isset( $atts_array['id'] ) ? (int) $atts_array['id'] : $default_league_id );
        $team       = isset( $atts_array['team'] ) ? (int) $atts_array['team'] : 0;
        $template   = isset( $atts_array['template'] ) ? (string) $atts_array['template'] : 'daily';
        $template_t = isset( $atts_array['template_type'] ) ? (string) $atts_array['template_type'] : 'tabs';
        $season     = isset( $atts_array['season'] ) && '' !== $atts_array['season'] && false !== $atts_array['season'] ? (string) $atts_array['season'] : null;
        $limit      = isset( $atts_array['limit'] ) ? (string) $atts_array['limit'] : 'true';
        $matches    = isset( $atts_array['matches'] ) ? (string) $atts_array['matches'] : 'current';
        $home_only  = isset( $atts_array['home_only'] ) ? (string) $atts_array['home_only'] : 'false';
        $group      = isset( $atts_array['group'] ) && '' !== $atts_array['group'] && false !== $atts_array['group'] ? (string) $atts_array['group'] : null;
        $time       = isset( $atts_array['time'] ) ? (string) $atts_array['time'] : '';
        $selection  = isset( $atts_array['show_match_day_selection'] ) ? (string) $atts_array['show_match_day_selection'] : '';

        return new self(
            league_id: $league_id,
            team: $team,
            template: $template,
            template_type: $template_t,
            season: $season,
            limit: $limit,
            matches: $matches,
            home_only: $home_only,
            group: $group,
            time: $time,
            show_match_day_selection: $selection
        );
    }
}
