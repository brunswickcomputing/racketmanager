<?php

namespace Racketmanager\Services\Fixture;

use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Domain\DTO\Fixture\Fixture_Reset_Response;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Confirmation_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Domain\DTO\Rubber\Rubber_Update_Request;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Result\Rubber_Result_Manager;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;
use Racketmanager\Domain\Enums\Fixture_Reset_Status;
use Racketmanager\Domain\Fixture;
use Racketmanager\Domain\Result;
use Racketmanager\Domain\Scoring\Scoring_Context;
use Racketmanager\Exceptions\Fixture_Validation_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Services\Competition\Knockout_Progression_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Result_Factory;
use Racketmanager\Services\Result_Service;
use Racketmanager\Services\Result_Calculator;
use Racketmanager\Services\Validator\Score_Validation_Service;

use Racketmanager\Repositories\League_Repository;

/**
 * Service for managing fixture results and state transitions.
 * Orchestrates logic formerly in Racketmanager_Match.
 */
class Fixture_Result_Manager
{
    /**
     * @var Result_Service
     */
    private Result_Service $result_service;

    /**
     * @var Knockout_Progression_Service
     */
    private Knockout_Progression_Service $progression_service;

    /**
     * @var League_Service
     */
    private League_Service $league_service;

    /**
     * @var Score_Validation_Service
     */
    private Score_Validation_Service $score_validator;

    /**
     * @var Rubber_Result_Manager|null
     */
    private Rubber_Result_Manager|null $rubber_manager;

    /**
     * @var Registration_Service|null
     */
    private Registration_Service|null $registration_service;

    public function __construct(
        Result_Service $result_service,
        Knockout_Progression_Service $progression_service,
        League_Service $league_service,
        Score_Validation_Service $score_validator,
        ?Rubber_Result_Manager $rubber_manager = null,
        ?Registration_Service $registration_service = null
    ) {
        $this->result_service       = $result_service;
        $this->progression_service  = $progression_service;
        $this->league_service       = $league_service;
        $this->score_validator      = $score_validator;
        $this->rubber_manager       = $rubber_manager;
        $this->registration_service = $registration_service;
    }

    /**
     * Handle result update for a single fixture (player/tournament match).
     *
     * @param Fixture $fixture
     * @param Fixture_Result_Update_Request $request
     *
     * @return Fixture_Update_Response
     * @throws Fixture_Validation_Exception If validation fails.
     */
    public function handle_fixture_result_update( Fixture $fixture, Fixture_Result_Update_Request $request ): Fixture_Update_Response {
        $league = $this->league_service->get_league( $fixture->get_league_id() );
        if ( ! $league ) {
            throw new League_Not_Found_Exception( 'League not found for fixture: ' . $fixture->get_id() );
        }

        $scoring_context = new Scoring_Context(
            num_sets_to_win: (int) $league->num_sets_to_win,
            scoring_type: $league->scoring ?? 'TB',
            point_rule: $league->get_point_rule(),
            is_championship: (bool) $league->is_championship,
            final_round: $fixture->get_final(),
            num_rubbers: (int) $league->num_rubbers,
            leg: $fixture->get_leg(),
            num_sets: (int) $league->num_sets
        );

        // 1. Validate the match score using extracted logic
        $this->score_validator->validate( $scoring_context, $request->sets, $request->match_status, 'set_' );

        if ($this->score_validator->get_error()) {
            throw new Fixture_Validation_Exception(
                $this->score_validator->get_err_msgs(),
                $this->score_validator->get_err_flds()
            );
        }

        $status = 0;
        $custom = $fixture->get_custom() ?: [];

        switch ($request->match_status) {
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
            default:
                break;
        }

        $custom['sets'] = $request->sets;

        $result_data = [
            'status' => $status,
            'custom' => $custom,
            'sets'   => $request->sets,
        ];

        $result = Result_Factory::from_array($result_data, $fixture->get_home_team(), $fixture->get_away_team());
        
        return $this->update_result($fixture, $result, $request->confirmed);
    }

