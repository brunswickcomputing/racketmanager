<?php
$clubs = $racketmanager->getClubs( );
?>
<div class="container">

	<div class="row justify-content-between mb-3">
		<form id="invoices-filter" method="get" action="" class="form-control">
			<input type="hidden" name="page" value="<?php echo 'racketmanager-finances' ?>" />
			<input type="hidden" name="tab" value="<?php echo 'invoices' ?>" />
			<div class="col-auto">
				<select class="f" size="1" name="club" id="club">
					<option value="all"><?php _e( 'All clubs', 'racketmanager' ) ?></option>
					<?php foreach ( $clubs AS $club ) { ?>
						<option value="<?php echo $club->id ?>" <?php echo $club->id == $clubId ?  'selected' :  '' ?>><?php echo $club->name ?></option>
					<?php } ?>
				</select>
				<select class="" size="1" name="status" id="status">
					<option value="all" <?php echo $status == 'all' ?  'selected' :  '' ?>><?php _e( 'All', 'racketmanager' ) ?></option>
					<option value="open" <?php echo $status == 'open' ?  'selected' :  '' ?>><?php _e( 'Open', 'racketmanager' ) ?></option>
					<option value="overdue" <?php echo $status == 'overdue' ?  'selected' :  '' ?>><?php _e( 'Overdue', 'racketmanager' ) ?></option>
					<option value="paid" <?php echo $status == 'paid' ?  'selected' :  '' ?>><?php _e( 'Paid', 'racketmanager' ) ?></option>
				</select>
				<button class="btn btn-primary"><?php _e('Filter') ?></button>
			</div>
		</form>
	</div>
	<div class="mb-3">
		<?php if ( $invoices ) { ?>
			<form id="invoices-action" method="post" action="" class="form-control">
				<?php wp_nonce_field( 'invoices-bulk' ) ?>
				<div class="row justify-content-between mb-3">
					<!-- Bulk Actions -->
					<div class="col-auto">
						<select name="action" size="1">
							<option value="-1" selected="selected"><?php _e('Change Status') ?></option>
							<option value="paid"><?php _e('Paid')?></option>
						</select>
						<button name="doActionInvoices" id="doActionInvoices" class="btn btn-secondary"><?php _e('Apply') ?></button>
					</div>
				</div>
				<div class="container striped">
					<div class="row table-header">
						<div class="col-2 col-lg-1 check-column">
							<input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('invoices-action'));" />
						</div>
						<div class="d-none d-lg-1 col-1 column-num">ID</div>
						<div class="col-1"><?php _e( 'Invoice', 'racketmanager' ) ?></div>
						<div class="col-3"><?php _e( 'Charge', 'racketmanager' ) ?></div>
						<div class="col-3"><?php _e( 'Club', 'racketmanager' ) ?></div>
						<div class="col-2"><?php _e( 'Status', 'racketmanager' ) ?></div>
						<div class="col-2"><?php _e( 'Date Due', 'racketmanager' ) ?></div>
					</div>
					<?php
					foreach ( $invoices AS $invoice ) { ?>
						<div class="row table-row">
							<div class="col-2 col-lg-1 check-column"><input type="checkbox" value="<?php echo $invoice->id ?>" name="invoice[<?php echo $invoice->id ?>]" /></div>
							<div class="d-none d-lg-1 col-1 column-num"><?php echo $charge->id ?></div>
							<div class="col-1"><a href="admin.php?page=racketmanager-finances&amp;subpage=invoice&amp;invoice=<?php echo $invoice->id ?>"><?php echo $invoice->invoiceNumber ?></a></div>
							<div class="col-3"><?php echo ucfirst($invoice->charge->type).' '.$invoice->charge->season ?></div>
							<div class="col-3"><?php echo $invoice->club->name ?></div>
							<div class="col-2"><?php echo $invoice->status ?></div>
							<div class="col-2"><?php echo $invoice->date_due ?></div>
						</div>
					<?php } ?>
				</div>
			</form>
		<?php } else { ?>
			<div class="error">
				<?php _e('No invoices found for search criteria', 'racketmanager') ?>
			</div>
		<?php } ?>
	</div>
</div>
