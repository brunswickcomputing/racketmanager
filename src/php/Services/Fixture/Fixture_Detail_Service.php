<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Fixture;

use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Fixture_Not_Found_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Util\Util;

/**
 * Service to handle retrieval of fixture details and context.
 */
class Fixture_Detail_Service {

    private Fixture_Repository_Interface $fixture_repository;
    private League_Repository_Interface $league_repository;
    private Team_Repository_Interface $team_repository;
    private Competition_Service $competition_service;
    private Team_Service $team_service;
    private Fixture_Permission_Service $permission_service;

    public function __construct( Repository_Provider $repository_provider, Service_Provider $service_provider ) {
        $this->fixture_repository   = $repository_provider->get_fixture_repository();
        $this->league_repository    = $repository_provider->get_league_repository();
        $this->team_repository      = $repository_provider->get_team_repository();
        $this->competition_service  = $service_provider->get_competition_service();
        $this->team_service         = $service_provider->get_team_service();
        $this->permission_service   = $service_provider->get_fixture_permission_service() ?? new Fixture_Permission_Service( $repository_provider, $service_provider );
    }

    /**
     * @param int|null $player_id
     * @param int|null $tournament_id
     *
     * @return Fixture_Details_DTO[]
     */
    public function get_fixtures_for_player_for_tournament( ?int $player_id, ?int $tournament_id ): array {
        $fixtures = $this->fixture_repository->find_fixtures_for_player_by_tournament( $player_id, $tournament_id );

        return array_map( fn( $fixture ) => $this->get_tournament_fixture_with_details( $fixture ), $fixtures );
    }

    public function get_fixture_with_details( int|Fixture|null $fixture_id, bool $is_tournament = false ): ?Fixture_Details_DTO {
        try {
            $fixture = $this->get_fixture_entity( $fixture_id );
            if ( ! $fixture ) {
                return null;
            }

            [ $league, $event, $competition ] = $this->get_competition_context( (int) $fixture->get_league_id() );

            $home_team = $this->get_team_details_for_fixture( $fixture->get_home_team() );
            $away_team = $this->get_team_details_for_fixture( $fixture->get_away_team() );

            [ $prev_home_match_title, $prev_away_match_title ] = $this->resolve_tournament_placeholders( $fixture, $league, $is_tournament );

            $is_update_allowed = $this->permission_service->is_update_allowed( $fixture );

        } catch ( Fixture_Not_Found_Exception|League_Not_Found_Exception|Event_Not_Found_Exception|Competition_Not_Found_Exception $e ) {
            throw new Fixture_Not_Found_Exception( $e->getMessage() );
        }

        return new Fixture_Details_DTO( $fixture, $league, $event, $competition, $home_team, $away_team, $prev_home_match_title, $prev_away_match_title, $is_update_allowed );
    }

    /**
     * Get fixture entity from ID or instance.
     */
    private function get_fixture_entity( int|Fixture|null $fixture_id ): ?Fixture {
        if ( $fixture_id instanceof Fixture ) {
            return $fixture_id;
        }

        return $this->fixture_repository->find_by_id( (int) $fixture_id );
    }

    /**
     * Get league, event, and competition context.
     */
    private function get_competition_context( int $league_id ): array {
        $league      = $this->league_repository->find_by_id( $league_id );
        if ( ! $league ) {
            throw new League_Not_Found_Exception();
        }
        $event       = $this->competition_service->get_event_by_id( $league->event->id );
        $competition = $this->competition_service->get_by_id( $event->competition->id );

        return [ $league, $event, $competition ];
    }

    /**
     * Get team details for a fixture team.
     */
    private function get_team_details_for_fixture( string|int|null $team_id ): ?object {
        if ( empty( $team_id ) ) {
            return null;
        }

        if ( is_numeric( $team_id ) ) {
            return $this->team_service->get_team_details( (int) $team_id );
        }

        return $this->team_service->derive_team_details( (string) $team_id );
    }

