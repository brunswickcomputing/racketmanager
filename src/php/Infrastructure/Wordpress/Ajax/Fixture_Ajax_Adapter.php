<?php

namespace Racketmanager\Infrastructure\Wordpress\Ajax;

use Exception;
use Racketmanager\Domain\DTO\Fixture\Fixture_Date_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Reset_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Status_Options_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Switch_Teams_Request;
use Racketmanager\Domain\DTO\Fixture\Match_Option_Request;
use Racketmanager\Domain\DTO\Fixture\Rubber_Status_Options_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Status_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Confirmation_Request;
use Racketmanager\Domain\DTO\Fixture\Team_Result_Update_Request;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Fixture_Not_Found_Exception;
use Racketmanager\Exceptions\Fixture_Validation_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\Presenters\Fixture_Presenter;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Fixture\Service_Provider as Fixture_Service_Provider;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Services\Validator\Validator_Fixture;
use Racketmanager\Util\Util_Messages;
use function Racketmanager\match_header;
use function Racketmanager\show_alert;

/**
 * Adapter for Fixture AJAX requests
 */
final class Fixture_Ajax_Adapter {
    private Simple_Container $container;
    private Security_Service_Interface $security_service;
    private Json_Response_Factory_Interface $response_factory;
    private Fixture_Detail_Service $fixture_detail_service;
    private View_Renderer_Interface $view_renderer;
    private Fixture_Presenter $presenter;

    public function __construct(
        $container,
        Security_Service_Interface $security_service,
        Json_Response_Factory_Interface $response_factory,
        Fixture_Detail_Service $fixture_detail_service,
        View_Renderer_Interface $view_renderer,
        Fixture_Presenter $presenter
    ) {
        $this->container              = $container;
        $this->security_service       = $security_service;
        $this->response_factory       = $response_factory;
        $this->fixture_detail_service = $fixture_detail_service;
        $this->view_renderer          = $view_renderer;
        $this->presenter              = $presenter;
    }

    /**
     * Build screen to allow printing of match cards
     */
    public function print_match_card(): Response {
        if ( ! $this->security_service->verify_nonce( $_POST['security'] ?? '', 'ajax-nonce' ) ) {
            return $this->response_factory->create_error_response( array( 'msg' => __( 'Security check failed', 'racketmanager' ) ), 403 );
        }

        try {
            $match_id = isset( $_POST['matchId'] ) ? intval( $_POST['matchId'] ) : null;
            if ( ! $match_id ) {
                throw new Fixture_Not_Found_Exception( __( 'Match id not supplied', 'racketmanager' ), 400 );
            }

            $dto = $this->fixture_detail_service->get_fixture_with_details( $match_id );
            if ( ! $dto ) {
                throw new Fixture_Not_Found_Exception( Util_Messages::fixture_not_found( $match_id ), 404 );
            }

            $match    = $dto->fixture;
            $template = 'match/match-card';
            if ( ! empty( $dto->league->num_rubbers ) ) {
                $match->rubbers = $match->get_rubbers();
                $template       = 'match/match-card-rubbers';
            }

            $output = $this->view_renderer->render_to_string(
                $template,
                [
                    'dto'          => $dto,
                    'match'        => $match,
                    'sponsor_html' => '',
                ]
            );

            $response = $this->response_factory->create_raw_response( $output );
        } catch ( Fixture_Not_Found_Exception $e ) {
            $response = $this->response_factory->create_error_response( array( 'msg' => $e->getMessage() ), $e->getCode() ?: 400 );
        }

        return $response;
    }

