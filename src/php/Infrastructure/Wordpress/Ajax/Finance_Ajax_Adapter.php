<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Ajax;

use Racketmanager\Application\Finance\Commands\Set_Purchase_Order_Command;
use Racketmanager\Application\Finance\Commands\Set_Purchase_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Query;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Validator\Validator_Finance;
use stdClass;
use function Racketmanager\show_alert;
use function Racketmanager\show_invoice;

/**
 * Adapter for Finance AJAX requests.
 */
class Finance_Ajax_Adapter {
    private const NONCE_FIELD = 'racketmanager_nonce';
    private const DEFAULT_NONCE_FIELD = 'security';
    private const DEFAULT_NONCE_ACTION = 'ajax-nonce';

    public function __construct(
        private Security_Service_Interface $security_service,
        private Json_Response_Factory_Interface $response_factory,
        private Finance_Service $finance_service,
        private Set_Purchase_Order_Handler $set_purchase_order_handler,
        private Get_Purchase_Order_Handler $get_purchase_order_handler,
        private Shortcode_View_Presenter $view_presenter
    ) {
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

    public function show_purchase_order_modal( array $request = [] ): Response {
        $security_error = $this->nonce_failure_html_response( self::DEFAULT_NONCE_FIELD, self::DEFAULT_NONCE_ACTION, $request );
        if ( $security_error ) {
            return $security_error;
        }

        $query  = Get_Purchase_Order_Query::from_request( $request );
        $result = $this->get_purchase_order_handler->handle( $query );
        if ( $result->is_error() ) {
            $vm = Shortcode_View_Model::for_error( $result->error_message ?? '', 'modal' );
        } else {
            $vm = Shortcode_View_Model::for_template( $result->template ? 'purchase-order-modal-' . $result->template : 'purchase-order-modal', [
                'invoice' => $result->invoice,
                'modal'   => $result->modal,
            ], 'club' );
        }
        $output = $this->view_presenter->render( $vm );

        return $this->response_factory->create_raw_response( $output );
    }

    public function set_purchase_order( array $request = [] ): Response {
        $security_error = $this->nonce_failure_response( self::NONCE_FIELD, 'purchase-order-update', null, $request );
        if ( $security_error ) {
            return $security_error;
        }

        $command   = Set_Purchase_Order_Command::from_post( $request );
        $validator = new Validator_Finance();
        $validator = $validator->modal( $command->modal, 'purchaseOrder' );

        if ( ! empty( $validator->error ) ) {
            $return = $validator->get_details();
            if ( empty( $return->msg ) ) {
                $return->msg = __( 'Unable to set purchase order', 'racketmanager' );
            }

            return $this->response_factory->create_error_response( $return, $return->status );
        }

        try {
            $saved = $this->set_purchase_order_handler->handle( $command );
            if ( ! $saved ) {
                $return         = new stdClass();
                $return->error  = true;
                $return->status = 400;
                $return->msg    = __( 'Unable to set purchase order', 'racketmanager' );

                return $this->response_factory->create_error_response( $return, $return->status );
            }

            $return          = new stdClass();
            $return->msg     = __( 'Purchase order updated', 'racketmanager' );
            $return->modal   = $command->modal;
            $return->invoice = show_invoice( $command->invoice_id );

            return $this->response_factory->create_success_response( $return );
        } catch ( Invoice_Not_Found_Exception|Invalid_Argument_Exception $e ) {
            $return         = new stdClass();
            $return->error  = true;
            $return->msg    = $e->getMessage();
            $return->status = 400;

            return $this->response_factory->create_error_response( $return, 400 );
        }
    }

    private function nonce_failure_response( string $field, string $action, ?string $override_msg = null, array $request = [] ): ?Response {
        $details = $this->verify_nonce_details( $field, $action, $request );
        if ( $details === null ) {
            return null;
        }
        if ( $override_msg !== null ) {
            $details->msg = $override_msg;
        }

        return $this->response_factory->create_error_response( $details, $details->status );
    }

    private function nonce_failure_html_response( string $field, string $action, array $request = [] ): ?Response {
        $details = $this->verify_nonce_details( $field, $action, $request );
        if ( $details === null ) {
            return null;
        }

        $output = show_alert( (string) $details->msg, 'danger', 'modal' );

        return $this->response_factory->create_raw_response( $output, (int) $details->status );
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
