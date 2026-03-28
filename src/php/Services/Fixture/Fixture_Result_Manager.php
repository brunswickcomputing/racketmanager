<?php

namespace Racketmanager\Services\Fixture;

use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Domain\DTO\Fixture\Fixture_Reset_Response;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Confirmation_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Response;
use Racketmanager\Domain\DTO\Player\Player_Warnings_Response;
use Racketmanager\Domain\DTO\Rubber\Rubber_Update_Request;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Result\Rubber_Result_Manager;
use Racketmanager\Services\Notification\Notification_Service;
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
use Racketmanager\Services\Validator\Player_Validation_Service;
use Racketmanager\Services\Settings_Service;

use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Repositories\Results_Checker_Repository;
use Racketmanager\Repositories\Rubber_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Services\Validator\Validator_Fixture;
use Racketmanager\Util\Util_Lookup;
use Racketmanager\Util\Util_Messages;
use stdClass;

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
     * @var Settings_Service
     */
    private Settings_Service $settings_service;

    /**
     * @var Player_Validation_Service
     */
    private Player_Validation_Service $player_validator;

    /**
     * @var Rubber_Result_Manager|null
     */
    private Rubber_Result_Manager|null $rubber_manager;

    /**
     * @var Registration_Service|null
     */
    private Registration_Service|null $registration_service;

    /**
     * @var Notification_Service|null
     */
    private Notification_Service|null $notification_service;

    /**
     * @var Team_Repository|null
     */
    private Team_Repository|null $team_repository;

    /**
     * @var Player_Repository|null
     */
    private Player_Repository|null $player_repository;

    /**
     * @var League_Team_Repository|null
     */
    private League_Team_Repository|null $league_team_repository;

    /**
     * @var Rubber_Repository|null
     */
    private Rubber_Repository|null $rubber_repository;

    /**
     * @var Results_Checker_Repository|null
     */
    private Results_Checker_Repository|null $results_checker_repository;

    /**
     * Constructor.
     *
     * @param Result_Service               $result_service
     * @param Knockout_Progression_Service $progression_service
     * @param League_Service               $league_service
     * @param Score_Validation_Service     $score_validator
     * @param Settings_Service             $settings_service
     * @param Player_Validation_Service    $player_validator
     * @param Rubber_Result_Manager|null   $rubber_manager
     * @param Registration_Service|null    $registration_service
     * @param Notification_Service|null    $notification_service
     * @param League_Repository|null       $league_repository
     * @param League_Team_Repository|null  $league_team_repository
     * @param Team_Repository|null         $team_repository
     * @param Player_Repository|null       $player_repository
     * @param Rubber_Repository|null       $rubber_repository
     * @param Results_Checker_Repository|null $results_checker_repository
     */
    public function __construct(
        Result_Service $result_service,
        Knockout_Progression_Service $progression_service,
        League_Service $league_service,
        Score_Validation_Service $score_validator,
        ?Settings_Service $settings_service = null,
        Player_Validation_Service $player_validator = null,
        ?Rubber_Result_Manager $rubber_manager = null,
        ?Registration_Service $registration_service = null,
        ?Notification_Service $notification_service = null,
        ?League_Repository $league_repository = null,
        ?League_Team_Repository $league_team_repository = null,
        ?Team_Repository $team_repository = null,
        ?Player_Repository $player_repository = null,
        ?Rubber_Repository $rubber_repository = null,
        ?Results_Checker_Repository $results_checker_repository = null,
        ?Fixture_Repository $fixture_repository = null
    ) {
        $this->result_service         = $result_service;
        $this->progression_service    = $progression_service;
        $this->league_service         = $league_service;
        $this->score_validator        = $score_validator;
        $this->settings_service       = $settings_service ?? new Settings_Service();
        $this->player_validator       = $player_validator ?? new Player_Validation_Service( $registration_service ?? new Registration_Service( $GLOBALS['racketmanager'] ), $results_checker_repository ?? new Results_Checker_Repository(), $fixture_repository ?? new Fixture_Repository() );
        $this->rubber_manager         = $rubber_manager;
        $this->registration_service   = $registration_service;
        $this->team_repository        = $team_repository ?? new Team_Repository();
        $this->player_repository      = $player_repository ?? new Player_Repository();
        $this->league_team_repository = $league_team_repository ?? new League_Team_Repository();
        $this->rubber_repository      = $rubber_repository ?? new Rubber_Repository();
        $this->results_checker_repository = $results_checker_repository ?? new Results_Checker_Repository();

        if ( ! $notification_service ) {
            $league_repo          = $league_repository ?? new League_Repository();
            $notification_service = new Notification_Service(
                $league_repo,
                $this->league_team_repository,
                $this->team_repository,
                $this->player_repository,
                new Club_Repository()
            );
        }
        $this->notification_service = $notification_service;
    }

    /**
     * Handle result update for a single fixture (player/tournament match).
     *
     * @param Fixture $fixture
     * @param Fixture_Result_Update_Request $request
     *
     * @return Fixture_Update_Response
     * @throws Fixture_Validation_Exception If validation fails.
     * @throws League_Not_Found_Exception
     */
    public function handle_fixture_result_update( Fixture $fixture, Fixture_Result_Update_Request $request ): Fixture_Update_Response {
        $league = $this->league_service->get_league( $fixture->get_league_id() );
        if ( ! $league ) {
            throw new League_Not_Found_Exception( Util_Messages::league_not_found_for_fixture( $fixture->get_id() ) );
        }

        $scoring_context = new Scoring_Context(
            num_sets_to_win: (int) $league->num_sets_to_win,
            scoring_type: $league->scoring ?? 'TB',
            point_rule: $league->get_point_rule(),
            is_championship: $league->is_championship,
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
     * @return Team_Result_Response
     * @throws Fixture_Validation_Exception
     * @throws League_Not_Found_Exception
     */
    public function handle_team_result_update( Fixture $fixture, Team_Result_Update_Request $request, ?League_Repository $league_repository = null ): Team_Result_Response {
        $league = $this->league_service->get_league( $fixture->get_league_id() );
        if ( ! $league ) {
            throw new League_Not_Found_Exception( Util_Messages::league_not_found_for_fixture( $fixture->get_id() ) );
        }

        $dummy_players = $this->get_dummy_players_for_fixture( $fixture );

        $is_withdrawn = $this->is_any_team_withdrawn( $fixture );
        $is_cancelled = 'cancelled' === $request->match_status;

        $validator = new Validator_Fixture();
        $processed_rubbers = [];
        $updated_rubbers = [];

        foreach ( $request->rubber_ids as $ix => $rubber_id ) {
            try {
                $rubber_result = $this->process_rubber_update( $fixture, $request, (int) $ix, $is_withdrawn, $is_cancelled, $dummy_players );

                $updated_rubbers[ (int) $request->rubber_ids[ $ix ] ] = [
                    'players'     => $rubber_result->players,
                    'homepoints'  => $rubber_result->home_points,
                    'awaypoints'  => $rubber_result->away_points,
                    'sets'        => $rubber_result->sets,
                    'winner'      => $rubber_result->winner_id,
                ];

                $processed_rubbers[] = (object)[
                    'status'      => $rubber_result->status,
                    'winner_id'   => $rubber_result->winner_id,
                    'home_points' => $rubber_result->home_points,
                    'away_points' => $rubber_result->away_points,
                    'custom'      => $rubber_result->custom,
                ];

            } catch ( Fixture_Validation_Exception $e ) {
                $validator->error    = true;
                $validator->err_msgs = array_merge( $validator->err_msgs, $e->get_error_msgs() );
                $validator->err_flds = array_merge( $validator->err_flds, $e->get_error_flds() );
            }
        }

        if ( ! empty( $validator->error ) ) {
            $details = (array) $validator->get_details();
            $details['rubbers'] = $updated_rubbers;
            return new Team_Result_Response( $details );
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
            Util_Lookup::get_match_status_code( $request->match_status ),
            (int) $league->num_rubbers
        );
        
        $custom = $fixture->get_custom() ?: [];
        $custom['stats'] = $stats_result['stats'];

        $determined = Result_Calculator::determine_winner_and_loser(
            $match_points['home_points'],
            $match_points['away_points'],
            (int) $fixture->get_home_team(),
            (int) $fixture->get_away_team(),
            Util_Lookup::get_match_status_code( $request->match_status ),
            $custom
        );

        $result = new Result(
            home_points: $match_points['home_points'],
            away_points: $match_points['away_points'],
            winner_id: $determined['winner_id'] ? (int) $determined['winner_id'] : null,
            loser_id: $determined['loser_id'] ? (int) $determined['loser_id'] : null,
            status: Util_Lookup::get_match_status_code( $request->match_status ),
            sets: [], // Team fixture sets are in rubbers
            custom: $custom
        );

        $confirmed = null;
        if ( empty( $fixture->get_confirmed() ) ) {
            $confirmed = 'P'; // Pending
        }

        $this->update_result( $fixture, $result, $confirmed, $league_repository );

        $options = $this->settings_service->get_all_options();
        
        // Run player and result checks
        $this->player_validator->run_fixture_checks( $fixture, $league, $updated_rubbers, $options );

        // Handle notifications (legacy notification via service)
        if ( $this->notification_service ) {
            $msg = __( 'Result saved', 'racketmanager' );
            $this->notification_service->send_result_notification( $fixture, $confirmed ?: 'P', $msg );
        }

        $response = new Team_Result_Response( [
            'msg'       => __( 'Result saved', 'racketmanager' ),
            'rubbers'   => $updated_rubbers,
            'status'    => 'success',
            'winner_id' => $fixture->get_winner_id(),
            'loser_id'  => $fixture->get_loser_id(),
        ] );

        $warnings = $this->handle_player_warnings( $fixture );
        if ( ! empty( $warnings->msg ) ) {
            $response->msg    .= $warnings->msg;
            $response->status  = $warnings->status;
        }
        $response->warnings = $warnings->warnings;
        
        return $response;
    }

    /**
     * Get dummy players for the home and away teams of a fixture.
     *
     * @param Fixture $fixture
     * @return array
     */
    private function get_dummy_players_for_fixture( Fixture $fixture ): array {
        $dummy_players = [];
        $opponents     = [ 'home', 'away' ];

        foreach ( $opponents as $opponent ) {
            $team_id = $opponent === 'home' ? (int) $fixture->get_home_team() : (int) $fixture->get_away_team();
            $team    = $this->team_repository->find_by_id( $team_id );
            if ( $team && $team->club_id && $this->registration_service ) {
                $dummy_players[ $opponent ] = $this->registration_service->get_dummy_players( (int) $team->club_id );
            }
        }
        return $dummy_players;
    }

    /**
     * Check if any team in the fixture is withdrawn.
     *
     * @param Fixture $fixture
     * @return bool
     */
    private function is_any_team_withdrawn( Fixture $fixture ): bool {
        $league_id = (int) $fixture->get_league_id();
        $season    = (int) $fixture->get_season();

        $home_league_team = $this->league_team_repository->find_by_team_league_and_season( (int) $fixture->get_home_team(), $league_id, $season );
        $away_league_team = $this->league_team_repository->find_by_team_league_and_season( (int) $fixture->get_away_team(), $league_id, $season );

        return ( $home_league_team && $home_league_team->is_withdrawn ) || ( $away_league_team && $away_league_team->is_withdrawn );
    }

    /**
     * Process a single rubber update.
     *
     * @param Fixture $fixture
     * @param Team_Result_Update_Request $request
     * @param int $ix
     * @param bool $is_withdrawn
     * @param bool $is_cancelled
     * @param array $dummy_players
     * @return object
     * @throws Fixture_Validation_Exception
     */
    private function process_rubber_update( Fixture $fixture, Team_Result_Update_Request $request, int $ix, bool $is_withdrawn, bool $is_cancelled, array $dummy_players ): object {
        $rubber_id     = (int) $request->rubber_ids[ $ix ];
        $rubber_status = $request->rubber_statuses[ $ix ] ?? null;
        $rubber_type   = $request->rubber_types[ $ix ] ?? null;
        $players       = $request->players[ $ix ] ?? [];
        $sets          = $request->sets[ $ix ] ?? [];

        $rubber_request = new Rubber_Update_Request(
            rubber_id: $rubber_id,
            rubber_type: $rubber_type,
            rubber_number: $ix,
            players: $players,
            sets: $sets,
            rubber_status: $rubber_status,
            is_withdrawn: $is_withdrawn,
            is_cancelled: $is_cancelled
        );

        return $this->rubber_manager->handle_rubber_update( $fixture, $rubber_request, $dummy_players );
    }

    /**
     * Handle result confirmation for a team fixture.
     *
     * @param Fixture $fixture
     * @param Team_Result_Confirmation_Request $request
     * @param League_Repository|null $league_repository Optional league repository for testing.
     *
     * @return Team_Result_Response
     * @throws League_Not_Found_Exception
     */
    public function handle_team_result_confirmation( Fixture $fixture, Team_Result_Confirmation_Request $request, ?League_Repository $league_repository = null ): Team_Result_Response {
        $validator = new Validator_Fixture();
        $validator = $validator->result_confirm( $request->result_confirm, $request->confirm_comments );
        if ( ! empty( $validator->error ) ) {
            return new Team_Result_Response( (array) $validator->get_details() );
        }

        $actioned_by = '';
        if ( $request->result_home ) {
            $actioned_by = 'home';
        } elseif ( $request->result_away ) {
            $actioned_by = 'away';
        }

        $league = $this->league_service->get_league( $fixture->get_league_id() );
        if ( ! $league ) {
            throw new League_Not_Found_Exception( Util_Messages::league_not_found_for_fixture( $fixture->get_id() ) );
        }

        $competition_type    = $league->get_competition_type() ?: 'league';
        $result_confirmation = $this->settings_service->get_option( $competition_type, 'resultConfirmation', 'manual' );

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

        // 1. Update confirmation status and comments in the domain object
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

        // 2. Persist changes using the result service
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

        // 4. Run player and result checks if approved
        if ( 'A' === $request->result_confirm ) {
            $options = $this->settings_service->get_all_options();
            $this->player_validator->run_fixture_checks( $fixture, $league, $this->rubber_repository->find_by_fixture_id( (int) $fixture->get_id() ), $options );
        }

        // 5. Handle notifications (via notification service)
        $this->notification_service?->send_result_notification( $fixture, $final_confirmed_status, $match_msg ? : '', $actioned_by ? : false );

        $response = new Team_Result_Response( [
            'msg'     => $match_msg,
            'rubbers' => [],
            'status'  => 'success',
        ] );

        $warnings = $this->handle_player_warnings( $fixture );
        if ( ! empty( $warnings->msg ) ) {
            $response->msg    .= $warnings->msg;
            $response->status  = $warnings->status;
        }
        $response->warnings = $warnings->warnings;

        return $response;
    }

    /**
     * Function to check and return any player warnings for a match
     *
     * @param Fixture $fixture
     * @return Player_Warnings_Response
     */
    private function handle_player_warnings( Fixture $fixture ): Player_Warnings_Response {
        $msg             = '';
        $player_warnings = [];
        $result_status   = null;

        $has_result_check = $this->results_checker_repository->has_results_check( (int) $fixture->get_id() );

        if ( $has_result_check ) {
            $warning_player  = false;
            $warning_match   = array();
            $result_status   = 'warning';
            $result_warnings = $this->results_checker_repository->find_by_fixture_id( (int) $fixture->get_id() );

            foreach ( $result_warnings as $player_warning ) {
                if ( $player_warning->rubber_id ) {
                    $warning_player = true;
                    $rubber = $this->rubber_repository->find_by_id( $player_warning->rubber_id );
                    if ( $rubber ) {
                        $team = ( $player_warning->team_id === (int) $fixture->get_home_team() ) ? 'home' : 'away';
                        
                        $player_number = ( (int) $player_warning->player_id === (int) $rubber->players[ $team ]['1']->id ) ? 1 : 2;
                        
                        $player_ref                     = 'players_' . $rubber->rubber_number . '_' . $team . '_' . $player_number;
                        $player_warnings[ $player_ref ] = $player_warning->description;
                    }
                } else {
                    $warning_match[] = $player_warning->description;
                }
            }
            if ( $warning_player ) {
                $msg .= '<br>' . __( 'Match has player warnings', 'racketmanager' );
            }
            foreach ( $warning_match as $warning ) {
                $msg .= '<br>' . $warning;
            }
        }
        
        return new Player_Warnings_Response( $msg, $result_status, $player_warnings );
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
     * @throws League_Not_Found_Exception
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
        $this->result_service->apply_to_fixture($fixture, $empty_result );

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
