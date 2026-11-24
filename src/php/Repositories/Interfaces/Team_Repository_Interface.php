<?php
declare( strict_types=1 );
/**
 * Team_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\DTO\Team\Team_Fixture_Settings_DTO;

/**
 * Interface to implement the Team repository
 */
interface Team_Repository_Interface extends Repository_Interface {
    /**
     * Finds a team by its identifier.
     *
     * @param int|string|null $id The team identifier.
     *
     * @return Team|null The matching team, or null if not found.
     */
    public function find_by_id( int|string|null $id ): ?Team;

    /**
     * Finds the identifier associated with the specified title.
     *
     * @param string $title The title to search for.
     *
     * @return int The matching identifier, or 0 if no matching title is found.
     */
    public function find_id_by_title( string $title ): int;

    /**
     * Finds the identifier associated with the given name.
     *
     * @param string $name The name to search for.
     *
     * @return int|null The matching identifier, or null if no match is found.
     */
    public function find_id_by_name( string $name ): ?int;

    /**
     * Finds records associated with a club, optionally filtered by type.
     *
     * @param int         $club_id The club identifier.
     * @param string|null $type The optional type filter, or null to include all types.
     *
     * @return array The records associated with the club.
     */
    public function find_by_club( int $club_id, ?string $type = null ): array;

    /**
     * Finds records available for players by the specified type.
     *
     * @param string $type The type of records to find for players.
     *
     * @return array The matching records for players.
     */
    public function find_for_players( string $type ): array;

    /**
     * Saves the specified entity.
     *
     * @param object $entity The entity to save.
     *
     * @return bool|int The saved entity identifier, true if the entity was saved successfully, or false on failure.
     */
    public function save( object $entity ): bool|int;

    /**
     * Determines whether a club has any teams associated with it.
     *
     * @param int $club_id The club identifier.
     *
     * @return bool True if the club has one or more teams, false otherwise.
     */
    public function has_teams( int $club_id ): bool;

    /**
     * Finds the next available sequence number for the specified club and type.
     *
     * @param string $club_shortcode The shortcode identifying the club.
     * @param string $type The sequence type to search for.
     *
     * @return int The next available sequence number.
     */
    public function find_next_sequence_number( string $club_shortcode, string $type ): int;

    /**
     * Determines whether the specified player is a captain for the given club.
     *
     * @param int $club_id The club identifier.
     * @param int $player The player identifier.
     *
     * @return bool True if the player is a captain for the club, false otherwise.
     */
    public function find_captain( int $club_id, int $player ): bool;

    /**
     * Finds teams in a competition for a given season, including related details, filtered by a minimum number of fixtures.
     *
     * @param int $competition_id The competition identifier.
     * @param int $season The season to search within.
     * @param int $min_fixtures The minimum number of fixtures required for a team to be included.
     *
     * @return array The matching teams with their associated details.
     */
    public function find_teams_by_competition_with_details( int $competition_id, int $season, int $min_fixtures ): array;

    /**
     * Finds the fixture settings for a team within a specific event.
     *
     * @param int $team_id The team identifier.
     * @param int $event_id The event identifier.
     *
     * @return Team_Fixture_Settings_DTO|null The team fixture settings for the event, or null if none are found.
     */
    public function find_team_settings_for_event( int $team_id, int $event_id ): ?Team_Fixture_Settings_DTO;

    /**
     * Finds a team associated with the specified players.
     *
     * @param array $player_ids The player identifiers used to locate the team.
     *
     * @return int|null The team identifier if a matching team is found, or null otherwise.
     */
    public function find_team_by_players( array $player_ids ): ?int;

    /**
     * Saves the players assigned to a team.
     *
     * @param int   $team_id The team identifier.
     * @param array $player_ids The identifiers of the players to assign to the team.
     *
     * @return bool True if the team players were saved successfully, false otherwise.
     */
    public function save_team_players( int $team_id, array $player_ids ): bool;

    /**
     * Saves a team together with its associated players.
     *
     * @param Team  $team The team to save.
     * @param array $player_ids The identifiers of the players to associate with the team.
     *
     * @return int|false The saved team identifier on success, or false on failure.
     */
    public function save_with_players( Team $team, array $player_ids ): int|false;

    /**
     * Deletes a team and its associated fixtures using the provided fixture repository.
     *
     * @param int                          $team_id The team identifier.
     * @param Fixture_Repository_Interface $fixture_repository The fixture repository used to delete associated fixtures.
     *
     * @return bool True if the team and its fixtures were deleted successfully, false otherwise.
     */
    public function delete_with_fixtures( int $team_id, Fixture_Repository_Interface $fixture_repository ): bool;

    /**
     * Updates the settings for a team event, including season, captain, match day, and optional match time.
     *
     * @param int         $team_id The team identifier.
     * @param int         $event_id The event identifier.
     * @param string      $season The season associated with the event.
     * @param string      $captain The captain assigned to the event.
     * @param string      $match_day The scheduled match day.
     * @param string|null $match_time The scheduled match time, or null if not set.
     *
     * @return bool True if the event settings were updated successfully, false otherwise.
     */
    public function update_event_settings( int $team_id, int $event_id, string $season, string $captain, string $match_day, ?string $match_time ): bool;

    /**
     * Finds all items that match the specified query arguments.
     *
     * @param array $args Optional query arguments used to filter, sort, or limit the results.
     *
     * @return array|int Matching items as an array, or an integer result when requested by the query arguments.
     */
    public function find_all( array $args = array() ): array|int;
}