    /**
     * Resolve tournament placeholder titles.
     */
    private function resolve_tournament_placeholders( Fixture $fixture, object $league, bool $is_tournament ): array {
        $prev_home_match_title = null;
        $prev_away_match_title = null;

        if ( $is_tournament ) {
            if ( ! is_numeric( $fixture->get_home_team() ) ) {
                $prev_home_match_title = $this->resolve_placeholder_title( $fixture->get_home_team(), $fixture->get_season(), $league, $fixture->get_final() );
            }

            if ( ! is_numeric( $fixture->get_away_team() ) ) {
                $prev_away_match_title = $this->resolve_placeholder_title( $fixture->get_away_team(), $fixture->get_season(), $league, $fixture->get_final() );
            }
        }

        return [ $prev_home_match_title, $prev_away_match_title ];
    }

    public function get_tournament_fixture_with_details( int|Fixture|null $fixture_id ): ?Fixture_Details_DTO {
        return $this->get_fixture_with_details( $fixture_id, true );
    }

    /**
     * Resolve placeholder title for fixtures
     */
    public function resolve_placeholder_title( string $team_ref, string $season, object $league, ?string $fixture_final = null ): ?string {
        $team  = explode( '_', $team_ref );
        $final = $team[1] ?? null;
        if ( empty( $final ) ) {
            return null;
        }

        if ( 'final' !== $fixture_final ) {
            return $this->build_standard_placeholder_title( $team );
        }

        return $this->resolve_final_placeholder_title( $team, $season, $league );
    }

    /**
     * Build a standard placeholder title (Winner/Loser Round Match).
     */
    private function build_standard_placeholder_title( array $team ): string {
        $type = match ( $team[0] ) {
            '1' => __( 'Winner', 'racketmanager' ),
            '2' => __( 'Loser', 'racketmanager' ),
            default => null
        };
        $round_name = Util::get_final_name( $team[1] );
        $match_num  = $team[2] ?? '';

        /* translators: %1$s: type (Winner/Loser), %2$s: round name, %3$s: match number */
        return sprintf( __( '%1$s %2$s %3$s', 'racketmanager' ), $type, $round_name, $match_num );
    }

    /**
     * Resolve the placeholder title for a final round.
     */
    private function resolve_final_placeholder_title( array $team, string $season, object $league ): ?string {
        $args = [
            'final'   => $team[1],
            'season'  => $season,
            'orderby' => [ 'id' => 'ASC' ],
        ];

        if ( ! empty( $league->event->current_season['home_away'] ) ) {
            $args['leg'] = '2';
        }

        $prev_matches = $league->get_matches( $args );
        if ( ! $prev_matches ) {
            return $this->build_standard_placeholder_title( $team );
        }

        return $this->resolve_placeholder_from_previous_match( $team, $season, $league, $prev_matches );
    }

    /**
     * Resolve a placeholder title from a specific previous match.
     */
    private function resolve_placeholder_from_previous_match( array $team, string $season, object $league, array $prev_matches ): ?string {
        $match_ref  = (int) ( $team[2] ?? 1 ) - 1;
        $prev_match = $prev_matches[ $match_ref ] ?? null;

        if ( ! $prev_match ) {
            return null;
        }

        $prev_fixture = $this->fixture_repository->find_by_id( $prev_match->id );
        if ( ! $prev_fixture ) {
            return null;
        }

        $home_name = $this->get_team_name_or_placeholder( $prev_fixture->get_home_team(), $season, $league, $prev_fixture->get_final() );
        $away_name = $this->get_team_name_or_placeholder( $prev_fixture->get_away_team(), $season, $league, $prev_fixture->get_final() );

        return sprintf( '%s - %s', $home_name, $away_name );
    }

    /**
     * Get team name or resolve recursively if placeholder.
     */
    public function get_team_name_or_placeholder( $team_id, string $season, object $league, ?string $final ): string {
        if ( is_numeric( $team_id ) ) {
            $team = $this->team_repository->find_by_id( (int) $team_id );
            return $team ? $team->get_name() : __( 'Unknown', 'racketmanager' );
        }

        return $this->resolve_placeholder_title( $team_id, $season, $league, $final ) ?? __( 'Unknown', 'racketmanager' );
    }
}
