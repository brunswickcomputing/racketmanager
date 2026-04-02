<?php
/**
 * Results_Checker_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Results_Checker;

/**
 * Interface to implement the Results Checker repository
 */
interface Results_Checker_Repository_Interface {
    public function save( Results_Checker $results_checker );
    public function find_by_id( int $id ): ?Results_Checker;
    public function find_by_fixture_id( int $fixture_id ): array;
    public function has_results_check( int $fixture_id ): bool;
    public function delete_by_fixture_id( int $fixture_id ): bool;
}
