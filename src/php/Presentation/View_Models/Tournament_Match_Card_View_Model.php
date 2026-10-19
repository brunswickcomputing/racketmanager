<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model representing a tournament match card for templates/tournament/match.php.
 */
final readonly class Tournament_Match_Card_View_Model {
	/**
	 * @param int|string|null $id
	 * @param string $match_display_class
	 * @param bool $is_selected
	 * @param string $selected_class
	 * @param string $final_name
	 * @param string|null $draw_link
	 * @param string $league_title
	 * @param bool $show_draw_link
	 * @param bool $show_header_date
	 * @param bool $header_play_by
	 * @param string|null $header_formatted_date
	 * @param string|null $header_time_display
	 * @param bool $show_header_location
	 * @param string|null $location
	 * @param array<Tournament_Match_Card_Opponent_View_Model> $opponents
	 * @param array<Tournament_Match_Card_Set_View_Model> $sets
	 * @param bool $show_update_button
	 * @param string|null $update_link
	 * @param string $pencil_icon_url
	 * @param string|null $footer_location
	 * @param bool $show_footer_date
	 * @param bool $footer_play_by
	 * @param string|null $footer_formatted_date
	 * @param string|null $footer_time_display
	 */
	public function __construct(
		public int|string|null $id = null,
		public string $match_display_class = '',
		public bool $is_selected = false,
		public string $selected_class = '',
		public string $final_name = '',
		public ?string $draw_link = null,
		public string $league_title = '',
		public bool $show_draw_link = false,
		public bool $show_header_date = false,
		public bool $header_play_by = false,
		public ?string $header_formatted_date = null,
		public ?string $header_time_display = null,
		public bool $show_header_location = false,
		public ?string $location = null,
		public array $opponents = [],
		public array $sets = [],
		public bool $show_update_button = false,
		public ?string $update_link = null,
		public string $pencil_icon_url = '',
		public ?string $footer_location = null,
		public bool $show_footer_date = false,
		public bool $footer_play_by = false,
		public ?string $footer_formatted_date = null,
		public ?string $footer_time_display = null,
		public bool $is_missing = false
	) {}
}
