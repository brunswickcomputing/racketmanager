<?php
/**
 * Competition_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\DTO\Competition\Competition_Overview_DTO;

/**
 * Interface to implement the Competition repository
 */
interface Competition_Repository_Interface extends Repository_Interface {
    public function save( object $entity ): bool|int;
    public function find_by_id( int|string|null $id ): ?Competition;
    public function find_all(): array;
    public function find_by( array $criteria ): array;
    public function find_competitions_with_summary( $age_group, $type ): array;
    public function delete( int $id ): bool;
    public function get_competition_overview( int $competition_id, int $season, ?int $min_fixtures = null ): ?Competition_Overview_DTO;
    public function is_club_participating( int $competition_id, int $club_id, string $season ): bool;
    public function get_league_winners( int $competition_id, ?int $season = null ): array;
    public function get_championship_winners( int $competition_id, ?int $season = null ): array;
}
