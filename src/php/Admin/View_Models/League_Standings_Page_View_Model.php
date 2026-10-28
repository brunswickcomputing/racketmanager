<?php
declare( strict_types=1 );
/**
 * League standings page view model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class League_Standings_Page_View_Model {

    /**
     * @param object       $league
     * @param string       $season
     * @param string       $tab
     * @param array        $teams
     * @param bool         $cup
     * @param array        $finals
     * @param array        $matches
     * @param array        $crosstable_teams
     * @param array        $crosstable_cells
     * @param int|false    $match_day
     * @param string       $group
     * @param int|false    $team_id
     * @param array        $options
     * @param array        $seasons
     * @param array        $menu
     * @param string       $league_mode
     * @param object|null  $tournament
     */
    public function __construct(
        public object $league,
        public string $season,
        public string $tab = 'standings',
        public array $teams = array(),
        public bool $cup = false,
        public array $finals = array(),
        public array $matches = array(),
        public array $crosstable_teams = array(),
        public array $crosstable_cells = array(),
        public int|false $match_day = false,
        public string $group = '',
        public int|false $team_id = false,
        public array $options = array(),
        public array $seasons = array(),
        public array $menu = array(),
        public string $league_mode = '',
        public ?object $tournament = null,
    ) {
    }
}
