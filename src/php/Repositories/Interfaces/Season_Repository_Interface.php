<?php
/**
 * Season_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Season;

/**
 * Interface to implement the Season repository
 */
interface Season_Repository_Interface {
    public function save( Season $season );
    public function find_by_id( null|int|string $season_id, string $type = 'id' ): ?Season;
    public function find_all(): array;
    public function delete( int $id ): bool;
}