    /**
     * Update fixture result
     */
    public function update_fixture_result(): Response {
        if ( ! $this->security_service->verify_nonce( $_POST['racketmanager_nonce'] ?? '', 'scores-match' ) ) {
            return $this->response_factory->create_error_response( array( 'msg' => __( 'Security check failed', 'racketmanager' ) ), 403 );
        }

        try {
            $request    = Fixture_Result_Update_Request::from_post( $_POST );
            $fixture_id = $request->fixture_id;

            $fixture_repository = $this->container->get( 'fixture_repository' );
            $fixture            = $fixture_repository->find_by_id( $fixture_id );

            if ( ! $fixture ) {
                throw new Fixture_Not_Found_Exception( Util_Messages::fixture_not_found( $fixture_id ), 404 );
            }

            $result_manager = $this->get_fixture_result_manager();
            $result_response = $result_manager->handle_fixture_result_update( $fixture, $request );

            $read_model = $this->presenter->map_to_result_read_model( $fixture, $result_response, $request );

            $response = $this->response_factory->create_success_response( $read_model->to_array() );

        } catch ( Fixture_Validation_Exception $e ) {
            $response = $this->response_factory->create_error_response(
                array(
                    'msg'      => __( 'Unable to update result', 'racketmanager' ),
                    'err_msgs' => $e->get_error_msgs(),
                    'err_flds' => $e->get_error_flds(),
                ),
                400
            );
        } catch ( Fixture_Not_Found_Exception $e ) {
            $response = $this->response_factory->create_error_response( array( 'msg' => $e->getMessage() ), $e->getCode() ?: 400 );
        }

        return $response;
    }

    /**
     * Build screen to allow match status to be captured
     */
    public function match_status_options(): Response {
        if ( ! $this->security_service->verify_nonce( $_POST['security'] ?? '', 'ajax-nonce' ) ) {
            return $this->response_factory->create_error_response( array( 'msg' => __( 'Security check failed', 'racketmanager' ) ), 403 );
        }

        try {
            $request = Fixture_Status_Options_Request::from_post( $_POST );
            $request->validate();

            $dto = $this->fixture_detail_service->get_fixture_with_details( $request->fixture_id );
            if ( ! $dto ) {
                throw new Fixture_Not_Found_Exception( Util_Messages::fixture_not_found( $request->fixture_id ), 404 );
            }

            $vars = $this->presenter->map_to_status_options( $dto, $request->match_status, $request->modal );
            $html = $this->view_renderer->render_to_string( 'match/match-status-modal', $vars );

            $response = $this->response_factory->create_raw_response( $html );

        } catch ( Fixture_Validation_Exception $e ) {
            $response = $this->response_factory->create_error_response( array( 'msg' => $e->get_error_msgs()[0] ), 400 );
        } catch ( Fixture_Not_Found_Exception $e ) {
            $response = $this->response_factory->create_error_response( array( 'msg' => $e->getMessage() ), $e->getCode() ?: 500 );
        }

        return $response;
    }

    /**
     * Build screen to allow rubber status to be captured
     */
    public function match_rubber_status_options(): Response {
        if ( ! $this->security_service->verify_nonce( $_POST['security'] ?? '', 'ajax-nonce' ) ) {
            return $this->create_alert_response( __( 'Security check failed', 'racketmanager' ), 403 );
        }

        try {
            $request = Rubber_Status_Options_Request::from_post( $_POST );
            $request->validate();

            $rubber = \Racketmanager\get_rubber( $request->rubber_id );
            if ( ! $rubber ) {
                throw new Fixture_Not_Found_Exception( __( 'Rubber not found', 'racketmanager' ), 404 );
            }

            $dto = $this->fixture_detail_service->get_fixture_with_details( $rubber->match_id );
            if ( ! $dto ) {
                throw new Fixture_Not_Found_Exception( __( 'Fixture not found', 'racketmanager' ), 404 );
            }

            $vars = $this->presenter->map_to_rubber_status_options( $dto, $rubber, $request->score_status, $request->modal );
            $html = $this->view_renderer->render_to_string( 'match/rubber-status-modal', $vars );

            $response = $this->response_factory->create_raw_response( $html );

        } catch ( Fixture_Validation_Exception $e ) {
            $response = $this->create_alert_response( $e->getMessage() ?: '', 400 );
        } catch ( Fixture_Not_Found_Exception $e ) {
            $response = $this->create_alert_response( $e->getMessage(), $e->getCode() ?: 401 );
        } catch ( Exception ) {
            $response = $this->create_alert_response( __( 'An unexpected error occurred', 'racketmanager' ), 500 );
        }

        return $response;
    }

