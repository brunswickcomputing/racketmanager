<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Ajax;

use Racketmanager\Application\Finance\Commands\Set_Purchase_Order_Command;
use Racketmanager\Application\Finance\Commands\Set_Purchase_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Query;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Application\Ports\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Presentation\Ports\Shortcode_View_Presenter_Interface;
use Racketmanager\Application\Ports\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
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
    private const string NONCE_FIELD          = 'racketmanager_nonce';
    private const string DEFAULT_NONCE_FIELD  = 'security';
    private const string DEFAULT_NONCE_ACTION = 'ajax-nonce';

    /**
     * Initialises the service with dependencies required for security handling,
     * JSON response creation, finance operations, purchase order processing, and
     * shortcode view presentation.
     *
     * @param Security_Service_Interface         $security_service Security service used to validate and authorize requests.
     * @param Json_Response_Factory_Interface    $response_factory Factory used to create JSON responses.
     * @param Finance_Service                    $finance_service Service used to handle finance-related operations.
     * @param Set_Purchase_Order_Handler         $set_purchase_order_handler Handler used to set purchase order data.
     * @param Get_Purchase_Order_Handler         $get_purchase_order_handler Handler used to retrieve purchase order data.
     * @param Shortcode_View_Presenter_Interface $view_presenter Presenter used to render shortcode views.
     *
     * @return void
     */
    public function __construct(
        private readonly Security_Service_Interface $security_service,
        private readonly Json_Response_Factory_Interface $response_factory,
        private readonly Finance_Service $finance_service,
        private readonly Set_Purchase_Order_Handler $set_purchase_order_handler,
        private readonly Get_Purchase_Order_Handler $get_purchase_order_handler,
        private readonly Shortcode_View_Presenter_Interface $view_presenter
    ) {
    }

    /**
     * Creates an error response for unauthenticated users attempting to access a protected feature.
     *
     * The response includes a localised login-required message and modal alert markup,
     * and is returned with an unauthorised HTTP status code.
     *
     * @return Response Error response indicating that the user must be logged in.
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
     * Displays the purchase order modal after validating request security and
     * retrieving the purchase order data needed to render the modal content.
     *
     * @param array $request Request data used for nonce validation and purchase order lookup.
     *
     * @return Response Raw response containing the rendered purchase order modal or an error response.
     */
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
            $vm = Shortcode_View_Model::for_template(
                $result->template ? 'purchase-order-modal-' . $result->template : 'purchase-order-modal',
                [
                    'invoice' => $result->invoice,
                    'modal'   => $result->modal,
                ],
                'club'
            );
        }
        $output = $this->view_presenter->render( $vm );

        return $this->response_factory->create_raw_response( $output );
    }

    /**
     * Sets or updates the purchase order for an invoice request after validating
     * security nonce data, request input, and purchase order details.
     *
     * @param array $request Request data used to build the purchase order command.
     *
     * @return Response Success response containing updated purchase order details, or error response when validation, security, saving, or invoice lookup fails.
     */
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
            $saved  = $this->set_purchase_order_handler->handle( $command );
            $return = new stdClass();
            if ( ! $saved ) {
                $return->error  = true;
                $return->status = 400;
                $return->msg    = __( 'Unable to set purchase order', 'racketmanager' );

                return $this->response_factory->create_error_response( $return, $return->status );
            }

            $return->msg     = __( 'Purchase order updated', 'racketmanager' );
            $return->modal   = $command->modal;
            $return->invoice = show_invoice( $command->invoice_id );

            return $this->response_factory->create_success_response( $return );
        } catch ( Invoice_Not_Found_Exception | Invalid_Argument_Exception $e ) {
            $return         = new stdClass();
            $return->error  = true;
            $return->msg    = $e->getMessage();
            $return->status = 400;

            return $this->response_factory->create_error_response( $return, 400 );
        }
    }

    /**
     * Creates an error response for a failed nonce validation when failure details
     * are available.
     *
     * @param string      $field Nonce field name to validate.
     * @param string      $action Nonce action associated with the expected token.
     * @param string|null $override_msg Optional message used to replace the default failure message.
     * @param array       $request Request data used to retrieve and validate the nonce value.
     *
     * @return Response|null Error response for the nonce failure, or null when no failure details are available.
     */
    private function nonce_failure_response( string $field, string $action, ?string $override_msg = null, array $request = [] ): ?Response {
        $details = $this->verify_nonce_details( $field, $action, $request );
        if ( null === $details ) {
            return null;
        }
        if ( null !== $override_msg ) {
            $details->msg = $override_msg;
        }

        return $this->response_factory->create_error_response( $details, $details->status );
    }

    /**
     * Creates an HTML error response when nonce verification fails.
     *
     * @param string $field Nonce field name used to retrieve the nonce value.
     * @param string $action Nonce action used during verification.
     * @param array  $request Request data containing the nonce field and related values.
     *
     * @return Response|null Raw HTML response containing the nonce failure alert, or null when nonce verification does not fail.
     */
    private function nonce_failure_html_response( string $field, string $action, array $request = [] ): ?Response {
        $details = $this->verify_nonce_details( $field, $action, $request );
        if ( null === $details ) {
            return null;
        }

        $output = show_alert( (string) $details->msg, 'danger', 'modal' );

        return $this->response_factory->create_raw_response( $output, (int) $details->status );
    }

    /**
     * Verifies nonce data from the request for the specified field and action.
     *
     * Sanitises and validates the nonce value when present. Returns null when the
     * nonce is valid, or an error details object containing status and message
     * information when the nonce is missing, invalid, or expired.
     *
     * @param string $field Request field name containing the nonce value.
     * @param string $action Nonce action used for verification.
     * @param array  $request Request data containing the nonce field.
     *
     * @return stdClass|null Error details object when verification fails, or null when the nonce is valid.
     */
    private function verify_nonce_details( string $field, string $action, array $request = [] ): ?stdClass {
        $nonce_set = isset( $request[ $field ] );
        $nonce     = $request[ $field ] ?? '';
        $nonce     = is_string( $nonce ) ? sanitize_text_field( wp_unslash( $nonce ) ) : '';
        if ( '' !== $nonce && $this->security_service->verify_nonce( $nonce, $action ) ) {
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
