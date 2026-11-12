<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Ports;

use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Competition\Stage;

/**
 * Port interface for Standings operations.
 */
interface Standings_Service_Interface {
    /**
     * Calculate standings for stage and season.
     *
     * @param Stage $stage  Stage entity.
     * @param int   $season Season year.
     * @return array
     */
    public function calculate_standings( Stage $stage, int $season ): array;

    /**
     * Update rankings from standings array.
     *
     * @param array $standings Standings data.
     * @return void
     */
    public function update_rankings( array $standings ): void;

    /**
     * Get standings table.
     *
     * @param League $league    League entity.
     * @param array  $teams     Teams array.
     * @param int    $match_day Match day.
     * @param string $mode      Mode.
     * @return array
     */
    public function get_standings( League $league, array $teams, int $match_day, string $mode = 'all' ): array;

    /**
     * Set teams rank for a league.
     *
     * @param League       $league League entity.
     * @param false|string $season Season name or false.
     * @return void
     */
    public function set_teams_rank( League $league, false|string $season = false ): void;

    /**
     * Save standings manually.
     *
     * @param League $league  League entity.
     * @param array  $teams   Team IDs.
     * @param array  $points  Points data.
     * @param array  $matches Matches data.
     * @param array  $custom  Custom points data.
     * @return void
     */
    public function save_standings_manually( League $league, array $teams, array $points, array $matches, array $custom ): void;

    /**
     * Update standings for a league and season.
     *
     * @param League $league League entity.
     * @param string $season Season name.
     * @return void
     */
    public function update_standings( League $league, string $season ): void;

    /**
     * Save standings for a league team.
     *
     * @param League $league      League entity.
     * @param object $league_team League team object.
     * @return void
     */
    public function save_standings( League $league, object $league_team ): void;

    /**
     * Calculate points for a team.
     *
     * @param League      $league  League entity.
     * @param object      $team    Team object.
     * @param false|array $matches Matches or false.
     * @return array
     */
    public function calculate_points( League $league, object $team, false|array $matches = false ): array;

    /**
     * Whether two teams are tied.
     *
     * @param object $team1 First team.
     * @param object $team2 Second team.
     * @return bool
     */
    public function is_tie( object $team1, object $team2 ): bool;

    /**
     * Get standings data for a team.
     *
     * @param League      $league  League entity.
     * @param int         $team_id Team ID.
     * @param array       $data    Existing data.
     * @param array|false $matches Matches or false.
     * @return array
     */
    public function get_standings_data( League $league, int $team_id, array $data = array(), array|false $matches = array() ): array;

    /**
     * Refresh match counts for a league team.
     *
     * @param League      $league      League entity.
     * @param League_Team $league_team League team entity.
     * @return void
     */
    public function refresh_match_counts( League $league, League_Team $league_team ): void;
}
