<?php
/**
 * Invoice administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

if ( empty( $invoice->player_id ) ) {
	$view       = 'club-invoices';
	$breadcrumb = __( 'Club invoices', 'racketmanager' );
	$inv_name   = $invoice->club->name;
} else {
	$view       = 'player-invoices';
	$breadcrumb = __( 'Player invoices', 'racketmanager' );
	$inv_name   = $invoice->player->display_name;
}
?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-finances"><?php esc_html_e( 'RacketManager Finances', 'racketmanager' ); ?></a> &raquo; <a href="admin.php?page=racketmanager-finances&amp;view=<?php echo esc_attr( $view ); ?>"><?php echo esc_html( $breadcrumb ); ?></a> &raquo; <?php esc_html_e( 'View Invoice', 'racketmanager' ); ?>
		</div>
	</div>
	<div class="row mb-3">
		<h1><?php echo esc_html( __( 'Invoice', 'racketmanager' ) . ' ' . $invoice->invoice_number . ' (' . $invoice->status . ')' ); ?></h1>
		<h2><?php echo esc_html( ucfirst( $invoice->charge->competition->name ) . ' ' . $invoice->charge->season . ' - ' . $inv_name ); ?><h2>
	</div>
	<form method="post" enctype="multipart/form-data" name="invoice_edit" class="form-control mb-3">
		<?php wp_nonce_field( 'racketmanager_manage-invoice', 'racketmanager_nonce' ); ?>
		<div class="row justify-content-start align-items-center mb-3">
			<h2><?php esc_html_e( 'Change status', 'racketmanager' ); ?></h2>
			<div class="form-floating col-4">
				<select class="form-select" size="1" name="status" id="type" >
					<option><?php esc_html_e( 'Select type', 'racketmanager' ); ?></option>
					<option value="draft" <?php selected( 'draft', $invoice->status ); ?>><?php esc_html_e( 'Draft', 'racketmanager' ); ?></option>
					<option value="new" <?php selected( 'new', $invoice->status ); ?>><?php esc_html_e( 'New', 'racketmanager' ); ?></option>
					<option value="final" <?php selected( 'final', $invoice->status ); ?>><?php esc_html_e( 'Final', 'racketmanager' ); ?></option>
					<option value="sent" <?php selected( 'sent', $invoice->status ); ?>><?php esc_html_e( 'Sent', 'racketmanager' ); ?></option>
					<option value="resent" <?php selected( 'resent', $invoice->status ); ?>><?php esc_html_e( 'Resent', 'racketmanager' ); ?></option>
					<option value="paid" <?php selected( 'paid', $invoice->status ); ?>><?php esc_html_e( 'Paid', 'racketmanager' ); ?></option>
				</select>
				<label for="invoice"><?php esc_html_e( 'Status', 'racketmanager' ); ?></label>
			</div>
			<div class="col-auto">
				<input type="hidden" name="invoice_id" id="invoice_id" value="<?php echo esc_html( $invoice->id ); ?>" />
				<button type="submit" name="saveInvoice" class="btn btn-primary"><?php esc_html_e( 'Update', 'racketmanager' ); ?></button>
			</div>
		</div>
	</form>
	<div class="row mb-3">
		<?php echo $invoice_view; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<div class="mb-3">
		<a href="admin.php?page=racketmanager-finances&amp;view=<?php echo esc_attr( $view ); ?>" class="btn btn-secondary"><?php esc_html_e( 'Back to finances', 'racketmanager' ); ?></a>
	</div>
</div>
