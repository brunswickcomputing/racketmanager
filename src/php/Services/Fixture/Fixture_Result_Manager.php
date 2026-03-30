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
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Result\Rubber_Result_Manager;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;
use Racketmanager\Domain\Enums\Fixture_Reset_Status;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Result\Result;
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

use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Repositories\Club_Repository;
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
use function maybe_serialize;
use function maybe_unserialize;
use function Racketmanager\debug_to_console;

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
     * @var League_Repository|null
     */
    private League_Repository|null $league_repository;

    /**
     * @var Fixture_Repository|null
     */
    private Fixture_Repository|null $fixture_repository;

    /**
     * @var Club_Repository|null
     */
    private Club_Repository|null $club_repository;

    /**
     * @var Results_Checker_Repository|null
     */
    private Results_Checker_Repository|null $results_checker_repository;

    /**
     * @param Service_Provider $service_provider
     * @param Repository_Provider $repository_provider
     */
    public function __construct(
        Service_Provider $service_provider,
        Repository_Provider $repository_provider
    ) {
        $this->result_service      = $service_provider->get_result_service() ?? new Result_Service( $repository_provider->get_fixture_repository(), $repository_provider->get_team_repository() );
        $this->progression_service = $service_provider->get_progression_service() ?? new Knockout_Progression_Service();
        $this->league_service      = $service_provider->get_league_service() ?? new League_Service( $GLOBALS['racketmanager'], $repository_provider->get_league_repository(), new Event_Repository(), $repository_provider->get_league_team_repository(), $repository_provider->get_team_repository() );
        $this->score_validator     = $service_provider->get_score_validator() ?? new Score_Validation_Service();
        $this->settings_service    = $service_provider->get_settings_service() ?? new Settings_Service();

        $this->registration_service = $service_provider->get_registration_service();
        $this->player_validator     = $service_provider->get_player_validator() ?? new Player_Validation_Service( $this->registration_service, $repository_provider->get_results_checker_repository(), $repository_provider->get_fixture_repository() );
        $this->rubber_manager       = $service_provider->get_rubber_manager() ?? new Rubber_Result_Manager( $this->score_validator, $this->league_service, $repository_provider->get_rubber_repository(), $this->player_validator );

        $this->team_repository            = $repository_provider->get_team_repository();
        $this->player_repository          = $repository_provider->get_player_repository();
        $this->league_team_repository     = $repository_provider->get_league_team_repository();
        $this->league_repository          = $repository_provider->get_league_repository();
        $this->rubber_repository          = $repository_provider->get_rubber_repository();
        $this->results_checker_repository = $repository_provider->get_results_checker_repository();
        $this->fixture_repository         = $repository_provider->get_fixture_repository();
        $this->club_repository            = $repository_provider->get_club_repository();

        $this->notification_service = $service_provider->get_notification_service();
        if ( ! $this->notification_service ) {
            $this->notification_service = new Notification_Service(
                $repository_provider->get_league_repository(),
                $this->league_team_repository,
                $this->team_repository,
                $this->player_repository,
                $repository_provider->get_club_repository()
            );
        }
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

        $is_update_allowed = $this->is_update_allowed( $fixture );
        $validator         = new Validator_Fixture();
        $validator         = $validator->can_player_enter_result( $is_update_allowed, $request->sets );
        if ( ! empty( $validator->error ) ) {
            throw new Fixture_Validation_Exception(
                $validator->get_err_msgs(),
                $validator->get_err_flds()
            );
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

        if ( 'Y' === $request->confirmed ) {
            return $this->confirm_result( $fixture, '', null, $result );
        }

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

        $is_update_allowed = $this->is_update_allowed( $fixture );
        $validator         = new Validator_Fixture();
        $validator         = $validator->can_player_enter_result( $is_update_allowed, $request->players );
        if ( ! empty( $validator->error ) ) {
            return new Team_Result_Response( (array) $validator->get_details() );
        }

        $dummy_players = $this->get_dummy_players_for_fixture( $fixture );

        $is_withdrawn = $this->is_any_team_withdrawn( $fixture );
        $is_cancelled = 'cancelled' === $request->match_status;

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
        $update_standings       = false;

        if ( 'A' === $request->result_confirm ) {
            $match_msg = __( 'Result Approved', 'racketmanager' );
            if ( 'auto' === $result_confirmation ) {
                $update_standings       = true;
                $final_confirmed_status = 'Y';
            }
        } elseif ( 'C' === $request->result_confirm ) {
            $match_msg = __( 'Result Challenged', 'racketmanager' );
        }

        $result = new Result(
            home_points: (float) $fixture->get_home_points(),
            away_points: (float) $fixture->get_away_points(),
            status: $fixture->get_status(),
            sets: [],
            custom: $fixture->get_custom() ?: [] // sets for team match are in rubbers
        );

        if ( 'Y' === $final_confirmed_status ) {
            $this->confirm_result( $fixture, $actioned_by, $request->confirm_comments, $result, $league_repository );
        } else {
            $this->apply_confirmation_to_fixture(
                fixture: $fixture,
                status: $final_confirmed_status,
                actioned_by: $actioned_by,
                confirm_comments: $request->confirm_comments,
                result: $result,
                update_standings: $update_standings,
                run_checks: 'A' === $request->result_confirm,
                league_repository: $league_repository
            );
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
debug_to_console( $has_result_check);
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

        $league_repository = $league_repository ?? $this->league_repository;
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
     * Apply confirmation status and comments to a fixture.
     *
     * @param Fixture $fixture
     * @param string $status
     * @param string $actioned_by
     * @param string|null $confirm_comments
     * @param Result $result
     * @param bool $update_standings
     * @param bool $run_checks
     * @param League_Repository|null $league_repository
     * @return Fixture_Update_Response
     * @throws League_Not_Found_Exception
     */
    private function apply_confirmation_to_fixture( Fixture $fixture, string $status, string $actioned_by, ?string $confirm_comments, Result $result, bool $update_standings, bool $run_checks, ?League_Repository $league_repository = null ): Fixture_Update_Response {
        $fixture->set_confirmed( $status );

        $comments = $fixture->get_comments() ? maybe_unserialize( $fixture->get_comments() ) : [];
        if ( ! is_array( $comments ) ) {
            $comments = [];
        }

        if ( $actioned_by ) {
            $comments[ $actioned_by . '_confirm' ] = $confirm_comments;
        } else {
            $comments['confirm'] = $confirm_comments;
        }
        $fixture->set_comments( maybe_serialize( $comments ) );

        if ( $update_standings ) {
            $response = $this->update_result( $fixture, $result, $status, $league_repository );
        } else {
            $this->result_service->apply_to_fixture( $fixture, $result, $status );
            $response = new Fixture_Update_Response( [ Fixture_Update_Status::SAVED ] );
        }

        if ( $run_checks ) {
            $league = $this->league_service->get_league( $fixture->get_league_id() );
            if ( $league ) {
                $options = $this->settings_service->get_all_options();
                $this->player_validator->run_fixture_checks( $fixture, $league, $this->rubber_repository->find_by_fixture_id( (int) $fixture->get_id() ), $options );
            }
        }

        return $response;
    }

    /**
     * Confirm a fixture result.
     *
     * @param Fixture $fixture
     * @param string $actioned_by
     * @param string|null $confirm_comments
     * @param Result|null $result Optional result to apply.
     * @param League_Repository|null $league_repository Optional league repository for testing.
     * @return Fixture_Update_Response
     * @throws League_Not_Found_Exception
     */
    public function confirm_result( Fixture $fixture, string $actioned_by = '', ?string $confirm_comments = null, ?Result $result = null, ?League_Repository $league_repository = null ): Fixture_Update_Response {
        if ( ! $result ) {
            $result = new Result(
                home_points: (float) $fixture->get_home_points(),
                away_points: (float) $fixture->get_away_points(),
                status: $fixture->get_status(),
                sets: [],
                custom: $fixture->get_custom() ?: []
            );
        }

        return $this->apply_confirmation_to_fixture(
            fixture: $fixture,
            status: 'Y',
            actioned_by: $actioned_by,
            confirm_comments: $confirm_comments,
            result: $result,
            update_standings: true,
            run_checks: true,
            league_repository: $league_repository
        );
    }

    /**
     * Check if the current user is allowed to update a fixture.
     *
     * @param Fixture $fixture
     * @return object
     */
    public function is_update_allowed( Fixture $fixture ): object {
        $user_can_update = false;
        $user_type       = 'none';
        $user_team       = 'none';

        if ( is_user_logged_in() ) {
            $userid = get_current_user_id();

            if ( current_user_can( 'manage_racketmanager' ) ) {
                $user_type       = 'admin';
                $user_can_update = true;
            } else {
                $league = $this->league_repository->find_by_id( (int) $fixture->get_league_id() );
                if ( $league ) {
                    $competition_type = $league->event->competition->type;
                    $rm_options       = $this->settings_service->get_all_options();
                    $match_capability = $rm_options[ $competition_type ]['matchCapability'] ?? 'captains';
                    $result_entry     = $rm_options[ $competition_type ]['resultEntry'] ?? 'either';

                    $home_team = $this->team_repository->find_by_id( (int) $fixture->get_home_team() );
                    $away_team = $this->team_repository->find_by_id( (int) $fixture->get_away_team() );

                    if ( $home_team ) {
                        $home_club = $this->club_repository->find( (int) $home_team->get_club_id() );
                        if ( $home_club ) {
                            if ( isset( $home_club->match_secretary->id ) && intval( $home_club->match_secretary->id ) === $userid ) {
                                $user_type = 'matchsecretary';
                                $user_team = 'home';
                            } elseif ( $fixture->get_home_captain() && intval( $fixture->get_home_captain() ) === $userid ) {
                                $user_type = 'captain';
                                $user_team = 'home';
                            }
                        }
                    }

                    if ( $away_team && 'none' === $user_type ) {
                        $away_club = $this->club_repository->find( (int) $away_team->get_club_id() );
                        if ( $away_club ) {
                            if ( isset( $away_club->match_secretary->id ) && intval( $away_club->match_secretary->id ) === $userid ) {
                                $user_type = 'matchsecretary';
                                $user_team = 'away';
                            } elseif ( $fixture->get_away_captain() && intval( $fixture->get_away_captain() ) === $userid ) {
                                $user_type = 'captain';
                                $user_team = 'away';
                            }
                        }
                    }

                    if ( 'none' === $user_type && 'players' === $match_capability ) {
                        if ( $this->registration_service->is_player_active_in_club( $userid, (int) $home_team->get_club_id() ) ) {
                            $user_type = 'player';
                            $user_team = 'home';
                        } elseif ( $this->registration_service->is_player_active_in_club( $userid, (int) $away_team->get_club_id() ) ) {
                            $user_type = 'player';
                            $user_team = 'away';
                        }
                    }

                    if ( 'none' !== $user_type && ( 'either' === $result_entry || $user_team === $result_entry ) ) {
                        $user_can_update = true;
                    }
                }
            }
        }

        return (object) [
            'user_can_update' => $user_can_update,
            'user_type'       => $user_type,
            'user_team'       => $user_team,
        ];
    }
}
