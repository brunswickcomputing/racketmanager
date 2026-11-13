<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Player\Ports;

use Racketmanager\Domain\Player\Player;

/**
 * Port interface for Player Query operations.
 */
interface Player_Query_Service_Interface {
    /**
     * Get competitions for a player.
     *
     * @param Player       $player Player entity.
     * @param array|string $args   Query arguments.
     * @return array
     */
    public function get_competitions( Player $player, array|string $args = array() ): array;

    /**
     * Get matches for a player.
     *
     * @param Player      $player       Player entity.
     * @param object|null $grouping     Grouping object.
     * @param string|null $season       Season name.
     * @param string      $match_source Match source.
     * @param int|null    $period       Period.
     * @param string|null $type         Match type.
     * @return array
     */
    public function get_matches( Player $player, ?object $grouping, ?string $season, string $match_source, ?int $period = null, ?string $type = null ): array;
}
