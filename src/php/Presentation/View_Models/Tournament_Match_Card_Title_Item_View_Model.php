<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model representing a single title/player item in a match card row.
 */
final readonly class Tournament_Match_Card_Title_Item_View_Model {
	/**
	 * @param string $name
	 * @param string|null $link
	 * @param bool $is_link
	 * @param int|string|null $tournament_id
	 * @param int|string|null $player_id
	 */
	public function __construct(
		public string $name,
		public ?string $link = null,
		public bool $is_link = false,
		public int|string|null $tournament_id = null,
		public int|string|null $player_id = null
	) {}
}
