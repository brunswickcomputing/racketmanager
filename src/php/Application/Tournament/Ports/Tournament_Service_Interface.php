<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Ports;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Admin\Tournament\Tournament_Draw_Data_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Details_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Event_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Infrastructure\Wordpress\Request\Tournament\Championship_Rounds_Request_DTO;
use Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Finals_Config_Request_DTO;
use Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Finals_Request_DTO;
use Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Information_Request_DTO;
use Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Request_DTO;
use stdClass;
use WP_Error;

/**
 * Port interface for tournament query and lifecycle operations.
 */
interface Tournament_Service_Interface {
    /**
     * Get tournaments matching criteria.
     *
     * @param array $criteria Search criteria.
     * @return array
     */
    public function get_tournaments( array $criteria = array() ): array;

    /**
     * Get tournaments for a player.
     *
     * @param int|null $player_id Player ID.
     * @return array
     */
    public function get_tournaments_for_player( ?int $player_id ): array;

    /**
     * Get tournaments with details matching criteria.
     *
     * @param array $criteria Search criteria.
     * @return array
     */
    public function get_tournaments_with_details( array $criteria = array() ): array;

    /**
     * Get tournament with details by name.
     *
     * @param string|null $name Tournament name.
     * @return Tournament_Details_DTO|null
     */
    public function get_tournament_with_details_by_name( ?string $name ): ?Tournament_Details_DTO;

    /**
     * Get event identifiers that cannot be entered.
     *
     * @param Tournament $tournament        Tournament entity.
     * @param array      $event_identifiers Event identifiers.
     * @return array
     */
    public function get_unavailable_entry_events( Tournament $tournament, array $event_identifiers ): array;

    /**
     * Get tournament by name.
     *
     * @param string|null $name Tournament name.
     * @return Tournament|null
     */
    public function get_tournament_by_name( ?string $name ): ?Tournament;

    /**
     * Get tournament by identifier.
     *
     * @param null|string|int $tournament_id Tournament identifier.
     * @param string          $search_term   Search term column.
     * @return Tournament|null
     */
    public function get_tournament( null|string|int $tournament_id, string $search_term = 'id' ): ?Tournament;

    /**
     * Get active tournament.
     *
     * @param string|null $age_group Optional age group.
     * @return Tournament|null
     */
    public function get_active_tournament( ?string $age_group = null ): ?Tournament;

    /**
     * Get tournament with details.
     *
     * @param int|string|null $tournament_id Tournament ID.
     * @return Tournament_Details_DTO|null
     */
    public function get_tournament_with_details( int|string|null $tournament_id ): ?Tournament_Details_DTO;

    /**
     * Get tournament overview.
     *
     * @param int $tournament_id Tournament ID.
     * @return stdClass|null
     */
    public function get_tournament_overview( int $tournament_id ): ?stdClass;

    /**
     * Get events with details for tournament.
     *
     * @param int|null $tournament_id Tournament ID.
     * @return array
     */
    public function get_events_with_details_for_tournament( ?int $tournament_id ): array;

    /**
     * Get tournament and fees.
     *
     * @param int|null $tournament_id Tournament ID.
     * @return array
     */
    public function get_tournament_and_fees( ?int $tournament_id ): array;

    /**
     * Get tournament fees.
     *
     * @param int|null $tournament_id Tournament ID.
     * @return stdClass
     */
    public function get_fees( ?int $tournament_id ): stdClass;

    /**
     * Add tournament.
     *
     * @param Tournament_Request_DTO $tournament_request Tournament request DTO.
     * @return Tournament|int|WP_Error
     */
    public function add_tournament( Tournament_Request_DTO $tournament_request ): Tournament|int|WP_Error;

    /**
     * Update tournament.
     *
     * @param Tournament_Request_DTO $tournament_request Tournament request DTO.
     * @return Tournament|int|WP_Error
     */
    public function update_tournament( Tournament_Request_DTO $tournament_request ): Tournament|int|WP_Error;

    /**
     * Remove tournament.
     *
     * @param int|null $tournament_id Tournament ID.
     * @return bool
     */
    public function remove_tournament( ?int $tournament_id ): bool;

    /**
     * Bulk remove tournaments.
     *
     * @param array $tournament_ids Tournament IDs.
     * @return array
     */
    public function bulk_remove_tournaments( array $tournament_ids ): array;

    /**
     * Get player entry for event.
     *
     * @param int $event_id  Event ID.
     * @param int $player_id Player ID.
     * @param int $season    Season.
     * @return stdClass|null
     */
    public function get_player_entry_for_event( int $event_id, int $player_id, int $season ): ?stdClass;

    /**
     * Get tournament event entry details for player.
     *
     * @param int|null $player_id     Player ID.
     * @param int|null $tournament_id Tournament ID.
     * @return array
     */
    public function get_tournament_event_entry_details_for_player( ?int $player_id, ?int $tournament_id ): array;

    /**
     * Set tournament information.
     *
     * @param int|null                           $tournament_id                       Tournament ID.
     * @param Tournament_Information_Request_DTO $tournament_information_request_DTO Tournament information request DTO.
     * @return bool|WP_Error
     */
    public function set_tournament_information( ?int $tournament_id, Tournament_Information_Request_DTO $tournament_information_request_DTO ): bool|WP_Error; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

    /**
     * Notify finalists for tournament.
     *
     * @param int|null $tournament_id Tournament ID.
     * @return bool
     */
    public function notify_finalists_for_tournament( ?int $tournament_id ): bool;

    /**
     * Get event details for tournament.
     *
     * @param Tournament      $tournament Tournament entity.
     * @param int|string|null $event_id   Event ID.
     * @return Tournament_Event_DTO|null
     */
    public function get_event_details_for_tournament( Tournament $tournament, null|int|string $event_id ): ?Tournament_Event_DTO;

