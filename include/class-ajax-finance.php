<?php
/**
 * AJAX Finance response methods
 *
 * @package    RacketManager
 * @subpackage RacketManager_AJAX
 */

namespace Racketmanager;

use JetBrains\PhpStorm\NoReturn;
use stdClass;

/**
 * Implement AJAX front end responses.
 *
 * @author Paul Moffat
 */
class Ajax_Finance extends Ajax {
    /**
     * Register ajax actions.
     */
    public function __construct() {
        parent::__construct();
        add_action( 'wp_ajax_nopriv_racketmanager_purchase_order_modal', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_purchase_order_modal', array( &$this, 'show_purchase_order_modal' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_set_purchase_order', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_set_purchase_order', array( &$this, 'set_purchase_order' ) );
    }
    /**
     * Build screen to purchase order edit
     */
    #[NoReturn] public function show_purchase_order_modal(): void {
        $return = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $invoice_id = isset( $_POST['invoiceId'] ) ? intval( $_POST['invoiceId'] ) : null;
            $modal      = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $output     = show_purchase_order_modal( $invoice_id, array( 'modal' => $modal ) );
        } else {
            $output = show_alert( $return->msg, 'danger', 'modal' );
            if ( ! empty( $return->status ) ) {
                status_header( $return->status );
            }
        }
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        wp_die();
    }
    /**
     * Set purchase order function
     *
     * @return void
     */
    public function set_purchase_order(): void {
        $invoice_id     = null;
        $modal          = null;
        $purchase_order = null;
        $invoice        = null;
        $error_field    = 'purchaseOrder';
        $validator      = new Validator_Finance();
        $validator      = $validator->check_security_token( 'racketmanager_nonce', 'purchase-order-update' );
        if ( empty( $validator->error ) ) {
            $modal          = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $invoice_id     = isset( $_POST['invoiceId'] ) ? intval( $_POST['invoiceId'] ) : null;
            $purchase_order = isset( $_POST['purchaseOrder'] ) ? sanitize_text_field( wp_unslash( $_POST['purchaseOrder'] ) ) : null;
            $validator      = $validator->modal( $modal, $error_field );
            $validator      = $validator->invoice( $invoice_id, $error_field );
        }
        if ( empty( $validator->error ) ) {
            $invoice   = get_invoice( $invoice_id );
            $validator = $validator->purchase_order( $purchase_order, $invoice->purchase_order );
        }
        if ( empty( $validator->error ) ) {
            $invoice->set_purchase_order( $purchase_order );
            $return          = new stdClass();
            $return->msg     = __( 'Purchase order updated', 'racketmanager' );
            $return->modal   = $modal;
            $return->invoice = show_invoice( $invoice->id );
            wp_send_json_success( $return );
        }
        $return = $validator->get_details();
        if ( empty( $return->msg ) ) {
            $return->msg = __( 'Unable to set purchase order', 'racketmanager' );
        }
        wp_send_json_error( $return, $return->status );
    }
}
