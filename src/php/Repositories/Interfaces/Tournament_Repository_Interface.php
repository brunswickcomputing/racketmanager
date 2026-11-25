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
    /**
     * Persists the given entity.
     *
     * @param object $entity The entity to save.
     *
     * @return bool|int Result of the save operation, typically a boolean status or an identifier/count.
     */
    public function save( object $entity ): bool|int;

    /**
     * Persists the given tournament together with its associated charge.
     *
     * @param Tournament                  $tournament The tournament to save.
     * @param Charge                      $charge The charge associated with the tournament.
     * @param Charge_Repository_Interface $charge_repository The repository used to persist the charge.
     *
     * @return array Result data from saving the tournament and charge.
     */
    public function save_with_charge( Tournament $tournament, Charge $charge, Charge_Repository_Interface $charge_repository ): array;

    /**
     * Finds a tournament by its identifier.
     *
     * @param int|string|null $id The tournament identifier.
     *
     * @return Tournament|null The matching tournament, or null if not found.
     */
    public function find_by_id( int|string|null $id ): ?Tournament;

    /**
     * Finds overview information for a tournament.
     *
     * @param int $tournament_id The tournament identifier.
     *
     * @return stdClass|null The tournament overview record, or null if not found.
     */
    public function find_tournament_overview( int $tournament_id ): ?stdClass;

    /**
     * Finds the active tournament, optionally filtered by age group.
     *
     * @param string|null $age_group The age group to filter by, or null to search across all age groups.
     *
     * @return Tournament|null The active tournament if found, or null otherwise.
     */
    public function find_active( ?string $age_group = null ): ?Tournament;

    /**
     * Finds records matching the specified criteria.
     *
     * @param array $criteria Criteria used to filter the records.
     *
     * @return array Records matching the given criteria.
     */
    public function find_by( array $criteria ): array;

    /**
     * Finds players from previous tournaments based on opt-in entry status.
     *
     * @param int  $tournament_id Tournament identifier used as the reference point.
     * @param int  $limit Maximum number of players to return.
     * @param bool $entered Whether to find players who have opted in or not.
     *
     * @return array Players from previous tournaments matching the opt-in criteria.
     */
    public function find_previous_tournament_players_with_optin( int $tournament_id, int $limit, bool $entered ): array;

    /**
     * Deletes the record with the specified identifier.
     *
     * @param int $id Identifier of the record to delete.
     *
     * @return bool True if the record was deleted successfully, false otherwise.
     */
    public function delete( int $id ): bool;

    /**
     * Finds events for the specified tournament with their related details.
     *
     * @param int $tournament_id Tournament identifier used to retrieve the events.
     *
     * @return array Events associated with the tournament, including available details.
     */
    public function find_events_by_tournament_with_details( int $tournament_id ): array;

    /**
     * Finds event details for a specific player in a given season.
     *
     * @param int $player_id Player identifier used to locate the event details.
     * @param int $event_id Event identifier used to select the relevant event.
     * @param int $season Season identifier used to scope the event details.
     *
     * @return stdClass|null Event details for the player, or null if no matching details are found.
     */
    public function find_event_details_for_player( int $player_id, int $event_id, int $season ): ?stdClass;

    /**
     * Finds event entry details for a player within a tournament.
     *
     * @param int $player_id The player identifier.
     * @param int $tournament_id The tournament identifier.
     *
     * @return array The event entry detail records for the specified player and tournament.
     */
    public function find_event_entry_details_for_player_in_tournament( int $player_id, int $tournament_id ): array;

    /**
     * Finds teams for a specific event within a tournament.
     *
     * @param int $tournament_id The tournament identifier.
     * @param int $event_id The event identifier.
     *
     * @return array The team records associated with the specified tournament and event.
     */
    public function find_teams_for_tournament_event( int $tournament_id, int $event_id ): array;

    /**
     * Finds a specific event within a tournament.
     *
     * @param int        $tournament_id The tournament identifier.
     * @param int|string $event_id The event identifier.
     *
     * @return Event|null The matching event, or null if no event is found.
     */
    public function find_event_for_tournament( int $tournament_id, int|string $event_id ): ?Event;

    /**
     * Finds finalists for a specific tournament.
     *
     * @param int $tournament_id The tournament identifier.
     *
     * @return array The finalist records for the specified tournament.
     */
    public function find_finalists_for_tournament( int $tournament_id ): array;

    /**
     * Finds winners for a specific tournament.
     *
     * @param int $tournament_id The tournament identifier.
     *
     * @return array The winner records for the specified tournament.
     */
    public function find_winners_for_tournament( int $tournament_id ): array;

    /**
     * Finds match dates for a specific tournament.
     *
     * @param int $tournament_id The tournament identifier.
     *
     * @return array The match dates associated with the specified tournament.
     */
    public function find_match_dates_for_tournament( int $tournament_id ): array;

    /**
     * Finds matches scheduled on a specific date within a tournament.
     *
     * @param int    $tournament_id The tournament identifier.
     * @param string $match_date The match date to search for.
     *
     * @return array The matching records for the specified tournament and date.
     */
    public function find_matches_by_date_for_tournament( int $tournament_id, string $match_date ): array;

    /**
     * Finds matches for a specific event within a tournament.
     *
     * @param int $tournament_id The tournament identifier.
     * @param int $event_id The event identifier.
     *
     * @return array The matching records for the specified tournament and event.
     */
    public function find_matches_by_event_for_tournament( int $tournament_id, int $event_id ): array;

    /**
     * Finds active players registered for the specified tournament.
     *
     * @param int $tournament_id The identifier of the tournament.
     *
     * @return array The list of active players associated with the tournament.
     */
    public function find_active_players_for_tournament( int $tournament_id ): array;

    /**
     * Finds leagues associated with events for the specified tournament.
     *
     * @param int $tournament_id The identifier of the tournament.
     *
     * @return array The leagues associated with events in the tournament.
     */
    public function find_leagues_by_event_for_tournament( int $tournament_id ): array;

    /**
     * Finds records associated with the given player.
     *
     * @param int $player_id The identifier of the player to search by.
     *
     * @return array The records associated with the specified player.
     */
    public function find_by_player( int $player_id ): array;
}
