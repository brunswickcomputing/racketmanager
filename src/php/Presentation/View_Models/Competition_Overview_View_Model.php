<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model for rendering competition overview.
 */
final readonly class Competition_Overview_View_Model {
    /**
     * @param string|null $venue_name
     * @param int $num_events
     * @param int $num_teams
     * @param int|null $num_players
     * @param string|null $grade
     * @param array<int, array{key: string, label: string, date: string, item_class: string, current_class: string|null}> $timeline_items
     */
    public function __construct(
        public ?string $venue_name = null,
        public int $num_events = 0,
        public int $num_teams = 0,
        public ?int $num_players = null,
        public ?string $grade = null,
        public array $timeline_items = []
    ) {}
}
