<?php

namespace Racketmanager\Infrastructure\Wordpress\Ajax;

use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Partner_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Payment_Command;
use Racketmanager\Domain\DTO\Tournament\Tournament_Withdrawal_Command;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Stripe_API_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Validator\Validator_Entry_Form;
use Racketmanager\Services\Validator\Validator_Tournament;
use stdClass;
use function Racketmanager\event_partner_modal;
use function Racketmanager\show_alert;
use function Racketmanager\tournament_withdrawal_modal;

/**
 * Adapter for Tournament AJAX requests.
 */
class Tournament_Ajax_Adapter {
    private const NONCE_FIELD = 'racketmanager_nonce';
    private const DEFAULT_NONCE_FIELD = 'security';
    private const DEFAULT_NONCE_ACTION = 'ajax-nonce';

    public function __construct(
        private Security_Service_Interface $security_service,
        private Json_Response_Factory_Interface $response_factory,
        private Competition_Entry_Service $competition_entry_service,
        private Finance_Service $finance_service
    ) {
    }

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

    public function tournament_entry_request( array $request = [] ): Response {
        $validator = new Validator_Entry_Form();
        $nonce_error = $this->verify_nonce_details( self::NONCE_FIELD, 'racketmanager_tournament-entry', $request );
        if ( $nonce_error ) {
            $validator->error  = true;
            $validator->status = $nonce_error->status;
            $validator->msg    = $nonce_error->msg;
        }

        if ( ! $validator->error ) {
            $validator = $validator->logged_in_entry();
            if ( ! $validator->error ) {
                $request = new Tournament_Entry_Request_DTO( $request );
                try {
                    $response = $this->competition_entry_service->request_tournament_entry( $request );
                    if ( ! is_wp_error( $response ) ) {
                        $return = array();
                        array_push( $return, $response->message, $response->message_type, $response->payment_required, $response->return_link );

                        return $this->response_factory->create_success_response( $return );
                    }
                    $validator->error    = true;
                    $validator->err_flds = $response->get_error_codes();
                    $validator->err_msgs = $response->get_error_messages();
                    $err_msg             = $response->get_error_message( 'tournament' );
                    if ( empty( $err_msg ) ) {
                        $validator->msg = __( 'Errors in tournament entry form', 'racketmanager' );
                    } else {
                        $validator->msg = $err_msg;
                    }
                } catch ( Tournament_Not_Found_Exception ) {
                    $validator->error = true;
                }
            }
        }

        $return = $validator->get_details();
        if ( empty( $return->msg ) ) {
            $return->msg = __( 'Errors in entry form', 'racketmanager' );
        }

        return $this->response_factory->create_error_response( $return, $return->status );
    }

    public function validate_partner( array $request = [] ): Response {
        $validator   = new Validator_Tournament();
        $nonce_error = $this->verify_nonce_details( self::NONCE_FIELD, 'racketmanager_team-partner', $request );
        if ( $nonce_error ) {
            $validator->error  = true;
            $validator->status = $nonce_error->status;
            $validator->msg    = $nonce_error->msg;
        }

        if ( empty( $validator->error ) ) {
            $partner_request = new Tournament_Partner_Request_DTO( $request );
            $response        = $this->competition_entry_service->validate_tournament_partner( $partner_request );
            if ( ! is_wp_error( $response ) ) {
                $return = array();
                array_push( $return, $partner_request->modal, $partner_request->partner_id, $partner_request->partner_name, $partner_request->event_id );

                return $this->response_factory->create_success_response( $return );
            }
            $validator->error    = true;
            $validator->err_flds = $response->get_error_codes();
            $validator->err_msgs = $response->get_error_messages();
        }
        if ( empty( $validator->status ) ) {
            $validator->status = 400;
        }

        return $this->response_factory->create_error_response( $validator, $validator->status );
    }

    public function tournament_withdrawal( array $request = [] ): Response {
        $nonce_error = $this->verify_nonce_details( self::DEFAULT_NONCE_FIELD, self::DEFAULT_NONCE_ACTION, $request );
        if ( $nonce_error ) {
            return $this->response_factory->create_raw_response( (string) $nonce_error->msg );
        }

        $command = Tournament_Withdrawal_Command::from_post( $request );
        $modal   = isset( $request['modal'] ) ? sanitize_text_field( wp_unslash( $request['modal'] ) ) : null;
        $output  = tournament_withdrawal_modal(
            (int) $command->tournament_id,
            array(
                'modal'     => $modal,
                'player_id' => $command->player_id,
            )
        );

        return $this->response_factory->create_raw_response( $output );
    }

