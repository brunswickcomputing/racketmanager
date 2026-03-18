<?php

namespace Racketmanager\Services\Competition;

use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Domain\Fixture;

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
     * @return void
     */
    public function progress_winner(Stage $stage, Fixture $fixture): void
    {
        // TODO: Move logic from Championship_Manager::proceed.
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
