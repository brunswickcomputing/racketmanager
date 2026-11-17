<?php
declare( strict_types=1 );

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\DTO\Competition\Competition_Overview_DTO;

/**
 * Interface for Competition Repository.
 */
interface Competition_Repository_Interface extends Repository_Interface {
    /**
     * Save an entity.
     *
     * @param object $entity Entity to save.
     *
     * @return bool|int
     */
    public function save( object $entity ): bool|int;

    /**
     * Find a competition by its ID.
     *
     * @param int|string|null $id identifier.
     *
     * @return Competition|null
     */
    public function find_by_id( int|string|null $id ): ?Competition;

    /**
     * Find all records.
     *
     * @return array
     */
    public function find_all(): array;

    /**
     * Finds records matching the specified criteria.
     *
     * @param array $criteria Criteria used to filter the records.
     *
     * @return array Matching records.
     */
    public function find_by( array $criteria ): array;

    /**
     * Finds competitions with summary information, optionally filtered by age group and type.
     *
     * @param string|null $age_group Age group used to filter competitions, or null to include all age groups.
     * @param string|null $type Competition type used to filter competitions, or null to include all types.
     *
     * @return array Competitions with their summary information.
     */
    public function find_competitions_with_summary( ?string $age_group = null, ?string $type = null ): array;

    /**
     * Deletes the record with the specified identifier.
     *
     * @param int $id Identifier of the record to delete.
     *
     * @return bool True if the record was deleted successfully, false otherwise.
     */
    public function delete( int $id ): bool;

    /**
     * Retrieves an overview of a competition for the specified season.
     *
     * @param int      $competition_id Competition identifier.
     * @param int      $season Season year or identifier.
     * @param int|null $min_fixtures Minimum number of fixtures required for inclusion, or null for no minimum.
     *
     * @return Competition_Overview_DTO|null Competition overview data, or null if no overview is available.
     */
    public function get_competition_overview( int $competition_id, int $season, ?int $min_fixtures = null ): ?Competition_Overview_DTO;

    /**
     * Determines whether a club is participating in a competition for the specified season.
     *
     * @param int    $competition_id Competition identifier.
     * @param int    $club_id Club identifier.
     * @param string $season Season to check.
     *
     * @return bool True if the club is participating, false otherwise.
     */
    public function is_club_participating( int $competition_id, int $club_id, string $season ): bool;

    /**
     * Retrieves the winners for the specified league competition and optional season.
     *
     * @param int      $competition_id Competition identifier.
     * @param int|null $season Season identifier, or null to include all available seasons.
     *
     * @return array League winner records.
     */
    public function get_league_winners( int $competition_id, ?int $season = null ): array;

    /**
     * Retrieves the championship winners for a competition, optionally filtered by season.
     *
     * @param int      $competition_id Competition identifier.
     * @param int|null $season Season to filter by, or null to include all seasons.
     *
     * @return array Championship winners matching the specified competition and season.
     */
    public function get_championship_winners( int $competition_id, ?int $season = null ): array;

    /**
     * Finds a competition by its name.
     *
     * @param string $name Name of the competition to find.
     *
     * @return Competition|null Matching competition, or null if none is found.
     */
    public function find_by_name( string $name ): ?Competition;

    /**
     * Finds teams associated with the specified competition.
     *
     * @param int   $competition_id Competition identifier used to find teams.
     * @param array $args Optional arguments used to filter, sort, or limit the results.
     *
     * @return array|int Matching teams, or an integer result when requested by the provided arguments.
     */
    public function find_teams( int $competition_id, array $args = [] ): array|int;

    /**
     * Queries teams associated with the specified competition.
     *
     * @param int   $competition_id Competition identifier used to limit the team query.
     * @param array $args Optional query arguments used to filter, sort, or modify the results.
     *
     * @return array|int Matching teams, or an integer result when requested by the query arguments.
     */
    public function query_teams( int $competition_id, array $args = [] ): array|int;

    /**
     * Finds players associated with the specified competition.
     *
     * @param int   $competition_id Competition identifier used to locate players.
     * @param array $args Optional arguments used to filter, sort, or limit the results.
     *
     * @return array|int Matching players, or an integer result when requested by the arguments.
     */
    public function find_players( int $competition_id, array $args = [] ): array|int;

    /**
     * Finds events for the specified competition.
     *
     * @param int   $competition_id Competition identifier used to locate events.
     * @param array $args Optional arguments used to filter or modify the event search.
     *
     * @return array Matching events.
     */
    public function find_events( int $competition_id, array $args = [] ): array;

    /**
     * Finds matches for the specified competition.
     *
     * @param int   $competition_id Competition identifier used to locate matches.
     * @param array $args Optional arguments used to filter or modify the match query.
     *
     * @return array|int Matching match records, or an integer result value.
     */
    public function find_matches( int $competition_id, array $args = [] ): array|int;

    /**
     * Show winners
     *
     * @param string  $season season.
     * @param int     $competition_id competition id.
     * @param string  $competition_type competition type.
     * @param boolean $group_by group by type.
     *
     * @return array|false of winners|false.
     */
    public function get_winners( string $season, int $competition_id, string $competition_type = 'tournament', bool $group_by = false ): false|array;
}
