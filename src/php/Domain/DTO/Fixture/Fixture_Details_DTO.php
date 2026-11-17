<?php
declare( strict_types=1 );
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
class Fixture_Details_DTO {
    public string $league_name {
        final get => $this->league->name;
    }

    public string $league_title {
        final get => $this->league->title;
    }

    public readonly Fixture $fixture;
    public readonly League $league;
    public readonly Event $event;
    public readonly Competition $competition;
    public readonly ?Team_Details_DTO $home_team;
    public readonly ?Team_Details_DTO $away_team;
    public readonly ?string $prev_home_fixture_title;
    public readonly ?string $prev_away_fixture_title;
    public readonly ?object $is_update_allowed;
    public readonly string $link;
    public readonly ?string $score_display;
    public readonly array $status_flags;
    public readonly string $fixture_title;

    /**
     * Fixture_Details_DTO constructor.
     *
     */
    public function __construct( Fixture $fixture, League $league, Event $event, Competition $competition, ?Team_Details_DTO $home_team = null, ?Team_Details_DTO $away_team = null, ?string $prev_home_fixture_title = null, ?string $prev_away_fixture_title = null, ?object $is_update_allowed = null, string $link = '', ?string $score_display = null, array $status_flags = array(), string $fixture_title = '' ) {
        $this->fixture                 = $fixture;
        $this->league                  = $league;
        $this->event                   = $event;
        $this->competition             = $competition;
        $this->home_team               = $home_team;
        $this->away_team               = $away_team;
        $this->prev_home_fixture_title = $prev_home_fixture_title;
        $this->prev_away_fixture_title = $prev_away_fixture_title;
        $this->is_update_allowed       = $is_update_allowed;
        $this->link                    = $link;
        $this->score_display           = $score_display;
        $this->status_flags            = $status_flags;
        $this->fixture_title           = $fixture_title;
    }

}
