<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model representing a single set score on a tournament match card.
 */
final readonly class Tournament_Match_Card_Set_View_Model {
	/**
	 * @param string $player1_games
	 * @param string $player2_games
	 * @param string $player1_winner_class ' winner'|''
	 * @param string $player2_winner_class ' winner'|''
	 * @param string|null $tiebreak
	 * @param bool $show_p1_tiebreak
	 * @param bool $show_p2_tiebreak
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