    /**
     * Handle result update for a team fixture.
     *
     * @param Fixture $fixture
     * @param Team_Result_Update_Request $request
     * @param League_Repository|null $league_repository Optional league repository for testing.
     *
     * @return object Validator details for now to maintain compatibility with legacy AJAX.
     * @throws Fixture_Validation_Exception
     */
    public function handle_team_result_update( Fixture $fixture, Team_Result_Update_Request $request, ?League_Repository $league_repository = null ): object {
        $league = $this->league_service->get_league( $fixture->get_league_id() );
        if ( ! $league ) {
            throw new \Racketmanager\Exceptions\League_Not_Found_Exception( 'League not found for fixture: ' . $fixture->get_id() );
        }

        $dummy_players = [];
        $opponents     = [ 'home', 'away' ];

        foreach ( $opponents as $opponent ) {
            $team_id = $opponent === 'home' ? $fixture->get_home_team() : $fixture->get_away_team();
            $team    = \Racketmanager\get_team( $team_id );
            if ( $team && $team->club_id && $this->registration_service ) {
                $dummy_players[ $opponent ] = $this->registration_service->get_dummy_players( (int) $team->club_id );
            }
        }

        $home_points     = 0.0;
        $away_points     = 0.0;
        $updated_rubbers = [];
        $match_stats     = \Racketmanager\Util\Util::initialise_match_stats();

        $is_withdrawn = false;
        if ( \Racketmanager\get_team( $fixture->get_home_team() )->is_withdrawn || \Racketmanager\get_team( $fixture->get_away_team() )->is_withdrawn ) {
            $is_withdrawn = true;
        }

        $is_cancelled = 'cancelled' === $request->match_status;

        $validator = new \Racketmanager\Services\Validator\Validator_Fixture();

        $processed_rubbers = [];

        foreach ( $request->rubber_ids as $ix => $rubber_id ) {
            $rubber_id     = (int) $rubber_id;
            $rubber_status = $request->rubber_statuses[ $ix ] ?? null;
            $rubber_type   = $request->rubber_types[ $ix ] ?? null;
            $players       = $request->players[ $ix ] ?? [];
            $sets          = $request->sets[ $ix ] ?? [];

            $rubber_request = new Rubber_Update_Request(
                rubber_id: $rubber_id,
                rubber_type: $rubber_type,
                rubber_number: (int) $ix,
                players: $players,
                sets: $sets,
                rubber_status: $rubber_status,
                is_withdrawn: $is_withdrawn,
                is_cancelled: $is_cancelled
            );

            try {
                $rubber_result = $this->rubber_manager->handle_rubber_update( $fixture, $rubber_request, $dummy_players );

                $updated_rubbers[ $rubber_id ] = [
                    'players'     => $rubber_result->players,
                    'homepoints'  => $rubber_result->home_points,
                    'awaypoints'  => $rubber_result->away_points,
                    'sets'        => $rubber_result->sets,
                    'winner'      => $rubber_result->winner_id,
                ];

                $processed_rubbers[] = (object)[
                    'status'    => $rubber_result->status,
                    'winner_id' => $rubber_result->winner_id,
                    'home_points' => $rubber_result->home_points,
                    'away_points' => $rubber_result->away_points,
                    'custom'    => $rubber_result->custom,
                ];

            } catch ( Fixture_Validation_Exception $e ) {
                $validator->error    = true;
                $validator->err_msgs = array_merge( $validator->err_msgs, $e->get_error_msgs() );
                $validator->err_flds = array_merge( $validator->err_flds, $e->get_error_flds() );
            }
        }

        if ( ! empty( $validator->error ) ) {
            $data          = $validator->get_details();
            $data->rubbers = $updated_rubbers;
            return $data;
        }

        // Finalize fixture update using Result_Calculator
        $stats_result = Result_Calculator::calculate_stats_from_rubbers(
            $processed_rubbers,
            (string) $fixture->get_home_team(),
            (string) $fixture->get_away_team()
        );

        $match_points = Result_Calculator::calculate_points_from_stats(
            $stats_result,
            $league->get_point_rule(),
            \Racketmanager\Util\Util_Lookup::get_match_status_code( $request->match_status ),
            (int) $league->num_rubbers
        );
        
        $custom = $fixture->get_custom() ?: [];
        $custom['stats'] = $stats_result['stats'];

        $result = new Result(
            home_points: $match_points['home_points'],
            away_points: $match_points['away_points'],
            winner_id: null, // update_result will determine
            loser_id: null,
            status: \Racketmanager\Util\Util_Lookup::get_match_status_code( $request->match_status ),
            sets: [], // Team fixture sets are in rubbers
            custom: $custom
        );

        $confirmed = null;
        if ( empty( $fixture->get_confirmed() ) ) {
            $confirmed = 'P'; // Pending
        }

        $this->update_result( $fixture, $result, $confirmed, $league_repository );

        $data          = new \stdClass();
        $data->msg     = __( 'Result saved', 'racketmanager' );
        $data->rubbers = $updated_rubbers;
        $data->status  = 'success';
        $data->warnings = []; // TODO: handle warnings
        
        return $data;
    }

