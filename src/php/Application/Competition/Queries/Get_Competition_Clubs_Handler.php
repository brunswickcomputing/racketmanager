<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Application\Competition\DTOs\Competition_Clubs_Result;
use Racketmanager\Application\Competition\Ports\Competition_Season_Service_Interface;
use Racketmanager\Application\Competition\Ports\Competition_Service_Interface;
use Racketmanager\Application\Player\Ports\Player_Stats_Service_Interface;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use function is_numeric;
use function method_exists;
use function substr;

/**
 * Handler for resolving [competition-clubs] shortcode data into a view model.
 */
readonly class Get_Competition_Clubs_Handler {
    /**
     * Initialises the service with required competition, club, player statistics, and season dependencies.
     *
     * @param Competition_Service_Interface        $competition_service Competition service dependency.
     * @param Club_Service_Interface               $club_service Club service dependency.
     * @param Player_Stats_Service_Interface       $player_stats_service Player statistics service dependency.
     * @param Competition_Season_Service_Interface $competition_season_service Competition season service dependency.
     *
     * @return void
     */
    public function __construct(
        private Competition_Service_Interface $competition_service,
        private Club_Service_Interface $club_service,
        private Player_Stats_Service_Interface $player_stats_service,
        private Competition_Season_Service_Interface $competition_season_service
    ) {}

    /**
     * Handles a request for competition club information, resolving the competition, season, optional club details, fixtures, results, teams, and player statistics.
     *
     * @param Get_Competition_Clubs_Query $query Query containing the competition identifier, optional season, club identifier, and template selection.
     *
     * @return Competition_Clubs_Result Result containing competition club data, a club-specific view, or an error message when the competition, club, or season cannot be resolved.
     */
    public function handle( Get_Competition_Clubs_Query $query ): Competition_Clubs_Result {
        $comp_id = $query->competition_id ?? $query->competition_name;

        try {
            $competition                  = $this->resolve_competition( $comp_id );
            [ $competition_club, $clubs ] = $this->resolve_clubs_data( $competition, $query );
        } catch ( Competition_Not_Found_Exception | Club_Not_Found_Exception | Season_Not_Found_Exception $e ) {
            return Competition_Clubs_Result::error( $e->getMessage() );
        }

        $template = '' !== $query->template ? 'clubs-' . $query->template : 'clubs';

        return new Competition_Clubs_Result(
            competition: $competition,
            competition_club: $competition_club,
            clubs: $clubs ?? [],
            template: $template
        );
    }

    /**
     * Resolves the competition entity from a given identifier or slug.
     *
     * @param int|string|null $comp_id Raw competition identifier or SEO slug.
     *
     * @return Competition Resolved competition entity.
     *
     * @throws Competition_Not_Found_Exception If the identifier is missing or the competition cannot be found.
     */
    private function resolve_competition( int|string|null $comp_id ): Competition {
        if ( null === $comp_id || '' === $comp_id ) {
            throw new Competition_Not_Found_Exception( esc_html__( 'Competition not found', 'racketmanager' ) );
        }

        if ( is_string( $comp_id ) ) {
            $comp_id = Seo_Slug::decode( $comp_id );
        }

        return $this->competition_service->get_by_id( $comp_id );
    }

    /**
     * Resolves either a single hydrated club or the list of competition clubs for the current season.
     *
     * @param Competition                 $competition Competition entity context.
     * @param Get_Competition_Clubs_Query $query Query containing club and season filters.
     *
     * @return array{0: object|null, 1: array|null} Tuple containing [ $competition_club, $clubs ].
     *
     * @throws Club_Not_Found_Exception If the specified club cannot be resolved.
     * @throws Season_Not_Found_Exception If competition clubs cannot be retrieved for the resolved season.
     */
    private function resolve_clubs_data( Competition $competition, Get_Competition_Clubs_Query $query ): array {
        $this->competition_season_service->resolve_current_season( $competition, (string) ( $query->season ?? '' ) );

        $season_name = isset( $competition->current_season['name'] ) ? (string) $competition->current_season['name'] : null;
        $season_int  = $query->season ?? ( is_numeric( $season_name ) ? (int) $season_name : null );

        if ( ! empty( $query->club_identifier ) ) {
            $club = $this->find_club( $query->club_identifier );
            $this->hydrate_club( $club, $competition, $season_name );

            return [ $club, null ];
        }

        $clubs = $this->competition_service->get_club_details_for_competition( (int) $competition->get_id(), $season_int );

        return [ null, $clubs ];
    }

    /**
     * Resolves a club by numeric ID or shortcode identifier.
     *
     * @param string|int $identifier Club ID or shortcode.
     *
     * @return object Resolved club object.
     *
     * @throws Club_Not_Found_Exception When the club does not exist.
     */
    private function find_club( string|int $identifier ): object {
        try {
            if ( is_numeric( $identifier ) ) {
                return $this->club_service->get_club( (int) $identifier );
            }

            return $this->club_service->get_club_by_shortcode( (string) $identifier );
        } catch ( Club_Not_Found_Exception ) {
            throw new Club_Not_Found_Exception( esc_html__( 'Club not found', 'racketmanager' ) );
        }
    }

    /**
     * Hydrates a club with teams, grouped fixtures, grouped results, and player statistics for the competition season.
     *
     * @param object      $club Club entity or domain object.
     * @param Competition $competition Competition context.
     * @param string|null $season_name Current season name.
     *
     * @return void
     */
    private function hydrate_club( object $club, Competition $competition, ?string $season_name ): void {
        $club_id = method_exists( $club, 'get_id' ) ? $club->get_id() : ( $club->id ?? null );

        $teams = $this->competition_service->get_teams_by_query(
            $competition,
            [
                'club'   => $club_id,
                'season' => $season_name,
                'status' => 1,
            ]
        );

        $matches = $this->get_grouped_matches( $competition, $club_id, $season_name, 'next' );
        $results = $this->get_grouped_matches( $competition, $club_id, $season_name, 'latest' );

        $players = $this->competition_service->get_players_for_competition_query(
            $competition,
            [
                'club'   => $club_id,
                'season' => $season_name,
            ]
        );
        $players = $this->player_stats_service->populate_players_stats(
            $players,
            $competition,
            $season_name,
            'competition'
        );

        if ( $club instanceof Club ) {
            $club->set_teams( $teams );
            $club->set_matches( $matches );
            $club->set_results( $results );
            $club->set_players( $players );
        } else {
            $club->teams   = $teams;
            $club->matches = $matches;
            $club->results = $results;
            $club->players = $players;
        }
    }

    /**
     * Retrieves and groups competition matches by date for a given club and time filter.
     *
     * @param Competition     $competition Competition context.
     * @param int|string|null $club_id Club identifier.
     * @param string|null     $season_name Season name.
     * @param string          $time Time filter ('next' or 'latest').
     *
     * @return array<string, array> Grouped matches indexed by date string (YYYY-MM-DD).
     */
    private function get_grouped_matches( Competition $competition, int|string|null $club_id, ?string $season_name, string $time ): array {
        $matches = $this->competition_service->get_matches_for_competition(
            $competition,
            [
                'season'  => $season_name,
                'club'    => $club_id,
                'time'    => $time,
                'orderby' => [
                    'date'      => 'ASC',
                    'league_id' => 'DESC',
                ],
            ]
        );

        return $this->group_matches_by_date( $matches );
    }

    /**
     * Groups an array of match objects by their date (YYYY-MM-DD).
     *
     * @param array $matches List of match objects.
     *
     * @return array<string, array> Matches grouped by date.
     */
    private function group_matches_by_date( array $matches ): array {
        $grouped = [];
        foreach ( $matches as $match ) {
            $key = substr( (string) $match->date, 0, 10 );
            if ( ! isset( $grouped[ $key ] ) ) {
                $grouped[ $key ] = [];
            }
            $grouped[ $key ][] = $match;
        }

        return $grouped;
    }
}
