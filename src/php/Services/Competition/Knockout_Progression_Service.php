<?php

namespace Racketmanager\Services\Competition;

use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Services\Championship_Manager;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\Event_Repository;

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
     * @param League|null $league Optional league object to avoid the legacy get_league call.
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
     * Reset progression for a fixture (removing winners/losers from the next round).
     *
     * @param Stage $stage
     * @param Fixture $fixture
     * @param League|null $league
     * @return void
     */
    public function reset_progression(Stage $stage, Fixture $fixture, ?League $league = null): void
    {
        $championship = $stage->get_championship();
        $final_round = $fixture->get_final();
        
        if (!$championship || empty($final_round) || 'final' === $final_round) {
            return;
        }

        if (!$league) {
            $league = (new League_Repository())->find_by_id($championship->league_id());
        }

        if ($league && $fixture->get_season() && $league->get_season() !== $fixture->get_season()) {
            $league->set_season($fixture->get_season(), true);
            // Re-fetch championship since season change affects round counts
            $championship = $league->championship;
        }

        if (!$championship) {
            return;
        }

        $round_data = $championship->get_finals($final_round);
        if (!$round_data) {
            return;
        }
        
        $current_round_num = $round_data['round'];
        $next_round_key = $championship->final_key_for_round($current_round_num + 1);
        
        $fixture_repository = new Fixture_Repository();
        
        if ($league && $next_round_key) {
            $found_index = $this->find_fixture_index($fixture_repository, $league, $fixture, $final_round);
            if ($found_index !== -1) {
                $this->update_next_round_fixtures($fixture_repository, $league, $fixture, $next_round_key, $final_round, $found_index);
            }
        }
    }

    /**
     * Find the 0-based index of the fixture in its current round.
     *
     * @param Fixture_Repository $repository
     * @param League $league
     * @param Fixture $fixture
     * @param string $final_round
     * @return int
     */
    private function find_fixture_index(Fixture_Repository $repository, League $league, Fixture $fixture, string $final_round): int
    {
        $current_round_fixtures = $repository->find_by_league_and_final(
            $league->get_id(),
            $fixture->get_season(),
            $final_round,
            $fixture->get_leg() ?: null
        );

        foreach ($current_round_fixtures as $i => $m) {
            if ($fixture->get_id() === $m->get_id()) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Update fixtures in the next round with placeholders.
     *
     * @param Fixture_Repository $repository
     * @param League $league
     * @param Fixture $fixture
     * @param string $next_round_key
     * @param string $final_round
     * @param int $found_index
     * @return void
     */
    private function update_next_round_fixtures(
        Fixture_Repository $repository,
        League $league,
        Fixture $fixture,
        string $next_round_key,
        string $final_round,
        int $found_index
    ): void {
        $next_round_match_index = (int) floor($found_index / 2);
        $is_home = ($found_index % 2 === 0);
        $placeholder = '1_' . $final_round . '_' . ($found_index + 1);

        $next_round_fixtures = $repository->find_by_league_and_final(
            $league->get_id(),
            $fixture->get_season(),
            $next_round_key
        );

        if (!isset($next_round_fixtures[$next_round_match_index])) {
            return;
        }

        $next_fixture = $next_round_fixtures[$next_round_match_index];
        $this->update_fixture_team($next_fixture, $is_home, $placeholder);
        $repository->save($next_fixture);

        if ($next_fixture->get_linked_match()) {
            $linked_fixture = $repository->find_by_id($next_fixture->get_linked_match());
            if ($linked_fixture) {
                $this->update_fixture_team($linked_fixture, $is_home, $placeholder);
                $repository->save($linked_fixture);
            }
        }
    }

    /**
     * Set the home or away team of a fixture.
     *
     * @param Fixture $fixture
     * @param bool $is_home
     * @param string $team_id
     * @return void
     */
    private function update_fixture_team(Fixture $fixture, bool $is_home, string $team_id): void
    {
        if ($is_home) {
            $fixture->set_home_team($team_id);
        } else {
            $fixture->set_away_team($team_id);
        }
    }

    /**
     * Move losing entrants to a consolation draw if applicable.
     *
     * @param Stage $stage
     * @param Fixture $fixture
     * @param League|null $league
     * @return void
     */
    public function handle_consolation(Stage $stage, Fixture $fixture, ?League $league = null): void
    {
        $championship = $stage->get_championship();
        if (!$championship || $championship->is_consolation()) {
            return;
        }

        // Only handle consolation for the first few rounds as per legacy logic (round < 3)
        $final_round = $fixture->get_final();
        $round_data = $championship->get_finals($final_round);
        if (!$round_data || $round_data['round'] >= 3) {
            return;
        }

        if (!$league) {
            $league_repository = new League_Repository();
            $league = $league_repository->find_by_id($championship->league_id());
        }

        if (!$league) {
            return;
        }

        $event_repository = new Event_Repository();
        $event = $event_repository->find_by_id($league->get_event_id());
        if (!$event) {
            return;
        }

        // Identify consolation leagues in this event
        $consolation_league_ids = $event->get_leagues(['consolation' => true]);
        if (empty($consolation_league_ids)) {
            return;
        }

        $loser_id = $fixture->get_loser_id();
        if (empty($loser_id)) {
            return;
        }

        $team_ref = '2_' . $final_round . '_' . $fixture->get_id();

        foreach ($consolation_league_ids as $c_league_id) {
            $c_league = (new League_Repository())->find_by_id($c_league_id);
            if (!$c_league) {
                continue;
            }

            // Find the placeholder team in a consolation league
            $c_teams = $c_league->get_league_teams([
                'team_name'        => $team_ref,
                'reset_query_args' => true,
            ]);

            if (empty($c_teams)) {
                continue;
            }

            $c_team = $c_teams[0];
            $fixture_repository = new Fixture_Repository();
            
            // Find fixtures in consolation where this placeholder is assigned
            // We use league->get_matches here for now as there's no simple repo way yet for team_id search,
            // but we'll convert to Fixture objects
            $c_matches = $c_league->get_matches([
                'team_id' => $c_team->id,
                'final'   => 'all',
            ]);

            foreach ($c_matches as $c_match_data) {
                $c_fixture = $fixture_repository->find_by_id($c_match_data->id);
                if (!$c_fixture) {
                    continue;
                }

                if ($c_fixture->get_home_team() === (string)$c_team->id) {
                    $c_fixture->set_home_team((string)$loser_id);
                } elseif ($c_fixture->get_away_team() === (string)$c_team->id) {
                    $c_fixture->set_away_team((string)$loser_id);
                }
                $fixture_repository->save($c_fixture);
            }
        }
    }
}
