<?php

namespace Racketmanager\Services\Fixture;

use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Domain\DTO\Fixture\Fixture_Confirmation_Context;
use Racketmanager\Domain\DTO\Fixture\Fixture_Reset_Response;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Confirmation_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Response;
use Racketmanager\Domain\DTO\Player\Player_Warnings_Response;
use Racketmanager\Domain\DTO\Rubber\Rubber_Update_Request;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Services\Container\Simple_Container;
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

use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Result\Result_Reporting_Service;
use Racketmanager\Services\Validator\Validator_Fixture;
use Racketmanager\Util\Util_Lookup;
use Racketmanager\Util\Util_Messages;

/**
 * Service for managing fixture results and state transitions.
 * Orchestrates logic formerly in Racketmanager_Match.
 */
class Fixture_Result_Manager {
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
    private ?Rubber_Result_Manager $rubber_manager;

    /**
     * @var Registration_Service|null
     */
    private ?Registration_Service $registration_service;

    /**
     * @var Notification_Service|null
     */
    private ?Notification_Service $notification_service;

    /**
     * @var Team_Repository_Interface|null
     */
    private ?Team_Repository_Interface $team_repository;

    /**
     * @var League_Team_Repository_Interface|null
     */
    private ?League_Team_Repository_Interface $league_team_repository;

    /**
     * @var Rubber_Repository_Interface|null
     */
    private ?Rubber_Repository_Interface $rubber_repository;

    /**
     * @var League_Repository_Interface|null
     */
    private ?League_Repository_Interface $league_repository;

    /**
     * @var Results_Checker_Repository_Interface|null
     */
    private ?Results_Checker_Repository_Interface $results_checker_repository;

    /**
     * @var Fixture_Repository_Interface|null
     */
    private ?Fixture_Repository_Interface $fixture_repository;

    /**
     * @var Fixture_Permission_Service
     */
    private Fixture_Permission_Service $permission_service;

    /** @var Result_Reporting_Service */
    private Result_Reporting_Service $result_reporting_service;
    private Fixture_Maintenance_Service $fixture_maintenance_service;

    private Service_Provider $service_provider;

    /**
     * @param Service_Provider $service_provider
     * @param Repository_Provider $repository_provider
     * @param Simple_Container|null $container
     */
    public function __construct(
        Service_Provider $service_provider,
        Repository_Provider $repository_provider,
        ?Simple_Container $container = null
    ) {
        $this->service_provider = $service_provider;
        $this->result_reporting_service = $service_provider->get_result_reporting_service() ?? new Result_Reporting_Service( $repository_provider );
        $this->fixture_maintenance_service = $service_provider->get_fixture_maintenance_service() ?? new Fixture_Maintenance_Service( $service_provider, $repository_provider, $this );
        $this->result_service      = $service_provider->get_result_service() ?? new Result_Service( $repository_provider->get_fixture_repository(), $repository_provider->get_team_repository() );
        
        $container = $container ?? $GLOBALS['racketmanager']->container ?? null;
        $this->progression_service = $service_provider->get_progression_service( $container ) ?? new Knockout_Progression_Service();
        $this->league_service      = $service_provider->get_league_service() ?? new League_Service( $GLOBALS['racketmanager'], $repository_provider->get_league_repository(), new Event_Repository(), $repository_provider->get_league_team_repository(), $repository_provider->get_team_repository() );
        $this->score_validator     = $service_provider->get_score_validator() ?? new Score_Validation_Service();
        $this->settings_service    = $service_provider->get_settings_service() ?? new Settings_Service();

        $this->registration_service = $service_provider->get_registration_service();
        $this->player_validator     = $service_provider->get_player_validator() ?? new Player_Validation_Service( $this->registration_service, $repository_provider->get_results_checker_repository(), $repository_provider->get_fixture_repository() );
        $this->rubber_manager       = $service_provider->get_rubber_manager() ?? new Rubber_Result_Manager( $this->score_validator, $this->league_service, $repository_provider->get_rubber_repository(), $this->player_validator );

        $this->team_repository            = $repository_provider->get_team_repository();
        $this->league_team_repository     = $repository_provider->get_league_team_repository();
        $this->league_repository          = $repository_provider->get_league_repository();
        $this->rubber_repository          = $repository_provider->get_rubber_repository();
        $this->results_checker_repository = $repository_provider->get_results_checker_repository();
        $this->fixture_repository         = $repository_provider->get_fixture_repository();

        $this->permission_service = $service_provider->get_fixture_permission_service() ?? new Fixture_Permission_Service( $repository_provider, $this->registration_service );

        $this->notification_service = $service_provider->get_notification_service();
        if ( ! $this->notification_service && $container ) {
            $this->notification_service = new Notification_Service(
                $repository_provider,
                $this->settings_service,
                $container->get( 'notification_presenter' ),
                $container->get( 'view_renderer' ),
                $GLOBALS['racketmanager']
            );
        }
    }

