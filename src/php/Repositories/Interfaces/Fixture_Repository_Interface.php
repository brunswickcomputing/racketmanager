<?php
declare( strict_types=1 );
/**
 * Fixture_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Application\Export\DTO\Export_Criteria;
use Racketmanager\Domain\Fixture\Fixture;

/**
 * Interface to implement the Fixture repository
 */
interface Fixture_Repository_Interface extends Repository_Interface {
    /**
     * Save a fixture entity.
     *
     * @param object $entity The fixture entity to save.
     * @return bool|int The fixture ID on insert, true on update, or false on failure.
     */
    public function save( object $entity ): bool|int;

    /**
     * Delete a fixture by ID, including its associated rubbers.
     *
     * @param int $id The fixture ID.
     * @return bool True on success, false on failure.
     */
    public function delete( int $id ): bool;

    /**
     * Delete a fixture by ID (alias of delete).
     *
     * @param int $id The fixture ID.
     * @return bool True on success, false on failure.
     */
    public function delete_fixture( int $id ): bool;

    /**
     * Find a fixture by its ID.
     *
     * @noinspection PhpMissingParamTypeInspection
     *
     * @param int|string|null $id The fixture ID.
     *
     * @return Fixture|null The fixture object or null if not found.
     */
    public function find_by_id( $id ): ?Fixture;

    /**
     * Find a fixture by ID and attach league and team relations.
     *
     * @param int $id The fixture ID.
     * @return Fixture|null The fixture object with relations, or null if not found.
     */
    public function find_by_id_with_relations( int $id ): ?Fixture;

    /**
     * Find raw fixture row by ID.
     *
     * @param int  $fixture_id The fixture ID.
     * @param bool $legacy     Whether to include legacy date formatting fields.
     * @return object|null The raw fixture database object, or null if not found.
     */
    public function find_raw_by_id( int $fixture_id, bool $legacy = false ): ?object;

    /**
     * Find finals fixtures for a tournament.
     *
     * @param int $tournament_id The tournament ID.
     * @return Fixture[] Array of fixture objects.
     */
    public function find_finals_fixtures_for_tournament( int $tournament_id ): array;

    /**
     * Find fixtures for a player in a tournament.
     *
     * @param int $player_id     The player ID.
     * @param int $tournament_id The tournament ID.
     * @return Fixture[] Array of fixture objects.
     */
    public function find_fixtures_for_player_by_tournament( int $player_id, int $tournament_id ): array;

    /**
     * Delete fixtures by league and season.
     *
     * @param int    $league_id The league ID.
     * @param string $season    The season identifier.
     * @return bool True on success, false on failure.
     */
    public function delete_by_league_and_season( int $league_id, string $season ): bool;

    /**
     * Find fixtures by team ID.
     *
     * @param int $team_id The team ID.
     * @return Fixture[] Array of fixture objects.
     */
    public function find_by_team( int $team_id ): array;

    /**
     * Delete all fixtures and their rubbers for a given team ID.
     *
     * @param int $team_id The team ID.
     * @return bool True on success, false on failure.
     */
    public function delete_by_team( int $team_id ): bool;

    /**
     * Find fixtures by league, season, and final key.
     *
     * @param int      $league_id  The league ID.
     * @param string   $season     The season identifier.
     * @param string   $final_key  The final round key.
     * @param int|null $leg        Optional leg number.
     * @return Fixture[] Array of fixture objects.
     */
    public function find_by_league_and_final( int $league_id, string $season, string $final_key, ?int $leg = null ): array;

    /**
     * Find fixtures by league, season, and team ID.
     *
     * @param int    $league_id The league ID.
     * @param string $season    The season identifier.
     * @param string $team_id   The team ID.
     * @return Fixture[] Array of fixture objects.
     */
    public function find_by_league_and_team( int $league_id, string $season, string $team_id ): array;

    /**
     * Count how many other matches a player has played on the same match day.
     *
     * @param string $season         The season identifier.
     * @param int    $match_day      The match day number.
     * @param int    $league_id      The league ID to exclude.
     * @param int    $club_player_id The club player ID.
     * @return int The count of matches played on the same day.
     */
    public function count_player_matches_on_same_day( string $season, int $match_day, int $league_id, int $club_player_id ): int;

    /**
     * Update fixture status.
     *
     * @param int $id     The fixture ID.
     * @param int $status The new status value.
     * @return bool True on success, false on failure.
     */
    public function update_status( int $id, int $status ): bool;

    /**
     * Update home and away team identifiers for a fixture.
     *
     * @param int    $id        The fixture ID.
     * @param string $home_team The home team ID or placeholder.
     * @param string $away_team The away team ID or placeholder.
     * @return bool True on success, false on failure.
     */
    public function update_teams( int $id, string $home_team, string $away_team ): bool;

    /**
     * Update fixture match date and optional original date.
     *
     * @param int         $id            The fixture ID.
     * @param string      $date          The match date string.
     * @param string|null $original_date Optional original match date string.
     * @return bool True on success, false on failure.
     */
    public function update_date( int $id, string $date, ?string $original_date = null ): bool;

    /**
     * Update post ID for a fixture.
     *
     * @param int $id      The fixture ID.
     * @param int $post_id The associated WordPress post ID.
     * @return bool True on success, false on failure.
     */
    public function update_post_id( int $id, int $post_id ): bool;

    /**
     * Find fixture by associated WordPress post ID.
     *
     * @param int $post_id The associated WordPress post ID.
     * @return Fixture|null The fixture object or null if not found.
     */
    public function find_by_post( int $post_id ): ?Fixture;

    /**
     * Find fixtures matching export criteria.
     *
     * @param Export_Criteria $criteria The export criteria DTO.
     * @return Fixture[] Array of fixture objects.
     */
    public function find_by_criteria( Export_Criteria $criteria ): array;

    /**
     * Find a single fixture matching slug criteria.
     *
     * @param array $criteria The query criteria containing league, team slugs, etc.
     * @return Fixture|null The fixture object or null if not found.
     */
    public function find_one_by_slug_criteria( array $criteria ): ?Fixture;

    /**
     * Find all confirmed fixtures played by a specific player with optional filters.
     *
     * @param int   $player_id The player ID.
     * @param array $args      Optional filter arguments (season, time, type, status).
     * @return Fixture[] Array of fixture objects.
     */
    public function find_player_matches( int $player_id, array $args = [] ): array;

    /**
     * Get matches matching criteria or count.
     *
     * @param array $match_args Query arguments.
     * @return array|int Array of match objects or total count if count argument is true.
     */
    public function find_all( array $match_args = [] ): array|int;
}
