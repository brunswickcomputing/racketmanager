<?php
?>
<div class="container">

	<form id="charges-filter" method="post" action="" class="form-control mb-3">
		<?php wp_nonce_field( 'charges-bulk' ) ?>

		<div class="tablenav">
			<!-- Bulk Actions -->
			<select name="action" size="1">
				<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
				<option value="delete"><?php _e('Delete')?></option>
			</select>
			<input type="submit" value="<?php _e('Apply'); ?>" name="doChargesDel" id="doChargesDel" class="btn btn-secondary action" />
		</div>

		<div class="container">
			<div class="row table-header">
				<div class="col-2 col-lg-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('charges-filter'));" /></div>
				<div class="d-none d-lg-1 col-1 column-num">ID</div>
				<div class="col-6"><?php _e( 'Name', 'racketmanager' ) ?></div>
				<div class="col-3"><?php _e( 'Status', 'racketmanager' ) ?></div>
			</div>

			<?php
			if ( $charges = $racketmanager->getCharges() ) {
				$class = '';
				foreach ( $charges AS $charge ) {
					$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
					<div class="row table-row <?php echo $class ?>">
						<div class="col-2 col-lg-1 check-column"><input type="checkbox" value="<?php echo $charge->id ?>" name="charge[<?php echo $charge->id ?>]" /></div>
						<div class="d-none d-lg-1 col-1 column-num"><?php echo $charge->id ?></div>
						<div class="col-6"><a href="admin.php?page=racketmanager-finances&amp;subpage=charges&amp;charges=<?php echo $charge->id ?>"><?php echo $charge->season.' '.ucfirst($charge->type).' '.ucfirst($charge->competitionType) ?></a></div>
						<div class="col-3 "><?php echo $charge->status ?></div>
					</div>
				<?php } ?>
			<?php } ?>
		</form>
	</div>

	<div class="mb-3">
		<!-- Add New Charge -->
		<a href="admin.php?page=racketmanager-finances&amp;subpage=charges" name="addCharges" class="btn btn-primary submit"><?php _e( 'Add Charges','racketmanager' ) ?></a>
	</div>
</div>
