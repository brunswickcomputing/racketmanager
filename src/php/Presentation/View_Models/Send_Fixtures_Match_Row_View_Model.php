<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model representing a single match row for templates/email/send-fixtures.php.
 */
final readonly class Send_Fixtures_Match_Row_View_Model {
	/**
	 * @param string $round_display
	 * @param string $date_display
	 * @param string $day_display
	 * @param string $time_display
	 * @param string $home_team_title
	 * @param string $away_team_title
	 * @param bool $is_home
	 * @param bool $is_away
	 * @param int|null $id
	 * @param int|null $match_day
	 * @param string|null $date
	 * @param string|null $start_time
	 * @param int|string|null $home_team
	 * @param int|string|null $away_team
	 * @param array|null $teams
	 */
	public function __construct(
		public string $round_display = '',
		public string $date_display = '',
		public string $day_display = '',
		public string $time_display = '',
		public string $home_team_title = '',
		public string $away_team_title = '',
		public bool $is_home = false,
		public bool $is_away = false,
		public ?int $id = null,
		public ?int $match_day = null,
		public ?string $date = null,
		public ?string $start_time = null,
		public int|string|null $home_team = null,
		public int|string|null $away_team = null,
		public ?array $teams = null,
	) {}
}
