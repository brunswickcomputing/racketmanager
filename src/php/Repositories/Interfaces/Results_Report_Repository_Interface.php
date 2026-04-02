<?php
/**
 * Results_Report_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Results_Report;

/**
 * Interface to implement the Results Report repository
 */
interface Results_Report_Repository_Interface extends Repository_Interface {
    public function save( object $entity ): bool|int;
    public function find_by_id( int|string|null $id ): ?Results_Report;
    public function find_by_fixture_id( int $fixture_id ): ?Results_Report;
    public function delete_by_fixture_id( int $fixture_id ): bool;
}
