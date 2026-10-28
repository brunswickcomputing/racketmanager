<?php
declare( strict_types=1 );
/**
 * Teams List Page View Model
 *
 * View model for admin teams list view across tournaments, leagues, and cups.
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class Teams_List_Page_View_Model {

    /**
     * @param object       $league
     * @param int          $league_id
     * @param string       $season
     * @param int|null     $tournament_id
     * @param object|null  $tournament
     * @param array        $teams
     * @param string|null  $type
     */
    public function __construct(
        public object $league,
        public int $league_id,
        public string $season,
        public ?int $tournament_id = null,
        public ?object $tournament = null,
        public array $teams = array(),
        public ?string $type = null,
    ) {
    }
}
