<?php
declare( strict_types=1 );
/**
 * Tournament_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Finance\Charge;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Domain\Competition\Event;
use stdClass;

/**
 * Interface to implement the Tournament repository
 */
interface Tournament_Repository_Interface extends Repository_Interface {
    public function save( object $entity ): bool|int;
    /**
     * Save a tournament and its charge inside one transaction.
     *
     * @return array{tournament: bool|int, charge: bool|int|null} A `false` value means that save failed and the transaction was rolled back.
     */
    public function save_with_charge( Tournament $tournament, Charge $charge, Charge_Repository_Interface $charge_repository ): array;
    public function find_by_id( int|string|null $id ): ?Tournament;
    public function find_tournament_overview( int $tournament_id ): ?stdClass;
    public function find_active( ?string $age_group = null ): ?Tournament;
    public function find_by( array $criteria ): array;
    public function find_previous_tournament_players_with_optin( int $tournament_id, int $limit, bool $entered ): array;
    public function delete( int $id ): bool;
    public function find_events_by_tournament_with_details( int $tournament_id ): array;
    public function find_event_details_for_player( int $player_id, int $event_id, int $season ): ?stdClass;
    public function find_event_entry_details_for_player_in_tournament( int $player_id, int $tournament_id ): array;
    public function find_teams_for_tournament_event( int $tournament_id, int $event_id ): array;
    public function find_event_for_tournament( int $tournament_id, int|string $event_id ): ?Event;
    public function find_finalists_for_tournament( int $tournament_id ): array;
    public function find_winners_for_tournament( int $tournament_id ): array;
    public function find_match_dates_for_tournament( int $tournament_id ): array;
    public function find_matches_by_date_for_tournament( int $tournament_id, string $match_date ): array;
    public function find_matches_by_event_for_tournament( int $tournament_id, int $event_id ): array;
    public function find_active_players_for_tournament( int $tournament_id ): array;
    public function find_leagues_by_event_for_tournament( int $tournament_id ): array;
    public function find_by_player( int $player_id ): array;
}
