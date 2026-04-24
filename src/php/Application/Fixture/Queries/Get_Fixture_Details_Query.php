<?php

namespace Racketmanager\Application\Fixture\Queries;

class Get_Fixture_Details_Query {
    public ?int $fixture_id;
    public array $slug_criteria;
    public ?int $focus_player_id;
    public ?int $focus_team_id;

    public function __construct(
        ?int $fixture_id = null,
        array $slug_criteria = [],
        ?int $focus_player_id = null,
        ?int $focus_team_id = null
    ) {
        $this->fixture_id = $fixture_id;
        $this->slug_criteria = $slug_criteria;
        $this->focus_player_id = $focus_player_id;
        $this->focus_team_id = $focus_team_id;
    }
}
