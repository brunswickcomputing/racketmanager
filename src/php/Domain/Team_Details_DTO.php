<?php
/**
 * Team_Details_DTO API: Team_Details_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain;

/**
 * Class to implement the Team Details Data Transfer Object
 */
class Team_Details_DTO {
    public Team $team;
    public Club $club;
    public ?Player $match_secretary;

    /**
     * Team_Details_DTO constructor.
     *
     * @param Team $team
     * @param Club $club
     */
    public function __construct( Team $team, Club $club, ?Player $match_secretary ) {
        $this->team            = $team;
        $this->club            = $club;
        $this->match_secretary = $match_secretary;
    }

}
