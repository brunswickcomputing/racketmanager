<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Competition;

use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Notification\Notification_Service;

/**
 * Service for managing knockout progression and bracket logic.
 */
class Knockout_Progression_Service
{
    private Fixture_Repository_Interface $fixture_repository;
    private League_Repository_Interface $league_repository;
    private Event_Repository_Interface $event_repository;
    private ?Notification_Service $notification_service = null;
    private League_Query_Service|\Closure|null $league_query_service;

    public function __construct(
        ?Fixture_Repository_Interface $fixture_repository = null,
        ?League_Repository_Interface $league_repository = null,
        ?Event_Repository_Interface $event_repository = null,
        ?Notification_Service $notification_service = null,
        League_Query_Service|\Closure|null $league_query_service = null
    ) {
        $this->event_repository = $event_repository ?? new Event_Repository();
        $this->league_repository = $league_repository ?? new League_Repository( null, $this->event_repository );
        $this->fixture_repository = $fixture_repository ?? new Fixture_Repository();
        $this->notification_service = $notification_service;
        $this->league_query_service = $league_query_service;
    }

    /**
     * Set the notification service.
     *
     * @param Notification_Service $notification_service
     */
    public function set_notification_service(Notification_Service $notification_service): void
    {
        $this->notification_service = $notification_service;
    }

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

        if (!$league) {
            $league = $this->league_repository->find_by_id($championship->league_id());
        }

        if ($league && $fixture->get_season() && $league->get_season() !== $fixture->get_season()) {
            $this->league_query()->set_season( $league, $fixture->get_season(), true);
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

        if ($current_round_num >= $championship->num_rounds()) {
            return;
        }

        $next_round_key = $championship->final_key_for_round($current_round_num + 1);
        if (!$next_round_key) {
            return;
        }

        $found_index = $this->find_fixture_index($this->fixture_repository, $league, $fixture, $final_round);
        if ($found_index === -1) {
            return;
        }

        $legs = !empty($league->current_season['home_away']);
        $winner_col = $legs ? 'winner_id_tie' : 'winner_id';
        $winner_id = $fixture->{"get_$winner_col"}();


        if (empty($winner_id)) {
            return;
        }

        $next_round_match_index = (int) floor($found_index / 2);
        $is_home = ($found_index % 2 === 0);


        $next_round_fixtures = $this->fixture_repository->find_by_league_and_final(
            $league->get_id(),
            $fixture->get_season(),
            $next_round_key,
            $legs && 'final' !== $next_round_key ? 1 : null
        );

        if (!isset($next_round_fixtures[$next_round_match_index])) {
            return;
        }

        $next_fixture = $next_round_fixtures[$next_round_match_index];
        $this->update_fixture_team($next_fixture, $is_home, (string)$winner_id);
        $this->fixture_repository->save($next_fixture);

        if ($next_fixture->get_linked_fixture()) {
            $linked_fixture = $this->fixture_repository->find_by_id($next_fixture->get_linked_fixture());
            if ($linked_fixture) {
                $this->update_fixture_team($linked_fixture, $is_home, (string)$winner_id);
                $this->fixture_repository->save($linked_fixture);
            }
        }

        // Notify teams if both teams are now set
        if ($this->notification_service && is_numeric($next_fixture->get_home_team()) && is_numeric($next_fixture->get_away_team())) {
            $this->notification_service->send_next_fixture_notification($next_fixture);
        }

        // Special case for third place playoff
        if ('semi' === $final_round && $championship->settings()->match_place3) {
            $this->handle_third_place_playoff($championship, $fixture, $league);
        }
    }