    /**
     * Set match status
     */
    public function set_match_status(): Response {
        return $this->handle_status_update( 'match-status' );
    }

    /**
     * Build screen to show the selected match option
     */
    public function show_match_option(): Response {
        if ( ! $this->security_service->verify_nonce( $_POST['security'] ?? '', 'ajax-nonce' ) ) {
            return $this->create_alert_response( __( 'Security check failed', 'racketmanager' ), 403 );
        }

        try {
            $request = Match_Option_Request::from_post( $_POST );
            $request->validate();

            $dto = $this->fixture_detail_service->get_fixture_with_details( $request->match_id );
            if ( ! $dto ) {
                throw new Fixture_Not_Found_Exception( __( 'Match not found', 'racketmanager' ), 404 );
            }

            switch ( $request->option ) {
                case 'schedule_match':
                    $title  = __( '(Re)schedule fixture', 'racketmanager' );
                    $button = __( 'Save', 'racketmanager' );
                    $action = 'setMatchDate';
                    break;
                case 'adjust_team_score':
                    $title  = __( 'Adjust team score', 'racketmanager' );
                    $button = __( 'Change Results', 'racketmanager' );
                    $action = 'adjustTeamScore';
                    break;
                case 'switch_home':
                    $title  = __( 'Switch home and away', 'racketmanager' );
                    $button = __( 'Switch', 'racketmanager' );
                    $action = 'switchHomeAway';
                    break;
                case 'reset_match_result':
                    $title  = __( 'Reset result', 'racketmanager' );
                    $button = __( 'Save', 'racketmanager' );
                    $action = 'resetMatchResult';
                    break;
                default:
                    throw new Fixture_Validation_Exception( [ __( 'Invalid fixture option', 'racketmanager' ) ] );
            }

            $filename = ( ! empty( $request->template ) ) ? 'match/match-option-modal-' . $request->template : 'match/match-option-modal';

            $vars = $this->presenter->map_to_match_option_vars( $dto, $request, $title, $button, $action );

            $html = $this->view_renderer->render_to_string( $filename, $vars );

            $response = $this->response_factory->create_raw_response( $html );

        } catch ( Fixture_Validation_Exception $e ) {
            $response = $this->create_alert_response( $e->getMessage() ?: '', 400 );
        } catch ( Fixture_Not_Found_Exception $e ) {
            $response = $this->create_alert_response( $e->getMessage(), $e->getCode() ?: 401 );
        } catch ( Exception ) {
            $response = $this->create_alert_response( __( 'An unexpected error occurred', 'racketmanager' ), 500 );
        }

        return $response;
    }

    /**
     * Set match rubber status
     *
     */
    public function set_match_rubber_status(): Response {
        return $this->handle_status_update( 'match-rubber-status' );
    }

    /**
     * Set the match date
     */
    public function set_match_date(): Response {
        if ( ! $this->security_service->verify_nonce( $_POST['racketmanager_nonce'] ?? '', 'match-option' ) ) {
            return $this->create_alert_response( __( 'Security check failed', 'racketmanager' ), 403 );
        }

        try {
            $request = Fixture_Date_Update_Request::from_post( $_POST );
            $request->validate();

            $fixture_repository = $this->container->get( 'fixture_repository' );
            $match              = $fixture_repository->find_by_id( $request->match_id );
            if ( ! $match ) {
                throw new Fixture_Not_Found_Exception( Util_Messages::fixture_not_found( $request->match_id ), 404 );
            }

            if ( strlen( (string) $request->schedule_date ) === 10 ) {
                $schedule_date_fmt = mysql2date( 'D j M', $request->schedule_date );
            } else {
                $schedule_date_fmt = mysql2date( 'j F Y H:i', $request->schedule_date );
            }

            $maintenance_service = $this->get_fixture_maintenance_service();
            $maintenance_service->update_fixture_date( $request->match_id, (string) $request->schedule_date, $match->get_date() );
            $maintenance_service->update_fixture_status( $request->match_id, 5 );

            $read_model = $this->presenter->map_to_date_update_read_model( $request, $schedule_date_fmt );

            $response = $this->response_factory->create_success_response( $read_model->to_array() );
        } catch ( Fixture_Validation_Exception $e ) {
            $response = $this->response_factory->create_error_response(
                [
                    'msg'      => $e->getMessage(),
                    'err_flds' => $e->get_error_flds(),
                    'err_msgs' => $e->get_error_msgs(),
                ],
                400
            );
        } catch ( Exception $e ) {
            $response = $this->response_factory->create_error_response( [ 'msg' => $e->getMessage() ], $e->getCode() ?: 400 );
        }

        return $response;
    }

