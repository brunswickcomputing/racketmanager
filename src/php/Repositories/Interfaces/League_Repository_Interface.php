<?php
declare( strict_types=1 );
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
interface League_Repository_Interface extends Repository_Interface {
    /**
     * Save a league entity.
     *
     * @param object $entity The league entity to save.
     * @return bool|int The league ID on insert, true on update, or false on failure.
     */
    public function save( object $entity ): bool|int;

    /**
     * Find a league by its ID.
     *
     * @noinspection PhpMissingParamTypeInspection
     *
     * @param int|string|null $id The league ID.
     *
     * @return League|null The league object or null if not found.
     */
    public function find_by_id( $id ): ?League;

    /**
     * Find a league by its name or title.
     *
     * @param string $name The league title.
     * @return League|null The league object or null if not found.
     */
    public function find_by_name( string $name ): ?League;

    /**
     * Find the next sequence number for a league within an event.
     *
     * @param string $event_name The event name.
     * @return int The next sequence number.
     */
    public function find_next_sequence_number( string $event_name ): int;

    /**
     * Get leagues associated with an event.
     *
     * @param int|null $event_id The event ID.
     * @param int|null $season   Optional season identifier.
     * @return array Array of league database rows.
     */
    public function get_by_event_id( ?int $event_id, ?int $season = null ): array;

    /**
     * Find the lowest league ID (highest rank) for a given event.
     *
     * @param int $event_id The event ID.
     * @return int|null The lowest league ID or null if none found.
     */
    public function get_lowest_league_id_by_event( int $event_id ): ?int;

    /**
     * Delete a league by its ID.
     *
     * @param int $id The league ID.
     * @return bool True on success, false on failure.
     */
    public function delete( int $id ): bool;

    /**
     * Update the title and optional sequence for a league.
     *
     * @param int         $id       The league ID.
     * @param string      $title    The new title.
     * @param string|null $sequence Optional new sequence number.
     * @return bool True on success, false on failure.
     */
    public function update_title_and_sequence( int $id, string $title, ?string $sequence = null ): bool;

    /**
     * Delete all teams assigned to a league.
     *
     * @param int $league_id The league ID.
     * @return bool True on success, false on failure.
     */
    public function delete_league_teams( int $league_id ): bool;

    /**
     * Delete a team from a league for a specific season.
     *
     * @param int    $league_id The league ID.
     * @param int    $team_id   The team ID.
     * @param string $season    The season identifier.
     * @return bool True on success, false on failure.
     */
    public function delete_team( int $league_id, int $team_id, string $season ): bool;

    /**
     * Mark a team as withdrawn from a league for a specific season.
     *
     * @param int    $league_id The league ID.
     * @param int    $team_id   The team ID.
     * @param string $season    The season identifier.
     * @return bool True on success, false on failure.
     */
    public function withdraw_team( int $league_id, int $team_id, string $season ): bool;

    /**
     * Get the match day number for a league and season based on query type.
     *
     * @param int    $league_id The league ID.
     * @param string $season    The season identifier.
     * @param string $type      The match day query type ('last', 'next', 'current', 'latest').
     * @return int|null The match day number or null if none found.
     */
    public function get_match_day( int $league_id, string $season, string $type ): ?int;

    /**
     * Query teams assigned to a league with filters or return the count.
     *
     * @param int   $league_id The league ID.
     * @param array $args      Query filter arguments.
     * @param bool  $count     Whether to return a count instead of results.
     * @return array|int Array of team rows or total count if count is true.
     */
    public function query_league_teams( int $league_id, array $args, bool $count = false ): array|int;

    /**
     * Add a team to a league for a season.
     *
     * @param int         $league_id The league ID.
     * @param int         $team_id   The team ID.
     * @param string      $season    The season identifier.
     * @param string|null $rank      Optional ranking.
     * @param string|null $status    Optional status string.
     * @param int         $profile   Team profile type.
     * @return bool|int Insert ID on success, or false on failure/existing.
     */
    public function add_team_to_league( int $league_id, int $team_id, string $season, ?string $rank = null, ?string $status = null, int $profile = 1 ): bool|int;

    /**
     * Get team details and league standings information for a team in a season.
     *
     * @param int    $league_id The league ID.
     * @param int    $team_id   The team ID.
     * @param string $season    The season identifier.
     * @return object|null Team details object or null if not found.
     */
    public function get_team_details( int $league_id, int $team_id, string $season ): ?object;

    /**
     * Get the rank of a team in a league for a season.
     *
     * @param int    $league_id The league ID.
     * @param string $season    The season identifier.
     * @param int    $team_id   The team ID.
     * @return int|null The team rank or null if not found.
     */
    public function get_rank( int $league_id, string $season, int $team_id ): ?int;

    /**
     * Get the status of a team in a league for a season.
     *
     * @param int    $league_id The league ID.
     * @param string $season    The season identifier.
     * @param int    $team_id   The team ID.
     * @return string|null The team status or null if not found.
     */
    public function get_status( int $league_id, string $season, int $team_id ): ?string;

    /**
     * Update team standings manually with given points and match figures.
     *
     * @param int    $league_id The league ID.
     * @param string $season    The season identifier.
     * @param int    $team_id   The team ID.
     * @param array  $points    Array containing points_plus, points_minus, and add_points.
     * @param array  $matches   Array containing match breakdown counts.
     * @return bool True on success, false on failure.
     */
    public function update_standings_manually( int $league_id, string $season, int $team_id, array $points, array $matches ): bool;

    /**
     * Update team standings data for a team in a league.
     *
     * @param int    $league_id The league ID.
     * @param string $season    The season identifier.
     * @param int    $team_id   The team ID.
     * @param array  $data      Associative array of standings columns to update.
     * @return bool True on success, false on failure.
     */
    public function update_team_standings( int $league_id, string $season, int $team_id, array $data ): bool;

    /**
     * Query players that participated in league matches or return count.
     *
     * @param int   $league_id The league ID.
     * @param array $args      Query filter arguments.
     * @param bool  $count     Whether to return a count instead of results.
     * @return array|int Array of player records or total count if count is true.
     */
    public function query_players( int $league_id, array $args, bool $count = false ): array|int;

    /**
     * Get team information for a player in a league.
     *
     * @param int   $league_id The league ID.
     * @param array $args      Filter arguments including player ID and optional season.
     * @return object|null Team object or null if not found.
     */
    public function get_player_team( int $league_id, array $args ): ?object;

    /**
     * Update serialized seasons configuration for a league.
     *
     * @param int   $league_id The league ID.
     * @param array $seasons   Array of season data.
     * @return bool True on success, false on failure.
     */
    public function update_seasons( int $league_id, array $seasons ): bool;

    /**
     * Query matches in a league with filters or return the count.
     *
     * @param int   $league_id   The league ID.
     * @param array $query_args  Query filter arguments.
     * @param bool  $count       Whether to return a count instead of results.
     * @return array|int Array of match records or total count if count is true.
     */
    public function query_matches( int $league_id, array $query_args, bool $count = false ): array|int;

    /**
     * Retrieve league names projected as id and name.
     *
     * @return array Array of objects containing id and name.
     */
    public function find_names(): array;

    /**
     * Get leagues matching criteria.
     *
     * @param array $args Query arguments for filtering and pagination.
     * @return League[] Array of League objects.
     */
    public function find_all( array $args = array() ): array;

    /**
     * Get league standings summary rows.
     *
     * @param array $args Filter arguments (season, team, age_group).
     * @return array Array of standings summary rows.
     */
    public function get_league_standings( array $args = array() ): array;
}
