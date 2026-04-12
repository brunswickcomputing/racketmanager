<?php
/**
 * Team_Details_DTO API: Team_Details_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain\DTO\Team;

use Racketmanager\Domain\Club;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Team;

/**
 * Class to implement the Team Details Data Transfer Object
 */
class Team_Details_DTO {
    public Team $team;
    public ?Club $club;
    public ?Player $match_secretary;
    public bool $is_withdrawn;

    /**
     * Team_Details_DTO constructor.
     *
     * @param Team $team
     * @param Club|null $club
     * @param Player|null $match_secretary
     * @param bool $is_withdrawn
     */
    public function __construct( Team $team, ?Club $club, ?Player $match_secretary, bool $is_withdrawn = false ) {
        $this->team            = $team;
        $this->club            = $club;
        $this->match_secretary = $match_secretary;
        $this->is_withdrawn    = $is_withdrawn;
    }

}
