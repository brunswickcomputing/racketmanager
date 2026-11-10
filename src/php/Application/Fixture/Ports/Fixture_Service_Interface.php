<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Fixture\Ports;

use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;

/**
 * Port interface for fixture lifecycle, creation, update, and query operations.
 */
interface Fixture_Service_Interface {
    /**
     * Get matches from the repository.
     *
     * @param array $args Query arguments.
     * @return array|int Matches collection or count.
     */
    public function get_matches( array $args = [] ): array|int;

    /**
     * Add a match to a league.
     *
     * @param object $match
     * @param object $league
     *
     * @return Fixture
     */
    public function add_match( object $match, object $league ): Fixture;

    /**
     * Create a new fixture and associated rubbers.
     *
     * @param Fixture $fixture
     * @param object $league
     *
     * @return Fixture
     */
    public function create_fixture( Fixture $fixture, object $league ): Fixture;

    /**
     * Update match object.
     *
     * @param object $match
     */
    public function update_match( object $match ): void;

    /**
     * Update fixture entity in repository.
     *
     * @param Fixture $fixture
     */
    public function update_fixture( Fixture $fixture ): void;

    /**
     * Update fixture location.
     *
     * @param Fixture $fixture
     * @param string $location
     */
    public function update_fixture_location( Fixture $fixture, string $location ): void;

    /**
     * Set fixture date and time.
     *
     * @param Fixture $fixture
     * @param string $start_date
     * @param string|null $match_day
     * @param string|null $match_time
     */
    public function set_fixture_date( Fixture $fixture, string $start_date, ?string $match_day, ?string $match_time ): void;

    /**
     * Update fixture date.
     *
     * @param Fixture $fixture
     * @param string $new_date
     * @param string|null $original_date
     */
    public function update_fixture_date( Fixture $fixture, string $new_date, ?string $original_date = null ): void;

    /**
     * Reset finals fixtures for a tournament.
     *
     * @param int $tournament_id
     * @param string $fixture_date
     *
     * @return bool
     */
    public function reset_finals_fixtures_for_tournament( int $tournament_id, string $fixture_date ): bool;

    /**
     * Update finals fixture court and date.
     *
     * @param int $fixture_id
     * @param string $new_date
     * @param string $court_name
     *
     * @return bool
     */
    public function update_fixture_finals_data( int $fixture_id, string $new_date, string $court_name ): bool;

    /**
     * Get fixture by ID.
     *
     * @param int $fixture_id
     *
     * @return Fixture|null
     */
    public function get_fixture( int $fixture_id ): ?Fixture;

    /**
     * Get finals fixtures for tournament.
     *
     * @param int $tournament_id
     *
     * @return Fixture[]
     */
    public function get_finals_fixtures_for_tournament( int $tournament_id ): array;

    /**
     * Delete fixtures for a season.
     *
     * @param int $league_id
     * @param string $season
     */
    public function delete_fixtures_for_season( int $league_id, string $season ): void;

    /**
     * Get tournament fixtures for a player.
     *
     * @param int|null $player_id
     * @param int|null $tournament_id
     *
     * @return Fixture_Details_DTO[]
     */
    public function get_fixtures_for_player_for_tournament( ?int $player_id, ?int $tournament_id ): array;

    /**
     * Get tournament fixture with details.
     *
     * @param int|Fixture|null $fixture_id
     *
     * @return Fixture_Details_DTO|null
     */
    public function get_tournament_fixture_with_details( int|Fixture|null $fixture_id ): ?Fixture_Details_DTO;

    /**
     * Check whether match update is allowed.
     *
     * @param int|Fixture $fixture_id
     *
     * @return object
     */
    public function is_update_allowed( int|Fixture $fixture_id ): object;
}
