<?php
?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-finances"><?php _e( 'RacketManager Finances', 'racketmanager' ) ?></a> &raquo; <?php _e('View Invoice', 'racketmanager') ?>
		</div>
	</div>
	<div class="row mb-3">
    <h1><?php echo __('Invoice', 'racketmanager').' '.$invoice->invoiceNumber.' ('.__($invoice->status, 'racketmanager').')' ?></h1>
    <h2><?php echo ucfirst($invoice->charge->type).' '.ucfirst($invoice->charge->competitionType).' '.$invoice->charge->season.' - '.$invoice->club->name ?><h2>
  </div>
	<form method="post" enctype="multipart/form-data" name="invoice_edit" class="form-control mb-3">
  	<div class="row justify-content-start align-items-center mb-3">
      <h2><?php _e('Change status', 'racketmanager') ?></h2>
      <div class="form-floating col-4">
				<select class="form-select" size="1" name="status" id="type" >
					<option><?php _e( 'Select type' , 'racketmanager') ?></option>
					<option value="draft" <?php selected( 'draft', $invoice->status ) ?>><?php _e( 'Draft', 'racketmanager') ?></option>
          <option value="new" <?php selected( 'new', $invoice->status ) ?>><?php _e( 'New', 'racketmanager') ?></option>
					<option value="final" <?php selected( 'final', $invoice->status ) ?>><?php _e( 'Final', 'racketmanager') ?></option>
          <option value="sent" <?php selected( 'sent', $invoice->status ) ?>><?php _e( 'Sent', 'racketmanager') ?></option>
          <option value="resent" <?php selected( 'resent', $invoice->status ) ?>><?php _e( 'Resent', 'racketmanager') ?></option>
					<option value="paid" <?php selected( 'paid', $invoice->status ) ?>><?php _e( 'Paid', 'racketmanager') ?></option>
				</select>
				<label for="invoice"><?php _e( 'Status', 'racketmanager' ) ?></label>
			</div>
      <div class="col-auto">
        <input type="hidden" name="invoice_id" id="invoice_id" value="<?php echo $invoice->id ?>" />
				<button type="submit" name="saveInvoice" class="btn btn-primary"><?php _e('Update', 'racketmanager') ?></button>
			</div>
		</div>
  </form>
  <div class="row mb-3">
    <?php echo $invoiceView ?>
  </div>
  <div class="mb-3">
    <a href="admin.php?page=racketmanager-finances&amp;tab=<?php echo $tab ?>" class="btn btn-secondary"><?php _e('Back to finances', 'racketmanager'); ?></a>
  </div>
</div>
