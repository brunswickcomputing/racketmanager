<?php
/**
 * Team_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Team;
use Racketmanager\Domain\DTO\Team\Team_Fixture_Settings_DTO;

/**
 * Interface to implement the Team repository
 */
interface Team_Repository_Interface extends Repository_Interface {
    public function find_by_id( int|string|null $id ): ?Team;
    public function find_by_club( int $club_id, ?string $type = null ): array;
    public function find_for_players( string $type ): array;
    public function save( object $entity ): bool|int;
    public function has_teams( int $club_id ): bool;
    public function find_next_sequence_number( string $club_shortcode, string $type ): int;
    public function find_captain( int $club_id, int $player ): bool;
    public function find_teams_by_competition_with_details( int $competition_id, int $season, int $min_fixtures ): array;
    public function find_team_settings_for_event( int $teamId, int $eventId ): ?Team_Fixture_Settings_DTO;
    public function find_team_by_players( array $player_ids ): ?int;
    public function save_team_players( int $team_id, array $player_ids ): bool;
}
