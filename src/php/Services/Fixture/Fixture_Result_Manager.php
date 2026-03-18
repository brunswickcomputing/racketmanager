<?php

namespace Racketmanager\Services\Fixture;

use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Domain\Fixture;
use Racketmanager\Domain\Result;
use Racketmanager\Services\Competition\Knockout_Progression_Service;
use Racketmanager\Services\Result_Factory;
use Racketmanager\Services\Result_Service;
use function Racketmanager\get_league;

/**
 * Service for managing fixture results and state transitions.
 * Orchestrates logic formerly in Racketmanager_Match.
 */
final class Fixture_Result_Manager
{
    /**
     * @var Result_Service
     */
    private Result_Service $result_service;

    /**
     * @var Knockout_Progression_Service
     */
    private Knockout_Progression_Service $progression_service;

    public function __construct(
        Result_Service $result_service,
        Knockout_Progression_Service $progression_service
    ) {
        $this->result_service = $result_service;
        $this->progression_service = $progression_service;
    }

    /**
     * Handle result update for a single fixture (player/tournament match).
     * 
     * @param Fixture $fixture
     * @param array|null $sets
     * @param string|null $match_status
     * @param string|null $confirmed
     * @return void
     */
    public function handle_single_result_update(Fixture $fixture, ?array $sets, ?string $match_status, ?string $confirmed = null): void
    {
        $status = 0;
        $custom = $fixture->get_custom() ?: [];

        switch ($match_status) {
            case 'walkover_player1':
                $custom['walkover'] = 'home';
                $status = 1;
                break;
            case 'walkover_player2':
                $custom['walkover'] = 'away';
                $status = 1;
                break;
            case 'retired_player1':
                $custom['retired'] = 'home';
                $status = 2;
                break;
            case 'retired_player2':
                $custom['retired'] = 'away';
                $status = 2;
                break;
            case 'share':
                $custom['share'] = 'true';
                $status = 3;
                break;
            case 'abandoned':
                $custom['abandoned'] = 'true';
                $status = 6;
                break;
            case 'cancelled':
                $custom['cancelled'] = 'true';
                $status = 8;
                break;
        }

        $custom['sets'] = $sets;

        $result_data = [
            'status' => $status,
            'custom' => $custom,
            'sets'   => $sets,
        ];

        $result = Result_Factory::from_array($result_data, $fixture->get_home_team(), $fixture->get_away_team());
        
        $this->result_service->apply_to_fixture($fixture, $result, $confirmed);
    }

    /**
     * Update a fixture with a new result.
     * 
     * @param Fixture $fixture The fixture to update.
     * @param Result $result The new result.
     * @param string|null $confirmed Confirmation status ('Y', 'N', or null).
     * @return void
     */
    public function update_result(Fixture $fixture, Result $result, ?string $confirmed = null): void
    {
        $fixture->set_result($result);
        
        if ($confirmed !== null) {
            $fixture->set_confirmed($confirmed);
        }

        if ($result->get_status() !== null) {
            $fixture->set_status($result->get_status());
        }

        $league = get_league($fixture->get_league_id());
        if (!$league) {
            return;
        }

        $stage = Stage::from_league($league);

        if ($league->is_championship) {
            $this->progression_service->progress_winner($stage, $fixture, $league);
        } else {
            $league->update_standings($fixture->get_season());
        }
    }

    /**
     * Confirm a fixture result.
     * 
     * @param Fixture $fixture
     * @param string $actioned_by
     * @param string|null $comments
     * @return void
     */
    public function confirm_result(Fixture $fixture, string $actioned_by, ?string $comments = null): void
    {
        $fixture->set_confirmed('Y');
        if ($comments) {
            $fixture->set_comments($comments);
        }
        
        // TODO: Handle post-confirmation logic (e.g., updating standings).
    }
}
