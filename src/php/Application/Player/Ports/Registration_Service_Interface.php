<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Player\Ports;

use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
use WP_Error;

/**
 * Port interface for Registration operations.
 */
interface Registration_Service_Interface {
    /**
     * Get registration by ID.
     *
     * @param mixed $registration_id Registration ID.
     * @return Club_Player_DTO
     */
    public function get_registration( mixed $registration_id ): Club_Player_DTO;

    /**
     * Register a player to a club.
     *
     * @param int|null   $club_id               Club ID.
     * @param int|null   $registered_by_user_id User ID performing registration.
     * @param array|null $request               Registration request data.
     * @return string|WP_Error
     */
    public function register_player_to_club( ?int $club_id, ?int $registered_by_user_id = null, ?array $request = null ): string|WP_Error;

    /**
     * Approve a registration.
     *
     * @param int $registration_id Registration ID.
     * @param int $approving_user  User ID approving registration.
     * @return void
     */
    public function approve_registration( int $registration_id, int $approving_user ): void;

    /**
     * Remove a registration.
     *
     * @param int $registration_id Registration ID.
     * @param int $removing_user   User ID removing registration.
     * @return void
     */
    public function remove_registration( int $registration_id, int $removing_user ): void;

    /**
     * Get registered players list.
     *
     * @param string|null $active   Active status.
     * @param string|null $status   Status filter.
     * @param int|null    $club_id  Club ID filter.
     * @param string|null $gender   Gender filter.
     * @param bool        $system   System flag.
     * @param int|null    $max_age  Maximum age.
     * @param int|null    $min_age  Minimum age.
     * @return array
     */
    public function get_registered_players_list( ?string $active = null, ?string $status = null, ?int $club_id = null, ?string $gender = null, bool $system = false, ?int $max_age = null, ?int $min_age = null ): array;

    /**
     * Get clubs for a player.
     *
     * @param int         $player_id Player ID.
     * @param string|null $status    Status filter.
     * @return array
     */
    public function get_clubs_for_player( int $player_id, ?string $status = 'active' ): array;

    /**
     * Get player registration for a club.
     *
     * @param int $club_id   Club ID.
     * @param int $player_id Player ID.
     * @return Club_Player_DTO|null
     */
    public function get_player_for_club( int $club_id, int $player_id ): ?Club_Player_DTO;

    /**
     * Whether a player is active in a club.
     *
     * @param int $club_id   Club ID.
     * @param int $player_id Player ID.
     * @return bool
     */
    public function is_player_active_in_club( int $club_id, int $player_id ): bool;

    /**
     * Get dummy walkover players for a club.
     *
     * @param int $club_id Club ID.
     * @return array
     */
    public function get_dummy_players( int $club_id ): array;
}
