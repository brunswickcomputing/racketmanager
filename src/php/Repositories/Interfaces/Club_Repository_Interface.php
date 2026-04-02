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
interface Club_Repository_Interface extends Repository_Interface {
    public function save( object $entity ): bool|int;
    public function find_by_id( null|int|string $id, string $search_term = 'id' ): ?Club;
    public function get_teams( array $args = array() ): array|int;
    public function find_all( array $args = array() ): array|int;
    public function delete( int $id ): bool;
    public function find_clubs_by_competition_and_season( int $competition_id, string $season, int $min_fixtures ): array;
    public function find_clubs_not_entered( int $competition_id, string $season ): array;
}
