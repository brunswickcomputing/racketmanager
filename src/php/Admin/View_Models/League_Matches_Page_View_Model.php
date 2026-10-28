<?php
declare( strict_types=1 );
/**
 * League matches page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class League_Matches_Page_View_Model {

    /**
     * @param object $event
     * @param string $season
     * @param array<int,object> $matches
     * @param string $tab
     */
    public function __construct(
        public object $event,
        public string $season,
        public array $matches = array(),
        public string $tab = 'matches',
    ) {
    }
}
