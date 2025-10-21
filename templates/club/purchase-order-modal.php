<?php
/**
 * Template for purchase order modal
 *
 * @package Racketmanager/Templates/Club
 */

namespace Racketmanager;

/** @var object $invoice */
/** @var string $modal */
?>
<div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
        <form id="purchase-order-update" class="" action="#" method="post">
            <?php wp_nonce_field( 'purchase-order-update', 'racketmanager_nonce' ); ?>
            <input type="hidden" name="invoiceId" value="<?php echo esc_attr( $invoice->id ); ?>" />
            <input type="hidden" name="modal" value="<?php echo esc_attr( $modal ); ?>" />
            <div class="modal-header modal__header">
                <h4 class="modal-title"><?php esc_html_e( 'Edit purchase order', 'racketmanager' ) ; ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container">
                    <div class="mb-3">
                        <label for="purchaseOrder" ><?php esc_html_e( 'Purchase Order', 'racketmanager' ); ?></label>
                        <input type="text" class="form-control" id="purchaseOrder" name="purchaseOrder" value="<?php echo esc_html( $invoice->purchase_order ); ?>" />
                        <div class="invalid-feedback" id="purchaseOrderFeedback"></div>
                    </div>
                    <div id="POUpdateResponse" class="alert_rm alert--danger" style="display: none;">
                        <div class="alert__body">
                            <div class="alert__body-inner">
                                <span id="POUpdateResponseText"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-plain" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
                    <button class="btn btn-primary" type="button" id="POUpdateSubmit" name="POUpdateSubmit" data-action="set-purchase-order">
                        <?php esc_html_e( 'Update', 'racketmanager' ); ?>
                    </button>
                </div>
        </form>
    </div>
</div>
