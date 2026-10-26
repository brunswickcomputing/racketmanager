<?php
declare( strict_types=1 );
/**
 * Event_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Competition\Event;

/**
 * Interface to implement the Event repository
 */
interface Event_Repository_Interface extends Repository_Interface {
    public function save( object $entity ): bool|int;
    public function find_by_id( $id ): ?Event;
    public function find_by_name( string $name ): ?Event;
    public function find_by_competition_id( int $competition_id ): array;
    public function find_all( array $args = [] ): array;
    public function delete( int $id ): bool;
    public function find_events_by_competition_with_counts( int $competition_id, int $season, ?int $min_fixtures = 1): array;

    public function insert_event( object $event ): int;
    public function update_name( int $id, string $name ): bool;
    public function update_settings_and_rules( int $id, array $settings, ?int $num_rubbers, ?int $num_sets, string $type ): bool;
    public function count_leagues( int $event_id ): int;
    public function query_leagues( int $event_id, array $args = [] ): array|int;
    public function get_player_stats( int $event_id, array $args ): array;
    public function get_teams_info( int $event_id, ?string $season, array $args = [] ): array;
    public function get_team_info( int $event_id, int $team_id, ?string $season ): ?object;
    public function get_settings( int $id ): array;
    public function query_constitution( int $event_id, array $args = [] ): array|int;
    public function build_constitution( int $event_id, array $args = [] ): array;
    public function query_clubs( int $event_id, array $args = [] ): array|int;
    public function query_teams( int $event_id, array $args = [] ): array|int;
    public function query_players( int $event_id, bool $is_player_entry, array $args = [] ): array|int;
    public function mark_teams_withdrawn( int $event_id, string $season, int $club, false|int $team = false ): bool;
    public function mark_teams_entered( int $event_id, int $team, string $season ): bool;
    public function query_matches( int $event_id, array $match_args = [] ): array|int;
    public function count_by_event_season( int $event_id, string $season, bool $completed_only = false ): int;
    public function update_seasons( int $id, array $seasons ): bool;
    public function delete_league_teams_for_season( int $league_id, string $season ): bool;
}
