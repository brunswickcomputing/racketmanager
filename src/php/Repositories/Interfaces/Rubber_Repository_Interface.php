<?php
/**
 * Rubber_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Fixture\Rubber;

/**
 * Interface to implement the Rubber repository
 */
interface Rubber_Repository_Interface {
    public function save( Rubber $rubber );
    public function find_by_id( $rubber_id ): ?Rubber;
    public function find_by_fixture_id( int $fixture_id, ?int $player_id = null ): array;
    public function count_by_fixture_id( int $fixture_id, ?int $player_id = null ): int;
    public function delete_by_fixture_id( int $fixture_id ): bool;
}
