<?php
/**
 * League_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Competition\League;

/**
 * Interface to implement the League repository
 */
interface League_Repository_Interface {
    public function save( League $league );
    public function find_by_id( $league_id ): ?League;
    public function find_next_sequence_number( string $event_name ): int;
    public function get_by_event_id( ?int $event_id, ?int $season = null ): array;
    public function get_lowest_league_id_by_event( int $event_id ): ?int;
    public function delete( int $id ): bool;
}
