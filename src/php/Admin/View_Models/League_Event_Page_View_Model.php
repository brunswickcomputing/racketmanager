<?php
declare( strict_types=1 );
/**
 * League Event Page View Model
 *
 * View model for admin league event screen.
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class League_Event_Page_View_Model {

    /**
     * @param object          $event
     * @param string          $season
     * @param array           $leagues
     * @param array<int, int> $league_matches_map
     * @param int|null        $league_id
     * @param string          $league_title
     * @param object|null     $league_edit
     * @param string          $tab
     */
    public function __construct(
        public object $event,
        public string $season,
        public array $leagues = array(),
        public array $league_matches_map = array(),
        public ?int $league_id = null,
        public string $league_title = '',
        public ?object $league_edit = null,
        public string $tab = 'leagues',
    ) {
    }
}
