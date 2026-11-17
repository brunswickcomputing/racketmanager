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

final class League_Event_Page_View_Model {

    public readonly string $league_name;

    /**
     * Backward-compatible property hook for legacy callers reading $vm->league_title.
     */
    public string $league_title {
        final get => $this->league_name;
    }

    /**
     * @param object          $event
     * @param string          $season
     * @param array           $leagues
     * @param array<int, int> $league_matches_map
     * @param int|null        $league_id
     * @param string          $league_name
     * @param object|null     $league_edit
     * @param string          $tab
     * @param string|null     $league_title
     */
    public function __construct(
        public readonly object $event,
        public readonly string $season,
        public readonly array $leagues = array(),
        public readonly array $league_matches_map = array(),
        public readonly ?int $league_id = null,
        string $league_name = '',
        public readonly ?object $league_edit = null,
        public readonly string $tab = 'leagues',
        ?string $league_title = null,
    ) {
        $this->league_name = '' !== $league_name ? $league_name : (string) ( $league_title ?? '' );
    }
}
