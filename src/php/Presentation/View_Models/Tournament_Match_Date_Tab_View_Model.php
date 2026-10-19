<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

use Stringable;

/**
 * View model representing a tournament match date tab/option.
 */
final readonly class Tournament_Match_Date_Tab_View_Model implements Stringable {
	public function __construct(
		public string $date,
		public string $matches_link,
		public string $weekday,
		public string $day,
		public string $month,
		public string $formatted_select,
		public bool $is_selected = false,
		public string $selected_class = ''
	) {}

	public function __toString(): string {
		return $this->date;
	}
}
