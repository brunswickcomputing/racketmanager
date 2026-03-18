<?php

namespace Racketmanager\Services\Standings;

use Racketmanager\Domain\Competition\Stage;

/**
 * Service for calculating and managing competition standings (league tables).
 * Extraction from League_Service and Racketmanager_Match.
 */
final class Standings_Service
{
    /**
     * Calculate standings for a given stage and season.
     * 
     * @param Stage $stage
     * @param int $season
     * @return array Standings data.
     */
    public function calculate_standings(Stage $stage, int $season): array
    {
        // TODO: Move logic from League_Service::get_league_standings and rank_teams_by_points.
        return [];
    }

    /**
     * Update the stored rankings for a set of teams.
     * 
     * @param array $standings
     * @return void
     */
    public function update_rankings(array $standings): void
    {
        // TODO: Move logic from League_Service::update_ranking.
    }
}
