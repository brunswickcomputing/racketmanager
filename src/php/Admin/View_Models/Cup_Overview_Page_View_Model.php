<?php
declare( strict_types=1 );
/**
 * Cup Overview Page View Model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class Cup_Overview_Page_View_Model {

    /**
     * @param object               $competition
     * @param string               $season
     * @param object               $cup_season
     * @param object               $competition_overview
     * @param array<int, object>   $competition_events
     * @param array<string, string> $draw_sizes
     * @param array<string, string> $entry_urls
     * @param string               $tab
     */
    public function __construct(
        public object $competition,
        public string $season,
        public object $cup_season,
        public ?object $competition_overview,
        public array $competition_events,
        public array $draw_sizes,
        public array $entry_urls,
        public string $tab = 'overview',
    ) {
    }
}
