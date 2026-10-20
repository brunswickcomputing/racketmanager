<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use function is_array;
use function preg_replace;
use function str_replace;
use function trim;

/**
 * Query for retrieving league archive data for [leaguearchive].
 */
final readonly class Get_League_Archive_Query {
    public ?string $league_name;
    public string $standings;
    public string $crosstable;
    public string $matches;
    public string $teams;
    public string $template;
    public string $matches_template_type;
    public ?string $tab;
    public int|string|null $post_id;
    public ?string $match_day;
    public ?string $team_id;
    public ?string $player_id;
    public ?string $team;

    public function __construct(
        ?string $league_name = null,
        string $standings = 'last5',
        string $crosstable = '',
        string $matches = '',
        string $teams = 'list',
        string $template = '',
        string $matches_template_type = '',
        ?string $tab = null,
        int|string|null $post_id = null,
        ?string $match_day = null,
        ?string $team_id = null,
        ?string $player_id = null,
        ?string $team = null
    ) {
        $this->league_name           = ( null !== $league_name && '' !== trim( $league_name ) ) ? trim( $league_name ) : null;
        $this->standings             = preg_replace( '/[^A-Za-z0-9_-]/', '', $standings ) ?: 'last5';
        $this->crosstable            = preg_replace( '/[^A-Za-z0-9_-]/', '', $crosstable ) ?: '';
        $this->matches               = preg_replace( '/[^A-Za-z0-9_-]/', '', $matches ) ?: '';
        $this->teams                 = preg_replace( '/[^A-Za-z0-9_-]/', '', $teams ) ?: 'list';
        $this->template              = preg_replace( '/[^A-Za-z0-9_-]/', '', $template ) ?: '';
        $this->matches_template_type = preg_replace( '/[^A-Za-z0-9_-]/', '', $matches_template_type ) ?: '';
        $this->tab                   = ( null !== $tab && '' !== trim( $tab ) ) ? trim( $tab ) : null;
        $this->post_id               = $post_id;
        $this->match_day             = $match_day;
        $this->team_id               = $team_id;
        $this->player_id             = $player_id;
        $this->team                  = $team;
    }

    public static function from_atts( array|string $atts, array $query_vars = [], ?string $default_league_name = null, array $get_params = [] ): self {
        $atts_array  = is_array( $atts ) ? $atts : [];
        $league_name = isset( $atts_array['league_name'] ) && '' !== $atts_array['league_name'] ? (string) $atts_array['league_name'] : null;
        if ( ! $league_name && isset( $atts_array['league'] ) && '' !== $atts_array['league'] ) {
            $league_name = (string) $atts_array['league'];
        }
        if ( ! $league_name && isset( $query_vars['league_name'] ) ) {
            $league_name = str_replace( '-', ' ', (string) $query_vars['league_name'] );
        }
        if ( ! $league_name && isset( $query_vars['league'] ) ) {
            $league_name = str_replace( '-', ' ', (string) $query_vars['league'] );
        }
        if ( ! $league_name && $default_league_name ) {
            $league_name = $default_league_name;
        }

        $match_day = isset( $query_vars['match_day'] ) ? (string) $query_vars['match_day'] : ( isset( $get_params['match_day'] ) ? (string) $get_params['match_day'] : null );
        if ( '0' === $match_day ) {
            $match_day = '-1';
        }
        $team_id   = isset( $query_vars['team_id'] ) ? (string) $query_vars['team_id'] : ( isset( $get_params['team_id'] ) ? (string) $get_params['team_id'] : null );
        $player_id = isset( $query_vars['player_id'] ) ? (string) $query_vars['player_id'] : ( isset( $get_params['player_id'] ) ? (string) $get_params['player_id'] : null );
        $team      = isset( $query_vars['team'] ) ? (string) $query_vars['team'] : ( isset( $get_params['team'] ) ? (string) $get_params['team'] : null );

        $tab = isset( $atts_array['tab'] ) ? (string) $atts_array['tab'] : ( isset( $query_vars['tab'] ) ? (string) $query_vars['tab'] : ( isset( $get_params['tab'] ) ? (string) $get_params['tab'] : null ) );

        if ( empty( $tab ) ) {
            if ( ! empty( $match_day ) || ! empty( $team_id ) ) {
                $tab = 'matches';
            } elseif ( ! empty( $player_id ) ) {
                $tab = 'players';
            } elseif ( ! empty( $team ) ) {
                $tab = 'teams';
            } else {
                $tab = 'standings';
            }
        } elseif ( ! empty( $match_day ) || ! empty( $team_id ) ) {
            $tab = 'matches';
        }

        $post_id = $atts_array['post_id'] ?? $query_vars['post_id'] ?? null;

        return new self(
            league_name: $league_name,
            standings: (string) ( $atts_array['standings'] ?? 'last5' ),
            crosstable: (string) ( $atts_array['crosstable'] ?? '' ),
            matches: (string) ( $atts_array['matches'] ?? '' ),
            teams: (string) ( $atts_array['teams'] ?? 'list' ),
            template: (string) ( $atts_array['template'] ?? '' ),
            matches_template_type: (string) ( $atts_array['matches_template_type'] ?? '' ),
            tab: $tab,
            post_id: $post_id,
            match_day: $match_day,
            team_id: $team_id,
            player_id: $player_id,
            team: $team
        );
    }
}
