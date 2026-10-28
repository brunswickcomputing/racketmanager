<?php
declare( strict_types=1 );
/**
 * League Constitution Page View Model
 *
 * View model for admin league constitution screen.
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class League_Constitution_Page_View_Model {

    /**
     * @param object                $event
     * @param string                $season
     * @param array                 $seasons
     * @param array                 $leagues
     * @param array                 $event_seasons
     * @param int                   $num_teams
     * @param array<int, int>       $league_active_teams
     * @param array                 $teams
     * @param string                $constitution_action
     * @param bool                  $constitution_exists
     * @param bool                  $updates_allowed
     * @param string                $latest_season
     * @param string                $latest_event_season
     * @param array<string, string> $standing_status
     * @param bool                  $has_content
     * @param int|null              $event_id
     */
    public function __construct(
        public object $event,
        public string $season,
        public array $seasons = array(),
        public array $leagues = array(),
        public array $event_seasons = array(),
        public int $num_teams = 0,
        public array $league_active_teams = array(),
        public array $teams = array(),
        public string $constitution_action = 'update',
        public bool $constitution_exists = false,
        public bool $updates_allowed = false,
        public string $latest_season = '',
        public string $latest_event_season = '',
        public array $standing_status = array(),
        public bool $has_content = false,
        public ?int $event_id = null,
    ) {
    }
}
