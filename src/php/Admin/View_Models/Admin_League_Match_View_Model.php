<?php
/**
 * Admin league match row view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\View_Models;

/**
 * Preformatted match row for admin league matches tab.
 */
final readonly class Admin_League_Match_View_Model {

    public function __construct(
        public object $match,
        public string $formatted_date,
        public string $formatted_time,
        public string $edit_url,
        public string $formatted_location,
        public ?int $id = null,
        public mixed $home_team = null,
        public mixed $away_team = null,
        public ?string $group = null,
        public ?string $match_title = null,
        public mixed $custom = null,
        public mixed $home_points = null,
        public mixed $away_points = null,
        public ?string $hour = null,
        public ?string $minutes = null,
        public mixed $date = null,
        public ?string $location = null,
    ) {
    }

    public function __get( string $name ): mixed {
        return $this->match->$name ?? null;
    }

    public function __isset( string $name ): bool {
        return isset( $this->match->$name );
    }
}