    /**
     * Switch home and away teams
     */
    public function switch_home_away(): Response {
        if ( ! $this->security_service->verify_nonce( $_POST['racketmanager_nonce'] ?? '', 'match-option' ) ) {
            return $this->response_factory->create_error_response( array( 'msg' => __( 'Security check failed', 'racketmanager' ) ), 403 );
        }

        try {
            $request = Fixture_Switch_Teams_Request::from_post( $_POST );
            $request->validate();

            $fixture_repository = $this->container->get( 'fixture_repository' );
            $fixture            = $fixture_repository->find_by_id( $request->match_id );
            if ( ! $fixture ) {
                throw new Fixture_Not_Found_Exception( Util_Messages::fixture_not_found( $request->match_id ), 404 );
            }

            $league_repository = $this->container->get( 'league_repository' );
            $league            = $league_repository->find_by_id( $fixture->get_league_id() );

            $event_repository = $this->container->get( 'event_repository' );
            $event            = $league ? $event_repository->find_by_id( $league->get_event_id() ) : null;

            if ( ! $event ) {
                throw new Event_Not_Found_Exception( __( 'Fixture event not found', 'racketmanager' ) );
            }

            $season_dtl = $event->get_season_by_name( $fixture->get_season() );
            $match_date = $season_dtl['match_dates'][ $fixture->get_match_day() - 1 ] ?? null;

            if ( ! $match_date ) {
                throw new Fixture_Validation_Exception( [ __( 'Match day not found', 'racketmanager' ) ], [ 'schedule-date' ] );
            }

            $maintenance_service = $this->get_fixture_maintenance_service();
            $maintenance_service->update_fixture_date( $fixture->get_id(), $match_date );
            $maintenance_service->update_fixture_teams( $fixture->get_id(), $fixture->get_away_team(), $fixture->get_home_team() );

            $details    = $this->fixture_detail_service->get_fixture_with_details( $fixture->get_id() );
            $read_model = $this->presenter->map_to_switch_teams_read_model( $fixture, $request, $details );
            $response   = $this->response_factory->create_success_response( $read_model->to_array() );
        } catch ( Fixture_Validation_Exception $e ) {
            $response = $this->response_factory->create_error_response( [
                'msg'      => $e->getMessage(),
                'err_msgs' => $e->get_error_msgs(),
                'err_flds' => $e->get_error_flds(),
            ], 400 );
        } catch ( Exception $e ) {
            $response = $this->response_factory->create_error_response( [ 'msg' => $e->getMessage() ?: __( 'Unable to update switch home and away teams', 'racketmanager' ) ], $e->getCode() ?: 400 );
        }

        return $response;
    }

    /**
     * Reset fixture result
     */
    public function reset_match_result(): Response {
        if ( ! $this->security_service->verify_nonce( $_POST['racketmanager_nonce'] ?? '', 'match-option' ) ) {
            return $this->response_factory->create_error_response( array( 'msg' => __( 'Security check failed', 'racketmanager' ) ), 403 );
        }

        try {
            $request = Fixture_Reset_Request::from_post( $_POST );

            $fixture_repository = $this->container->get( 'fixture_repository' );
            $fixture            = $fixture_repository->find_by_id( $request->fixture_id );

            if ( ! $fixture ) {
                throw new Fixture_Not_Found_Exception( Util_Messages::fixture_not_found(), 400 );
            }

            $fixture_result_manager = $this->get_fixture_result_manager();
            $result_response        = $fixture_result_manager->reset_result( $fixture );

            $message = $this->presenter->get_reset_message( $result_response->status );

            $response = $this->response_factory->create_success_response( [
                'msg'      => $message,
                'modal'    => $request->modal,
                'match_id' => $result_response->fixture_id,
            ] );

        } catch ( Fixture_Not_Found_Exception $e ) {
            $response = $this->response_factory->create_error_response( array( 'msg' => $e->getMessage() ), $e->getCode() ?: 500 );
        }

        return $response;
    }

