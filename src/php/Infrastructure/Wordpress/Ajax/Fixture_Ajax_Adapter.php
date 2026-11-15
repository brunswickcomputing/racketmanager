<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Ajax;

use Exception;
use LogicException;
use Racketmanager\Application\Fixture\Queries\Get_Fixture_Details_Handler;
use Racketmanager\Application\Fixture\Queries\Get_Fixture_Details_Query;
use Racketmanager\Exceptions\Fixture_Update_Exception;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Date_Update_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Reset_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Status_Options_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Switch_Teams_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Option_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Rubber_Status_Options_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Status_Update_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Team_Result_Confirmation_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Team_Result_Update_Request;
use Racketmanager\Domain\Fixture\Fixture_Time_Parser;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Fixture_Not_Found_Exception;
use Racketmanager\Exceptions\Fixture_Validation_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Services\Validator\Validator_Fixture;
use Racketmanager\Util\Util_Messages;
use function Racketmanager\show_alert;

/**
 * Adapter for Fixture AJAX requests
 */
readonly class Fixture_Ajax_Adapter {
    /**
     * Initialise the fixture details controller with its required services.
     *
     * @param Simple_Container                $container Service container used to resolve additional dependencies.
     * @param Security_Service_Interface      $security_service Service used to handle request security and authorization.
     * @param Json_Response_Factory_Interface $response_factory Factory used to create JSON responses.
     * @param Fixture_Detail_Service          $fixture_detail_service Service used to retrieve fixture detail data.
     * @param View_Renderer_Interface         $view_renderer Renderer used to generate view output.
     * @param Fixture_Presenter               $presenter Presenter used to format fixture data for display.
     * @param Get_Fixture_Details_Handler     $fixture_details_handler Handler used to process fixture detail queries.
     *
     * @return void
     */
    public function __construct(
        private Simple_Container $container,
        private Security_Service_Interface $security_service,
        private Json_Response_Factory_Interface $response_factory,
        private Fixture_Detail_Service $fixture_detail_service,
        private View_Renderer_Interface $view_renderer,
        private Fixture_Presenter $presenter,
        private Get_Fixture_Details_Handler $fixture_details_handler
    ) {}

    /**
     * Build screen to allow printing of match cards
     *
     * @param array $post post data.
     *
     * @return Response
     * @throws Fixture_Not_Found_Exception Fixture not found.
     */
    public function print_match_card( array $post = [] ): Response {
        if ( ! $this->security_service->verify_nonce( $post['security'] ?? '', 'ajax-nonce' ) ) {
            return $this->response_factory->create_error_response( array( 'msg' => __( 'Security check failed', 'racketmanager' ) ), 403 );
        }

        try {
            $match_id = isset( $post['matchId'] ) ? intval( $post['matchId'] ) : null;
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

            $vars = $this->presenter->map_to_match_card_vars( $dto, $match );

            $output = $this->view_renderer->render_to_string(
                $template,
                $vars
            );

            $response = $this->response_factory->create_raw_response( $output );
        } catch ( Fixture_Not_Found_Exception $e ) {
            $response = $this->response_factory->create_error_response( array( 'msg' => $e->getMessage() ), $e->getCode() ?: 400 );
        }

        return $response;
    }

    /**
     * Updates a fixture result from submitted post data and returns the corresponding response.
     *
     * @param array $post Submitted fixture result data, including nonce and fixture result fields.
     *
     * @return Response Success response containing the updated result read model, or an error response when validation or saving fails.
     * @throws Fixture_Not_Found_Exception When the fixture is not found.
     */
    public function update_fixture_result( array $post = [] ): Response {
        if ( ! $this->security_service->verify_nonce( $post['racketmanager_nonce'] ?? '', 'scores-match' ) ) {
            return $this->response_factory->create_error_response( array( 'msg' => __( 'Security check failed', 'racketmanager' ) ), 403 );
        }

        try {
            $request    = Fixture_Result_Update_Request::from_post( $post );
            $fixture_id = $request->fixture_id;

            $fixture_repository = $this->container->get( 'fixture_repository' );
            $fixture            = $fixture_repository->find_by_id( $fixture_id );

            if ( ! $fixture ) {
                throw new Fixture_Not_Found_Exception( Util_Messages::fixture_not_found( $fixture_id ), 404 );
            }

            $result_manager  = $this->get_fixture_result_manager();
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
     * Builds the fixture status options modal response after validating the request nonce,
     * parsing submitted data, loading fixture details, and rendering the status options view.
     *
     * @param array $post Submitted request data used to verify security and determine the fixture status options to render.
     *
     * @return Response Raw rendered modal content on success, or an error response when validation, security, or fixture lookup fails.
     * @throws Fixture_Not_Found_Exception When the fixture is not found.
     */
    public function fixture_status_options( array $post = [] ): Response {
        if ( ! $this->security_service->verify_nonce( $post['security'] ?? '', 'ajax-nonce' ) ) {
            return $this->response_factory->create_error_response( array( 'msg' => __( 'Security check failed', 'racketmanager' ) ), 403 );
        }

        try {
            $request = Fixture_Status_Options_Request::from_post( $post );
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
     * Builds and returns the rubber status options modal response after validating the AJAX request.
     *
     * @param array $post Posted request data used to verify security, identify the rubber, and determine status options.
     *
     * @return Response Raw rendered modal response on success, or an alert response when validation, lookup, or processing fails.
     * @throws Fixture_Not_Found_Exception When the fixture is not found.
     */
    public function match_rubber_status_options( array $post = [] ): Response {
        if ( ! $this->security_service->verify_nonce( $post['security'] ?? '', 'ajax-nonce' ) ) {
            return $this->create_alert_response( __( 'Security check failed', 'racketmanager' ), 403 );
        }

        try {
            $request = Rubber_Status_Options_Request::from_post( $post );
            $request->validate();

            $rubber = $this->container->get( 'rubber_repository' )->find_by_id( $request->rubber_id );
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
     * Updates the fixture status using the provided request data.
     *
     * @param array $post Status update data for the fixture.
     *
     * @return Response Response returned after handling the fixture status update.
     */
    public function set_fixture_status( array $post = [] ): Response {
        return $this->handle_status_update( 'match-status', $post );
    }

    /**
     * Renders the fixture option modal response for a validated AJAX request.
     *
     * @param array $post Posted request data containing security, fixture, option, and optional template values.
     *
     * @return Response Raw modal HTML response when successful, or an alert response when validation, security, lookup, or unexpected errors occur.
     * @throws Fixture_Not_Found_Exception When the fixture is not found.
     * @throws Fixture_Validation_Exception When the request is invalid.
     */
    public function show_fixture_option( array $post = [] ): Response {
        if ( ! $this->security_service->verify_nonce( $post['security'] ?? '', 'ajax-nonce' ) ) {
            return $this->create_alert_response( __( 'Security check failed', 'racketmanager' ), 403 );
        }

        try {
            $request = Fixture_Option_Request::from_post( $post );
            $request->validate();

            $dto = $this->fixture_detail_service->get_fixture_with_details( $request->match_id );
            if ( ! $dto ) {
                throw new Fixture_Not_Found_Exception( __( 'Fixture not found', 'racketmanager' ), 404 );
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
                case 'reset_fixture_result':
                    $title  = __( 'Reset result', 'racketmanager' );
                    $button = __( 'Save', 'racketmanager' );
                    $action = 'resetMatchResult';
                    break;
                default:
                    throw new Fixture_Validation_Exception( [ __( 'Invalid fixture option', 'racketmanager' ) ] );
            }

            $filename = ( ! empty( $request->template ) ) ? 'match/match-option-modal-' . $request->template : 'match/match-option-modal';

            $vars = $this->presenter->map_to_fixture_option_vars( $dto, $request, $title, $button, $action );

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
     * Updates the rubber status for a match using the provided post data.
     *
     * @param array $post Status update data submitted for the rubber.
     *
     * @return Response Response generated after handling the rubber status update.
     */
    public function set_rubber_status( array $post = [] ): Response {
        return $this->handle_status_update( 'match-rubber-status', $post );
    }

    /**
     * Updates a fixture's scheduled date after validating the submitted request and nonce.
     *
     * @param array $post Posted fixture date update data.
     *
     * @return Response Success response containing the updated fixture date data, or an error response when validation, security, lookup, or update fails.
     * @throws Fixture_Not_Found_Exception When the fixture is not found.
     * @throws Fixture_Validation_Exception When fixture date validation fails.
     * @throws Fixture_Update_Exception When the fixture date update operation fails.
     */
    public function set_fixture_date( array $post = [] ): Response {
        if ( ! $this->security_service->verify_nonce( $post['racketmanager_nonce'] ?? '', 'match-option' ) ) {
            return $this->create_alert_response( __( 'Security check failed', 'racketmanager' ), 403 );
        }

        try {
            $request = Fixture_Date_Update_Request::from_post( $post );
            $request->validate();

            $fixture_repository = $this->container->get( 'fixture_repository' );
            $match              = $fixture_repository->find_by_id( $request->match_id );
            if ( ! $match ) {
                throw new Fixture_Not_Found_Exception( Util_Messages::fixture_not_found( $request->match_id ), 404 );
            }

            $current_match_date = $match->get_date();
            $is_date_unchanged  = false;
            if ( ! empty( $current_match_date ) && ! str_starts_with( trim( (string) $current_match_date ), '0000-00-00' ) ) {
                if ( strlen( (string) $request->schedule_date ) === 10 ) {
                    $is_date_unchanged = substr( (string) $current_match_date, 0, 10 ) === $request->schedule_date;
                } else {
                    try {
                        $is_date_unchanged = Fixture_Time_Parser::to_mysql_datetime( $request->schedule_date ) === Fixture_Time_Parser::to_mysql_datetime( $current_match_date );
                    } catch ( \InvalidArgumentException ) {
                        $is_date_unchanged = false;
                    }
                }
            }

            if ( $is_date_unchanged ) {
                throw new Fixture_Validation_Exception( array( __( 'Date not changed', 'racketmanager' ) ), array( 'schedule-date' ) );
            }

            if ( strlen( (string) $request->schedule_date ) === 10 ) {
                $schedule_date_fmt = mysql2date( 'D j M', $request->schedule_date );
            } else {
                $schedule_date_fmt = mysql2date( 'j F Y H:i', $request->schedule_date );
            }

            $maintenance_service = $this->get_fixture_maintenance_service();
            $updated             = $maintenance_service->update_fixture_date( $request->match_id, (string) $request->schedule_date, $match->get_date() );
            if ( ! $updated ) {
                throw new Fixture_Update_Exception( __( 'Failed to update match schedule', 'racketmanager' ) );
            }
            $maintenance_service->update_fixture_status( $request->match_id, 5 );

            $read_model = $this->presenter->map_to_date_update_read_model( $request, $schedule_date_fmt );

            $response = $this->response_factory->create_success_response( $read_model->to_array() );
        } catch ( Fixture_Not_Found_Exception $e ) {
            $response = $this->response_factory->create_error_response(
                [
                    'msg'      => $e->getMessage(),
                    'err_flds' => $e->get_error_flds(),
                    'err_msgs' => $e->get_error_msgs(),
                ],
                400
            );
        } catch ( Fixture_Validation_Exception $e ) {
            $response = $this->response_factory->create_error_response(
                array(
                    'msg'      => __( 'Unable to set fixture date', 'racketmanager' ),
                    'err_msgs' => $e->get_error_msgs(),
                    'err_flds' => $e->get_error_flds(),
                ),
                400
            );
        } catch ( Fixture_Update_Exception $e ) {
            $response = $this->response_factory->create_error_response( [ 'msg' => $e->getMessage() ], $e->getCode() ?: 400 );
        }

        return $response;
    }

    /**
     * Switch the home and away teams for a fixture after validating the request and restoring the scheduled match date.
     *
     * @param array $post Posted request data containing the nonce and fixture switch details.
     *
     * @return Response Success response with updated fixture details, or an error response when validation or processing fails.
     * @throws Fixture_Not_Found_Exception When the fixture is not found.
     * @throws Fixture_Validation_Exception When the request is invalid.
     * @throws Event_Not_Found_Exception When the event is not found.
     */
    public function switch_home_away( array $post = [] ): Response {
        if ( ! $this->security_service->verify_nonce( $post['racketmanager_nonce'] ?? '', 'match-option' ) ) {
            return $this->response_factory->create_error_response( array( 'msg' => __( 'Security check failed', 'racketmanager' ) ), 403 );
        }

        try {
            $request = Fixture_Switch_Teams_Request::from_post( $post );
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

            $target_date = $match_date;
            if ( ! empty( $fixture->get_start_time() ) && strlen( trim( $match_date ) ) === 10 ) {
                $target_date = Fixture_Time_Parser::merge_time_into_date( $match_date, $fixture->get_start_time() );
            }

            $maintenance_service = $this->get_fixture_maintenance_service();
            $maintenance_service->update_fixture_date( $fixture->get_id(), $target_date );
            $maintenance_service->update_fixture_teams( $fixture->get_id(), $fixture->get_away_team(), $fixture->get_home_team() );

            $details    = $this->fixture_detail_service->get_fixture_with_details( $fixture->get_id() );
            $read_model = $this->presenter->map_to_switch_teams_read_model( $fixture, $request, $details );
            $response   = $this->response_factory->create_success_response( $read_model->to_array() );
        } catch ( Fixture_Validation_Exception $e ) {
            $response = $this->response_factory->create_error_response(
                [
                    'msg'      => $e->getMessage(),
                    'err_msgs' => $e->get_error_msgs(),
                    'err_flds' => $e->get_error_flds(),
                ],
                400
            );
        } catch ( Exception $e ) {
            $response = $this->response_factory->create_error_response( [ 'msg' => $e->getMessage() ?: __( 'Unable to update switch home and away teams', 'racketmanager' ) ], $e->getCode() ?: 400 );
        }

        return $response;
    }

    /**
     * Resets the result for a fixture from submitted request data.
     *
     * @param array $post Submitted request data containing the nonce, fixture identifier, and related reset options.
     *
     * @return Response Success response with reset details, or error response when security validation fails or the fixture cannot be found.
     * @throws Fixture_Not_Found_Exception When the fixture is not found.
     */
    public function reset_fixture_result( array $post = [] ): Response {
        if ( ! $this->security_service->verify_nonce( $post['racketmanager_nonce'] ?? '', 'match-option' ) ) {
            return $this->response_factory->create_error_response( array( 'msg' => __( 'Security check failed', 'racketmanager' ) ), 403 );
        }

        try {
            $request = Fixture_Reset_Request::from_post( $post );

            $fixture_repository = $this->container->get( 'fixture_repository' );
            $fixture            = $fixture_repository->find_by_id( $request->fixture_id );

            if ( ! $fixture ) {
                throw new Fixture_Not_Found_Exception( Util_Messages::fixture_not_found(), 400 );
            }

            $fixture_result_manager = $this->get_fixture_result_manager();
            $result_response        = $fixture_result_manager->reset_result( $fixture );

            $message = $this->presenter->get_reset_message( $result_response->status );

            $response = $this->response_factory->create_success_response(
                [
                    'msg'      => $message,
                    'modal'    => $request->modal,
                    'match_id' => $result_response->fixture_id,
                ]
            );

        } catch ( Fixture_Not_Found_Exception $e ) {
            $response = $this->response_factory->create_error_response( array( 'msg' => $e->getMessage() ), $e->getCode() ?: 500 );
        }

        return $response;
    }

    /**
     * Creates a validator for fixtures using configured repository dependencies when available.
     *
     * @return Validator_Fixture Fixture validator instance.
     */
    private function create_validator(): Validator_Fixture {
        return new Validator_Fixture(
            fixture_repository: $this->container->has( 'fixture_repository' ) ? $this->container->get( 'fixture_repository' ) : null,
            rubber_repository: $this->container->has( 'rubber_repository' ) ? $this->container->get( 'rubber_repository' ) : null
        );
    }

    /**
     * Updates and renders the fixture header for the specified match.
     *
     * Validates the request security token and match identifier, retrieves fixture
     * details, maps them to a header read model, and returns a success response with
     * the rendered header. Returns an error response when validation fails or the
     * fixture cannot be found.
     *
     * @param array $post Request data containing the security token, match ID, and optional edit mode flag.
     *
     * @return Response Response containing the rendered fixture header or validation error details.
     * @throws Fixture_Update_Exception When the request security token or match identifier is invalid.
     * @throws Fixture_Not_Found_Exception When the fixture is not found.
     */
    public function update_fixture_header( array $post = [] ): Response {
        $validator = $this->create_validator();
        $validator = $validator->check_security_token( 'security', 'ajax-nonce', $post );

        try {
            if ( ! empty( $validator->error ) ) {
                throw new Fixture_Update_Exception();
            }

            $match_id  = isset( $post['match_id'] ) ? intval( $post['match_id'] ) : null;
            $validator = $validator->match( $match_id );

            if ( ! empty( $validator->error ) ) {
                throw new Fixture_Not_Found_Exception();
            }

            $edit_mode = ! empty( $post['edit_mode'] ) && filter_var( $post['edit_mode'], FILTER_VALIDATE_BOOLEAN );

            $query = new Get_Fixture_Details_Query( fixture_id: $match_id );
            $dto   = $this->fixture_details_handler->handle( $query );

            if ( ! $dto ) {
                throw new Fixture_Not_Found_Exception( Util_Messages::fixture_not_found(), 400 );
            }

            $model  = $this->presenter->map_to_header_read_model( $dto, $edit_mode );
            $output = $this->presenter->render_header( $model );

            $response = $this->response_factory->create_success_response( $output );
        } catch ( Fixture_Update_Exception ) {
            $return   = $validator->get_details();
            $response = $this->response_factory->create_error_response( $return, $return->status ?: 400 );
        }

        return $response;
    }

    /**
     * Updates or confirms the result for a team match from submitted post data.
     *
     * @param array $post Submitted match result data, including the security token, match identifier, and update action.
     *
     * @return Response Success response containing updated validation data, rubbers, status, and warnings, or an error response when validation or saving fails.
     * @throws Fixture_Not_Found_Exception When the fixture is not found.
     * @throws Fixture_Update_Exception When the request security token or match identifier is invalid.
     */
    public function update_team_match( array $post = [] ): Response {
        $validator = $this->create_validator();
        $validator->check_security_token( 'racketmanager_nonce', 'rubbers-match', $post );

        try {
            if ( ! empty( $validator->error ) ) {
                throw new Fixture_Update_Exception();
            }

            $request = Team_Result_Update_Request::from_post( $post );

            $fixture_repository = $this->container->get( 'fixture_repository' );
            $fixture            = $fixture_repository->find_by_id( $request->match_id );

            if ( ! $fixture ) {
                throw new Fixture_Not_Found_Exception( Util_Messages::fixture_not_found(), 400 );
            }

            $result_manager = $this->get_fixture_result_manager();
            $action         = isset( $post['updateRubber'] ) ? sanitize_text_field( wp_unslash( $post['updateRubber'] ) ) : null;

            switch ( $action ) {
                case 'results':
                    $validator = $result_manager->handle_team_result_update( $fixture, $request );
                    break;
                case 'confirm':
                    $request   = Team_Result_Confirmation_Request::from_post( $post );
                    $validator = $result_manager->handle_team_result_confirmation( $fixture, $request );
                    break;
                default:
                    break;
            }

            if ( ! empty( $validator->error ) ) {
                throw new Fixture_Update_Exception();
            }

            $return           = $validator;
            $return->rubbers  = $validator->rubbers ?? array();
            $return->status   = $validator->status ?? 200;
            $return->warnings = $validator->warnings ?? array();

            $response = $this->response_factory->create_success_response( $return );

        } catch ( Fixture_Update_Exception $e ) {
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
     * Handles a fixture status update request and returns the corresponding response.
     *
     * @param string $nonce_action The nonce action used to verify the request.
     * @param array  $post The submitted post data used to build and validate the status update request.
     *
     * @return Response The success response containing the fixture status read model, or an error response when verification, validation, or lookup fails.
     * @throws Fixture_Not_Found_Exception When the fixture is not found.
     */
    private function handle_status_update( string $nonce_action, array $post = [] ): Response {
        if ( ! $this->security_service->verify_nonce( $post['racketmanager_nonce'] ?? '', $nonce_action ) ) {
            return $this->response_factory->create_error_response( array( 'msg' => __( 'Security check failed', 'racketmanager' ) ), 403 );
        }

        try {
            $request = Fixture_Status_Update_Request::from_post( $post );
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
     * Creates an unauthorised error response for users who are not logged in.
     *
     * @return Response The error response indicating that login is required.
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
     * Creates an unauthorised error response for users who are not logged in.
     *
     * @return Response The error response containing the login-required message and modal alert output.
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
     * Retrieves the fixture maintenance service from the container or service provider.
     *
     * @return Fixture_Maintenance_Service The fixture maintenance service instance.
     *
     * @throws LogicException If the fixture result manager required for fallback retrieval is not configured.
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
     * Retrieves the configured fixture result manager from the container.
     *
     * @return Fixture_Result_Manager The fixture result manager instance.
     *
     * @throws LogicException If the fixture result manager has not been configured.
     */
    public function get_fixture_result_manager(): Fixture_Result_Manager {
        if ( $this->container->has( 'fixture_result_manager' ) ) {
            return $this->container->get( 'fixture_result_manager' );
        }

        throw new LogicException( 'fixture_result_manager has not been configured in the container.' );
    }

    /**
     * Creates a response containing a rendered danger alert modal.
     *
     * @param string $message The alert message to display.
     * @param int    $status_code The HTTP status code for the response.
     *
     * @return Response The raw response containing the rendered alert modal HTML.
     */
    private function create_alert_response( string $message, int $status_code = 200 ): Response {
        $vars = $this->presenter->map_to_alert( $message, 'danger' );
        $html = $this->view_renderer->render_to_string( 'alert-modal', $vars );
        return $this->response_factory->create_raw_response( $html ?: '', $status_code );
    }
}
