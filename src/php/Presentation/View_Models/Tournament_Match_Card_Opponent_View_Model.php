<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model representing an opponent row (home or away) on a tournament match card.
 */
final readonly class Tournament_Match_Card_Opponent_View_Model {
	/**
	 * @param string $key 'home'|'away'
	 * @param bool $is_winner
	 * @param string $winner_class ' winner'|''
	 * @param array<Tournament_Match_Card_Title_Item_View_Model> $titles
	 * @param bool $show_w_badge
	 * @param bool $show_l_badge
	 * @param string|null $warning_message 'Walkover'|'Retired'|'Not played'|null
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
