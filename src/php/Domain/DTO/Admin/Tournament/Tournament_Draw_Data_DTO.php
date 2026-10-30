<?php
declare( strict_types=1 );
/**
 * Tournament Draw Data DTO
 *
 * @package RacketManager
 * @subpackage Domain/DTO/Admin/Tournament
 */

namespace Racketmanager\Domain\DTO\Admin\Tournament;

use Racketmanager\Domain\Tournament\Tournament;

readonly final class Tournament_Draw_Data_DTO {

    /**
     * @param Tournament $tournament
     * @param object $league
     * @param string $tab
     * @param string $season
     * @param array $matches
     * @param array $teams
     * @param array $finals
     */
    public function __construct(
        public Tournament $tournament,
        public object $league,
        public string $tab,
        public string $season,
        public array $matches = array(),
        public array $teams = array(),
        public array $finals = array(),
    ) {
    }
}
