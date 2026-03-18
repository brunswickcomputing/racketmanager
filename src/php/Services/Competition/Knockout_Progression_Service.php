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
class Knockout_Progression_Service
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
     * Reset progression for a fixture (removing winners/losers from next round).
     * 
     * @param Stage $stage
     * @param Fixture $fixture
     * @param League|null $league
     * @return void
     */
    public function reset_progression(Stage $stage, Fixture $fixture, ?\Racketmanager\Domain\League $league = null): void
    {
        // TODO: this does not work - need to be fixed
        $championship = $stage->get_championship();
        if (!$championship) {
            return;
        }

        $final_round = $fixture->get_final();
        if (empty($final_round) || 'final' === $final_round) {
            return;
        }

        if (!$league) {
            // No choice but to use legacy here if league is not provided, 
            // but we'll prioritize the passed object.
            $league = \Racketmanager\get_league($championship->league_id());
        }

        if (!$league) {
            return;
        }

        $round_data = $championship->get_finals($final_round);
        if (!$round_data) {
            return;
        }
        $round = $round_data['round'];
        $next_round = $round + 1;
        $next_round_key = $championship->final_key_for_round($next_round);

        $match_args = [
            'final'   => $final_round,
            'orderby' => ['id' => 'ASC'],
        ];

        if (!empty($fixture->get_leg())) {
            $match_args['leg'] = $fixture->get_leg();
        }

        $current_round_matches = $league->get_matches($match_args);
        $found_index = -1;
        foreach ($current_round_matches as $i => $m) {
            if ($fixture->get_id() === $m->id) {
                $found_index = $i;
                break;
            }
        }

        if ($found_index === -1) {
            return;
        }

        $next_round_match_index = floor($found_index / 2);
        $next_match_args = [
            'final' => $next_round_key,
        ];
        if (!empty($fixture->get_leg())) {
            $next_match_args['leg'] = 1;
        }

        $next_round_matches = $league->get_matches($next_match_args);
        if ($next_round_matches && isset($next_round_matches[$next_round_match_index])) {
            $next_match = $next_round_matches[$next_round_match_index];
            $championship_manager = new Championship_Manager();
            $championship_manager->set_teams($next_match, '0', '0');
        }
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
