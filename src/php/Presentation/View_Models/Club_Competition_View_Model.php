<?php
/**
 * Club Competition View Model
 *
 * @package RacketManager
 * @subpackage Presentation/View_Models
 */

declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable competition card on a club's competitions page.
 */
final readonly class Club_Competition_View_Model {
    /**
     * @param string $name Competition name.
     * @param string $url Club specific competition link.
     * @param array<int, Club_Event_View_Model> $events Events in which the club has teams.
     */
    public function __construct(
        public string $name,
        public string $url = '',
        public array $events = [],
    ) {}
}
