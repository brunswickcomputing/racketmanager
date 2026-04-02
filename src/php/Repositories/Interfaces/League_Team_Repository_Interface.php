<?php
/**
 * League_Team_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Competition\League_Team;
use stdClass;

/**
 * Interface to implement the League Team repository
 */
interface League_Team_Repository_Interface {
    public function save( League_Team $league_team );
    public function delete( int $id ): bool;
    public function find_by_id( $league_team_id ): ?League_Team;
    public function find_club_ids_where_player_is_captain( int $player_id, ?int $club_id = null ): array;
    public function find_by_event_id( ?int $event_id, ?int $season = null, ?int $team_id = null, ?int $club_id = null ): array;
    public function get_clubs_by_event_id( ?int $event_id, ?int $season = null ): array;
    public function get_clubs_by_competition_id( ?int $competition_id, ?int $season = null ): array;
    public function find_teams_to_withdraw_from_league( int $club_id, string $season, int $event_id, array $team_ids ): array;
    public function player_already_entered_league( int $player_id, int $league_id, int $season ): ?stdClass;
    public function find_player_teams_by_player_for_events( int $player_id, array $event_ids, int $season ): array;
    public function find_league_standings( int $league_id, int $season ): array;
    public function get_teams_by_league_and_season( int $league_id, int $season ): array;
    public function find_by_team_league_and_season( int $team_id, int $league_id, int $season ): ?League_Team;
}
