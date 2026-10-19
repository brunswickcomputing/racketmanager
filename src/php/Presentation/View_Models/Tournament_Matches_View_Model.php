<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model representing tournament matches and match date selection tabs.
 */
final readonly class Tournament_Matches_View_Model {
	/**
	 * @param int|string $tournament_id
	 * @param string $tournament_name
	 * @param string|null $current_match_date
	 * @param array<Tournament_Match_Date_Tab_View_Model> $match_dates
	 * @param array<Tournament_Match_Group_View_Model> $match_groups
	 * @param bool $has_many_dates
	 * @param bool $has_matches
	 */
	public function __construct(
		public int|string $tournament_id,
		public string $tournament_name,
		public ?string $current_match_date = null,
		public array $match_dates = [],
		public array $match_groups = [],
		public bool $has_many_dates = false,
		public bool $has_matches = false
	) {}
}