    /**
     * Update match header
     */
    public function update_match_header(): Response {
        $validator = new Validator_Fixture();
        $validator = $validator->check_security_token();

        try {
            if ( ! empty( $validator->error ) ) {
                throw new Exception();
            }

            $match_id  = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
            $validator = $validator->match( $match_id );

            if ( ! empty( $validator->error ) ) {
                throw new Exception();
            }

            $edit_mode = isset( $_POST['edit_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['edit_mode'] ) ) : false;
            $output    = match_header( $match_id, array( 'edit' => $edit_mode ) );

            $response = $this->response_factory->create_success_response( $output );
        } catch ( Exception $e ) {
            $return   = $validator->get_details();
            $response = $this->response_factory->create_error_response( $return, $return->status ?: 400 );
        }

        return $response;
    }

    /**
     * Update match details for team matches only
     */
    public function update_team_match(): Response {
        $validator = new Validator_Fixture();
        $validator->check_security_token( 'racketmanager_nonce', 'rubbers-match' );

        try {
            if ( ! empty( $validator->error ) ) {
                throw new Exception();
            }

            $request = Team_Result_Update_Request::from_post( $_POST );

            $fixture_repository = $this->container->get( 'fixture_repository' );
            $fixture            = $fixture_repository->find_by_id( $request->match_id );

            if ( ! $fixture ) {
                throw new Fixture_Not_Found_Exception( Util_Messages::fixture_not_found(), 400 );
            }

            $result_manager = $this->get_fixture_result_manager();
            $action         = isset( $_POST['updateRubber'] ) ? sanitize_text_field( wp_unslash( $_POST['updateRubber'] ) ) : null;

            switch ( $action ) {
                case 'results':
                    $validator = $result_manager->handle_team_result_update( $fixture, $request );
                    break;
                case 'confirm':
                    $request   = Team_Result_Confirmation_Request::from_post( $_POST );
                    $validator = $result_manager->handle_team_result_confirmation( $fixture, $request );
                    break;
                default:
                    break;
            }

            if ( ! empty( $validator->error ) ) {
                throw new Exception();
            }

            $return           = $validator;
            $return->rubbers  = $validator->rubbers ?? array();
            $return->status   = $validator->status ?? 200;
            $return->warnings = $validator->warnings ?? array();

            $response = $this->response_factory->create_success_response( $return );

        } catch ( Exception $e ) {
            if ( ! empty( $e->getMessage() ) ) {
                $validator->error = true;
                $validator->msg   = $e->getMessage();
            }

            $return = $validator;
            if ( empty( $return->msg ) ) {
                $return->msg = __( 'Unable to save result', 'racketmanager' );
            }
            $return->rubbers = $validator->rubbers ?? array();
            $response        = $this->response_factory->create_error_response( $return, $return->status ?? 400 );
        }

        return $response;
    }

    /**
     * Handle status update
     *
     * @param string $nonce_action
     * @return Response
     */
    private function handle_status_update( string $nonce_action ): Response {
        if ( ! $this->security_service->verify_nonce( $_POST['racketmanager_nonce'] ?? '', $nonce_action ) ) {
            return $this->response_factory->create_error_response( array( 'msg' => __( 'Security check failed', 'racketmanager' ) ), 403 );
        }

        try {
            $request = Fixture_Status_Update_Request::from_post( $_POST );
            $request->validate();

            $fixture_repository = $this->container->get( 'fixture_repository' );
            $fixture            = $fixture_repository->find_by_id( $request->fixture_id );

            if ( ! $fixture ) {
                throw new Fixture_Not_Found_Exception( Util_Messages::fixture_not_found(), 400 );
            }

            $league_repository = $this->container->get( 'league_repository' );
            $league            = $league_repository->find_by_id( (int) $fixture->get_league_id() );
            $num_rubbers       = $league ? (int) $league->num_rubbers : 0;

            $read_model = $this->presenter->map_to_status_read_model( $fixture, $request, $num_rubbers );

            $response = $this->response_factory->create_success_response( $read_model->to_array() );

        } catch ( Fixture_Validation_Exception $e ) {
            $response = $this->response_factory->create_error_response(
                array(
                    'msg'      => __( 'Unable to set fixture status', 'racketmanager' ),
                    'err_msgs' => $e->get_error_msgs(),
                    'err_flds' => $e->get_error_flds(),
                ),
                400
            );
        } catch ( Fixture_Not_Found_Exception $e ) {
            $response = $this->response_factory->create_error_response( array( 'msg' => $e->getMessage() ), $e->getCode() ?: 400 );
        }

        return $response;
    }

    /**
     * Build error response for logged-out users.
     */
    public function logged_out(): Response {
        $msg = __( 'Must be logged in to access this feature', 'racketmanager' );

        return $this->response_factory->create_error_response(
            array(
                $msg,
                array(),
                array(),
            ),
            401
        );
    }

    /**
     * Build error response for logged-out users (modal style).
     */
    public function logged_out_modal(): Response {
        $msg    = __( 'Must be logged in to access this feature', 'racketmanager' );
        $output = show_alert( $msg, 'danger', 'modal' );

        return $this->response_factory->create_error_response(
            array(
                $msg,
                $output,
            ),
            401
        );
    }

    /**
     * Get the Fixture Maintenance Service with its dependencies.
     */
    public function get_fixture_maintenance_service(): Fixture_Maintenance_Service {
        if ( $this->container->has( 'fixture_maintenance_service' ) ) {
            return $this->container->get( 'fixture_maintenance_service' );
        }

        $result_manager   = $this->get_fixture_result_manager();
        $service_provider = $result_manager->get_service_provider();

        return $service_provider->get_fixture_maintenance_service();
    }

    /**
     * Replicate the complex dependency injection for Fixture_Result_Manager
     */
    public function get_fixture_result_manager(): Fixture_Result_Manager {
        if ( $this->container->has( 'fixture_result_manager' ) ) {
            return $this->container->get( 'fixture_result_manager' );
        }

        $c = $this->container;

        $repository_provider = new Repository_Provider(
            $c->get( 'league_repository' ),
            $c->get( 'event_repository' ),
            $c->get( 'competition_repository' ),
            $c->get( 'league_team_repository' ),
            $c->get( 'team_repository' ),
            $c->get( 'player_repository' ),
            $c->get( 'rubber_repository' ),
            $c->get( 'results_checker_repository' ),
            $c->get( 'results_report_repository' ),
            $c->get( 'fixture_repository' ),
            $c->get( 'club_repository' )
        );

        $service_provider = new Fixture_Service_Provider(
            $c->get( 'result_service' ),
            $c->get( 'knockout_progression_service' ),
            $c->get( 'league_service' ),
            $c->get( 'score_validation_service' ),
            $c->get( 'player_validation_service' ),
            $c->get( 'notification_service' ),
            $c->get( 'registration_service' )
        );

        $service_provider->set_settings_service( $c->get( 'settings_service' ) );
        $service_provider->set_fixture_permission_service( $c->get( 'fixture_permission_service' ) );
        $service_provider->set_fixture_detail_service( $c->get( 'fixture_detail_service' ) );
        $service_provider->set_team_service( $c->get( 'team_service' ) );
        $service_provider->set_competition_service( $c->get( 'competition_service' ) );

        return new Fixture_Result_Manager( $service_provider, $repository_provider );
    }

    /**
     * Helper to create an alert response
     *
     * @param string $message
     * @param int $status_code
     * @return Response
     */
    private function create_alert_response( string $message, int $status_code = 200 ): Response {
        $vars = $this->presenter->map_to_alert( $message, 'danger' );
        $html = $this->view_renderer->render_to_string( 'alert-modal', $vars );
        return $this->response_factory->create_raw_response( $html ?: '', $status_code );
    }
}
