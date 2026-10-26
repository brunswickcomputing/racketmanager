<?php
declare( strict_types=1 );
/**
 * League_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Competition\League;

/**
 * Interface to implement the League repository
 */
interface League_Repository_Interface extends Repository_Interface {
    public function save( object $entity ): bool|int;
    public function find_by_id( $id ): ?League;
    public function find_by_name( string $name ): ?League;
    public function find_next_sequence_number( string $event_name ): int;
    public function get_by_event_id( ?int $event_id, ?int $season = null ): array;
    public function get_lowest_league_id_by_event( int $event_id ): ?int;
    public function delete( int $id ): bool;
    public function update_title_and_sequence( int $id, string $title, ?string $sequence = null ): bool;
    public function delete_league_teams( int $league_id ): bool;
    public function delete_team( int $league_id, int $team_id, string $season ): bool;
    public function withdraw_team( int $league_id, int $team_id, string $season ): bool;
    public function get_match_day( int $league_id, string $season, string $type ): ?int;
    public function query_league_teams( int $league_id, array $args, bool $count = false ): array|int;
    public function add_team_to_league( int $league_id, int $team_id, string $season, ?string $rank = null, ?string $status = null, int $profile = 1 ): bool|int;
    public function get_team_details( int $league_id, int $team_id, string $season ): ?object;
    public function get_rank( int $league_id, string $season, int $team_id ): ?int;
    public function get_status( int $league_id, string $season, int $team_id ): ?string;
    public function update_standings_manually( int $league_id, string $season, int $team_id, array $points, array $matches ): bool;
    public function update_team_standings( int $league_id, string $season, int $team_id, array $data ): bool;
    public function query_players( int $league_id, array $args, bool $count = false ): array|int;
    public function get_player_team( int $league_id, array $args ): ?object;
    public function update_seasons( int $league_id, array $seasons ): bool;
    public function query_matches( int $league_id, array $query_args, bool $count = false ): array|int;
    public function find_all( array $args = array() ): array;
    public function get_league_standings( array $args = array() ): array;
}
