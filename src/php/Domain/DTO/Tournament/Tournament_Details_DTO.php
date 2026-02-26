<?php
/**
 * Tournament_Details_DTO API: Tournament_Details_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain\DTO\Tournament;

use Racketmanager\Domain\Club;
use Racketmanager\Domain\Competition;
use Racketmanager\Domain\Tournament;

/**
 * Class to implement the Tournament Details Data Transfer Object
 */
class Tournament_Details_DTO {
    public Tournament $tournament;
    public Competition $competition;

    /**
     * Tournament_Details_DTO constructor.
     *
     * @param Tournament $tournament
     * @param Competition $competition
     * @param Club $club
     */
    public function __construct( Tournament $tournament, Competition $competition, Club $club ) {
        $tournament->set_meta( 'venue_name', $club->get_shortcode() );
        $this->tournament  = $tournament;
        $this->competition = $competition;
    }

}
