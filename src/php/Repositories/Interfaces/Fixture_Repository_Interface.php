<?php
/**
 * Fixture_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Fixture\Fixture;

/**
 * Interface to implement the Fixture repository
 */
interface Fixture_Repository_Interface {
    public function save( Fixture $fixture );
    public function delete( int $id ): bool;
    public function find_by_id( $fixture_id ): ?Fixture;
    public function find_raw_by_id( int $fixture_id, bool $legacy = false ): ?object;
    public function find_finals_fixtures_for_tournament( int $tournament_id ): array;
    public function find_fixtures_for_player_by_tournament( int $player_id, int $tournament_id ): array;
    public function delete_by_league_and_season( int $league_id, string $season ): bool;
    public function find_by_league_and_final( int $league_id, string $season, string $final_key, ?int $leg = null ): array;
    public function find_by_league_and_team( int $league_id, string $season, string $team_id ): array;
    public function count_player_matches_on_same_day( string $season, int $match_day, int $league_id, int $club_player_id ): int;
}
