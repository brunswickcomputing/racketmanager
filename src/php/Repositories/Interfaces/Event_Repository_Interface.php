<?php
/**
 * Event_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Competition\Event;

/**
 * Interface to implement the Event repository
 */
interface Event_Repository_Interface extends Repository_Interface {
    public function save( object $entity ): bool|int;
    public function find_by_id( $id ): ?Event;
    public function find_by_competition_id( int $competition_id ): array;
    public function delete( int $id ): bool;
    public function find_events_by_competition_with_counts( int $competition_id, int $season, ?int $min_fixtures = 1): array;
}