    /**
     * Handle third place playoff progression.
     *
     * @param Championship $championship
     * @param Fixture $fixture
     * @param League $league
     * @return void
     */
    private function handle_third_place_playoff(Championship $championship, Fixture $fixture, League $league): void
    {
        $legs = !empty($league->current_season['home_away']);
        $loser_col = $legs ? 'loser_id_tie' : 'loser_id';
        $loser_id = $fixture->{"get_$loser_col"}();

        if (empty($loser_id)) {
            return;
        }

        $found_index = $this->find_fixture_index($this->fixture_repository, $league, $fixture, $fixture->get_final());
        $is_home = ($found_index === 0);


        $third_place_fixtures = $this->fixture_repository->find_by_league_and_final(
            $league->get_id(),
            $fixture->get_season(),
            'third'
        );

        if (empty($third_place_fixtures)) {
            return;
        }

        $third_fixture = $third_place_fixtures[0];
        $this->update_fixture_team($third_fixture, $is_home, (string)$loser_id);
        $this->fixture_repository->save($third_fixture);

        if ($this->notification_service && is_numeric($third_fixture->get_home_team()) && is_numeric($third_fixture->get_away_team())) {
            $this->notification_service->send_next_fixture_notification($third_fixture);
        }
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
            $league = $this->league_repository->find_by_id($championship->league_id());
        }

        if ($league && $fixture->get_season() && $league->get_season() !== $fixture->get_season()) {
            $this->league_query()->set_season( $league, $fixture->get_season(), true);
            // Re-fetch championship since season change affects round counts
            $championship = $league->championship;
        }

        $round_data = $championship->get_finals($final_round);
        if (!$round_data) {
            return;
        }
        
        $current_round_num = $round_data['round'];
        $next_round_key = $championship->final_key_for_round($current_round_num + 1);
        
        if ($league && $next_round_key) {
            $found_index = $this->find_fixture_index($this->fixture_repository, $league, $fixture, $final_round);
            if ($found_index !== -1) {
                $this->update_next_round_fixtures($this->fixture_repository, $league, $fixture, $next_round_key, $final_round, $found_index);
            }
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

        if (!$league) {
            $league = $this->league_repository->find_by_id($fixture->get_league_id());
        }

        if ($league && $fixture->get_season() && $league->get_season() !== $fixture->get_season()) {
            $this->league_query()->set_season( $league, $fixture->get_season(), true);
            // Re-fetch championship since season change affects round counts
            $championship = $league->championship;
        }

        if (!$championship || $championship->is_consolation()) {
            return;
        }

        // Only handle consolation for the first few rounds as per legacy logic (round < 3)
        $final_round = $fixture->get_final();
        $round_data = $championship->get_finals($final_round);
        if (!$round_data || $round_data['round'] >= 3) {
            return;
        }

        if (!$league || !$championship) {
            return;
        }

        $event = $this->event_repository->find_by_id($league->get_event_id());
        if (!$event) {
            return;
        }

        // Identify consolation leagues in this event (load + championship filter).
        $consolation_leagues = $this->load_consolation_leagues($event);
        if (empty($consolation_leagues)) {
            return;
        }

        $legs = !empty($league->current_season['home_away']);
        $loser_col = $legs ? 'loser_id_tie' : 'loser_id';
        $loser_id = $fixture->{"get_$loser_col"}();

        if (empty($loser_id)) {
            return;
        }

        $team_ref = '2_' . $final_round . '_' . $fixture->get_id();

        foreach ($consolation_leagues as $c_league) {

            // Find fixtures in consolation where this placeholder is assigned
            // We'll search by team_id which can be a placeholder string
            $c_fixtures = $this->fixture_repository->find_by_league_and_team(
                $c_league->get_id(),
                $fixture->get_season(),
                $team_ref
            );


            foreach ($c_fixtures as $c_fixture) {
                if ($c_fixture->get_home_team() === $team_ref) {
                    $c_fixture->set_home_team((string)$loser_id);
                } elseif ($c_fixture->get_away_team() === $team_ref) {
                    $c_fixture->set_away_team((string)$loser_id);
                }
                $this->fixture_repository->save($c_fixture);

                if ($this->notification_service && is_numeric($c_fixture->get_home_team()) && is_numeric($c_fixture->get_away_team())) {
                    $this->notification_service->send_next_fixture_notification($c_fixture);
                }
            }
        }
    }

