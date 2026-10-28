<?php
declare( strict_types=1 );
/**
 * League Overview Page View Model
 *
 * View model for admin league season overview screen.
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

final readonly class League_Overview_Page_View_Model {

    /**
     * @param object                $competition
     * @param string                $season
     * @param object                $competition_overview
     * @param array                 $competition_events
     * @param object                $current_season
     * @param array<string, string> $entry_urls
     * @param string                $tab
     */
    public function __construct(
        public object $competition,
        public string $season,
        public object $competition_overview,
        public array $competition_events,
        public object $current_season,
        public array $entry_urls = array(),
        public string $tab = 'overview',
    ) {
    }
}
