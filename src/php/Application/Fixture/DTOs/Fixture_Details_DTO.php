<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Fixture\DTOs;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;

/**
 * Data transfer object for carrying detailed fixture information and related context.
 *
 * Provides a single structured payload for fixture detail workflows, including the
 * fixture itself, optional focus identifiers, associated competition context, team
 * details, display metadata, status information, approvals, captain contact details,
 * rubbers, players, and enrichment data.
 */
class Fixture_Details_DTO {
    public string $league_name {
    final get => $this->league?->name ?? '';
    }

    public string $league_title {
    final get => $this->league?->title ?? '';
    }

    public Fixture $fixture;
    public ?int $focus_player_id;
    public ?int $focus_team_id;
    public array $enriched_data = [];

    public ?League $league                  = null;
    public ?Event $event                    = null;
    public ?Competition $competition        = null;
    public ?Team_Details_DTO $home_team     = null;
    public ?Team_Details_DTO $away_team     = null;
    public ?string $prev_home_fixture_title = null;
    public ?string $prev_away_fixture_title = null;
    public ?object $is_update_allowed       = null;
    public ?string $link                    = null;
    public ?string $score_display           = null;
    public array $status_flags              = [];
    public ?string $fixture_title           = null;
    public ?string $fixture_link            = null;
    public ?string $home_approver_name      = null;
    public ?string $away_approver_name      = null;
    public ?string $home_captain_name       = null;
    public ?string $away_captain_name       = null;
    public ?string $home_captain_contactno  = null;
    public ?string $away_captain_contactno  = null;
    public ?string $home_captain_email      = null;
    public ?string $away_captain_email      = null;
    public array $rubbers                   = [];
    public array $club_players              = [];

    /**
     * Initialises the instance with the fixture and optional focus identifiers.
     *
     * @param Fixture  $fixture The fixture associated with the instance.
     * @param int|null $focus_player_id The optional identifier of the focused player.
     * @param int|null $focus_team_id The optional identifier of the focused team.
     *
     * @return void
     */
    public function __construct(
        Fixture $fixture,
        ?int $focus_player_id = null,
        ?int $focus_team_id = null
    ) {
        $this->fixture         = $fixture;
        $this->focus_player_id = $focus_player_id;
        $this->focus_team_id   = $focus_team_id;
    }
}
