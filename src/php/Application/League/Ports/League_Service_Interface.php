<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Ports;

use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;

/**
 * Port interface for League operations.
 */
interface League_Service_Interface {
    /**
     * Get leagues.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_leagues( array $args = array() ): array;

    /**
     * Get league by ID, object or name.
     *
     * @param object|int|string|null $league_id League identifier.
     * @return object|null
     */
    public function get_league( object|int|string|null $league_id ): ?object;

    /**
     * Get league by name.
     *
     * @param string $name League name.
     * @return object|null
     */
    public function get_league_by_name( string $name ): ?object;

    /**
     * Add a league to an event.
     *
     * @param int         $event_id Event ID.
     * @param string|null $name     League name.
     * @return League
     */
    public function add_league_to_event( int $event_id, ?string $name = null ): League;

    /**
     * Add a team to a league.
     *
     * @param int $team_id   Team ID.
     * @param int $league_id League ID.
     * @param int $season    Season year.
     * @return League_Team
     */
    public function add_team_to_league( int $team_id, int $league_id, int $season ): League_Team;

    /**
     * Add teams to a league.
     *
     * @param array $team_ids  Team IDs.
     * @param int   $league_id League ID.
     * @param int   $season    Season year.
     * @return int
     */
    public function add_teams_to_league( array $team_ids, int $league_id, int $season ): int;

    /**
     * Remove team from a league.
     *
     * @param int $team_id   Team ID.
     * @param int $league_id League ID.
     * @param int $season    Season year.
     * @return void
     */
    public function remove_team_from_league( int $team_id, int $league_id, int $season ): void;

    /**
     * Remove teams from a league.
     *
     * @param array $league_team_ids League team IDs.
     * @param int   $league_id       League ID.
     * @param int   $season          Season year.
     * @return array
     */
    public function remove_teams_from_league( array $league_team_ids, int $league_id, int $season ): array;

    /**
     * Delete a team from a league.
     *
     * @param object|int  $league  League object or ID.
     * @param int         $team_id Team ID.
     * @param string|null $season  Season name.
     * @return void
     */
    public function delete_team( object|int $league, int $team_id, ?string $season ): void;

    /**
     * Withdraw a team from a league.
     *
     * @param object|int $league  League object or ID.
     * @param int        $team_id Team ID.
     * @param string|int $season  Season name or year.
     * @return void
     */
    public function withdraw_team( object|int $league, int $team_id, string|int $season ): void;

    /**
     * Get consolation teams for a league.
     *
     * @param League $league League entity.
     * @param int    $season Season year.
     * @return array
     */
    public function get_consolation_teams( League $league, int $season ): array;

    /**
     * Rank teams in a league.
     *
     * @param object $league   League object.
     * @param string $mode     Ranking mode.
     * @param array  $post     Ranking data.
     * @param array  $team_ids Team IDs.
     * @return bool
     */
    public function rank_teams( object $league, string $mode, array $post, array $team_ids ): bool;

    /**
     * Update ranking from standings.
     *
     * @param array $teams Teams data.
     * @return void
     */
    public function update_ranking( array $teams ): void;

    /**
     * Delete a league.
     *
     * @param object|int $league League object or ID.
     * @return void
     */
    public function delete_league( object|int $league ): void;
}
