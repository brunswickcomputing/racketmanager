<?php
/**
 * RacketManager Admin invoices page
 *
 * @author Paul Moffat
 * @package Racketmanager_admin
 */

$racketmanager_clubs = $racketmanager->get_clubs();
?>
<div class="container">

	<div class="row justify-content-between mb-3">
		<form id="invoices-filter" method="get" action="" class="form-control">
			<input type="hidden" name="page" value="<?php echo 'racketmanager-finances'; ?>" />
			<input type="hidden" name="tab" value="<?php echo 'racketmanager-invoices'; ?>" />
			<div class="col-auto">
				<select class="f" size="1" name="club" id="club">
					<option value="all"><?php esc_html_e( 'All clubs', 'racketmanager' ); ?></option>
					<?php
					foreach ( $racketmanager_clubs as $racketmanager_club ) {
						?>
						<option value="<?php echo esc_html( $racketmanager_club->id ); ?>" <?php echo esc_html( $racketmanager_club->id ) === $club_id ? 'selected' : ''; ?>><?php echo esc_html( $racketmanager_club->name ); ?></option>
						<?php
					}
					?>
				</select>
				<select class="" size="1" name="status" id="status">
					<option value="all" <?php echo esc_html( 'all' === $status ? 'selected' : '' ); ?>><?php esc_html_e( 'All', 'racketmanager' ); ?></option>
					<option value="open" <?php echo esc_html( 'open' === $status ? 'selected' : '' ); ?>><?php esc_html_e( 'Open', 'racketmanager' ); ?></option>
					<option value="overdue" <?php echo esc_html( 'overdue' === $status ? 'selected' : '' ); ?>><?php esc_html_e( 'Overdue', 'racketmanager' ); ?></option>
					<option value="paid" <?php echo esc_html( 'paid' === $status ? 'selected' : '' ); ?>><?php esc_html_e( 'Paid', 'racketmanager' ); ?></option>
				</select>
				<button class="btn btn-primary"><?php esc_html_e( 'Filter', 'racketmanager' ); ?></button>
			</div>
		</form>
	</div>
	<div class="mb-3">
		<?php if ( $invoices ) { ?>
			<form id="invoices-action" method="post" action="" class="form-control">
				<?php wp_nonce_field( 'invoices-bulk' ); ?>
				<div class="row justify-content-between mb-3">
					<!-- Bulk Actions -->
					<div class="col-auto">
						<select name="action" size="1">
							<option value="-1" selected="selected"><?php esc_html_e( 'Change Status', 'racketmanager' ); ?></option>
							<option value="paid"><?php esc_html_e( 'Paid', 'racketmanager' ); ?></option>
						</select>
						<button name="doActionInvoices" id="doActionInvoices" class="btn btn-secondary"><?php esc_html_e( 'Apply', 'racketmanager' ); ?></button>
					</div>
				</div>
				<div class="container striped">
					<div class="row table-header">
						<div class="col-2 col-lg-1 check-column">
							<input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('invoices-action'));" />
						</div>
						<div class="d-none d-lg-1 col-1 column-num">ID</div>
						<div class="col-1"><?php esc_html_e( 'Invoice', 'racketmanager' ); ?></div>
						<div class="col-3"><?php esc_html_e( 'Charge', 'racketmanager' ); ?></div>
						<div class="col-3"><?php esc_html_e( 'Club', 'racketmanager' ); ?></div>
						<div class="col-2"><?php esc_html_e( 'Status', 'racketmanager' ); ?></div>
						<div class="col-2"><?php esc_html_e( 'Date Due', 'racketmanager' ); ?></div>
					</div>
					<?php
					foreach ( $invoices as $racketmanager_invoice ) {
						?>
						<div class="row table-row">
							<div class="col-2 col-lg-1 check-column"><input type="checkbox" value="<?php echo esc_html( $racketmanager_invoice->id ); ?>" name="invoice[<?php echo esc_html( $racketmanager_invoice->id ); ?>]" /></div>
							<div class="d-none d-lg-1 col-1 column-num"><?php echo esc_html( $charge->id ); ?></div>
							<div class="col-1"><a href="admin.php?page=racketmanager-finances&amp;subpage=invoice&amp;invoice=<?php echo esc_html( $racketmanager_invoice->id ); ?>"><?php echo esc_html( $racketmanager_invoice->invoice_number ); ?></a></div>
							<div class="col-3"><?php echo esc_html( ucfirst( $racketmanager_invoice->charge->competition->name ) . ' ' . $racketmanager_invoice->charge->season ); ?></div>
							<div class="col-3"><?php echo esc_html( $racketmanager_invoice->club->name ); ?></div>
							<div class="col-2"><?php echo esc_html( $racketmanager_invoice->status ); ?></div>
							<div class="col-2"><?php echo esc_html( $racketmanager_invoice->date_due ); ?></div>
						</div>
						<?php
					}
					?>
				</div>
			</form>
			<?php
		} else {
			?>
			<div class="error">
				<?php esc_html_e( 'No invoices found for search criteria', 'racketmanager' ); ?>
			</div>
			<?php
		}
		?>
	</div>
</div>
