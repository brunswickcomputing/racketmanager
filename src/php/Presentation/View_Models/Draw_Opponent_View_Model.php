<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model representing an opponent (home or away) in a draw fixture row.
 */
final readonly class Draw_Opponent_View_Model {
    /**
     * @param Draw_Player_View_Model[] $players
     * @param Draw_Set_Score_View_Model[] $scores
     */
    public function __construct(
        public int|string|null $team_id,
        public string $team_name,
        public bool $has_team = false,
        public ?string $winner_class = null,
        public ?string $team_url = null,
        public bool $is_withdrawn = false,
        public string $withdrawn_text = '',
        public array $players = [],
        public ?string $match_message_class = null,
        public ?string $match_message_text = null,
        public ?string $match_status_class = null,
        public ?string $match_status_text = null,
        public array $scores = []
    ) {}
}
