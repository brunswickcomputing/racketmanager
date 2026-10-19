<?php
declare( strict_types=1 );

/**
 * Plan Final Card View Model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

/**
 * Logic-less, pre-computed draggable card for a final match/fixture in the cup and tournament planners.
 */
final readonly class Plan_Final_Card_View_Model {

    /**
     * @param int         $id               Fixture id (used for the draggable `match-{id}` element).
     * @param string      $league_title     League/draw title.
     * @param string      $btn_type         Bootstrap button type (`success` when both teams are known, otherwise `warning`).
     * @param string      $home_title       Home team name or placeholder title.
     * @param string      $away_title       Away team name or placeholder title.
     * @param bool        $is_home_pending  True when the home team is a placeholder (not yet decided).
     * @param bool        $is_away_pending  True when the away team is a placeholder (not yet decided).
     * @param string|null $home_club        Home club shortcode (only when the home team is known).
     * @param string|null $away_club        Away club shortcode (only when the away team is known).
     */
    public function __construct(
        public int $id,
        public string $league_title,
        public string $btn_type,
        public string $home_title,
        public string $away_title,
        public bool $is_home_pending,
        public bool $is_away_pending,
        public ?string $home_club = null,
        public ?string $away_club = null,
    ) {
    }
}