    public function confirm_tournament_withdrawal( array $request = [] ): Response {
        $validator   = new Validator_Tournament();
        $nonce_error = $this->verify_nonce_details( self::DEFAULT_NONCE_FIELD, self::DEFAULT_NONCE_ACTION, $request );
        if ( $nonce_error ) {
            $validator->error  = true;
            $validator->status = $nonce_error->status;
            $validator->msg    = $nonce_error->msg;
        }

        if ( empty( $validator->error ) ) {
            $command = Tournament_Withdrawal_Command::from_post( $request );
            try {
                $response = $this->competition_entry_service->confirm_tournament_withdrawal( $command->tournament_id, $command->player_id );
                if ( $response ) {
                    $output = __( 'Tournament withdrawal successful and refund will be issued when tournament starts', 'racketmanager' );
                } else {
                    $output = __( 'Tournament withdrawal successful', 'racketmanager' );
                }

                return $this->response_factory->create_success_response( $output );
            } catch ( Tournament_Not_Found_Exception | Player_Not_Found_Exception $e ) {
                $validator->set_errors( 'tournament', $e->getMessage(), 404 );
            }
        }

        $details = $validator->get_details();

        return $this->response_factory->create_error_response( $details, $validator->status );
    }

    public function team_partner( array $request = [] ): Response {
        $nonce_error = $this->verify_nonce_details( self::DEFAULT_NONCE_FIELD, self::DEFAULT_NONCE_ACTION, $request );
        if ( $nonce_error ) {
            $output = show_alert( (string) $nonce_error->msg, 'danger', 'modal' );

            return $this->response_factory->create_raw_response( $output, (int) $nonce_error->status );
        }

        $event_id              = isset( $request['eventId'] ) ? intval( $request['eventId'] ) : 0;
        $args                  = array();
        $args['player']        = isset( $request['playerId'] ) ? intval( $request['playerId'] ) : null;
        $args['gender']        = isset( $request['gender'] ) ? sanitize_text_field( wp_unslash( $request['gender'] ) ) : null;
        $args['season']        = isset( $request['season'] ) ? intval( $request['season'] ) : null;
        $args['date_end']      = isset( $request['dateEnd'] ) ? intval( $request['dateEnd'] ) : null;
        $args['modal']         = isset( $request['modal'] ) ? sanitize_text_field( wp_unslash( $request['modal'] ) ) : null;
        $args['partner_id']    = isset( $request['partnerId'] ) ? intval( $request['partnerId'] ) : null;
        $args['tournament_id'] = isset( $request['tournament_id'] ) ? intval( $request['tournament_id'] ) : null;
        $output                = event_partner_modal( $event_id, $args );

        return $this->response_factory->create_raw_response( $output );
    }

    public function tournament_payment_create( array $request = [] ): Response {
        $command = Tournament_Payment_Command::from_post( $request );
        try {
            $client_secret = $this->finance_service->create_tournament_payment_request( $command->tournament_id, $command->player_id, $command->invoice_id );

            return $this->response_factory->create_success_response( $client_secret );
        } catch ( Tournament_Not_Found_Exception | Player_Not_Found_Exception | Stripe_API_Exception | Invoice_Not_Found_Exception $e ) {
            return $this->response_factory->create_error_response( array( 'error' => $e->getMessage() ), 500 );
        }
    }

    private function verify_nonce_details( string $field, string $action, array $request = [] ): ?stdClass {
        $nonce_set = isset( $request[ $field ] );
        $nonce     = $request[ $field ] ?? '';
        $nonce     = is_string( $nonce ) ? sanitize_text_field( wp_unslash( $nonce ) ) : '';
        if ( $nonce !== '' && $this->security_service->verify_nonce( $nonce, $action ) ) {
            return null;
        }

        $details           = new stdClass();
        $details->error    = true;
        $details->err_flds = array();
        $details->err_msgs = array();
        $details->status   = 403;
        $details->msg      = ! $nonce_set
            ? __( 'There was a problem with your request. Please try again or refresh the page', 'racketmanager' )
            : __( 'Sorry, the action could not be completed. The link or form you are using has expired or is invalid. Please try again, or go back to the previous page and try again', 'racketmanager' );

        return $details;
    }
}
