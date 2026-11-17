<?php
declare( strict_types=1 );
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
    /**
     * Persists the given entity.
     *
     * @param object $entity The entity to save.
     *
     * @return bool|int The save result, typically a boolean status or generated identifier.
     */
    public function save( object $entity ): bool|int;

    /**
     * Finds an event by its identifier.
     *
     * @param mixed $id The event identifier.
     *
     * @return Event|null The matching event, or null if no event is found.
     */
    public function find_by_id( $id ): ?Event;

    /**
     * Finds an event by its name.
     *
     * @param string $name The name of the event to find.
     *
     * @return Event|null The matching event, or null if no event is found.
     */
    public function find_by_name( string $name ): ?Event;

    /**
     * Finds records associated with the given competition identifier.
     *
     * @param int $competition_id The competition identifier to search by.
     *
     * @return array The matching records for the specified competition.
     */
    public function find_by_competition_id( int $competition_id ): array;

    /**
     * Finds all records matching the provided arguments.
     *
     * @param array $args Optional query arguments used to filter, sort, or limit the results.
     *
     * @return array List of matching records.
     */
    public function find_all( array $args = [] ): array;

    /**
     * Deletes the record with the specified identifier.
     *
     * @param int $id Identifier of the record to delete.
     *
     * @return bool True if the record was deleted successfully, false otherwise.
     */
    public function delete( int $id ): bool;

    /**
     * Finds events for a competition and season, including related fixture counts.
     *
     * @param int      $competition_id Competition identifier used to filter events.
     * @param int      $season Season identifier or year used to filter events.
     * @param int|null $min_fixtures Minimum number of fixtures required for an event to be included.
     *
     * @return array List of matching events with their fixture counts.
     */
    public function find_events_by_competition_with_counts( int $competition_id, int $season, ?int $min_fixtures = 1 ): array;

    /**
     * Inserts the specified event into storage.
     *
     * @param object $event Event data to insert.
     *
     * @return int Identifier of the inserted event.
     */
    public function insert_event( object $event ): int;

    /**
     * Updates the name associated with the specified identifier.
     *
     * @param int    $id Identifier of the record to update.
     * @param string $name New name to assign.
     *
     * @return bool True if the name was updated successfully, false otherwise.
     */
    public function update_name( int $id, string $name ): bool;

    /**
     * Updates the settings and rule configuration for the specified record.
     *
     * @param int      $id Identifier of the record to update.
     * @param array    $settings Settings data to apply.
     * @param int|null $num_rubbers Optional number of rubbers to set.
     * @param int|null $num_sets Optional number of sets to set.
     * @param string   $type Type of configuration being updated.
     *
     * @return bool True if the settings and rules were updated successfully, false otherwise.
     */
    public function update_settings_and_rules( int $id, array $settings, ?int $num_rubbers, ?int $num_sets, string $type ): bool;

    /**
     * Counts the number of leagues associated with the specified event.
     *
     * @param int $event_id Event identifier used to count associated leagues.
     *
     * @return int Number of leagues associated with the event.
     */
    public function count_leagues( int $event_id ): int;

    /**
     * Queries leagues associated with the specified event.
     *
     * @param int   $event_id The event identifier used to retrieve related leagues.
     * @param array $args Optional query arguments used to filter, sort, limit, or modify the result.
     *
     * @return array|int List of matching leagues, or an integer result when requested by the query arguments.
     */
    public function query_leagues( int $event_id, array $args = [] ): array|int;

    /**
     * Retrieves player statistics for a specific event using the provided query arguments.
     *
     * @param int   $event_id Event identifier to retrieve player statistics for.
     * @param array $args Arguments used to filter, sort, or limit the requested player statistics.
     *
     * @return array Player statistics matching the specified event and arguments.
     */
    public function get_player_stats( int $event_id, array $args ): array;

    /**
     * Retrieves team information for a specific event and optional season.
     *
     * @param int         $event_id Event identifier to retrieve team information for.
     * @param string|null $season Season identifier or name to filter teams by, or null to include all seasons.
     * @param array       $args Additional query arguments or filters to apply.
     *
     * @return array Team information matching the specified criteria.
     */
    public function get_teams_info( int $event_id, ?string $season, array $args = [] ): array;

    /**
     * Retrieves information for a specific team within an event and optional season.
     *
     * @param int         $event_id Event identifier to retrieve team information for.
     * @param int         $team_id Team identifier to retrieve information for.
     * @param string|null $season Season identifier or name to filter the team information by, or null to omit season filtering.
     *
     * @return object|null Team information object if found, or null if no matching team information exists.
     */
    public function get_team_info( int $event_id, int $team_id, ?string $season ): ?object;

    /**
     * Retrieves settings for the specified identifier.
     *
     * @param int $id Identifier of the settings to retrieve.
     *
     * @return array Settings data associated with the specified identifier.
     */
    public function get_settings( int $id ): array;

    /**
     * Queries constitution records for a specific event.
     *
     * @param int   $event_id Event identifier to query constitution records for.
     * @param array $args Optional query arguments used to filter, sort, limit, or count results.
     *
     * @return array|int Matching constitution records, or an integer result when requested by the query arguments.
     */
    public function query_constitution( int $event_id, array $args = [] ): array|int;

    /**
     * Queries constitutions for a specific event using optional filter arguments.
     *
     * @param int   $event_id Event identifier to query constitutions for.
     * @param array $args Optional query arguments used to filter, sort, or limit the results.
     *
     * @return array|int Matching constitution records, or an integer result such as a count depending on the query arguments.
     */
    public function query_constitutions( int $event_id, array $args = [] ): array|int;

    /**
     * Builds the constitution data for a specific event.
     *
     * @param int   $event_id Event identifier to build constitution data for.
     * @param array $args Optional arguments used to customize the constitution build process.
     *
     * @return array Built constitution data.
     */
    public function build_constitution( int $event_id, array $args = [] ): array;

    /**
     * Queries clubs associated with a specific event using optional query arguments.
     *
     * @param int   $event_id Event identifier to query clubs for.
     * @param array $args Optional query arguments used to filter, sort, paginate, or change the returned result.
     *
     * @return array|int Matching club records, or an integer result such as a count when requested by the query arguments.
     */
    public function query_clubs( int $event_id, array $args = [] ): array|int;

    /**
     * Queries teams for a specific event using optional filter, sorting, or pagination arguments.
     *
     * @param int   $event_id Event identifier to query teams for.
     * @param array $args Optional query arguments used to filter, sort, or limit the returned teams.
     *
     * @return array|int Matching team records, or an integer result such as a count when requested by the query arguments.
     */
    public function query_teams( int $event_id, array $args = [] ): array|int;

    /**
     * Queries players for a specific event.
     *
     * @param int   $event_id Event identifier to query players for.
     * @param bool  $is_player_entry Whether to query player entries.
     * @param array $args Optional query arguments used to filter, sort, or limit results.
     *
     * @return array|int Matching player records, or an integer result such as a count.
     */
    public function query_players( int $event_id, bool $is_player_entry, array $args = [] ): array|int;

    /**
     * Marks one or more teams from a club as withdrawn for a specific event and season.
     *
     * @param int       $event_id Event identifier for which teams should be marked as withdrawn.
     * @param string    $season Season identifier or name to filter the affected teams by.
     * @param int       $club Club identifier whose team records should be updated.
     * @param false|int $team Specific team identifier to mark as withdrawn, or false to mark all matching club teams.
     *
     * @return bool True if the withdrawal update succeeds, false otherwise.
     */
    public function mark_teams_withdrawn( int $event_id, string $season, int $club, false|int $team = false ): bool;

    /**
     * Marks a team as entered for a specific event and season.
     *
     * @param int    $event_id Event identifier for which the team should be marked as entered.
     * @param int    $team Team identifier to mark as entered.
     * @param string $season Season identifier or name associated with the entry.
     *
     * @return bool True if the team was marked as entered successfully, false otherwise.
     */
    public function mark_teams_entered( int $event_id, int $team, string $season ): bool;

    /**
     * Queries matches for a specific event using optional query arguments.
     *
     * @param int   $event_id Event identifier to query matches for.
     * @param array $match_args Optional arguments used to filter, sort, or limit the match query.
     *
     * @return array|int Matching records, or an integer result when requested by the query arguments.
     */
    public function query_matches( int $event_id, array $match_args = [] ): array|int;

    /**
     * Counts records for a specific event and season.
     *
     * @param int    $event_id Event identifier to count records for.
     * @param string $season Season identifier or name to filter records by.
     * @param bool   $completed_only Whether to include only completed records.
     *
     * @return int Number of matching records.
     */
    public function count_by_event_season( int $event_id, string $season, bool $completed_only = false ): int;

    /**
     * Updates the seasons associated with the specified record.
     *
     * @param int   $id Identifier of the record to update.
     * @param array $seasons Seasons data to apply to the record.
     *
     * @return bool True if the seasons were updated successfully, false otherwise.
     */
    public function update_seasons( int $id, array $seasons ): bool;

    /**
     * Deletes all team associations for a league within the specified season.
     *
     * @param int    $league_id Unique identifier of the league whose season teams should be deleted.
     * @param string $season Season identifier for which league team associations should be removed.
     *
     * @return bool True if the deletion succeeds, false otherwise.
     */
    public function delete_league_teams_for_season( int $league_id, string $season ): bool;
}
