<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model representing a single match row for templates/includes/matches-teams-match.php.
 */
final readonly class Team_Match_Row_View_Model {
	/**
	 * @param int|string|null $id
	 * @param bool $is_selected
	 * @param bool $show_header
	 * @param string $league_title
	 * @param string|null $match_link
	 * @param string|null $final_name
	 * @param int|null $match_day
	 * @param string|null $match_day_text
	 * @param int|null $leg
	 * @param string|null $leg_text
	 * @param bool $show_date
	 * @param string|null $date
	 * @param string|null $formatted_date
	 * @param string|null $status_message
	 * @param bool $is_highlighted
	 * @param string|null $status_class
	 * @param string|null $status_text
	 * @param bool $is_pending
	 * @param string|null $score_class
	 * @param string|null $score_text
	 * @param string $home_name
	 * @param bool $home_is_withdrawn
	 * @param string|null $home_withdrawn_title
	 * @param string|null $home_points
	 * @param string $away_name
	 * @param bool $away_is_withdrawn
	 * @param string|null $away_withdrawn_title
	 * @param string|null $away_points
	 * @param bool $user_can_update
	 * @param string|null $update_link
	 * @param string $pencil_icon_url
	 * @param bool $is_tournament
	 * @param object|null $dto
	 * @param mixed $match
	 */
	public function __construct(
		public int|string|null $id = null,
		public bool $is_selected = false,
		public bool $show_header = true,
		public string $league_title = '',
		public ?string $match_link = null,
		public ?string $final_name = null,
		public ?int $match_day = null,
		public ?string $match_day_text = null,
		public ?int $leg = null,
		public ?string $leg_text = null,
		public bool $show_date = true,
		public ?string $date = null,
		public ?string $formatted_date = null,
		public ?string $status_message = null,
		public bool $is_highlighted = false,
		public ?string $status_class = null,
		public ?string $status_text = null,
		public bool $is_pending = false,
		public ?string $score_class = null,
		public ?string $score_text = null,
		public string $home_name = '',
		public bool $home_is_withdrawn = false,
		public ?string $home_withdrawn_title = null,
		public ?string $home_points = null,
		public string $away_name = '',
		public bool $away_is_withdrawn = false,
		public ?string $away_withdrawn_title = null,
		public ?string $away_points = null,
		public bool $user_can_update = false,
		public ?string $update_link = null,
		public string $pencil_icon_url = '',
		public bool $is_tournament = false,
		public ?object $dto = null,
		public mixed $match = null
	) {}
}
