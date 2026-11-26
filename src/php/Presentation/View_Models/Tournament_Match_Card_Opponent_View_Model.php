<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model representing an opponent row (home or away) on a tournament match card.
 */
final readonly class Tournament_Match_Card_Opponent_View_Model {
    /**
     * Initialises the instance with display and winner-related configuration.
     *
     * @param string      $key Unique key identifying the item.
     * @param bool        $is_winner Whether the item is marked as a winner.
     * @param string      $winner_class CSS class or marker associated with the winner state.
     * @param array       $titles List of titles associated with the item.
     * @param bool        $show_w_badge Whether to show the winner badge.
     * @param bool        $show_l_badge Whether to show the loser badge.
     * @param string|null $warning_message Optional warning message to display.
     *
     * @return void
     */
    public function __construct(
        public string $key,
        public bool $is_winner = false,
        public string $winner_class = '',
        public array $titles = [],
        public bool $show_w_badge = false,
        public bool $show_l_badge = false,
        public ?string $warning_message = null
    ) {}
}
