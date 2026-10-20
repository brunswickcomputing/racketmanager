<?php
/**
 * Club Event View Model
 *
 * @package RacketManager
 * @subpackage Presentation/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable event row on a club's competitions page.
 */
final readonly class Club_Event_View_Model {
    /**
     * @param int|string $id Event identifier (used to key the team rows).
     * @param string $name Event name.
     * @param string $url Club specific event link, empty when the event has no current season.
     * @param array<int, mixed> $team_rows Pre-built team rows for the club in this event.
     */
    public function __construct(
        public int|string $id,
        public string $name,
        public string $url = '',
        public array $team_rows = [],
    ) {}
}