    /**
     * Handle result confirmation for a team fixture.
     *
     * @param Fixture $fixture
     * @param Team_Result_Confirmation_Request $request
     * @param League_Repository|null $league_repository Optional league repository for testing.
     *
     * @return object Validator details for now to maintain compatibility with legacy AJAX.
     */
    public function handle_team_result_confirmation( Fixture $fixture, Team_Result_Confirmation_Request $request, ?League_Repository $league_repository = null ): object {
        $validator = new \Racketmanager\Services\Validator\Validator_Fixture();
        $validator = $validator->result_confirm( $request->result_confirm, $request->confirm_comments );
        if ( ! empty( $validator->error ) ) {
            return $validator;
        }

        $actioned_by = '';
        if ( $request->result_home ) {
            $actioned_by = 'home';
        } elseif ( $request->result_away ) {
            $actioned_by = 'away';
        }

        $league = $this->league_service->get_league( $fixture->get_league_id() );
        if ( ! $league ) {
             throw new League_Not_Found_Exception( 'League not found for fixture: ' . $fixture->get_id() );
        }

        global $racketmanager;
        $rm_options          = $racketmanager->get_options();
        $competition_type    = $league->get_competition_type() ?: 'league';
        $result_confirmation = $rm_options[ $competition_type ]['resultConfirmation'] ?? 'manual';

        $match_msg = null;
        $final_confirmed_status = $request->result_confirm;
        $update_standings = false;

        if ( 'A' === $request->result_confirm ) {
            $match_msg = __( 'Result Approved', 'racketmanager' );
            if ( 'auto' === $result_confirmation ) {
                $update_standings = true;
                $final_confirmed_status = 'Y';
            }
        } elseif ( 'C' === $request->result_confirm ) {
            $match_msg = __( 'Result Challenged', 'racketmanager' );
        }

        // 1. Update confirmation status and comments in domain object
        $fixture->set_confirmed( $final_confirmed_status );
        
        $comments = $fixture->get_comments() ? maybe_unserialize( $fixture->get_comments() ) : [];
        if ( ! is_array( $comments ) ) {
            $comments = [];
        }

        if ( $actioned_by ) {
             $comments[ $actioned_by . '_confirm' ] = $request->confirm_comments;
        } else {
             $comments['confirm'] = $request->confirm_comments;
        }
        $fixture->set_comments( maybe_serialize( $comments ) );

        // 2. Persist changes using result service
        $result = new Result(
            home_points: (float) $fixture->get_home_points(),
            away_points: (float) $fixture->get_away_points(),
            status: $fixture->get_status(),
            custom: $fixture->get_custom() ?: [],
            sets: [] // sets for team match are in rubbers
        );
        $this->result_service->apply_to_fixture( $fixture, $result, $final_confirmed_status );

        // 3. Handle league updates if approved
        if ( $update_standings ) {
            $this->update_result( $fixture, $result, $final_confirmed_status, $league_repository );
        }

        // 4. Handle notifications (legacy function call for now)
        if ( method_exists( \Racketmanager\Domain\Racketmanager_Match::class, 'result_notification' ) ) {
             // We still need a Racketmanager_Match instance for the legacy notification method
             // This is the last bit of coupling to be removed in the next step.
             $match = \Racketmanager\get_match( $fixture->get_id() );
             if ( $match ) {
                 $match->result_notification( $final_confirmed_status, $match_msg, $actioned_by );
             }
        }

        $data           = new \stdClass();
        $data->msg      = $match_msg;
        $data->rubbers  = array();
        $data->status   = 'success';
        $data->warnings = []; // TODO: handle player warnings migration

        return $data;
    }

    /**
     * Update a fixture with a new result.
     *
     * @param Fixture $fixture The fixture to update.
     * @param Result $result The new result.
     * @param string|null $confirmed Confirmation status ('Y', 'N', or null).
     * @param League_Repository|null $league_repository Optional league repository for testing.
     * @return Fixture_Update_Response
     */
    public function update_result( Fixture $fixture, Result $result, ?string $confirmed = null, ?League_Repository $league_repository = null ): Fixture_Update_Response
    {
        $this->result_service->apply_to_fixture($fixture, $result, $confirmed);

        $outcomes = [Fixture_Update_Status::SAVED];

        $league_repository = $league_repository ?? new League_Repository();
        $league = $league_repository->find_by_id($fixture->get_league_id());
        if (!$league) {
            return new Fixture_Update_Response($outcomes);
        }

        $stage = Stage::from_league($league);

        if ($league->is_championship) {
            $this->progression_service->progress_winner($stage, $fixture, $league);
            $this->progression_service->handle_consolation($stage, $fixture, $league);
            $outcomes[] = Fixture_Update_Status::PROGRESSED;
        } else {
            $league->update_standings($fixture->get_season());
            $outcomes[] = Fixture_Update_Status::TABLE_UPDATED;
        }

        return new Fixture_Update_Response($outcomes);
    }

    /**
     * Reset the result of a fixture.
     *
     * @param Fixture $fixture
     *
     * @return Fixture_Reset_Response
     */
    public function reset_result( Fixture $fixture ): Fixture_Reset_Response
    {
        $fixture->reset_result();
        
        // Persist the reset state.
        $empty_result = new Result(
            home_points: 0,
            away_points: 0,
            status: null,
            sets: [],
            custom: []
        );
        $this->result_service->apply_to_fixture($fixture, $empty_result, null);

        try {
            $league = $this->league_service->get_league( $fixture->get_league_id() );
        } catch ( League_Not_Found_Exception ) {
            $league = null;
        }

        if ($league && $league->is_championship) {
            $stage = Stage::from_league($league);
            $this->progression_service->reset_progression($stage, $fixture, $league);
        } elseif ($league) {
            $league->update_standings($fixture->get_season());
        }

        $status = ( $league && $league->is_championship )
            ? Fixture_Reset_Status::SUCCESS_KNOCKOUT_RESET
            : Fixture_Reset_Status::SUCCESS_DIVISION_RESET;

        return new Fixture_Reset_Response( $fixture->get_id(), $status );

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
