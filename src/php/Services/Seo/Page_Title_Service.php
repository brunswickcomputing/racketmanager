<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Seo;

use Racketmanager\Application\Ports\Page_Title_Service_Interface;
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Domain\Shared\Seo_Slug;

/**
 * Service responsible for constructing dynamic page and document titles.
 */
readonly class Page_Title_Service implements Page_Title_Service_Interface {

    /**
     * Constructor.
     *
     * @param string|null               $site_name  Default site name.
     * @param Query_Vars_Interface|null $query_vars Query vars port.
     */
    public function __construct(
        private ?string $site_name = null,
        private ?Query_Vars_Interface $query_vars = null,
    ) {
    }

    /**
     * Build dynamic page title based on current post slug and query variables.
     *
     * @param string      $title     Default or existing title.
     * @param string|null $site_name Optional site name override.
     * @return string
     */
    public function build_title( string $title = '', ?string $site_name = null ): string {
        $slug      = function_exists( 'get_post_field' ) ? (string) get_post_field( 'post_name' ) : '';
        $site_name = $site_name ?? $this->site_name ?? ( function_exists( 'get_bloginfo' ) ? (string) get_bloginfo( 'name' ) : '' );

        $slug_title = $this->resolve_slug_title( $slug );
        if ( null === $slug_title ) {
            return $title;
        }

        return '' !== $site_name ? $slug_title . ' - ' . $site_name : $slug_title;
    }

    /**
     * Resolve decoded query variable by key.
     *
     * @param string $key Query variable key.
     *
     * @return string Decoded, title-cased value or empty string.
     */
    private function get_query_var( string $key ): string {
        $val = $this->query_vars?->get( $key );

        return is_string( $val ) ? ucwords( Seo_Slug::decode( $val ) ) : '';
    }

    /**
     * Append club name suffix if present.
     *
     * @param string $title Base title.
     *
     * @return string Title with club name appended.
     */
    private function append_club( string $title ): string {
        $club = $this->get_query_var( 'club_name' );

        return '' !== $club ? $title . ' - ' . $club : $title;
    }

    /**
     * Resolve slug-specific title without site name suffix.
     *
     * @param string $slug Post slug.
     *
     * @return string|null Resolved title prefix or null if unhandled / unchanged.
     */
    private function resolve_slug_title( string $slug ): ?string {
        return match ( $slug ) {
            'player'         => $this->build_player_title(),
            'event'          => $this->build_event_title(),
            'competitions'   => $this->build_competitions_title(),
            'competition'    => $this->build_competition_title(),
            'latest-results' => $this->build_latest_results_title(),
            'cup'            => $this->build_cup_title(),
            'league'         => $this->build_league_title(),
            'team'           => $this->build_team_title(),
            'match'          => $this->build_match_title(),
            'entry'          => $this->build_entry_title(),
            'tournament'     => $this->build_tournament_title(),
            'club'           => $this->build_club_title(),
            'invoices'       => $this->build_invoices_title(),
            'roles'          => $this->build_roles_title(),
            'players'        => $this->build_players_title(),
            default          => null,
        };
    }

    /**
     * Build the player title from the current player query variable.
     *
     * @return string|null Player identifier when available, or null when unset.
     */
    private function build_player_title(): ?string {
        $player = $this->get_query_var( 'player_id' );

        return '' !== $player ? $player : null;
    }

    /**
     * Build the event page title from query variables.
     *
     * Combines the event name with an optional season, and prefixes it with a
     * player or club name when present.
     *
     * @return string Event title.
     */
    private function build_event_title(): string {
        $event  = $this->get_query_var( 'event' );
        $season = $this->get_query_var( 'season' );
        $player = $this->get_query_var( 'player_id' );
        $club   = $this->get_query_var( 'club_name' );

        if ( '' !== $season ) {
            $event .= ' ' . $season;
        }

        if ( '' !== $player ) {
            return $player . ' - ' . $event;
        }

        if ( '' !== $club ) {
            return $club . ' - ' . $event;
        }

        return $event;
    }

    /**
     * Build the competitions page title, using the requested type when available.
     *
     * @return string Competitions title with any applicable club context appended.
     */
    private function build_competitions_title(): string {
        $type = $this->get_query_var( 'type' );
        $base = '' !== $type
            ? $type . ' ' . __( 'List', 'racketmanager' )
            : __( 'Competitions', 'racketmanager' );

        return $this->append_club( $base );
    }

    /**
     * Build the competition page title, including competition, season, and club context when available.
     *
     * @return string Competition title with appended club context.
     */
    private function build_competition_title(): string {
        $competition = $this->get_query_var( 'competition' );
        $season      = $this->get_query_var( 'season' );

        $main = '' !== $competition ? $competition . ' ' : __( 'Competition', 'racketmanager' );
        if ( '' !== $season ) {
            $main .= ' ' . $season;
        }

        return $this->append_club( $main );
    }

    /**
     * Build the latest results page title from query variables.
     *
     * @return string Latest results title, optionally including competition type and competition name.
     */
    private function build_latest_results_title(): string {
        $type        = $this->get_query_var( 'competition_type' );
        $competition = $this->get_query_var( 'competition_name' );

        $prefix = '' !== $type
            /* translators: %s: competition type */
            ? sprintf( __( 'Latest %s Results', 'racketmanager' ), $type )
            : __( 'Latest results', 'racketmanager' );

        $parts = [ $prefix ];
        if ( '' !== $competition ) {
            $parts[] = $competition;
        }

        return implode( ' - ', $parts );
    }

    /**
     * Build the cup title from the current event and optional season query variables.
     *
     * @return string Cup title, including the season when available.
     */
    private function build_cup_title(): string {
        $event  = $this->get_query_var( 'event' );
        $season = $this->get_query_var( 'season' );

        return '' !== $season ? $event . ' - ' . $season : $event;
    }

    /**
     * Build the league page title from query variables.
     *
     * Combines the league name with the season when available, and prefixes the
     * result with the team name when a team query variable is present.
     *
     * @return string League title assembled from league, season, and optional team values.
     */
    private function build_league_title(): string {
        $league = $this->get_query_var( 'league_name' );
        $team   = $this->get_query_var( 'team' );
        $season = $this->get_query_var( 'season' );

        if ( '' !== $season ) {
            $league .= ' - ' . $season;
        }

        return '' !== $team ? $team . ' - ' . $league : $league;
    }

    /**
     * Build the team page title from available query variables.
     *
     * @return string Team title composed from team, competition, and event values.
     */
    private function build_team_title(): string {
        $team        = $this->get_query_var( 'team' );
        $competition = $this->get_query_var( 'competition_name' );
        $event       = $this->get_query_var( 'event' );

        $parts = array_filter( [ $team, $competition, $event ], static fn( string $v ): bool => '' !== $v );

        return implode( ' - ', $parts );
    }

    /**
     * Build the match page title from query variables.
     *
     * @return string Match title composed of matchup, league or season, and tournament details.
     */
    private function build_match_title(): string {
        $league     = $this->get_query_var( 'league_name' );
        $tournament = $this->get_query_var( 'tournament' );
        $team_home  = $this->get_query_var( 'teamHome' );
        $team_away  = $this->get_query_var( 'teamAway' );
        $season     = $this->get_query_var( 'season' );

        if ( '' !== $season ) {
            $league .= ' - ' . $season;
        }

        $heading = ( '' !== $team_home && '' !== $team_away )
            ? $team_home . ' ' . __( 'vs', 'racketmanager' ) . ' ' . $team_away
            : __( 'Match', 'racketmanager' );

        $parts = array_filter( [ $heading, $league, $tournament ], static fn( string $v ): bool => '' !== $v );

        return implode( ' - ', $parts );
    }

    /**
     * Build the entry form page title from query context.
     *
     * Uses competition, tournament, player, and season query variables when present,
     * then appends the current club context to the generated title.
     *
     * @return string Entry form title including available context and club suffix.
     */
    private function build_entry_title(): string {
        $competition = $this->get_query_var( 'competition_name' );
        $tournament  = $this->get_query_var( 'tournament' );
        $player      = $this->get_query_var( 'player_id' );
        $season      = $this->get_query_var( 'season' );

        $parts = [ __( 'Entry Form', 'racketmanager' ) ];
        if ( '' !== $competition ) {
            $parts[] = $competition;
        } elseif ( '' !== $tournament ) {
            $parts[] = $tournament . ' - ' . __( 'Tournament', 'racketmanager' );
            if ( '' !== $player ) {
                $parts[] = $player;
            }
        }

        if ( '' !== $season ) {
            $parts[] = $season;
        }

        return $this->append_club( implode( ' - ', $parts ) );
    }

    /**
     * Build the tournament page title from active query variables.
     *
     * Combines player, draw, event, matches tab, and tournament context into a localised title string,
     * defaulting the tournament name to "Latest" when no tournament query value is present.
     *
     * @return string Tournament title assembled from the current query context.
     */
    private function build_tournament_title(): string {
        $tournament = $this->get_query_var( 'tournament' );
        $tournament = '' !== $tournament ? $tournament : __( 'Latest', 'racketmanager' );
        $draw       = $this->get_query_var( 'draw' );
        $player     = $this->get_query_var( 'player' );
        $event      = $this->get_query_var( 'event' );
        $tab        = $this->get_query_var( 'tab' );

        $parts = [];
        if ( '' !== $player ) {
            $parts[] = $player . ' - ' . __( 'Player', 'racketmanager' );
        }
        if ( '' !== $draw ) {
            $parts[] = $draw . ' ' . __( 'Draw', 'racketmanager' );
        }
        if ( '' !== $event ) {
            $parts[] = $event . ' ' . __( 'Event', 'racketmanager' );
        }
        if ( 'matches' === $tab ) {
            $parts[] = __( 'Matches', 'racketmanager' );
        }
        $parts[] = $tournament . ' - ' . __( 'Tournament', 'racketmanager' );

        return implode( ' - ', $parts );
    }

    /**
     * Build the club page title from the current club query variable.
     *
     * @return string Club name when available, otherwise the default clubs title.
     */
    private function build_club_title(): string {
        $club = $this->get_query_var( 'club_name' );

        return '' !== $club ? $club : __( 'Clubs', 'racketmanager' );
    }

    /**
     * Build the invoices page title, including a specific invoice identifier when present.
     *
     * @return string Invoices title with the club name appended.
     */
    private function build_invoices_title(): string {
        $invoice = $this->get_query_var( 'invoice' );
        $prefix  = '' !== $invoice
            ? __( 'Invoice', 'racketmanager' ) . ' - ' . $invoice
            : __( 'Invoices', 'racketmanager' );

        return $this->append_club( $prefix );
    }

    /**
     * Build the roles page title with the current club name appended.
     *
     * @return string Roles page title including the club context.
     */
    private function build_roles_title(): string {
        return $this->append_club( __( 'Roles', 'racketmanager' ) );
    }

    /**
     * Build the players page title, using the player query value when present.
     *
     * @return string Players title with the club name appended.
     */
    private function build_players_title(): string {
        $player = $this->get_query_var( 'player_id' );
        $prefix = '' !== $player ? $player : __( 'Players', 'racketmanager' );

        return $this->append_club( $prefix );
    }
}
