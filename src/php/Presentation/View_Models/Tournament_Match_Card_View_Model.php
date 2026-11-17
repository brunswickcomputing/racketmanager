<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model representing a tournament match card for templates/tournament/match.php.
 */
final class Tournament_Match_Card_View_Model {

	public readonly string $league_name;

	/**
	 * Backward-compatible property hook for legacy callers reading $card->league_title.
	 */
	public string $league_title {
		final get => $this->league_name;
	}

	/**
	 * @param int|string|null $id
	 * @param string $match_display_class
	 * @param bool $is_selected
	 * @param string $selected_class
	 * @param string $final_name
	 * @param string|null $draw_link
	 * @param string $league_name
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
	 * @param bool $is_missing
	 * @param string|null $league_title Legacy alias for $league_name.
	 */
	public function __construct(
		public readonly int|string|null $id = null,
		public readonly string $match_display_class = '',
		public readonly bool $is_selected = false,
		public readonly string $selected_class = '',
		public readonly string $final_name = '',
		public readonly ?string $draw_link = null,
		string $league_name = '',
		public readonly bool $show_draw_link = false,
		public readonly bool $show_header_date = false,
		public readonly bool $header_play_by = false,
		public readonly ?string $header_formatted_date = null,
		public readonly ?string $header_time_display = null,
		public readonly bool $show_header_location = false,
		public readonly ?string $location = null,
		public readonly array $opponents = [],
		public readonly array $sets = [],
		public readonly bool $show_update_button = false,
		public readonly ?string $update_link = null,
		public readonly string $pencil_icon_url = '',
		public readonly ?string $footer_location = null,
		public readonly bool $show_footer_date = false,
		public readonly bool $footer_play_by = false,
		public readonly ?string $footer_formatted_date = null,
		public readonly ?string $footer_time_display = null,
		public readonly bool $is_missing = false,
		?string $league_title = null,
	) {
		$this->league_name = '' !== $league_name ? $league_name : (string) ( $league_title ?? '' );
	}
}
