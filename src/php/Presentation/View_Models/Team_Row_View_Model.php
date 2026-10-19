<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model representing a single team row in a team list or club competition list.
 */
final readonly class Team_Row_View_Model {
	/**
	 * @param int|string $id Team ID.
	 * @param string $title Team name/title.
	 * @param string $initials Abbreviated initials.
	 * @param string $link Team URL or modal target.
	 * @param string $data_attrs Pre-computed HTML data attributes for link.
	 * @param int|null $event_id Event ID if associated.
	 * @param string|null $suffix Suffix for element IDs (e.g. -{eventId}-{teamId}).
	 * @param string|null $calendar_url Calendar export URL if available.
	 * @param bool $is_team_entry Whether competition uses team entry mode.
	 * @param bool $is_player_entry Whether competition uses player entry mode.
	 * @param string|null $captain_name Captain display name.
	 * @param string|null $captain_contactno Captain telephone number.
	 * @param string|null $captain_contactemail Captain email address.
	 * @param string|null $match_day Match day of week.
	 * @param string|null $match_time Raw match time string.
	 * @param string|null $match_time_formatted Pre-formatted match time string.
	 * @param array<int, array{display_name: string, contactno: ?string, email: ?string}> $players List of player contacts for player entry.
	 * @param object|null $raw_team Underlying team object for backward compatibility.
	 */
	public function __construct(
		public int|string $id,
		public string $title,
		public string $initials = '',
		public string $link = '#',
		public string $data_attrs = '',
		public ?int $event_id = null,
		public ?string $suffix = null,
		public ?string $calendar_url = null,
		public bool $is_team_entry = false,
		public bool $is_player_entry = false,
		public ?string $captain_name = null,
		public ?string $captain_contactno = null,
		public ?string $captain_contactemail = null,
		public ?string $match_day = null,
		public ?string $match_time = null,
		public ?string $match_time_formatted = null,
		public array $players = [],
		public ?object $raw_team = null,
	) {
	}
}
