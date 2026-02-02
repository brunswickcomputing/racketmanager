<?php
/**
 * AJAX Finance response methods
 *
 * @package    RacketManager
 * @subpackage RacketManager_Ajax_Finance
 */

namespace Racketmanager\Ajax;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Services\Validator\Validator_Finance;
use stdClass;
use function Racketmanager\show_alert;
use function Racketmanager\show_invoice;
use function Racketmanager\show_purchase_order_modal;

/**
 * Implement AJAX front end responses.
 *
 * @author Paul Moffat
 */
class Ajax_Finance extends Ajax {
    /**
     * Register ajax actions.
     *
     * @param $plugin_instance
     */
    public function __construct( $plugin_instance ) {
        parent::__construct( $plugin_instance );
        add_action( 'wp_ajax_nopriv_racketmanager_purchase_order_modal', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_purchase_order_modal', array( &$this, 'show_purchase_order_modal' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_set_purchase_order', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_set_purchase_order', array( &$this, 'set_purchase_order' ) );
    }
    /**
     * Build screen to purchase order edit
     */
    #[NoReturn]
    public function show_purchase_order_modal(): void {
        $validator = new Validator_Finance();
        $validator = $validator->check_security_token();
        if ( empty( $validator->error ) ) {
            $invoice_id = isset( $_POST['invoiceId'] ) ? intval( $_POST['invoiceId'] ) : null;
            $modal      = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $output     = show_purchase_order_modal( $invoice_id, array( 'modal' => $modal ) );
        } else {
            $output = show_alert( $validator->msg, 'danger', 'modal' );
            if ( ! empty( $validator->status ) ) {
                status_header( $validator->status );
            }
        }
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        wp_die();
    }
    /**
     * Set the purchase order function
     *
     * @return void
     */
    public function set_purchase_order(): void {
        $modal       = null;
        $error_field = 'purchaseOrder';
        $validator   = new Validator_Finance();
        $validator   = $validator->check_security_token( 'racketmanager_nonce', 'purchase-order-update' );
        if ( empty( $validator->error ) ) {
            $modal     = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $validator = $validator->modal( $modal, $error_field );
        }
        if ( empty( $validator->error ) ) {
            $invoice_id     = isset( $_POST['invoiceId'] ) ? intval( $_POST['invoiceId'] ) : null;
            $purchase_order = isset( $_POST['purchaseOrder'] ) ? sanitize_text_field( wp_unslash( $_POST['purchaseOrder'] ) ) : null;
            try {
                $validator       = $this->finance_service->set_invoice_purchase_order( $invoice_id, $purchase_order );
                $return          = new stdClass();
                $return->msg     = __( 'Purchase order updated', 'racketmanager' );
                $return->modal   = $modal;
                $return->invoice = show_invoice( $invoice_id );
                wp_send_json_success( $return );
            } catch ( Invoice_Not_Found_Exception|Invalid_Argument_Exception $e ) {
                $validator->error = true;
                $validator->msg = $e->getMessage();
            }
        }
        $return = $validator->get_details();
        if ( empty( $return->msg ) ) {
            $return->msg = __( 'Unable to set purchase order', 'racketmanager' );
        }
        wp_send_json_error( $return, $return->status );
    }
}
