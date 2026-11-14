<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Ports;

use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Response_DTO;
use Racketmanager\Domain\Tournament\Tournament_Entry;
use Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Entry_Request_DTO;
use Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Partner_Request_DTO;
use WP_Error;

/**
 * Port interface for competition entry query and submission operations.
 */
interface Competition_Entry_Service_Interface {
    /**
     * Request a tournament entry.
     *
     * @param Tournament_Entry_Request_DTO $request Request DTO.
     * @return Tournament_Entry_Response_DTO|WP_Error
     */
    public function request_tournament_entry( Tournament_Entry_Request_DTO $request ): Tournament_Entry_Response_DTO|WP_Error;

    /**
     * Get tournament entry by tournament and player.
     *
     * @param int $tournament_id Tournament ID.
     * @param int $player_id     Player ID.
     * @return Tournament_Entry|null
     */
    public function get_tournament_entry_by_tournament_and_player( int $tournament_id, int $player_id ): ?Tournament_Entry;

    /**
     * Validate a tournament partner request.
     *
     * @param Tournament_Partner_Request_DTO $partner_request Request DTO.
     * @param bool                           $is_modal        Whether this is a modal submission.
     * @return bool|WP_Error
     */
    public function validate_tournament_partner( Tournament_Partner_Request_DTO $partner_request, bool $is_modal = true ): bool|WP_Error;

    /**
     * Confirm tournament entry withdrawal.
     *
     * @param int|null $tournament_id Tournament ID.
     * @param int|null $player_id     Player ID.
     * @return int
     */
    public function confirm_tournament_withdrawal( ?int $tournament_id, ?int $player_id ): int;

    /**
     * Request league entry.
     *
     * @param mixed $request League entry request DTO or stdClass.
     * @return bool|WP_Error
     */
    public function request_league_entry( mixed $request ): bool|WP_Error;

    /**
     * Request cup entry.
     *
     * @param mixed $request Cup entry request DTO or stdClass.
     * @return bool|WP_Error
     */
    public function request_cup_entry( mixed $request ): bool|WP_Error;

    /**
     * Notify team entry open.
     *
     * @param int|null $competition_id Competition ID.
     * @param int|null $season         Season.
     * @return object
     */
    public function notify_team_entry_open( ?int $competition_id, ?int $season ): object;

    /**
     * Notify team entry reminder.
     *
     * @param int|null $competition_id Competition ID.
     * @param int|null $season         Season.
     * @return object
     */
    public function notify_team_entry_reminder( ?int $competition_id, ?int $season ): object;

    /**
     * Get clubs pending entry.
     *
     * @param int|null $competition_id Competition ID.
     * @param int|null $season         Season.
     * @return array
     */
    public function get_clubs_pending_entry( ?int $competition_id, ?int $season ): array;

    /**
     * Validate league entry.
     *
     * @param int    $club_id    Club ID.
     * @param object $club_entry Club entry object.
     * @return bool
     */
    public function league_entry_valid( int $club_id, object $club_entry ): bool;

    /**
     * Withdraw teams from an event.
     *
     * @param int    $club_id  Club ID.
     * @param string $season   Season.
     * @param int    $event_id Event ID.
     * @param array  $teams    Teams to withdraw.
     * @return int
     */
    public function withdraw_teams( int $club_id, string $season, int $event_id, array $teams = array() ): int;

    /**
     * Validate cup entry.
     *
     * @param int    $club_id    Club ID.
     * @param object $club_entry Club entry object.
     * @return void
     */
    public function cup_entry_valid( int $club_id, object $club_entry ): void;

    /**
     * Notify tournament entry open.
     *
     * @param int $tournament_id Tournament ID.
     * @return int
     */
    public function notify_tournament_entry_open( int $tournament_id ): int;

    /**
     * Notify tournament entry reminder.
     *
     * @param int $tournament_id Tournament ID.
     * @return int
     */
    public function notify_tournament_entry_open_reminder( int $tournament_id ): int;

    /**
     * Check if player is eligible for event.
     *
     * @param object $player              Player object.
     * @param object $event               Event object.
     * @param int    $tournament_end_year Tournament end year.
     * @return bool
     */
    public function is_player_eligible_for_event( object $player, object $event, int $tournament_end_year ): bool;

    /**
     * Add team with players.
     *
     * @param array  $players Player array.
     * @param string $type    Type.
     * @return int|false
     */
    public function add_team_with_players( array $players, string $type ): int|false;

    /**
     * Get player teams for event.
     *
     * @param int $event_id  Event ID.
     * @param int $player_id Player ID.
     * @param int $season    Season.
     * @return array
     */
    public function get_player_teams_for_event( int $event_id, int $player_id, int $season ): array;
}
