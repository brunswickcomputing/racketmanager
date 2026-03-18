<?php

namespace Racketmanager\Services\Competition;

use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Domain\Fixture;
use Racketmanager\Domain\League;
use Racketmanager\Services\Championship_Manager;

/**
 * Service for managing knockout progression and bracket logic.
 * Extraction from Championship_Manager.
 */
final class Knockout_Progression_Service
{
    /**
     * Advance winning entrants to the next round.
     * 
     * @param Stage $stage
     * @param Fixture $fixture
     * @param League|null $league Optional league object to avoid legacy get_league call.
     * @return void
     */
    public function progress_winner(Stage $stage, Fixture $fixture, ?League $league = null): void
    {
        $championship = $stage->get_championship();
        if (!$championship) {
            return;
        }

        $final_round = $fixture->get_final();
        if (empty($final_round)) {
            return;
        }

        $round_data = $championship->get_finals($final_round);
        if (!$round_data) {
            return;
        }
        $round = $round_data['round'];

        $championship_manager = new Championship_Manager();
        $championship_manager->proceed($championship, $round, $league);
    }

    /**
     * Move losing entrants to a consolation draw if applicable.
     * 
     * @param Stage $stage
     * @param Fixture $fixture
     * @return void
     */
    public function handle_consolation(Stage $stage, Fixture $fixture): void
    {
        // TODO: Move logic from Championship_Manager::set_consolation_team.
    }
}