    /**
     * Find the 0-based index of the fixture in its current round.
     *
     * @param Fixture_Repository_Interface $repository
     * @param League $league
     * @param Fixture $fixture
     * @param string $final_round
     * @return int
     */
    private function find_fixture_index(Fixture_Repository_Interface $repository, League $league, Fixture $fixture, string $final_round): int
    {
        $current_round_fixtures = $repository->find_by_league_and_final(
            $league->get_id(),
            $fixture->get_season(),
            $final_round,
            $fixture->get_leg() ?: null
        );

        // Sorting by ID ensures consistency, but for tournaments, 
        // we assume creation order matches logical bracket order.
        usort($current_round_fixtures, fn($a, $b) => $a->get_id() <=> $b->get_id());

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
     * @param Fixture_Repository_Interface $repository
     * @param League $league
     * @param Fixture $fixture
     * @param string $next_round_key
     * @param string $final_round
     * @param int $found_index
     * @return void
     */
    private function update_next_round_fixtures(
        Fixture_Repository_Interface $repository,
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

        if ($next_fixture->get_linked_fixture()) {
            $linked_fixture = $repository->find_by_id($next_fixture->get_linked_fixture());
            if ($linked_fixture) {
                $this->update_fixture_team($linked_fixture, $is_home, $placeholder);
                $repository->save($linked_fixture);
            }
        }

        // Also reset consolation if applicable
        $this->reset_consolation($fixture, $league, $final_round);
    }

    /**
     * Reset consolation placeholders.
     *
     * @param Fixture $fixture
     * @param League $league
     * @param string $final_round
     * @return void
     */
    private function reset_consolation(Fixture $fixture, League $league, string $final_round): void
    {
        $event = $this->event_repository->find_by_id($league->get_event_id());
        if (!$event) {
            return;
        }

        $consolation_leagues = $this->load_consolation_leagues($event);
        if (empty($consolation_leagues)) {
            return;
        }

        $placeholder = '2_' . $final_round . '_' . $fixture->get_id();

        foreach ($consolation_leagues as $c_league) {
            $league_lookup_id = (int) $c_league->get_id();
            // Find fixtures in consolation where the winner or loser of the reset match might have been moved.
            // Since we don't know who the loser was, we check both teams.
            $teams_to_check = [(string)$fixture->get_home_team(), (string)$fixture->get_away_team()];
            
            foreach ($teams_to_check as $team_id) {
                if (empty($team_id) || !is_numeric($team_id)) {
                    continue;
                }

                $c_fixtures = $this->fixture_repository->find_by_league_and_team(
                    $league_lookup_id,
                    $fixture->get_season(),
                    $team_id
                );

                foreach ($c_fixtures as $c_fixture) {
                    // If this team is in a consolation fixture, but we are resetting the source match,
                    // we should probably put the placeholder back.
                    if ($c_fixture->get_home_team() === $team_id) {
                        $c_fixture->set_home_team($placeholder);
                    } elseif ($c_fixture->get_away_team() === $team_id) {
                        $c_fixture->set_away_team($placeholder);
                    }
                    $this->fixture_repository->save($c_fixture);
                }
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
     * Load consolation leagues for an event.
     *
     * Mirrors Event::get_leagues(['consolation' => true]): query candidates,
     * load each via the league repository, and drop any whose championship
     * is not a consolation draw.
     *
     * @return League[]
     */
    private function load_consolation_leagues(object $event): array
    {
        if (empty($event->id)) {
            return [];
        }

        $raw = $this->event_repository->query_leagues((int) $event->id, ['consolation' => true]);
        if (!is_array($raw) || empty($raw)) {
            return [];
        }

        $leagues = [];
        foreach ($raw as $item) {
            $id = is_object($item) ? (int) ($item->id ?? 0) : (int) $item;
            if ($id <= 0) {
                continue;
            }
            $loaded = $this->league_repository->find_by_id($id);
            if (!$loaded) {
                continue;
            }
            $championship = $loaded->championship ?? null;
            $is_consolation = $championship && (
                method_exists($championship, 'is_consolation')
                    ? $championship->is_consolation()
                    : (bool) ($championship->is_consolation ?? false)
            );
            if (!$is_consolation) {
                continue;
            }
            $leagues[] = $loaded;
        }

        return $leagues;
    }

    private function league_query(): League_Query_Service {
        if ( $this->league_query_service instanceof \Closure ) {
            $this->league_query_service = ( $this->league_query_service )();
        }
        if ( ! $this->league_query_service instanceof League_Query_Service ) {
            throw new \LogicException( 'League_Query_Service is required by Knockout_Progression_Service but was not configured.' );
        }
        return $this->league_query_service;
    }
}
