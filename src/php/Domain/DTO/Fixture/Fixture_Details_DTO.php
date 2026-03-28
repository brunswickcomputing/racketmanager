<?php
/**
 * Fixture_Details_DTO API: Fixture_Details_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain\DTO\Fixture;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Competition\League;

/**
 * Class to implement the Fixture Details Data Transfer Object
 */
readonly class Fixture_Details_DTO {
    public Fixture $fixture;
    public League $league;
    public Event $event;
    public Competition $competition;
    public ?Team_Details_DTO $home_team;
    public ?Team_Details_DTO $away_team;
    public ?string $prev_home_match_title;
    public ?string $prev_away_match_title;
    public ?object $is_update_allowed;

    /**
     * Fixture_Details_DTO constructor.
     *
     */
    public function __construct( Fixture $fixture, League $league, Event $event, Competition $competition, ?Team_Details_DTO $home_team = null, ?Team_Details_DTO $away_team = null, ?string $prev_home_match_title = null, ?string $prev_away_match_title = null, ?object $is_update_allowed = null ) {
        $this->fixture                = $fixture;
        $this->league                 = $league;
        $this->event                  = $event;
        $this->competition            = $competition;
        $this->home_team              = $home_team;
        $this->away_team              = $away_team;
        $this->prev_home_match_title  = $prev_home_match_title;
        $this->prev_away_match_title  = $prev_away_match_title;
        $this->is_update_allowed      = $is_update_allowed;
    }

}
