<?php
/**
 * Admin championship final fixture row view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

/**
 * Preformatted fixture row for admin championship finals tab.
 */
final readonly class Admin_Championship_Final_Fixture_View_Model {

    public function __construct(
        public object $fixture_details,
        public object $fixture,
        public object $league,
        public object $event,
        public object $competition,
        public mixed $home_team,
        public mixed $away_team,
        public string $formatted_date,
        public string $formatted_time,
        public string $formatted_location,
        public string $match_link,
        public string $teams_label,
        public string $home_name = '',
        public string $away_name = '',
        public string $home_class = '',
        public string $away_class = '',
        public string $home_tip = '',
        public string $away_tip = '',
        public string $formatted_score = '-',
    ) {
    }

    public function __get( string $name ): mixed {
        return $this->fixture_details->$name ?? null;
    }

    public function __isset( string $name ): bool {
        return isset( $this->fixture_details->$name );
    }
}
