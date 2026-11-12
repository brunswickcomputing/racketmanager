<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Ports;

use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Fixture_Settings_DTO;
use Racketmanager\Domain\Team\Team;
use WP_Error;

/**
 * Port interface for Team operations.
 */
interface Team_Service_Interface {
    /**
     * Get the team ID for a given team title.
     *
     * @param string $title The team title.
     *
     * @return int|null The team ID if found, null otherwise.
     */
    public function get_team_id( string $title ): ?int;

    /**
     * Get teams for a given club ID.
     *
     * @param int|null    $club_id The club ID.
     * @param string|null $type The team type.
     *
     * @return array An array of team IDs.
     */
    public function get_teams_for_club( ?int $club_id, ?string $type = null ): array;

    /**
     * Get player teams based on a team type.
     *
     * @param string|null $type The team type.
     *
     * @return array An array of team IDs.
     */
    public function get_player_teams( ?string $type ): array;

    /**
     * Retrieve team details by ID.
     *
     * @param int|string|null $team_id The team ID.
     *
     * @return Team_Details_DTO The team details DTO.
     */
    public function get_team_details( int|string|null $team_id ): Team_Details_DTO;

    /**
     * Get the latest team details for a given event.
     *
     * @param int|null $team_id The team ID.
     * @param int|null $event_id The event ID.
     *
     * @return Team_Fixture_Settings_DTO The team fixture settings DTO.
     */
    public function get_latest_team_details_for_event( ?int $team_id, ?int $event_id = null ): Team_Fixture_Settings_DTO;

    /**
     * Amend team details for a club.
     *
     * @param string|null $team_id The team ID.
     * @param int|null    $club_id The club ID.
     * @param string|null $type The team type.
     *
     * @return bool|WP_Error True if the team was amended, WP_Error otherwise.
     */
    public function amend_team_for_club( ?string $team_id, ?int $club_id, ?string $type ): bool|WP_Error;

    /**
     * Retrieve a team by its ID.
     *
     * @param string|int|null $team_id The team ID.
     *
     * @return Team The team object if found, null otherwise.
     */
    public function get_team_by_id( null|string|int $team_id ): Team;

    /**
     * Delete a team by ID.
     *
     * @param int|string|null $team_id The team ID.
     *
     * @return bool True if the team was deleted, false otherwise.
     */
    public function delete_team( int|string|null $team_id ): bool;

    /**
     * Update team event details.
     *
     * @param int         $team_id The team ID.
     * @param int         $event_id The event ID.
     * @param int         $captain The captain ID.
     * @param string      $contactno The contact number.
     * @param string      $contactemail The contact email.
     * @param int|null    $matchday The matchday.
     * @param string|null $matchtime The match time.
     *
     * @return false|string|null The result of the update operation.
     */
    public function update_team_event(
        int $team_id,
        int $event_id,
        int $captain,
        string $contactno,
        string $contactemail,
        ?int $matchday = null,
        ?string $matchtime = null
    ): false|string|null;

    /**
     * Get club teams based on a league type.
     *
     * @param string $league_type The league type.
     *
     * @return array An array of team IDs.
     */
    public function get_club_teams( string $league_type ): array;

    /**
     * Derive team details based on a team reference.
     *
     * @param string $team_ref The team reference.
     *
     * @return Team_Details_DTO|null The team details DTO if found, null otherwise.
     */
    public function derive_team_details( string $team_ref ): null|Team_Details_DTO;
}
