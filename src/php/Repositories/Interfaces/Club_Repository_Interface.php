<?php
/**
 * Club_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Club;

/**
 * Interface to implement the Club repository
 */
interface Club_Repository_Interface {
    public function save( Club $club );
    public function find( null|int|string $id, string $search_term = 'id' ): ?Club;
    public function get_teams( array $args = array() ): array|int;
    public function find_all( array $args = array() ): array|int;
    public function delete( int $club_id ): bool;
    public function find_clubs_by_competition_and_season( int $competition_id, string $season, int $min_fixtures ): array;
    public function find_clubs_not_entered( int $competition_id, string $season ): array;
}
