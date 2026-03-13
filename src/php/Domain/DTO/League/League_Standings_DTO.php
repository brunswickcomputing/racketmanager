<?php
/**
 * League_Standings_DTO API: League_Standings_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain\DTO\League;

use Racketmanager\Domain\Enums\Team_Profile;
use stdClass;

/**
 * Class to implement the League Standings Data Transfer Object
 */

readonly class League_Standings_DTO {

    /**
     * Team_Fixture_Settings_DTO constructor.
     *
     */
    public function __construct(
        public int $id,
        public int $league_id,
        public int $team_id,
        public string $team_name,
        public int $season,
        public float $points,
        public int $done_matches,
        public int $won_matches,
        public int $lost_matches,
        public int $drawn_matches,
        public int $rank,
        public float $rating,
        public Team_Profile $profile,
        public string $status,
        public array $additional_info
    ) {}

}
