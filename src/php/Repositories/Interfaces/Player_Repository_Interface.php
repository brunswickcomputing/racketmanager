<?php
declare( strict_types=1 );
/**
 * Player_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Player\Player;
use WP_User;

/**
 * Interface to implement the Player repository
 */
interface Player_Repository_Interface {
    /**
     * Add a player as a WordPress user and save related player metadata.
     *
     * @param Player $player Player to add.
     *
     * @return bool|int User ID on success, false on failure.
     */
    public function add( Player $player );

    /**
     * Save an opt-in value for a player if it has not already been recorded.
     *
     * @param int    $player_id Player user ID.
     * @param string $opt_in Opt-in value to save.
     *
     * @return bool True if the opt-in already exists or was saved successfully, false otherwise.
     */
    public function save_opt_in( int $player_id, string $opt_in ): bool;

    /**
     * Update a player's WordPress user data and selected player metadata.
     *
     * @param Player $player Player domain entity.
     * @param array  $updates Associative array of update flags and payload values.
     *
     * @return bool True on successful update.
     */
    public function update( Player $player, array $updates ): bool;

    /**
     * Find all players matching the supplied filter arguments.
     *
     * @param array $args Filter arguments.
     *
     * @return array
     */
    public function find_all( array $args = array() ): array;

    /**
     * Find player summaries matching the supplied filters.
     *
     * @param array $args Optional arguments used to filter player summaries.
     *
     * @return array
     */
    public function find_players_summary( array $args = array() ): array;

    /**
     * Get active players who have played in a rubber or have tournament entries.
     *
     * @return array
     */
    public function get_active_players(): array;

    /**
     * Get players who have played in a rubber (excludes tournament-entry-only players).
     *
     * @return array
     */
    public function get_active_rubber_players(): array;

    /**
     * Find a player by ID, BTM number, or email address.
     *
     * @param int|string $player_id Player identifier.
     * @param string     $search_type Search type: 'id', 'btm', or 'email'.
     *
     * @return Player|null
     */
    public function find( int|string $player_id, string $search_type = 'id' ): ?Player;

    /**
     * Find a player by ID.
     *
     * @param int|string|null $id Player ID to find.
     *
     * @return object|null Player object if found, otherwise null.
     */
    public function find_by_id( int|string|null $id ): ?object;

    /**
     * Find a WordPress user record by British Tennis Membership (BTM) number.
     *
     * @param mixed $player_id BTM number to search for.
     *
     * @return mixed User object when found, or false when no matching user exists.
     */
    public function find_by_btm( $player_id ): mixed;

    /**
     * Find a WordPress user record by email address.
     *
     * @param mixed $player_id Email address to search for.
     *
     * @return false|WP_User User object if found, false otherwise.
     */
    public function find_by_email( $player_id ): false|WP_User;

    /**
     * Check whether a player has any club associations.
     *
     * @param int $player_id Player user ID.
     *
     * @return bool True if the player has one or more club associations, false otherwise.
     */
    public function has_club_associations( int $player_id ): bool;

    /**
     * Delete a player entity by its ID.
     *
     * @param int $id Player user ID.
     *
     * @return bool True on success, false on failure.
     */
    public function delete( int $id ): bool;

    /**
     * Find active player IDs registered to a specific club.
     *
     * @param int $club_id Club ID.
     *
     * @return array
     */
    public function find_player_ids_by_club( int $club_id ): array;

    /**
     * Find club players with registration details matching the supplied filters.
     *
     * @param int|null    $club_id Club ID.
     * @param string|null $status Registration status filter.
     * @param string|null $gender Gender filter ('M' or 'F').
     * @param string|null $active Active filter ('active' or 'inactive').
     * @param bool        $system Include system records.
     * @param int|null    $max_age Maximum age filter.
     * @param int|null    $min_age Minimum age filter.
     *
     * @return array
     */
    public function find_club_players_with_details( ?int $club_id = null, ?string $status = null, ?string $gender = null, ?string $active = null, bool $system = false, ?int $max_age = null, ?int $min_age = null ): array;

    /**
     * Get final match title records for a player, grouped by season and tournament.
     *
     * @param int|string $player_id Player ID.
     *
     * @return array
     */
    public function get_titles( $player_id ): array;

    /**
     * Find active players who have played in a competition during a season.
     *
     * @param int    $competition_id Competition ID.
     * @param string $season Season identifier.
     *
     * @return array
     */
    public function find_active_players_by_competition_and_season( int $competition_id, string $season ): array;

    /**
     * Find players assigned to a team.
     *
     * @param int $team_id Team ID.
     *
     * @return array
     */
    public function find_by_team( int $team_id ): array;

    /**
     * Find registered player autocomplete projections.
     *
     * @param string      $term Search query (name or BTM).
     * @param string      $type Lookup type ('name' or 'btm').
     * @param string|null $gender Optional gender filter ('M' or 'F').
     * @param int|null    $club_id Optional club ID filter.
     * @param int         $limit Max results to return.
     *
     * @return array
     */
    public function find_registered_player_autocomplete( string $term, string $type = 'name', ?string $gender = null, ?int $club_id = null, int $limit = 100 ): array;
}
