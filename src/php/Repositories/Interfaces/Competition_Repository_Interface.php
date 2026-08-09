<?php
declare( strict_types=1 );

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\DTO\Competition\Competition_Overview_DTO;

/**
 * Interface for Competition Repository.
 */
interface Competition_Repository_Interface extends Repository_Interface {
	public function save( object $entity ): bool|int;
	public function find_by_id( int|string|null $id ): ?Competition;
	public function find_all(): array;
	public function find_by( array $criteria ): array;
	public function find_competitions_with_summary( $age_group, $type ): array;
	public function delete( int $id ): bool;
	public function get_competition_overview( int $competition_id, int $season, ?int $min_fixtures = null ): ?Competition_Overview_DTO;
	public function is_club_participating( int $competition_id, int $club_id, string $season ): bool;
	public function get_league_winners( int $competition_id, ?int $season = null ): array;
	public function get_championship_winners( int $competition_id, ?int $season = null ): array;

	/**
	 * Find a competition by its name.
	 *
	 * @param string $name
	 * @return Competition|null
	 */
	public function find_by_name( string $name ): ?Competition;

	/**
	 * Get teams from database for a competition.
	 *
	 * @param int $competition_id
	 * @param array $args
	 * @return array|int
	 */
	public function find_teams( int $competition_id, array $args = [] ): array|int;

	/**
	 * Get players from database for a competition.
	 *
	 * @param int $competition_id
	 * @param array $args
	 * @return array|int
	 */
	public function find_players( int $competition_id, array $args = [] ): array|int;

	/**
	 * Get events from database for a competition.
	 *
	 * @param int $competition_id
	 * @param array $args
	 * @return array
	 */
	public function find_events( int $competition_id, array $args = [] ): array;

	/**
	 * Get matches from database for a competition.
	 *
	 * @param int $competition_id
	 * @param array $args
	 * @return array|int
	 */
	public function find_matches( int $competition_id, array $args = [] ): array|int;
}
