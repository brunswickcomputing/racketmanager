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

    public function __construct(
        ?string $league_name = null,
        string $standings = 'last5',
        string $crosstable = '',
        string $matches = '',
        string $teams = 'list',
        string $template = '',
        string $matches_template_type = '',
        ?string $tab = null
    ) {
        $this->league_name           = ( null !== $league_name && '' !== trim( $league_name ) ) ? trim( $league_name ) : null;
        $this->standings             = preg_replace( '/[^A-Za-z0-9_-]/', '', $standings ) ?: 'last5';
        $this->crosstable            = preg_replace( '/[^A-Za-z0-9_-]/', '', $crosstable ) ?: '';
        $this->matches               = preg_replace( '/[^A-Za-z0-9_-]/', '', $matches ) ?: '';
        $this->teams                 = preg_replace( '/[^A-Za-z0-9_-]/', '', $teams ) ?: 'list';
        $this->template              = preg_replace( '/[^A-Za-z0-9_-]/', '', $template ) ?: '';
        $this->matches_template_type = preg_replace( '/[^A-Za-z0-9_-]/', '', $matches_template_type ) ?: '';
        $this->tab                   = ( null !== $tab && '' !== trim( $tab ) ) ? trim( $tab ) : null;
    }

    public static function from_atts( array|string $atts, array $query_vars = [], ?string $default_league_name = null ): self {
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
        $tab = isset( $query_vars['tab'] ) ? (string) $query_vars['tab'] : ( isset( $_GET['tab'] ) ? (string) $_GET['tab'] : null );

        return new self(
            league_name: $league_name,
            standings: (string) ( $atts_array['standings'] ?? 'last5' ),
            crosstable: (string) ( $atts_array['crosstable'] ?? '' ),
            matches: (string) ( $atts_array['matches'] ?? '' ),
            teams: (string) ( $atts_array['teams'] ?? 'list' ),
            template: (string) ( $atts_array['template'] ?? '' ),
            matches_template_type: (string) ( $atts_array['matches_template_type'] ?? '' ),
            tab: $tab
        );
    }
}
