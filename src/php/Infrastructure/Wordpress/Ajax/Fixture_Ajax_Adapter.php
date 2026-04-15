<?php

namespace Racketmanager\Infrastructure\Wordpress\Ajax;

use Exception;
use Racketmanager\Application\Fixture\DTOs\Fixture_Status_Read_Model;
use Racketmanager\Domain\DTO\Fixture\Fixture_Reset_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Status_Update_Request;
use Racketmanager\Exceptions\Fixture_Validation_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Presenters\Fixture_Presenter;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Fixture\Service_Provider as Fixture_Service_Provider;

/**
 * Adapter for Fixture AJAX requests
 */
final class Fixture_Ajax_Adapter {
    private Simple_Container $container;
    private Security_Service_Interface $security_service;
    private Json_Response_Factory_Interface $response_factory;

    public function __construct( $container, Security_Service_Interface $security_service, Json_Response_Factory_Interface $response_factory ) {
        $this->container        = $container;
        $this->security_service = $security_service;
        $this->response_factory = $response_factory;
    }

    /**
     * Update fixture result
     */
    public function update_fixture_result(): void {
        if ( ! $this->security_service->verify_nonce( $_POST['racketmanager_nonce'] ?? '', 'scores-match' ) ) {
            $this->response_factory->send_error( array( 'msg' => __( 'Security check failed', 'racketmanager' ) ), 403 );
            return;
        }

        try {
            $request    = Fixture_Result_Update_Request::from_post( $_POST );
            $fixture_id = $request->fixture_id;

            $fixture_repository = $this->container->get( 'fixture_repository' );
            $fixture            = $fixture_repository->find_by_id( $fixture_id );

            if ( ! $fixture ) {
                $this->response_factory->send_error( array( 'msg' => __( 'Fixture not found', 'racketmanager' ) ), 404 );
                return;
            }

            $result_manager = $this->get_fixture_result_manager();
            $response       = $result_manager->handle_fixture_result_update( $fixture, $request );

            $presenter  = new Fixture_Presenter();
            $read_model = $presenter->map_to_result_read_model( $fixture, $response, $request );

            $this->response_factory->send_success( $read_model->to_array() );
            return;

        } catch ( Fixture_Validation_Exception $e ) {
            $this->response_factory->send_error(
                array(
                    'msg'      => __( 'Unable to update result', 'racketmanager' ),
                    'err_msgs' => $e->get_error_msgs(),
                    'err_flds' => $e->get_error_flds(),
                ),
                400
            );
            return;
        } catch ( Exception $e ) {
            $this->response_factory->send_error( array( 'msg' => $e->getMessage() ), 400 );
            return;
        }
    }

    /**
     * Set match status
     */
    public function set_match_status(): void {
        $this->handle_status_update( 'match-status' );
    }

    /**
     * Set match rubber status
     */
    public function set_match_rubber_status(): void {
        $this->handle_status_update( 'match-status' );
    }

    /**
     * Reset fixture result
     */
    public function reset_match_result(): void {
        if ( ! $this->security_service->verify_nonce( $_POST['racketmanager_nonce'] ?? '', 'match-option' ) ) {
            $this->response_factory->send_error( array( 'msg' => __( 'Security check failed', 'racketmanager' ) ), 403 );
            return;
        }

        try {
            $request = Fixture_Reset_Request::from_post( $_POST );

            $fixture_repository = $this->container->get( 'fixture_repository' );
            $fixture            = $fixture_repository->find_by_id( $request->fixture_id );

            if ( ! $fixture ) {
                $this->response_factory->send_error( array( 'msg' => __( 'Fixture not found', 'racketmanager' ) ), 404 );
                return;
            }

            $fixture_result_manager = $this->get_fixture_result_manager();
            $response               = $fixture_result_manager->reset_result( $fixture );

            $presenter = new Fixture_Presenter();
            $message   = $presenter->get_reset_message( $response->status );

            $this->response_factory->send_success( [
                'msg'      => $message,
                'modal'    => $request->modal,
                'match_id' => $response->fixture_id,
            ] );
            return;

        } catch ( Exception $e ) {
            $this->response_factory->send_error( array( 'msg' => $e->getMessage() ), 500 );
            return;
        }
    }

    /**
     * Handle status update
     *
     * @param string $nonce_action
     */
    private function handle_status_update( string $nonce_action ): void {
        if ( ! $this->security_service->verify_nonce( $_POST['racketmanager_nonce'] ?? '', $nonce_action ) ) {
            $this->response_factory->send_error( array( 'msg' => __( 'Security check failed', 'racketmanager' ) ), 403 );
            return;
        }

        try {
            $request = Fixture_Status_Update_Request::from_post( $_POST );
            $request->validate();

            $fixture_repository = $this->container->get( 'fixture_repository' );
            $fixture            = $fixture_repository->find_by_id( $request->fixture_id );

            if ( ! $fixture ) {
                $this->response_factory->send_error( array( 'msg' => __( 'Fixture not found', 'racketmanager' ) ), 404 );
                return;
            }

            $league_repository = $this->container->get( 'league_repository' );
            $league            = $league_repository->find_by_id( (int) $fixture->get_league_id() );
            $num_rubbers       = $league ? (int) $league->num_rubbers : 0;

            $presenter  = new Fixture_Presenter();
            $read_model = $presenter->map_to_status_read_model( $fixture, $request, $num_rubbers );

            $this->response_factory->send_success( $read_model->to_array() );
            return;

        } catch ( Fixture_Validation_Exception $e ) {
            $this->response_factory->send_error(
                array(
                    'msg'      => __( 'Unable to set match status', 'racketmanager' ),
                    'err_msgs' => $e->get_error_msgs(),
                    'err_flds' => $e->get_error_flds(),
                ),
                400
            );
            return;
        } catch ( Exception $e ) {
            $this->response_factory->send_error( array( 'msg' => $e->getMessage() ), 400 );
            return;
        }
    }

    /**
     * Replicate the complex dependency injection for Fixture_Result_Manager
     */
    private function get_fixture_result_manager(): Fixture_Result_Manager {
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
}
