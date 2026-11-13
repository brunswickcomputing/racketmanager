<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Application\Competition\DTOs\Competition_Clubs_Result;
use Racketmanager\Application\Player\Ports\Player_Stats_Service_Interface;
use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition_Service;
use function is_numeric;
use function method_exists;
use function substr;
use function __;

/**
 * Handler for resolving [competition-clubs] shortcode data into a view model.
 */
readonly class Get_Competition_Clubs_Handler {
    /**
     * Initialises the service with required competition, club, player statistics, and season dependencies.
     *
     * @param Competition_Service             $competition_service Competition service dependency.
     * @param Club_Service_Interface          $club_service Club service dependency.
     * @param Player_Stats_Service_Interface  $player_stats_service Player statistics service dependency.
     * @param Competition_Season_Service      $competition_season_service Competition season service dependency.
     *
     * @return void
     */
    public function __construct(
        private Competition_Service $competition_service,
        private Club_Service_Interface $club_service,
        private Player_Stats_Service_Interface $player_stats_service,
        private Competition_Season_Service $competition_season_service
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
        if ( null === $comp_id || '' === $comp_id ) {
            return Competition_Clubs_Result::error( __( 'Competition not found', 'racketmanager' ) );
        }

        if ( is_string( $comp_id ) ) {
            $comp_id = Seo_Slug::decode( $comp_id );
        }

        try {
            $competition = $this->competition_service->get_by_id( $comp_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return Competition_Clubs_Result::error( $e->getMessage() );
        }

        $this->competition_season_service->resolve_current_season( $competition, (string) ( $query->season ?? '' ) );

        $season_name = isset( $competition->current_season['name'] ) ? (string) $competition->current_season['name'] : null;
        $season_int  = $query->season ?? ( ( is_numeric( $season_name ) ) ? (int) $season_name : null );

        $clubs            = null;
        $competition_club = null;

        if ( ! empty( $query->club_identifier ) ) {
            try {
                if ( is_numeric( $query->club_identifier ) ) {
                    $competition_club = $this->club_service->get_club( (int) $query->club_identifier );
                } else {
                    $competition_club = $this->club_service->get_club_by_shortcode( $query->club_identifier );
                }
            } catch ( Club_Not_Found_Exception ) {
                $competition_club = null;
            }

            if ( $competition_club ) {
                $club_id                 = method_exists( $competition_club, 'get_id' ) ? $competition_club->get_id() : ( $competition_club->id ?? null );
                $competition_club->teams = $this->competition_service->get_teams_by_query(
                    $competition,
                    [
                        'club'   => $club_id,
                        'season' => $season_name,
                        'status' => 1,
                    ]
                );

                $competition_club->matches = [];
                $matches                   = $this->competition_service->get_matches_for_competition(
                    $competition,
                    [
                        'season'  => $season_name,
                        'club'    => $club_id,
                        'time'    => 'next',
                        'orderby' => [
                            'date'      => 'ASC',
                            'league_id' => 'DESC',
                        ],
                    ]
                );
                foreach ( $matches as $match ) {
                    $key = substr( (string) $match->date, 0, 10 );
                    if ( ! isset( $competition_club->matches[ $key ] ) ) {
                        $competition_club->matches[ $key ] = [];
                    }
                    $competition_club->matches[ $key ][] = $match;
                }

                $competition_club->results = [];
                $results_matches           = $this->competition_service->get_matches_for_competition(
                    $competition,
                    [
                        'season'  => $season_name,
                        'club'    => $club_id,
                        'time'    => 'latest',
                        'orderby' => [
                            'date'      => 'ASC',
                            'league_id' => 'DESC',
                        ],
                    ]
                );
                foreach ( $results_matches as $match ) {
                    $key = substr( (string) $match->date, 0, 10 );
                    if ( ! isset( $competition_club->results[ $key ] ) ) {
                        $competition_club->results[ $key ] = [];
                    }
                    $competition_club->results[ $key ][] = $match;
                }

                $competition_club->players = $this->competition_service->get_players_for_competition_query(
                    $competition,
                    [
                        'club'   => $club_id,
                        'season' => $season_name,
                    ]
                );
                $competition_club->players = $this->player_stats_service->populate_players_stats(
                    $competition_club->players,
                    $competition,
                    $season_name,
                    'competition'
                );
            } else {
                return Competition_Clubs_Result::error( __( 'Club not found', 'racketmanager' ) );
            }
        } else {
            try {
                $clubs = $this->competition_service->get_club_details_for_competition( (int) $competition->get_id(), $season_int );
            } catch ( Season_Not_Found_Exception $e ) {
                return Competition_Clubs_Result::error( $e->getMessage() );
            }
        }

        $template = '' !== $query->template ? 'clubs-' . $query->template : 'clubs';

        return new Competition_Clubs_Result(
            competition: $competition,
            competition_club: $competition_club,
            clubs: $clubs ?? [],
            template: $template
        );
    }
}
