<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Ports;

use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;

/**
 * Port interface for Event query operations.
 */
interface Event_Query_Service_Interface {
    /**
     * Get an event by identifier.
     *
     * @param int|string $event_id    Event ID.
     * @param string     $search_term Search term ('id' or 'name').
     * @return Event|null
     */
    public function get_event( int|string $event_id, string $search_term = 'id' ): ?Event;

    /**
     * Resolve requested season name for an event.
     *
     * @param Event $event Event entity.
     * @return string|null
     */
    public function resolve_requested_season( Event $event ): ?string;

    /**
     * Set season on event entity.
     *
     * @param Event       $event           Event entity.
     * @param string|null $season          Season name.
     * @param bool        $force_overwrite Whether to force overwrite.
     * @return void
     */
    public function set_season( Event $event, ?string $season = null, bool $force_overwrite = false ): void;

    /**
     * Get leagues for an event.
     *
     * @param Event $event Event entity.
     * @param array $args  Query arguments.
     * @return array|int
     */
    public function get_leagues( Event $event, array $args = array() ): array|int;

    /**
     * Get matches for an event.
     *
     * @param Event $event      Event entity.
     * @param array $match_args Match query arguments.
     * @return array|int
     */
    public function get_matches( Event $event, array $match_args = array() ): array|int;

    /**
     * Get teams for an event.
     *
     * @param Event $event Event entity.
     * @param array $args  Query arguments.
     * @return array|int
     */
    public function get_teams( Event $event, array $args = array() ): array|int;

    /**
     * Get teams info for an event.
     *
     * @param Event $event Event entity.
     * @param array $args  Query arguments.
     * @return array
     */
    public function get_teams_info( Event $event, array $args = array() ): array;

    /**
     * Get team info in an event.
     *
     * @param Event $event   Event entity.
     * @param int   $team_id Team ID.
     * @return object|null
     */
    public function get_team_info( Event $event, int $team_id ): ?object;

    /**
     * Get players for an event.
     *
     * @param Event        $event Event entity.
     * @param array|string $args  Query arguments.
     * @return array|int
     */
    public function get_players( Event $event, array|string $args = array() ): array|int;

    /**
     * Get constitution for an event.
     *
     * @param Event $event Event entity.
     * @param array $args  Query arguments.
     * @return array|int
     */
    public function get_constitution( Event $event, array $args = array() ): array|int;

    /**
     * Build constitution for an event.
     *
     * @param Event        $event Event entity.
     * @param array|string $args  Query arguments.
     * @return array
     */
    public function build_constitution( Event $event, array|string $args = array() ): array;

    /**
     * Get clubs for an event.
     *
     * @param Event        $event Event entity.
     * @param array|string $args  Query arguments.
     * @return array|int
     */
    public function get_clubs( Event $event, array|string $args = array() ): array|int;

    /**
     * Get player stats for an event.
     *
     * @param Event $event Event entity.
     * @param array $args  Query arguments.
     * @return array
     */
    public function get_player_stats( Event $event, array $args ): array;

    /**
     * Check if event has leagues.
     *
     * @param Event $event Event entity.
     * @return int
     */
    public function has_leagues( Event $event ): int;

    /**
     * Set number of leagues on event entity.
     *
     * @param Event $event Event entity.
     * @param bool  $total Whether total count.
     * @return void
     */
    public function set_num_leagues( Event $event, bool $total = false ): void;

    /**
     * Get primary league for an event.
     *
     * @param Event $event Event entity.
     * @return League|null
     */
    public function get_primary_league( Event $event ): ?League;

    /**
     * Get primary league ID for an event.
     *
     * @param Event $event Event entity.
     * @return int|null
     */
    public function get_primary_league_id( Event $event ): ?int;

    /**
     * Reload settings on an event.
     *
     * @param Event $event Event entity.
     * @return void
     */
    public function reload_settings( Event $event ): void;

    /**
     * Set settings on an event.
     *
     * @param Event $event    Event entity.
     * @param array $settings Settings array.
     * @return void
     */
    public function set_settings( Event $event, array $settings ): void;

    /**
     * Update season configuration on an event.
     *
     * @param Event $event  Event entity.
     * @param array $season Season configuration.
     * @return void
     */
    public function update_season( Event $event, array $season ): void;

    /**
     * Update all seasons on an event.
     *
     * @param Event $event   Event entity.
     * @param array $seasons Seasons array.
     * @return bool
     */
    public function update_seasons( Event $event, array $seasons ): bool;

    /**
     * Mark teams withdrawn for an event.
     *
     * @param Event     $event  Event entity.
     * @param string    $season Season name.
     * @param int       $club   Club ID.
     * @param false|int $team   Team ID or false for all club teams.
     * @return bool
     */
    public function mark_teams_withdrawn( Event $event, string $season, int $club, false|int $team = false ): bool;

    /**
     * Mark team entered for an event.
     *
     * @param Event  $event  Event entity.
     * @param int    $team   Team ID.
     * @param string $season Season name.
     * @return bool
     */
    public function mark_teams_entered( Event $event, int $team, string $season ): bool;

    /**
     * Get club info within an event context.
     *
     * @param Event  $event Event entity.
     * @param object $club  Club object.
     * @return object
     */
    public function get_club( Event $event, object $club ): object;
}
