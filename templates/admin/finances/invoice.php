<?php
/**
 * Invoice administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

/** @var object $invoice */
/** @var string $invoice_view */
$inv_name   = $invoice->billable_name;
if ( 'club' === $invoice->invoice->billable_type ) {
    $view       = 'club-invoices';
    $breadcrumb = __( 'Club invoices', 'racketmanager' );
} else {
    $view       = 'player-invoices';
    $breadcrumb = __( 'Player invoices', 'racketmanager' );
}
$invoice_status = $invoice->invoice->get_status();
?>
<div class="container">
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <a href="/wp-admin/admin.php?page=racketmanager-finances"><?php esc_html_e( 'RacketManager Finances', 'racketmanager' ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-finances&amp;view=<?php echo esc_attr( $view ); ?>"><?php echo esc_html( $breadcrumb ); ?></a> &raquo; <?php esc_html_e( 'View Invoice', 'racketmanager' ); ?>
        </div>
    </div>
    <div class="row mb-3">
        <h1><?php echo esc_html( __( 'Invoice', 'racketmanager' ) . ' ' . $invoice->invoice->get_invoice_number() . ' (' . $invoice_status . ')' ); ?></h1>
        <h2><?php echo esc_html( ucfirst( $invoice->charge_name ) . ' - ' . $inv_name ); ?><h2>
    </div>
    <form method="post" enctype="multipart/form-data" name="invoice_edit" class="form-control mb-3">
        <?php wp_nonce_field( 'racketmanager_manage-invoice', 'racketmanager_nonce' ); ?>
        <div class="row justify-content-start align-items-center mb-3">
            <h2><?php esc_html_e( 'Amend', 'racketmanager' ); ?></h2>
            <div class="form-floating col-4">
                <?php
                $is_invalid = false;
                $msg        = null;
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'status', $validator->err_flds, true ) ) ) {
                    $is_invalid = true;
                    $msg_id     = array_search( 'status', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[ $msg_id ] ?? null;
                }
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" size="1" name="status" id="status" >
                    <option disabled selected><?php esc_html_e( 'Select type', 'racketmanager' ); ?></option>
                    <option value="draft" <?php selected( 'draft', $invoice_status ); ?>><?php esc_html_e( 'Draft', 'racketmanager' ); ?></option>
                    <option value="new" <?php selected( 'new', $invoice_status ); ?>><?php esc_html_e( 'New', 'racketmanager' ); ?></option>
                    <option value="final" <?php selected( 'final', $invoice_status ); ?>><?php esc_html_e( 'Final', 'racketmanager' ); ?></option>
                    <option value="sent" <?php selected( 'sent', $invoice_status ); ?>><?php esc_html_e( 'Sent', 'racketmanager' ); ?></option>
                    <option value="resend"><?php esc_html_e( 'Resend', 'racketmanager' ); ?></option>
                    <option value="paid" <?php selected( 'paid', $invoice_status ); ?>><?php esc_html_e( 'Paid', 'racketmanager' ); ?></option>
                </select>
                <label for="status"><?php esc_html_e( 'Status', 'racketmanager' ); ?></label>
                <?php
                if ( $is_invalid ) {
                    ?>
                    <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                    <?php
                }
                ?>
            </div>
            <div class="form-floating col-4">
                <input class="form-control" type="text" id="purchaseOrder" name="purchaseOrder" placeholder="Purchase Order" value="<?php echo esc_html( $invoice->invoice->get_purchase_order() ); ?>" />
                <label for="purchaseOrder"><?php esc_html_e( 'Purchase Order', 'racketmanager' ); ?></label>
            </div>
            <div class="col-auto">
                <input type="hidden" name="invoice_id" id="invoice_id" value="<?php echo esc_html( $invoice->invoice->get_id() ); ?>" />
                <button type="submit" name="saveInvoice" class="btn btn-primary"><?php esc_html_e( 'Update', 'racketmanager' ); ?></button>
            </div>
        </div>
    </form>
    <div class="row mb-3">
        <?php echo $invoice_view; // phpcs:ignore WordPress.Security.EscapeOutput ?>
    </div>
    <div class="mb-3">
        <a href="/wp-admin/admin.php?page=racketmanager-finances&amp;view=<?php echo esc_attr( $view ); ?>" class="btn btn-secondary"><?php esc_html_e( 'Back to finances', 'racketmanager' ); ?></a>
    </div>
</div>
