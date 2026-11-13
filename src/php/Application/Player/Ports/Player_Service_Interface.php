<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Player\Ports;

use Racketmanager\Domain\Player\Player;
use stdClass;
use WP_Error;

/**
 * Port interface for Player operations.
 */
interface Player_Service_Interface {
    /**
     * Add a new player from request data.
     *
     * @param array|null $request Player request data.
     * @return Player|WP_Error
     */
    public function add_new_player( ?array $request = null ): Player|WP_Error;

    /**
     * Validate player details.
     *
     * @param array|null $request Player request data.
     * @return stdClass|WP_Error
     */
    public function validate_player( ?array $request = null ): stdClass|WP_Error;

    /**
     * Add a player.
     *
     * @param object $player Player data.
     * @return Player
     */
    public function add_player( object $player ): Player;

    /**
     * Get all players.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_all_players( array $args = array() ): array;

    /**
     * Get player by ID.
     *
     * @param int|null $player_id Player ID.
     * @return Player
     */
    public function get_player( ?int $player_id ): Player;

    /**
     * Find player by BTM / LTA tennis number.
     *
     * @param string $btm BTM number.
     * @return Player|null
     */
    public function find_player_by_btm( string $btm ): Player|null;

    /**
     * Get player by name.
     *
     * @param string $name Player name.
     * @return Player|null
     */
    public function get_player_by_name( string $name ): Player|null;

    /**
     * Delete a player.
     *
     * @param mixed $player_id Player ID.
     * @return bool
     */
    public function delete_player( mixed $player_id ): bool;

    /**
     * Save opt-in preferences for a player.
     *
     * @param int    $player_id Player ID.
     * @param string $opt_in    Opt-in value.
     * @return bool
     */
    public function save_opt_in( int $player_id, string $opt_in ): bool;

    /**
     * Amend player details.
     *
     * @param int|null   $player_id Player ID.
     * @param array|null $request   Request data.
     * @return Player|WP_Error
     */
    public function amend_player_details( ?int $player_id, ?array $request = null ): Player|WP_Error;

    /**
     * Update player details.
     *
     * @param int    $player_id      Player ID.
     * @param object $updated_player Updated player data.
     * @return Player|bool
     */
    public function update_player( int $player_id, object $updated_player ): Player|bool;

    /**
     * Handle tournament entry personal information.
     *
     * @param int         $player_id     Player ID.
     * @param int|null    $btm           BTM number.
     * @param string|null $contactno     Contact number.
     * @param string|null $contactemail  Contact email.
     * @return bool
     */
    public function handle_tournament_entry_personal_information( int $player_id, ?int $btm, ?string $contactno, ?string $contactemail ): bool;

    /**
     * Update player BTM.
     *
     * @param int $player_id Player ID.
     * @param int $btm       BTM number.
     * @return bool
     */
    public function update_btm( int $player_id, int $btm ): bool;

    /**
     * Update player contact details.
     *
     * @param int    $player_id      Player ID.
     * @param string $contact_no     Contact number.
     * @param string $contact_email  Contact email.
     * @return Player|bool
     */
    public function update_contact_details( int $player_id, string $contact_no, string $contact_email ): Player|bool;

    /**
     * Schedule player ratings for a club.
     *
     * @param int|null $club_id Club ID.
     * @return void
     */
    public function schedule_player_ratings( ?int $club_id = null ): void;

    /**
     * Schedule weekly recurring player ratings calculation for all clubs.
     *
     * @return bool|WP_Error
     */
    public function schedule_global_player_ratings(): bool|WP_Error;

    /**
     * Calculate player ratings.
     *
     * @param int|null $club_id Club ID to scope player list. Null for all clubs.
     * @return void
     */
    public function calculate_player_ratings( ?int $club_id = null ): void;

    /**
     * Get the latest WTN for a player.
     *
     * @param int|null $player_id Player ID.
     * @return void
     */
    public function get_latest_wtn( ?int $player_id ): void;

    /**
     * Get player errors.
     *
     * @param mixed $status Error status filter.
     * @return array
     */
    public function get_player_errors( mixed $status = null ): array;

    /**
     * Remove player error.
     *
     * @param mixed $id Error ID.
     * @return void
     */
    public function remove_player_error( mixed $id ): void;

    /**
     * Get titles for player.
     *
     * @param int|null $player_id Player ID.
     * @return array
     */
    public function get_titles_for_player( ?int $player_id ): array;

    /**
     * Get match secretary details for a club.
     *
     * @param int $club_id Club ID.
     * @return Player
     */
    public function get_match_secretary_details( int $club_id ): Player;

    /**
     * Finds all unique Club entities where the player has any association.
     *
     * @param int      $player_id Player ID.
     * @param string   $type      Association type.
     * @param int|null $club_id   Club ID.
     * @return array
     */
    public function find_all_associated_clubs( int $player_id, string $type, ?int $club_id ): array;

    /**
     * Retrieves a unique list of active player names for a competition/season.
     *
     * @param int|null    $competition_id Competition ID.
     * @param string|null $season         Season name.
     * @return array
     */
    public function get_players_for_competition( ?int $competition_id, ?string $season ): array;

    /**
     * Get players for team.
     *
     * @param int|null $team_id Team ID.
     * @return array
     */
    public function get_players_for_team( ?int $team_id ): array;
}
