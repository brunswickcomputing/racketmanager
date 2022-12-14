<?php
?>
<div class="container">

	<form id="invoices-filter" method="post" action="" class="form-control mb-3">
		<?php wp_nonce_field( 'invoices-bulk' ) ?>

		<div class="tablenav">
			<!-- Bulk Actions -->
			<select name="action" size="1">
				<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
				<option value="paid"><?php _e('Paid')?></option>
			</select>
			<input type="submit" value="<?php _e('Apply'); ?>" name="doActionInvoices" id="doActionInvoices" class="btn btn-secondary action" />
		</div>

		<div class="container">
			<div class="row table-header">
				<div class="col-2 col-lg-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('invoices-filter'));" /></div>
				<div class="d-none d-lg-1 col-1 column-num">ID</div>
				<div class="col-1"><?php _e( 'Invoice', 'racketmanager' ) ?></div>
				<div class="col-3"><?php _e( 'Charge', 'racketmanager' ) ?></div>
				<div class="col-3"><?php _e( 'Club', 'racketmanager' ) ?></div>
				<div class="col-2"><?php _e( 'Status', 'racketmanager' ) ?></div>
			</div>

			<?php
			if ( $invoices = $racketmanager->getInvoices() ) {
				$class = '';
				foreach ( $invoices AS $invoice ) {
					$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
					<div class="row table-row <?php echo $class ?>">
						<div class="col-2 col-lg-1 check-column"><input type="checkbox" value="<?php echo $invoice->id ?>" name="invoice[<?php echo $invoice->id ?>]" /></div>
						<div class="d-none d-lg-1 col-1 column-num"><?php echo $charge->id ?></div>
						<div class="col-1"><a href="admin.php?page=racketmanager-finances&amp;subpage=invoice&amp;invoice=<?php echo $invoice->id ?>"><?php echo $invoice->invoiceNumber ?></a></div>
						<div class="col-3"><?php echo ucfirst($invoice->charge->type).' '.$invoice->charge->season ?></div>
						<div class="col-3"><?php echo $invoice->club->name ?></div>
						<div class="col-2"><?php echo $invoice->status ?></div>
					</div>
				<?php } ?>
			<?php } ?>
		</form>
	</div>

</div>
