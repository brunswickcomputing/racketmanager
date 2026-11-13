<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Player\Ports;

use Racketmanager\Domain\Player\Player;

/**
 * Port interface for Player Statistics operations.
 */
interface Player_Stats_Service_Interface {
    /**
     * Populate player matches and stats.
     *
     * @param Player          $player       Player entity.
     * @param object|null     $grouping     Grouping object.
     * @param string|int|null $season       Season identifier.
     * @param string          $match_source Match source filter.
     * @return void
     */
    public function populate_player_stats( Player $player, ?object $grouping, string|int|null $season, string $match_source ): void;

    /**
     * Populate stats for a list of players.
     *
     * @param array           $players      Array of Player entities.
     * @param object|null     $grouping     Grouping object.
     * @param string|int|null $season       Season identifier.
     * @param string          $match_source Match source filter.
     * @return array
     */
    public function populate_players_stats( array $players, ?object $grouping, string|int|null $season, string $match_source ): array;

    /**
     * Get player statistics.
     *
     * @param Player      $player Player entity.
     * @param false|array $stats  Existing stats array or false.
     * @return array
     */
    public function get_stats( Player $player, false|array $stats = false ): array;

    /**
     * Get career statistics for a player.
     *
     * @param Player $player Player entity.
     * @return array
     */
    public function get_career_stats( Player $player ): array;

    /**
     * Calculate career statistics from team and player matches.
     *
     * @param array $team_matches   Team matches.
     * @param array $player_matches Player matches.
     * @return array
     */
    public function calculate_career_stats( array $team_matches, array $player_matches ): array;
}
