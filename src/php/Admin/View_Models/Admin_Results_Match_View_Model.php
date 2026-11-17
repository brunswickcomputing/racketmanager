<?php
/**
 * Admin results match row view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

/**
 * Preformatted match row for admin results tabs.
 */
final class Admin_Results_Match_View_Model {

    /**
     * Backward-compatible property hook for legacy callers reading $row->league_title.
     */
    public string $league_title {
        final get => $this->league_name;
    }

    public function __construct(
        public readonly int $league_id,
        public readonly string $league_name,
        public readonly string $formatted_date,
        public readonly string $match_title,
        public readonly string $match_link,
        public readonly string $result_link,
        public readonly string $confirmed_display,
        public readonly string $score,
        public readonly string $tooltip,
    ) {
    }
}
