<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Fixture;

use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Scoring\Set_Score;
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

    public function __construct( 
        Repository_Provider $repository_provider, 
        Competition_Service $competition_service,
        Team_Service $team_service,
        Fixture_Permission_Service $permission_service
    ) {
        $this->fixture_repository   = $repository_provider->get_fixture_repository();
        $this->league_repository    = $repository_provider->get_league_repository();
        $this->team_repository      = $repository_provider->get_team_repository();
        $this->competition_service  = $competition_service;
        $this->team_service         = $team_service;
        $this->permission_service   = $permission_service;
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

            $fixture->rubbers = $fixture->get_rubbers();

            [ $league, $event, $competition ] = $this->get_competition_context( (int) $fixture->get_league_id() );

            $home_team = $this->get_team_details_for_fixture( $fixture->get_home_team(), $league, $fixture->get_season() );
            $away_team = $this->get_team_details_for_fixture( $fixture->get_away_team(), $league, $fixture->get_season() );

            [ $prev_home_match_title, $prev_away_match_title ] = $this->resolve_tournament_placeholders( $fixture, $league, $is_tournament );

            $is_update_allowed = $this->permission_service->is_update_allowed( $fixture );

            $link          = $this->get_match_link( $fixture, $league, $home_team, $away_team );
            $score_display = $this->generate_score_display( $fixture, $event );
            $status_flags  = $this->generate_status_flags( $fixture );
            $match_title   = $this->generate_match_title( $fixture, $home_team, $away_team, $prev_home_match_title, $prev_away_match_title );

        } catch ( Fixture_Not_Found_Exception|League_Not_Found_Exception|Event_Not_Found_Exception|Competition_Not_Found_Exception $e ) {
            throw new Fixture_Not_Found_Exception( $e->getMessage() );
        }

        return new Fixture_Details_DTO( $fixture, $league, $event, $competition, $home_team, $away_team, $prev_home_match_title, $prev_away_match_title, $is_update_allowed, $link, $score_display, $status_flags, $match_title );
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
        $event       = $this->competition_service->get_event_by_id( $league->get_event_id() );
        $competition = $this->competition_service->get_by_id( $event->get_competition_id() );

        return [ $league, $event, $competition ];
    }

    /**
     * Get team details for a fixture team.
     */
    private function get_team_details_for_fixture( string|int|null $team_id, object $league, string $season ): ?Team_Details_DTO {
        if ( empty( $team_id ) ) {
            return null;
        }

        $dto = is_numeric( $team_id )
            ? $this->team_service->get_team_details( (int) $team_id )
            : $this->team_service->derive_team_details( (string) $team_id );

        if ( $dto && is_numeric( $team_id ) && method_exists( $league, 'get_status' ) ) {
            $status = $league->get_status( (int) $team_id, $season );
            $dto->is_withdrawn = ( 'W' === $status );
        }

        return $dto;
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

        $seasons = $league->get_seasons();
        if ( ! empty( $seasons[ $season ]['home_away'] ) ) {
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

    /**
     * Get the match link for a fixture.
     */
    private function get_match_link( Fixture $match, object $league, ?object $home_team, ?object $away_team ): string {
        $type = $league->get_competition_type();

        if ( 'tournament' === $type ) {
            return $this->get_tournament_link( $match, $league );
        }

        return $this->resolve_match_link( $match, $league, $home_team, $away_team );
    }

    /**
     * Resolve match link for a fixture.
     */
    private function resolve_match_link( Fixture $match, object $league, ?object $home_team, ?object $away_team ): string {
        $type = $league->get_competition_type();

        if ( 'box' === $type ) {
            $url  = home_url( '/league/' );
            $url .= Util::seo_url( $league->get_name() ) . '/match/' . $match->get_id() . '/';
            return $url;
        }

        return $this->generate_standard_match_link( $match, $league, $home_team, $away_team );
    }

    /**
     * Get the tournament link for a fixture.
     */
    private function get_tournament_link( Fixture $match, object $league ): string {
        $url  = home_url( '/tournament/' );
        $url .= Util::seo_url( (string) $league->get_id() ) . '/';
        $url .= Util::seo_url( $league->get_name() ) . '/';
        $url .= 'match-' . $match->get_id() . '/';

        return $url;
    }

    /**
     * Generate a standard match link for a fixture.
     */
    private function generate_standard_match_link( Fixture $match, object $league, ?object $home_team, ?object $away_team ): string {
        $url  = home_url( '/league/' );
        $url .= Util::seo_url( (string) $league->get_id() ) . '/';
        $url .= Util::seo_url( $league->get_name() ) . '/';
        $url .= Util::seo_url( $match->get_season() ) . '/';

        if ( $home_team && $away_team ) {
            $url .= Util::seo_url( $home_team->team->get_name() ) . '-v-' . Util::seo_url( $away_team->team->get_name() ) . '/';
        }

        if ( $match->get_leg() ) {
            $url .= 'leg-' . $match->get_leg() . '/';
        }

        return $url;
    }

    /**
     * Generate the score display for a fixture.
     */
    private function generate_score_display( Fixture $fixture, object $event ): ?string {
        if ( $this->is_fixture_walkover( $fixture, $event ) ) {
            return __( 'Walkover', 'racketmanager' );
        }

        $home_points = $fixture->get_home_points();
        $away_points = $fixture->get_away_points();

        if ( null === $home_points && null === $away_points ) {
            return null;
        }

        return $event->get_num_rubbers() ? sprintf( '%g - %g', $home_points, $away_points ) : $this->format_set_scores( $fixture );
    }

    /**
     * Check if a fixture is a walkover.
     */
    private function is_fixture_walkover( Fixture $fixture, object $event ): bool {
        if ( $fixture->is_walkover() ) {
            return true;
        }

        if ( null === $fixture->get_home_points() && null === $fixture->get_away_points() ) {
            return $fixture->get_winner_id() && ( '-1' === $fixture->get_home_team() || '-1' === $fixture->get_away_team() );
        }

        return $event->get_num_rubbers() && ( '-1' === $fixture->get_home_team() || '-1' === $fixture->get_away_team() );
    }

    /**
     * Format set scores for a fixture.
     */
    private function format_set_scores( Fixture $fixture ): string {
        $custom = $fixture->get_custom();
        $sets   = $custom['sets'] ?? array();
        if ( empty( $sets ) ) {
            return $fixture->is_walkover() ? __( 'Walkover', 'racketmanager' ) : sprintf( '%g - %g', $fixture->get_home_points(), $fixture->get_away_points() );
        }

        $set_scores = array();
        foreach ( $sets as $set ) {
            if ( $set instanceof Set_Score ) {
                $set_scores[] = sprintf( '%d-%d', $set->get_home_games(), $set->get_away_games() );
            } elseif ( isset( $set['player1'], $set['player2'] ) && '' !== $set['player1'] && '' !== $set['player2'] ) {
                $set_scores[] = sprintf( '%s-%s', $set['player1'], $set['player2'] );
            }
        }

        return implode( ' ', $set_scores );
    }

    /**
     * Generate status flags for a fixture.
     */
    private function generate_status_flags( Fixture $fixture ): array {
        $flags = array();
        if ( $fixture->is_walkover() ) {
            $flags[] = 'walkover';
        }
        if ( $fixture->is_retired() ) {
            $flags[] = 'retired';
        }
        if ( $fixture->is_shared() ) {
            $flags[] = 'shared';
        }
        if ( $fixture->is_abandoned() ) {
            $flags[] = 'abandoned';
        }
        if ( $fixture->is_withdrawn() ) {
            $flags[] = 'withdrawn';
        }
        if ( $fixture->is_cancelled() ) {
            $flags[] = 'cancelled';
        }

        return $flags;
    }

    /**
     * Generate match title for a fixture.
     */
    private function generate_match_title( Fixture $fixture, ?object $home_team, ?object $away_team, ?string $prev_home_title, ?string $prev_away_title ): string {
        $home = $home_team ? $home_team->team->get_name() : ( $prev_home_title ?? __( 'Unknown', 'racketmanager' ) );
        $away = $away_team ? $away_team->team->get_name() : ( $prev_away_title ?? __( 'Unknown', 'racketmanager' ) );

        return $home . ' v ' . $away;
    }
}
