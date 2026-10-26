<?php
declare( strict_types=1 );
/**
 * Player_Statistics_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

/**
 * Read-model port for the cross-table player statistics queries.
 */
interface Player_Statistics_Repository_Interface {
    /**
     * Competitions (with season) in which the player has played a rubber.
     *
     * @param int $player_id
     * @param string|null $type competition type filter.
     * @param string|null $season season filter.
     *
     * @return array<int, object> rows of id, name, season (newest season first).
     */
    public function find_competitions_played( int $player_id, ?string $type = null, ?string $season = null ): array;

    /**
     * Rubber-level rows for the player's team statistics.
     *
     * @param int $player_id
     * @param string|null $season season filter.
     *
     * @return array<int, object> rows: stat_type='teams', season, type, player_team, home_team, away_team, winner_id, loser_id.
     */
    public function find_team_stats( int $player_id, ?string $season = null ): array;

    /**
     * Match-level rows for the player's player statistics.
     *
     * @param int $player_id
     * @param string|null $season season filter.
     *
     * @return array<int, object> rows: stat_type='players', season, type, team_id, home_team, away_team, winner_id, loser_id.
     */
    public function find_player_stats( int $player_id, ?string $season = null ): array;
}
