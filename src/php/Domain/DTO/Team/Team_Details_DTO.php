<?php
/**
 * Team_Details_DTO API: Team_Details_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain\DTO\Team;

use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Team\Team;

/**
 * Class to implement the Team Details Data Transfer Object
 */
class Team_Details_DTO {
    public Team $team;
    public ?Club $club;
    public ?Player $match_secretary;
    public bool $is_withdrawn;
    public ?string $captain = null;
    public ?string $captain_email = null;
    public ?string $captain_tel = null;
    public ?string $contactemail = null;
    public ?string $contactno = null;
    public ?string $match_day = null;
    public ?string $match_time = null;
    public array $players = [];

    /**
     * Team_Details_DTO constructor.
     *
     * @param Team $team
     * @param Club|null $club
     * @param Player|null $match_secretary
     * @param bool $is_withdrawn
     */
    public function __construct( Team $team, ?Club $club = null, ?Player $match_secretary = null, bool $is_withdrawn = false ) {
        $this->team            = $team;
        $this->club            = $club;
        $this->match_secretary = $match_secretary;
        $this->is_withdrawn    = $is_withdrawn;
    }

}
