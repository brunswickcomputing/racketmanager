<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model representing a single set score on a tournament match card.
 */
final readonly class Tournament_Match_Card_Set_View_Model {
    /**
     * Initialises the game display data for both players.
     *
     * @param string      $player1_games The games value for player 1.
     * @param string      $player2_games The games value for player 2.
     * @param string      $player1_winner_class The winner CSS class for player 1.
     * @param string      $player2_winner_class The winner CSS class for player 2.
     * @param string|null $tiebreak The tiebreak value, if available.
     * @param bool        $show_p1_tiebreak Whether to show the tiebreak value for player 1.
     * @param bool        $show_p2_tiebreak Whether to show the tiebreak value for player 2.
     *
     * @return void
     */
    public function __construct(
        public string $player1_games,
        public string $player2_games,
        public string $player1_winner_class = '',
        public string $player2_winner_class = '',
        public ?string $tiebreak = null,
        public bool $show_p1_tiebreak = false,
        public bool $show_p2_tiebreak = false
    ) {}
}
