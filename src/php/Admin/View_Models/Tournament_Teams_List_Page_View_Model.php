<?php
/**
 * Tournament teams list (add teams) page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class Tournament_Teams_List_Page_View_Model {

    /**
     * @param array<int,object> $teams
     */
    public function __construct(
        public object $league,
        public int $league_id,
        public string $season,
        public ?int $tournament_id,
        public ?object $tournament,
        public array $teams,
        public ?string $type,
    ) {
    }
}
