<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model representing a group of team matches for templates/includes/matches-team-list-group.php.
 */
final readonly class Team_Matches_Group_View_Model {
	/**
	 * @param string $key Group key (e.g. '2026-10-18', 'Division 1', '1')
	 * @param string $header Display header text
	 * @param array $matches List of Team_Match_Row_View_Model | Tournament_Match_Card_View_Model
	 */
	public function __construct(
		public string $key,
		public string $header,
		public array $matches = []
	) {}
}