    /**
     * Handle result update for a single fixture (player/tournament match).
     *
     * @return Service_Provider
     */
    public function get_service_provider(): Service_Provider {
        return $this->service_provider;
    }

    public function handle_fixture_result_update( Fixture $fixture, Fixture_Result_Update_Request $request ): Fixture_Update_Response {
        $league_id = $fixture->get_league_id();
        $league = $league_id ? $this->league_service->get_league( $league_id ) : null;
        if ( ! $league ) {
            throw new League_Not_Found_Exception( Util_Messages::league_not_found_for_fixture( $fixture->get_id() ) );
        }

        $is_update_allowed = $this->is_update_allowed( $fixture );
        $validator         = new Validator_Fixture();
        $validator         = $validator->can_player_enter_result( $is_update_allowed, $request->sets );
        if ( ! empty( $validator->error ) ) {
            throw new Fixture_Validation_Exception(
                $validator->err_msgs,
                $validator->err_flds
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

        if ( ! $fixture->get_date_result_entered() ) {
            $fixture->set_date_result_entered( date( 'Y-m-d H:i:s' ) );
        }

        $this->assign_captain_to_fixture( $fixture, $is_update_allowed->user_team );

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
     * @param League_Repository_Interface|null $league_repository Optional league repository for testing.
     *
     * @return Team_Result_Response
     * @throws Fixture_Validation_Exception
     * @throws League_Not_Found_Exception
     */
    public function handle_team_result_update( Fixture $fixture, Team_Result_Update_Request $request, ?League_Repository_Interface $league_repository = null ): Team_Result_Response {
        $league = $this->get_league_for_fixture( $fixture );

        $is_update_allowed = $this->is_update_allowed( $fixture );
        $validator         = ( new Validator_Fixture() )->can_player_enter_result( $is_update_allowed, $request->players );
        if ( ! empty( $validator->error ) ) {
            return new Team_Result_Response( (array) $validator->get_details() );
        }

        $rubber_info       = $this->process_rubbers( $fixture, $request, $validator );
        if ( ! empty( $validator->error ) ) {
            $details = (array) $validator->get_details();
            $details['rubbers'] = $rubber_info['updated_rubbers'];
            return new Team_Result_Response( $details );
        }

        $result = $this->finalize_fixture_result( $fixture, $league, $request, $rubber_info['processed_rubbers'] );

        $confirmed = null;
        if ( empty( $fixture->get_confirmed() ) ) {
            $confirmed = 'P'; // Pending
            $fixture->set_updated_by( $is_update_allowed->user_team );
        }

        if ( ! $fixture->get_date_result_entered() ) {
            $fixture->set_date_result_entered( date( 'Y-m-d H:i:s' ) );
        }

        $this->assign_captain_to_fixture( $fixture, $is_update_allowed->user_team );

        $this->update_result( $fixture, $result, $confirmed, $league_repository );

        $this->player_validator->run_fixture_checks( $fixture, $league, $rubber_info['updated_rubbers'], $this->settings_service->get_all_options() );

        $this->notification_service?->send_result_notification( $fixture, $confirmed ? : 'P', __( 'Result saved', 'racketmanager' ) );

        return $this->build_team_result_response( $fixture, $rubber_info['updated_rubbers'] );
    }

    /**
     * Process all rubbers for a fixture update.
     *
     * @param Fixture $fixture
     * @param Team_Result_Update_Request $request
     * @param Validator_Fixture $validator
     * @return array
     */
    private function process_rubbers( Fixture $fixture, Team_Result_Update_Request $request, Validator_Fixture $validator ): array {
        $dummy_players     = $this->get_dummy_players_for_fixture( $fixture );
        $is_withdrawn      = $this->is_any_team_withdrawn( $fixture );
        $is_cancelled      = 'cancelled' === $request->match_status;
        $processed_rubbers = [];
        $updated_rubbers   = [];

        foreach ( $request->rubber_ids as $ix => $rubber_id ) {
            try {
                $rubber_result = $this->process_rubber_update( $fixture, $request, (int) $ix, $is_withdrawn, $is_cancelled, $dummy_players );

                $updated_rubbers[ (int) $request->rubber_ids[ $ix ] ] = [
                    'id'          => $rubber_id,
                    'players'     => $rubber_result->players,
                    'homepoints'  => $rubber_result->home_points,
                    'awaypoints'  => $rubber_result->away_points,
                    'sets'        => $rubber_result->sets,
                    'winner'      => $rubber_result->winner_id,
                ];

                $processed_rubbers[] = (object) [
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

        return [
            'processed_rubbers' => $processed_rubbers,
            'updated_rubbers'   => $updated_rubbers,
        ];
    }

    /**
     * Finalize fixture result calculation.
     *
     * @param Fixture $fixture
     * @param League $league
     * @param Team_Result_Update_Request $request
     * @param array $processed_rubbers
     *
     * @return Result
     */
    private function finalize_fixture_result( Fixture $fixture, League $league, Team_Result_Update_Request $request, array $processed_rubbers ): Result {
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

        $custom          = $fixture->get_custom() ?: [];
        $custom['stats'] = $stats_result['stats'];

        $determined = Result_Calculator::determine_winner_and_loser(
            $match_points['home_points'],
            $match_points['away_points'],
            (int) $fixture->get_home_team(),
            (int) $fixture->get_away_team(),
            Util_Lookup::get_match_status_code( $request->match_status ),
            $custom
        );

        return new Result(
            home_points: $match_points['home_points'],
            away_points: $match_points['away_points'],
            winner_id: $determined['winner_id'] ? (int) $determined['winner_id'] : null,
            loser_id: $determined['loser_id'] ? (int) $determined['loser_id'] : null,
            status: Util_Lookup::get_match_status_code( $request->match_status ),
            sets: [], // Team fixture sets are in rubbers
            custom: $custom
        );
    }

    /**
     * Build the team result response.
     *
     * @param Fixture $fixture
     * @param array $updated_rubbers
     * @return Team_Result_Response
     */
    private function build_team_result_response( Fixture $fixture, array $updated_rubbers ): Team_Result_Response {
        $response = new Team_Result_Response( [
            'msg'       => __( 'Result saved', 'racketmanager' ),
            'rubbers'   => $updated_rubbers,
            'status'    => 'success',
            'winner_id' => $fixture->get_winner_id(),
            'loser_id'  => $fixture->get_loser_id(),
        ] );

        $warnings = $this->handle_player_warnings( $fixture );
        if ( ! empty( $warnings->msg ) ) {
            $response->msg   .= $warnings->msg;
            $response->status = $warnings->status;
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
     * @param League_Repository_Interface|null $league_repository Optional league repository for testing.
     *
     * @return Team_Result_Response
     * @throws League_Not_Found_Exception
     */
    public function handle_team_result_confirmation( Fixture $fixture, Team_Result_Confirmation_Request $request, ?League_Repository_Interface $league_repository = null ): Team_Result_Response {
        $validator = new Validator_Fixture();
        $validator = $validator->result_confirm( $request->result_confirm, $request->confirm_comments );
        if ( ! empty( $validator->error ) ) {
            return new Team_Result_Response( (array) $validator->get_details() );
        }

        $league = $this->get_league_for_fixture( $fixture );
        $actioned_by = $this->determine_actioned_by( $request );
        
        $final_confirmed_status = $request->result_confirm;
        $update_standings       = false;

        if ( 'A' === $request->result_confirm ) {
            $result_confirmation = $this->settings_service->get_option( $league->get_competition_type(), 'resultConfirmation', 'manual' );
            if ( 'auto' === $result_confirmation ) {
                $update_standings       = true;
                $final_confirmed_status = 'Y';
            }
        }

        $result = $this->create_result_from_fixture( $fixture );

        if ( 'Y' === $final_confirmed_status ) {
            $this->confirm_result( $fixture, $actioned_by, $request->confirm_comments, $result, $league_repository );
        } else {
            $this->apply_confirmation_to_fixture(
                $fixture,
                new Fixture_Confirmation_Context(
                    status: $final_confirmed_status,
                    actioned_by: $actioned_by,
                    confirm_comments: $request->confirm_comments,
                    result: $result,
                    update_standings: $update_standings,
                    run_checks: 'A' === $request->result_confirm,
                    league_repository: $league_repository
                )
            );
        }

        return $this->prepare_confirmation_response( $fixture, $request->result_confirm, $final_confirmed_status, $actioned_by );
    }

    /**
     * Get the league associated with a fixture.
     *
     * @param Fixture $fixture
     * @return League
     * @throws League_Not_Found_Exception
     */
    private function get_league_for_fixture( Fixture $fixture ): League {
        $league_id = $fixture->get_league_id();
        $league    = $league_id ? $this->league_service->get_league( $league_id ) : null;
        if ( ! $league ) {
            throw new League_Not_Found_Exception( Util_Messages::league_not_found_for_fixture( $fixture->get_id() ) );
        }
        return $league;
    }

    /**
     * Determine which team actioned the confirmation.
     *
     * @param Team_Result_Confirmation_Request $request
     * @return string
     */
    private function determine_actioned_by( Team_Result_Confirmation_Request $request ): string {
        if ( $request->result_home ) {
            return 'home';
        } elseif ( $request->result_away ) {
            return 'away';
        }
        return '';
    }

    /**
     * Create a Result object based on the current fixture data.
     *
     * @param Fixture $fixture
     * @return Result
     */
    private function create_result_from_fixture( Fixture $fixture ): Result {
        return new Result(
            home_points: (float) ( $fixture->get_home_points() ?? 0 ),
            away_points: (float) ( $fixture->get_away_points() ?? 0 ),
            status: $fixture->get_status(),
            sets: [],
            custom: $fixture->get_custom() ?: []
        );
    }

    /**
     * Prepare the response after handling result confirmation.
     *
     * @param Fixture $fixture
     * @param string $requested_status
     * @param string $final_status
     * @param string $actioned_by
     * @return Team_Result_Response
     */
    private function prepare_confirmation_response( Fixture $fixture, string $requested_status, string $final_status, string $actioned_by ): Team_Result_Response {
        $match_msg = null;
        if ( 'A' === $requested_status ) {
            $match_msg = __( 'Result Approved', 'racketmanager' );
        } elseif ( 'C' === $requested_status ) {
            $match_msg = __( 'Result Challenged', 'racketmanager' );
        }

        // Handle notifications (via notification service)
        $this->notification_service?->send_result_notification( $fixture, $final_status, $match_msg ?: '', $actioned_by ?: false );

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
        $fixture_id = $fixture->get_id();
        if ( ! $fixture_id || ! $this->results_checker_repository->has_results_check( $fixture_id ) ) {
            return new Player_Warnings_Response( '', null, [] );
        }

        $player_warnings = [];
        $warning_match   = [];
        $warning_player  = false;

        $result_warnings = $this->results_checker_repository->find_by_fixture_id( $fixture_id );

        foreach ( $result_warnings as $warning ) {
            if ( $warning->rubber_id ) {
                $ref = $this->get_player_warning_reference( $fixture, $warning );
                if ( $ref ) {
                    $player_warnings[ $ref ] = $warning->description;
                    $warning_player          = true;
                }
            } else {
                $warning_match[] = $warning->description;
            }
        }

        $msg = $this->build_player_warnings_message( $warning_player, $warning_match );

        return new Player_Warnings_Response( $msg, 'warning', $player_warnings );
    }

    /**
     * Build the warning message for player warnings.
     *
     * @param bool $has_player_warnings
     * @param array $match_warnings
     * @return string
     */
    private function build_player_warnings_message( bool $has_player_warnings, array $match_warnings ): string {
        $msg = '';
        if ( $has_player_warnings ) {
            $msg .= '<br>' . __( 'Match has player warnings', 'racketmanager' );
        }
        foreach ( $match_warnings as $warning ) {
            $msg .= '<br>' . $warning;
        }
        return $msg;
    }

    /**
     * Get the player warning reference for a specific warning.
     *
     * @param Fixture $fixture
     * @param object $warning
     * @return string|null
     */
    private function get_player_warning_reference( Fixture $fixture, object $warning ): ?string {
        $rubber = $this->rubber_repository->find_by_id( (int) $warning->rubber_id );
        if ( ! $rubber ) {
            return null;
        }

        $team          = ( (int) $warning->team_id === (int) $fixture->get_home_team() ) ? 'home' : 'away';
        $player_number = ( (int) $warning->player_id === (int) $rubber->players[ $team ]['1']->id ) ? 1 : 2;

        return 'players_' . $rubber->rubber_number . '_' . $team . '_' . $player_number;
    }

    /**
     * Update a fixture with a new result.
     *
     * @param Fixture $fixture The fixture to update.
     * @param Result $result The new result.
     * @param string|null $confirmed Confirmation status ('Y', 'N', or null).
     * @param League_Repository_Interface|null $league_repository Optional league repository for testing.
     * @return Fixture_Update_Response
     */
    public function update_result( Fixture $fixture, Result $result, ?string $confirmed = null, ?League_Repository_Interface $league_repository = null ): Fixture_Update_Response {
        $this->result_service->apply_to_fixture( $fixture, $result, $confirmed );

        $outcomes = [ Fixture_Update_Status::SAVED ];

        $league_id = $fixture->get_league_id();
        if ( ! $league_id ) {
            return new Fixture_Update_Response( $outcomes );
        }

        $league_repository = $league_repository ?? $this->league_repository;
        $league            = $league_repository->find_by_id( $league_id );
        if ( ! $league ) {
            return new Fixture_Update_Response( $outcomes );
        }

        $stage = Stage::from_league( $league );

        if ( $league->is_championship ) {
            $this->progression_service->progress_winner( $stage, $fixture, $league );
            $this->progression_service->handle_consolation( $stage, $fixture, $league );
            $outcomes[] = Fixture_Update_Status::PROGRESSED;
        } else {
            $league->update_standings( (string) $fixture->get_season() );
            $outcomes[] = Fixture_Update_Status::TABLE_UPDATED;
        }

        return new Fixture_Update_Response( $outcomes );
    }

    /**
     * Reset the result of a fixture.
     *
     * @param Fixture $fixture
     *
     * @return Fixture_Reset_Response
     * @throws League_Not_Found_Exception
     */
    public function reset_result( Fixture $fixture ): Fixture_Reset_Response {
        $fixture->reset_result();

        // Persist the reset state.
        $empty_result = new Result(
            home_points: 0.0,
            away_points: 0.0,
            status: null,
            sets: [],
            custom: []
        );
        $this->result_service->apply_to_fixture( $fixture, $empty_result );

        $league_id = $fixture->get_league_id();
        try {
            $league = $league_id ? $this->league_service->get_league( $league_id ) : null;
        } catch ( League_Not_Found_Exception ) {
            $league = null;
        }

        if ( $league && $league->is_championship ) {
            $stage = Stage::from_league( $league );
            $this->progression_service->reset_progression( $stage, $fixture, $league );
        } elseif ( $league ) {
            $league->update_standings( (string) $fixture->get_season() );
        }

        $status = ( $league && $league->is_championship )
            ? Fixture_Reset_Status::SUCCESS_KNOCKOUT_RESET
            : Fixture_Reset_Status::SUCCESS_DIVISION_RESET;

        return new Fixture_Reset_Response( (int) $fixture->get_id(), $status );

    }

    /**
     * Apply confirmation status and comments to a fixture.
     *
     * @param Fixture $fixture
     * @param Fixture_Confirmation_Context $context
     * @return Fixture_Update_Response
     * @throws League_Not_Found_Exception
     */
    private function apply_confirmation_to_fixture( Fixture $fixture, Fixture_Confirmation_Context $context ): Fixture_Update_Response {
        $fixture->set_confirmed( $context->status );

        $comments = $fixture->get_comments() ? maybe_unserialize( $fixture->get_comments() ) : [];
        if ( ! is_array( $comments ) ) {
            $comments = [];
        }

        if ( $context->actioned_by ) {
            $comments[ $context->actioned_by . '_confirm' ] = $context->confirm_comments;
        } else {
            $comments['confirm'] = $context->confirm_comments;
        }
        $fixture->set_comments( maybe_serialize( $comments ) );

        if ( $context->update_standings ) {
            $response = $this->update_result( $fixture, $context->result, $context->status, $context->league_repository );
        } else {
            $this->result_service->apply_to_fixture( $fixture, $context->result, $context->status );
            $response = new Fixture_Update_Response( [ Fixture_Update_Status::SAVED ] );
        }

        if ( $context->run_checks ) {
            $league_id = $fixture->get_league_id();
            $league = $league_id ? $this->league_service->get_league( $league_id ) : null;
            if ( $league ) {
                $options = $this->settings_service->get_all_options();
                $this->player_validator->run_fixture_checks( $fixture, $league, $this->rubber_repository->find_by_fixture_id( (int) $fixture->get_id() ), $options );
            }
        }

        if ( 'Y' === $fixture->get_confirmed() ) {
            $this->fixture_maintenance_service->delete_result_report( (int) $fixture->get_id() );
            $report_data = $this->result_reporting_service->report_result( $fixture );
            if ( $report_data ) {
                $this->fixture_maintenance_service->save_result_report( (int) $fixture->get_id(), $report_data );
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
     * @param League_Repository_Interface|null $league_repository Optional league repository for testing.
     * @return Fixture_Update_Response
     * @throws League_Not_Found_Exception
     */
    public function confirm_result( Fixture $fixture, string $actioned_by = '', ?string $confirm_comments = null, ?Result $result = null, ?League_Repository_Interface $league_repository = null ): Fixture_Update_Response {
        if ( ! $result ) {
            $result = new Result(
                home_points: (float) ( $fixture->get_home_points() ?? 0 ),
                away_points: (float) ( $fixture->get_away_points() ?? 0 ),
                status: $fixture->get_status(),
                sets: [],
                custom: $fixture->get_custom() ?: []
            );
        }

        return $this->apply_confirmation_to_fixture(
            $fixture,
            new Fixture_Confirmation_Context(
                status: 'Y',
                actioned_by: $actioned_by,
                confirm_comments: $confirm_comments,
                result: $result,
                update_standings: true,
                run_checks: true,
                league_repository: $league_repository
            )
        );

    }

    /**
     * Assign the current user as the captain for the fixture if not already set.
     *
     * @param Fixture $fixture
     * @param string $team 'home' or 'away'
     */
    private function assign_captain_to_fixture( Fixture $fixture, string $team ): void {
        if ( 'home' !== $team && 'away' !== $team ) {
            return;
        }

        $current_user_id = get_current_user_id();
        if ( ! $current_user_id ) {
            return;
        }

        if ( 'home' === $team && ! $fixture->get_home_captain() ) {
            $fixture->set_home_captain( (string) $current_user_id );
        } elseif ( 'away' === $team && ! $fixture->get_away_captain() ) {
            $fixture->set_away_captain( (string) $current_user_id );
        }
    }

    /**
     * Check if the current user is allowed to update a fixture.
     *
     * @param Fixture $fixture
     * @return object
     */
    public function is_update_allowed( Fixture $fixture ): object {
        return $this->permission_service->is_update_allowed( $fixture );
    }

    /**
     * Update aggregate result for a multi-leg tie.
     *
     * @param Fixture $fixture
     * @return Fixture_Update_Response
     */
    public function update_result_tie( Fixture $fixture ): Fixture_Update_Response {
        $linked_fixture = $this->validate_tie_update( $fixture );
        if ( ! $linked_fixture ) {
            return new Fixture_Update_Response( [] );
        }

        $aggregate = Result_Calculator::calculate_aggregate_result(
            (float) $fixture->get_home_points(),
            (float) $fixture->get_away_points(),
            (float) $linked_fixture->get_home_points(),
            (float) $linked_fixture->get_away_points(),
            (int) $fixture->get_home_team(),
            (int) $fixture->get_away_team()
        );

        $fixture->set_home_points_tie( $aggregate['home_points_tie'] );
        $fixture->set_away_points_tie( $aggregate['away_points_tie'] );
        $fixture->set_winner_id_tie( (int) $aggregate['winner_id_tie'] );
        $fixture->set_loser_id_tie( (int) $aggregate['loser_id_tie'] );

        $this->fixture_repository->save( $fixture );

        return new Fixture_Update_Response( [ Fixture_Update_Status::TIE_UPDATED ] );
    }

    /**
     * Validate if a tie update is possible.
     *
     * @param Fixture $fixture
     * @return Fixture|null
     */
    private function validate_tie_update( Fixture $fixture ): ?Fixture {
        if ( 2 !== $fixture->get_leg() || ! $fixture->get_linked_fixture() ) {
            return null;
        }

        $linked_fixture = $this->fixture_repository->find_by_id( $fixture->get_linked_fixture() );
        if ( ! $linked_fixture || ! $linked_fixture->get_winner_id() ) {
            return null;
        }

        return $linked_fixture;
    }

    /**
     * Update leg and linked match for a fixture.
     *
     * @param Fixture $fixture
     * @param int $leg
     * @param int $linked_match_id
     * @return Fixture_Update_Response
     */
    public function update_legs( Fixture $fixture, int $leg, int $linked_match_id ): Fixture_Update_Response {
        $fixture->set_leg( $leg );
        $fixture->set_linked_fixture( $linked_match_id );
        $this->fixture_repository->save( $fixture );

        return new Fixture_Update_Response( [ Fixture_Update_Status::LEGS_UPDATED ] );
    }

    /**
     * Apply penalty points to a team's result in a fixture.
     *
     * @param Fixture $fixture
     * @param string $team_ref 'home' or 'away'
     * @param int $penalty
     * @return Fixture_Update_Response
     */
    public function apply_penalty( Fixture $fixture, string $team_ref, int $penalty ): Fixture_Update_Response {
        $home_points = (float) $fixture->get_home_points();
        $away_points = (float) $fixture->get_away_points();

        if ( 'home' === $team_ref ) {
            $home_points -= $penalty;
        } elseif ( 'away' === $team_ref ) {
            $away_points -= $penalty;
        }

        $fixture->set_home_points( (string) $home_points );
        $fixture->set_away_points( (string) $away_points );

        $winner_loser = Result_Calculator::determine_winner_and_loser(
            $home_points,
            $away_points,
            (int) $fixture->get_home_team(),
            (int) $fixture->get_away_team(),
            $fixture->get_status(),
            $fixture->get_custom() ?? []
        );

        $fixture->set_winner_id( (int) $winner_loser['winner_id'] );
        $fixture->set_loser_id( (int) $winner_loser['loser_id'] );

        $this->fixture_repository->save( $fixture );

        $outcomes = [ Fixture_Update_Status::PENALTY_APPLIED ];

        if ( 2 === $fixture->get_leg() ) {
            $tie_response = $this->update_result_tie( $fixture );
            $outcomes     = array_merge( $outcomes, $tie_response->outcomes );
        }

        return new Fixture_Update_Response( $outcomes );
    }
}
