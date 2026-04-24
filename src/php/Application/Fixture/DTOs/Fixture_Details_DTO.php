<?php

namespace Racketmanager\Application\Fixture\DTOs;

use Racketmanager\Domain\Fixture\Fixture;

class Fixture_Details_DTO {
    public Fixture $fixture;
    public ?int $focus_player_id;
    public ?int $focus_team_id;
    public array $enriched_data = [];

    public function __construct(
        Fixture $fixture,
        ?int $focus_player_id = null,
        ?int $focus_team_id = null
    ) {
        $this->fixture = $fixture;
        $this->focus_player_id = $focus_player_id;
        $this->focus_team_id = $focus_team_id;
    }
}
