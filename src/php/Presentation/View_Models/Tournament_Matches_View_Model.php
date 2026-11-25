<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model representing tournament matches and match date selection tabs.
 */
final readonly class Tournament_Matches_View_Model {
    /**
     * Initialises a tournament match overview instance.
     *
     * @param int|string  $tournament_id Tournament identifier.
     * @param string      $tournament_name Tournament name.
     * @param string|null $current_match_date Current match date, or null when not set.
     * @param array       $match_dates Available match dates.
     * @param array       $match_groups Grouped match data.
     * @param bool        $has_many_dates Whether the tournament has multiple match dates.
     * @param bool        $has_matches Whether the tournament has matches.
     *
     * @return void
     */
    public function __construct(
        public int|string $tournament_id,
        public string $tournament_name,
        public ?string $current_match_date = null,
        public array $match_dates = [],
        public array $match_groups = [],
        public bool $has_many_dates = false,
        public bool $has_matches = false
    ) {}
}
