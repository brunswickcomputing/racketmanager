<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

use Stringable;

/**
 * View model representing a tournament match date tab/option.
 */
final readonly class Tournament_Match_Date_Tab_View_Model implements Stringable {
    /**
     * Creates a date selection item for a matches link.
     *
     * @param string $date The date value.
     * @param string $matches_link The link to the matches for the date.
     * @param string $weekday The weekday label.
     * @param string $day The day label.
     * @param string $month The month label.
     * @param string $formatted_select The formatted date selection value.
     * @param bool   $is_selected Whether the date is currently selected.
     * @param string $selected_class The CSS class applied when selected.
     *
     * @return void
     */
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

    /**
     * Returns the date as a string.
     *
     * @return string The string representation of the date.
     */
    public function __toString(): string {
        return $this->date;
    }
}