    /**
     * Get draw details for tournament.
     *
     * @param Tournament      $tournament Tournament entity.
     * @param int|string|null $event_id   Event ID.
     * @return Event|null
     */
    public function get_draw_details_for_tournament( Tournament $tournament, null|int|string $event_id ): ?Event;

    /**
     * Get players for tournament.
     *
     * @param int|null    $tournament_id Tournament ID.
     * @param string|null $status        Entry status.
     * @return array|null
     */
    public function get_players_for_tournament( ?int $tournament_id, ?string $status = null ): ?array;

    /**
     * Get categorised players for tournament.
     *
     * @param int|null $tournament_id Tournament ID.
     * @return array
     */
    public function get_categorized_players_for_tournament( ?int $tournament_id ): array;

    /**
     * Get player details for tournament.
     *
     * @param Tournament      $tournament   Tournament entity.
     * @param int|string|null $player_input Player ID or name.
     * @return Player|null
     */
    public function get_player_details_for_tournament( Tournament $tournament, null|int|string $player_input ): ?Player;

    /**
     * Get statistics for a player by tournament.
     *
     * @param Player $player  Player entity.
     * @param array  $matches Matches array.
     * @return array
     */
    public function get_statistics_for_a_player_by_tournament( Player $player, array $matches ): array;

    /**
     * Get winners for tournament.
     *
     * @param Tournament $tournament Tournament entity.
     * @return array
     */
    public function get_winners_for_tournament( Tournament $tournament ): array;

    /**
     * Get fixture dates for tournament.
     *
     * @param Tournament $tournament Tournament entity.
     * @return array
     */
    public function get_fixture_dates_for_tournament( Tournament $tournament ): array;

    /**
     * Get fixtures by date for tournament.
     *
     * @param Tournament  $tournament Tournament entity.
     * @param string|null $match_date Match date string.
     * @return array
     */
    public function get_fixtures_by_date_for_tournament( Tournament $tournament, ?string $match_date ): array;

    /**
     * Contact teams.
     *
     * @param int|null    $tournament_id Tournament ID.
     * @param string|null $message       Message body.
     * @param bool        $active        Whether active players only.
     * @return bool
     */
    public function contact_teams( ?int $tournament_id, ?string $message, bool $active = false ): bool;

    /**
     * Get contact preview.
     *
     * @param int|null $tournament_id Tournament ID.
     * @param string   $season        Season string.
     * @param string   $email_title   Email title.
     * @param string   $email_intro   Email intro.
     * @param array    $email_body    Email body lines.
     * @param string   $email_close   Email closing text.
     * @return string
     */
    public function get_contact_preview( ?int $tournament_id, string $season, string $email_title, string $email_intro, array $email_body, string $email_close ): string;

    /**
     * Calculate player team rating for tournament.
     *
     * @param int|null $tournament_id Tournament ID.
     * @return bool
     */
    public function calculate_player_team_rating_for_tournament( ?int $tournament_id ): bool;

    /**
     * Get events for tournament.
     *
     * @param int|null $tournament_id Tournament ID.
     * @return array
     */
    public function get_events_for_tournament( ?int $tournament_id ): array;

    /**
     * Get leagues by event for tournament.
     *
     * @param int|null $tournament_id Tournament ID.
     * @return array
     */
    public function get_leagues_by_event_for_tournament( ?int $tournament_id ): array;

    /**
     * Set finals config for tournament.
     *
     * @param int|null                             $tournament_id Tournament ID.
     * @param Tournament_Finals_Config_Request_DTO $config        Finals config DTO.
     * @return bool|WP_Error
     */
    public function set_finals_config_for_tournament( ?int $tournament_id, Tournament_Finals_Config_Request_DTO $config ): bool|WP_Error;

    /**
     * Reset plan for tournament.
     *
     * @param int|null $tournament_id Tournament ID.
     * @return bool
     */
    public function reset_plan_for_tournament( ?int $tournament_id ): bool;

    /**
     * Get finals matches for tournament.
     *
     * @param int|null $tournament_id Tournament ID.
     * @return array
     */
    public function get_finals_matches_for_tournament( ?int $tournament_id ): array;

    /**
     * Save finals plan for tournament.
     *
     * @param int|null                      $tournament_id Tournament ID.
     * @param Tournament_Finals_Request_DTO $request       Finals request DTO.
     * @return bool|WP_Error
     */
    public function save_finals_plan_for_tournament( ?int $tournament_id, Tournament_Finals_Request_DTO $request ): bool|WP_Error;

    /**
     * Set round dates for tournament.
     *
     * @param int|null                        $tournament_id Tournament ID.
     * @param Championship_Rounds_Request_DTO $request       Championship rounds request DTO.
     * @return bool|WP_Error
     */
    public function set_round_dates_for_tournament( ?int $tournament_id, Championship_Rounds_Request_DTO $request ): bool|WP_Error;

    /**
     * Calculate default match dates for tournament.
     *
     * @param Tournament  $tournament  Tournament entity.
     * @param Competition $competition Competition entity.
     * @return array
     */
    public function calculate_default_match_dates( Tournament $tournament, Competition $competition ): array;

    /**
     * Get draw data.
     *
     * @param Tournament  $tournament   Tournament entity.
     * @param object      $league       League entity/object.
     * @param array       $query        Query parameters.
     * @param array       $post         Post parameters.
     * @param string|null $tab_override Optional tab override.
     * @return Tournament_Draw_Data_DTO
     */
    public function get_draw_data( Tournament $tournament, object $league, array $query, array $post, ?string $tab_override = null ): Tournament_Draw_Data_DTO;
}
