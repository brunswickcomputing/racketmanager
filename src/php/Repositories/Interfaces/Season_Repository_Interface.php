<?php
declare( strict_types=1 );
/**
 * Season_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Season\Season;

/**
 * Interface to implement the Season repository
 */
interface Season_Repository_Interface extends Repository_Interface {
    public function save( object $entity ): bool|int;
    public function find_by_id( int|string|null $id, string $type = 'id' ): ?Season;
    public function find_all(): array;
    public function delete( int $id ): bool;
}
