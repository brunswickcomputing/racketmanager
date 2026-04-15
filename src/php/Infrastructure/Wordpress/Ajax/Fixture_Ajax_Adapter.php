<?php

namespace Racketmanager\Infrastructure\Wordpress\Ajax;

use Exception;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
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
