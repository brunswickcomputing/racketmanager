<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model for team details presentation.
 */
final readonly class Team_Details_View_Model {
	/**
	 * @param bool $show_wtn Whether WTN rating display is enabled.
	 * @param string|null $format WTN match format (e.g. 'S' or 'D').
	 * @param string|null $help_text Tooltip / help text for WTN.
	 * @param string $standings_link Canonical URL for standings.
	 * @param string $club_link Canonical URL for the club page.
	 * @param array<int|string, string> $player_links Canonical URLs for team players keyed by player ID.
	 * @param string|null $match_time_formatted Localised match start time.
	 */
	public function __construct(
		public bool $show_wtn = false,
		public ?string $format = null,
		public ?string $help_text = null,
		public string $standings_link = '',
		public string $club_link = '',
		public array $player_links = [],
		public ?string $match_time_formatted = null,
	) {
	}
}
