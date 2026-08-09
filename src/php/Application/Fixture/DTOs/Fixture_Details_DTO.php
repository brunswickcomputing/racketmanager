<?php

namespace Racketmanager\Application\Fixture\DTOs;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;

class Fixture_Details_DTO {
    public Fixture $fixture;
    public ?int $focus_player_id;
    public ?int $focus_team_id;
    public array $enriched_data = [];

    // Fields from Domain DTO to make it complete
    public ?League $league = null;
    public ?Event $event = null;
    public ?Competition $competition = null;
    public ?Team_Details_DTO $home_team = null;
    public ?Team_Details_DTO $away_team = null;
    public ?string $prev_home_fixture_title = null;
    public ?string $prev_away_fixture_title = null;
    public ?object $is_update_allowed = null;
    public ?string $link = null;
    public ?string $score_display = null;
    public array $status_flags = [];
    public ?string $fixture_title = null;
    public ?string $fixture_link = null;
    public ?string $home_approver_name = null;
    public ?string $away_approver_name = null;
    public ?string $home_captain_name = null;
    public ?string $away_captain_name = null;
    public ?string $home_captain_contactno = null;
    public ?string $away_captain_contactno = null;
    public ?string $home_captain_email = null;
    public ?string $away_captain_email = null;
    public array $rubbers = [];
    public array $club_players = [];

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
