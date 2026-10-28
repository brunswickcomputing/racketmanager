<?php
declare( strict_types=1 );
/**
 * Setup Page View Model
 *
 * View model for admin league/event setup screen across leagues and cups.
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class Setup_Page_View_Model {

    /**
     * @param object       $competition
     * @param string|int   $season
     * @param array|object $current_season
     * @param object|null  $event
     * @param object|null  $league
     * @param int          $match_count
     * @param array        $match_dates
     */
    public function __construct(
        public object $competition,
        public string|int $season,
        public array|object $current_season,
        public ?object $event = null,
        public ?object $league = null,
        public int $match_count = 0,
        public array $match_dates = array(),
    ) {
    }
}
