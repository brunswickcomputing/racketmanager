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
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
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
    private Rubber_Repository_Interface $rubber_repository;
    private Competition_Service $competition_service;
    private Team_Service $team_service;
    private Fixture_Permission_Service $permission_service;
    private Fixture_Link_Service $link_service;

    public function __construct(
        Repository_Provider $repository_provider,
        Competition_Service $competition_service,
        Team_Service $team_service,
        Fixture_Permission_Service $permission_service,
        Fixture_Link_Service $link_service
    ) {
        $this->fixture_repository   = $repository_provider->get_fixture_repository();
        $this->league_repository    = $repository_provider->get_league_repository();
        $this->team_repository      = $repository_provider->get_team_repository();
        $this->rubber_repository    = $repository_provider->get_rubber_repository();
        $this->competition_service  = $competition_service;
        $this->team_service         = $team_service;
        $this->permission_service   = $permission_service;
        $this->link_service         = $link_service;
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

    public function get_fixture_with_details( int|Fixture|null $fixture_id, bool $is_tournament = false, ?int $rubber_player_id = null ): ?Fixture_Details_DTO {
        try {
            $fixture = $this->get_fixture_entity( $fixture_id );
            if ( ! $fixture ) {
                return null;
            }

            [ $league, $event, $competition ] = $this->get_competition_context( (int) $fixture->get_league_id() );

            if ( $event->get_num_rubbers() > 0 ) {
                $fixture->set_rubbers( $this->rubber_repository->find_by_fixture_id( (int) $fixture->get_id(), $rubber_player_id ) );
            }

            $home_team = $this->get_team_details_for_fixture( $fixture->get_home_team(), $league, $fixture->get_season() );
            $away_team = $this->get_team_details_for_fixture( $fixture->get_away_team(), $league, $fixture->get_season() );

            [ $prev_home_fixture_title, $prev_away_fixture_title ] = $this->resolve_tournament_placeholders( $fixture, $league, $is_tournament );

            $is_update_allowed = $this->permission_service->is_update_allowed( $fixture );

            $link          = $this->link_service->get_fixture_link( $fixture, $league, $home_team, $away_team );
            $score_display = $this->generate_score_display( $fixture, $event );
            $status_flags  = $this->generate_status_flags( $fixture );
            $fixture_title = $this->generate_fixture_title( $home_team, $away_team, $prev_home_fixture_title, $prev_away_fixture_title );

        } catch ( Fixture_Not_Found_Exception|League_Not_Found_Exception|Event_Not_Found_Exception|Competition_Not_Found_Exception $e ) {
            throw new Fixture_Not_Found_Exception( $e->getMessage() );
        }

        return new Fixture_Details_DTO( $fixture, $league, $event, $competition, $home_team, $away_team, $prev_home_fixture_title, $prev_away_fixture_title, $is_update_allowed, $link, $score_display, $status_flags, $fixture_title );
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

        if ( $dto && is_numeric( $team_id ) ) {
            $league_id = isset( $league->id ) ? (int) $league->id : ( method_exists( $league, 'get_id' ) ? (int) $league->get_id() : 0 );
            if ( $league_id > 0 ) {
                $status            = $this->league_repository->get_status( $league_id, $season, (int) $team_id );
                $dto->is_withdrawn = ( 'W' === $status );
            }
        }

        return $dto;
    }

    /**
     * Resolve tournament placeholder titles.
     */
    private function resolve_tournament_placeholders( Fixture $fixture, object $league, bool $is_tournament ): array {
        $prev_home_fixture_title = null;
        $prev_away_fixture_title = null;

        if ( $is_tournament ) {
            if ( ! is_numeric( $fixture->get_home_team() ) ) {
                $prev_home_fixture_title = $this->resolve_placeholder_title( $fixture->get_home_team(), $fixture->get_season(), $league, $fixture->get_final() );
            }

            if ( ! is_numeric( $fixture->get_away_team() ) ) {
                $prev_away_fixture_title = $this->resolve_placeholder_title( $fixture->get_away_team(), $fixture->get_season(), $league, $fixture->get_final() );
            }
        }

        return [ $prev_home_fixture_title, $prev_away_fixture_title ];
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
     * Build a standard placeholder title (Winner/Loser Round Fixture).
     */
    private function build_standard_placeholder_title( array $team ): string {
        $type = match ( $team[0] ) {
            '1' => __( 'Winner', 'racketmanager' ),
            '2' => __( 'Loser', 'racketmanager' ),
            default => null
        };
        $round_name = Util::get_final_name( $team[1] );
        $fixture_num  = $team[2] ?? '';

        /* translators: %1$s: type (Winner/Loser), %2$s: round name, %3$s: fixture number */
        return sprintf( __( '%1$s %2$s %3$s', 'racketmanager' ), $type, $round_name, $fixture_num );
    }

    /**
     * Resolve the placeholder title for a final round.
     */
    private function resolve_final_placeholder_title( array $team, string $season, object $league ): ?string {
        $leg           = $this->resolve_home_away_leg( $league, $season );
        $prev_fixtures = $this->fixture_repository->find_by_league_and_final( (int) $league->get_id(), $season, (string) $team[1], $leg );
        if ( ! $prev_fixtures ) {
            return $this->build_standard_placeholder_title( $team );
        }

        return $this->resolve_placeholder_from_previous_fixture( $team, $season, $league, $prev_fixtures );
    }

    /**
     * Resolve home/away second-leg indicator for a season.
     */
    private function resolve_home_away_leg( object $league, string $season ): ?int {
        if ( ! method_exists( $league, 'get_seasons' ) ) {
            return null;
        }
        $seasons = $league->get_seasons();
        if ( ! empty( $seasons[ $season ]['home_away'] ) ) {
            return 2;
        }

        return null;
    }

    /**
     * Resolve a placeholder title from a specific previous fixture.
     */
    private function resolve_placeholder_from_previous_fixture( array $team, string $season, object $league, array $prev_fixtures ): ?string {
        $fixture_ref  = (int) ( $team[2] ?? 1 ) - 1;
        $prev_fixture = $prev_fixtures[ $fixture_ref ] ?? null;

        if ( ! $prev_fixture ) {
            return null;
        }

        $home_name = $this->get_team_name_or_placeholder( $prev_fixture->get_home_team(), $season, $league, $prev_fixture->get_final() );
        $away_name = $this->get_team_name_or_placeholder( $prev_fixture->get_away_team(), $season, $league, $prev_fixture->get_final() );

        return sprintf( '%s - %s', $home_name, $away_name );
    }

    /**
     * Resolve previous fixture entity for a placeholder reference (leg-aware).
     *
     * @param Fixture $fixture
     * @param string|int|null $team_ref
     * @return Fixture|null
     */
    public function get_previous_fixture( Fixture $fixture, string|int|null $team_ref ): ?Fixture {
        if ( empty( $team_ref ) || is_numeric( $team_ref ) || ! str_contains( (string) $team_ref, '_' ) ) {
            return null;
        }

        $parts = explode( '_', (string) $team_ref );
        $final = $parts[1] ?? null;
        if ( empty( $final ) ) {
            return null;
        }

        $league_id = (int) $fixture->get_league_id();
        $season    = (string) $fixture->get_season();

        $leg = null;
        try {
            $league = $this->league_repository->find_by_id( $league_id );
            if ( $league ) {
                $leg = $this->resolve_home_away_leg( $league, $season );
            }
        } catch ( \Throwable ) {
            $leg = null;
        }

        try {
            $prev_fixtures = $this->fixture_repository->find_by_league_and_final( $league_id, $season, $final, $leg );
        } catch ( \Throwable ) {
            return null;
        }
        if ( empty( $prev_fixtures ) ) {
            return null;
        }

        $idx = (int) ( $parts[2] ?? 1 ) - 1;
        return $prev_fixtures[ $idx ] ?? null;
    }

    /**
     * Collect player IDs for a fixture, walking ancestor placeholder rounds.
     *
     * @param int|Fixture|Fixture_Details_DTO|null $fixture_id_or_entity
     * @return int[]
     */
    public function collect_fixture_player_ids( int|Fixture|Fixture_Details_DTO|null $fixture_id_or_entity ): array {
        $player_ids = array();
        $visited    = array();
        $this->collect_fixture_player_ids_recursive( $fixture_id_or_entity, $player_ids, $visited );

        return $player_ids;
    }

    /**
     * Recursively collect player IDs, guarding against cycles.
     *
     * @param mixed $fixture_id_or_entity
     * @param int[] $player_ids
     * @param array<int, bool> $visited
     */
    private function collect_fixture_player_ids_recursive( mixed $fixture_id_or_entity, array &$player_ids, array &$visited ): void {
        $details = $this->get_fixture_details_safe( $fixture_id_or_entity );
        if ( ! $details ) {
            return;
        }

        $fixture_entity = ( $details instanceof Fixture_Details_DTO ) ? $details->fixture : $details;
        if ( $fixture_entity instanceof Fixture ) {
            $fid = $fixture_entity->get_id();
            if ( $fid && isset( $visited[ $fid ] ) ) {
                return;
            }
            if ( $fid ) {
                $visited[ $fid ] = true;
            }
        }

        $this->append_team_player_ids( $details, $player_ids );

        if ( ! ( $fixture_entity instanceof Fixture ) ) {
            return;
        }

        foreach ( array( $fixture_entity->get_home_team(), $fixture_entity->get_away_team() ) as $team_ref ) {
            if ( empty( $team_ref ) || is_numeric( $team_ref ) ) {
                continue;
            }
            try {
                $prev_fixture = $this->get_previous_fixture( $fixture_entity, (string) $team_ref );
            } catch ( \Throwable ) {
                $prev_fixture = null;
            }
            if ( $prev_fixture ) {
                $this->collect_fixture_player_ids_recursive( $prev_fixture, $player_ids, $visited );
            }
        }
    }

    /**
     * Safely resolve fixture details, falling back to the entity or repository.
     */
    private function get_fixture_details_safe( mixed $fixture_id_or_entity ): Fixture_Details_DTO|Fixture|null {
        if ( ! $fixture_id_or_entity ) {
            return null;
        }
        if ( $fixture_id_or_entity instanceof Fixture_Details_DTO ) {
            return $fixture_id_or_entity;
        }

        try {
            $details = $this->get_fixture_with_details( $fixture_id_or_entity instanceof Fixture ? $fixture_id_or_entity : (int) $fixture_id_or_entity );
            if ( $details ) {
                return $details;
            }
        } catch ( \Throwable ) {
        }

        if ( $fixture_id_or_entity instanceof Fixture ) {
            return $fixture_id_or_entity;
        }

        try {
            return $this->fixture_repository->find_by_id( (int) $fixture_id_or_entity );
        } catch ( \Throwable ) {
            return null;
        }
    }

    /**
     * Append player IDs from home/away team details onto the collector.
     *
     * @param mixed $details
     * @param int[] $player_ids
     */
    private function append_team_player_ids( mixed $details, array &$player_ids ): void {
        $teams = array();
        if ( $details instanceof Fixture_Details_DTO ) {
            $teams = array( $details->home_team, $details->away_team );
        } elseif ( is_object( $details ) && isset( $details->home_team, $details->away_team ) && is_object( $details->home_team ) ) {
            $teams = array( $details->home_team, $details->away_team );
        } elseif ( is_object( $details ) && method_exists( $details, 'get_teams' ) ) {
            $teams_map = $details->get_teams();
            if ( is_array( $teams_map ) ) {
                $teams = array( $teams_map['home'] ?? null, $teams_map['away'] ?? null );
            }
        } elseif ( is_object( $details ) && isset( $details->teams ) && is_array( $details->teams ) ) {
            $teams = array( $details->teams['home'] ?? null, $details->teams['away'] ?? null );
        }

        foreach ( $teams as $team ) {
            if ( ! is_object( $team ) ) {
                continue;
            }

            $team_entity = isset( $team->team ) && is_object( $team->team ) ? $team->team : $team;
            $players     = isset( $team->players ) && is_iterable( $team->players ) ? $team->players : array();

            if ( empty( $players ) && $team_entity !== $team && isset( $team_entity->players ) && is_iterable( $team_entity->players ) ) {
                $players = $team_entity->players;
            }
            if ( empty( $players ) && isset( $team_entity->player_ids ) && is_iterable( $team_entity->player_ids ) ) {
                $players = $team_entity->player_ids;
            }
            if ( empty( $players ) && ! property_exists( $team_entity, 'players' ) && method_exists( $team_entity, 'get_players' ) ) {
                try {
                    $players = $team_entity->get_players();
                } catch ( \Throwable ) {
                    $players = array();
                }
            }

            foreach ( $players as $player ) {
                $player_id = is_object( $player ) ? ( $player->ID ?? $player->id ?? null ) : $player;
                if ( $player_id ) {
                    $player_ids[] = (int) $player_id;
                }
            }
        }
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
     * Generate fixture title for a fixture.
     */
    private function generate_fixture_title( ?object $home_team, ?object $away_team, ?string $prev_home_title, ?string $prev_away_title ): string {
        $home = $home_team ? $home_team->team->get_name() : ( $prev_home_title ?? __( 'Unknown', 'racketmanager' ) );
        $away = $away_team ? $away_team->team->get_name() : ( $prev_away_title ?? __( 'Unknown', 'racketmanager' ) );

        return $home . ' v ' . $away;
    }
}
