<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Player;

use Racketmanager\Domain\Fixture;
use Racketmanager\Domain\League;

/**
 * Context for player validation checks.
 */
class Validation_Context_DTO {
    public Fixture $fixture;
    public League $league;
    public ?array $competition_season;
    public ?array $event_season;
    public array $options;
    public int $team_id;
    public int $rubber_id;

    public function __construct( Fixture $fixture, League $league, ?array $competition_season, ?array $event_season, array $options, int $team_id, int $rubber_id ) {
        $this->fixture            = $fixture;
        $this->league             = $league;
        $this->competition_season = $competition_season;
        $this->event_season       = $event_season;
        $this->options            = $options;
        $this->team_id            = $team_id;
        $this->rubber_id          = $rubber_id;
    }
}
