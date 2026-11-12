<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Ports;

use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Club\Club_Role;
use Racketmanager\Domain\DTO\Club\Club_Details_DTO;
use Racketmanager\Domain\Team\Team;

/**
 * Port interface for Club operations.
 */
interface Club_Service_Interface {
    /**
     * Add a new club.
     *
     * @param object $club Club data.
     * @return Club
     */
    public function add_club( object $club ): Club;

    /**
     * Update club details.
     *
     * @param int    $id           Club ID.
     * @param object $club_updated Updated club data.
     * @return Club|bool
     */
    public function update_club( int $id, object $club_updated ): Club|bool;

    /**
     * Get club by ID.
     *
     * @param mixed $club_id Club ID.
     * @return Club
     */
    public function get_club( mixed $club_id ): Club;

    /**
     * Get club by shortcode.
     *
     * @param mixed $club_id Club shortcode or ID.
     * @return Club
     */
    public function get_club_by_shortcode( mixed $club_id ): Club;

    /**
     * Remove a club.
     *
     * @param int $club_id Club ID.
     * @return void
     */
    public function remove_club( int $club_id ): void;

    /**
     * Get clubs.
     *
     * @param array $args Query arguments.
     * @return array|int
     */
    public function get_clubs( array $args = array() ): array|int;

    /**
     * Get clubs with details.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_clubs_with_details( array $args = array() ): array;

    /**
     * Set a club role for a user.
     *
     * @param int $club_id Club ID.
     * @param int $role_id Role ID.
     * @param int $user_id User ID.
     * @return bool|Club_Role
     */
    public function set_club_role( int $club_id, int $role_id, int $user_id ): bool|Club_Role;

    /**
     * Reassign a club role to another user.
     *
     * @param mixed $club_role_id Club role ID.
     * @param mixed $user_id      User ID.
     * @return Club_Role|null
     */
    public function reassign_role_user( mixed $club_role_id, mixed $user_id ): ?Club_Role;

    /**
     * Add a club role.
     *
     * @param int $club_id Club ID.
     * @param int $role_id Role ID.
     * @param int $user_id User ID.
     * @return Club_Role
     */
    public function add_club_role( int $club_id, int $role_id, int $user_id ): Club_Role;

    /**
     * Remove a club role.
     *
     * @param int $club_role_id Club role ID.
     * @return void
     */
    public function remove_club_role( int $club_role_id ): void;

    /**
     * Get roles for a club.
     *
     * @param int|null $club_id Club ID.
     * @return array
     */
    public function get_roles_for_club( ?int $club_id ): array;

    /**
     * Get role by ID.
     *
     * @param int|null $role_id Role ID.
     * @return Club_Role|null
     */
    public function get_role( ?int $role_id ): ?Club_Role;

    /**
     * Get club details.
     *
     * @param int $club_id Club ID.
     * @return Club_Details_DTO
     */
    public function get_club_details( int $club_id ): Club_Details_DTO;

    /**
     * Whether a user is match secretary for a club.
     *
     * @param int $user_id User ID.
     * @param int $club_id Club ID.
     * @return bool
     */
    public function is_user_match_secretary( int $user_id, int $club_id ): bool;

    /**
     * Whether current user can update club.
     *
     * @param int $club_id Club ID.
     * @return bool
     */
    public function can_user_update( int $club_id ): bool;

    /**
     * Whether user can update as captain.
     *
     * @param int $club_id Club ID.
     * @return bool
     */
    public function can_user_update_as_captain( int $club_id ): bool;

    /**
     * Whether user can update players.
     *
     * @param int $club_id Club ID.
     * @return bool
     */
    public function can_user_update_players( int $club_id ): bool;

    /**
     * Whether a player is a captain.
     *
     * @param int $club_id Club ID.
     * @param int $player  Player ID.
     * @return bool
     */
    public function is_player_captain( int $club_id, int $player ): bool;

    /**
     * Create a new team for a club.
     *
     * @param int|null $club_id Club ID.
     * @param string   $type    Team type.
     * @return Team
     */
    public function create_team( ?int $club_id, string $type ): Team;
}
