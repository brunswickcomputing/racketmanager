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
final readonly class Admin_Results_Match_View_Model {

    public function __construct(
        public int $league_id,
        public string $league_title,
        public string $formatted_date,
        public string $match_title,
        public string $match_link,
        public string $result_link,
        public string $confirmed_display,
        public string $score,
        public string $tooltip,
    ) {
    }
}
