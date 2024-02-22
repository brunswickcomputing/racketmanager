<?php
/**
 * Charges administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

?>
<div class="container">

	<form id="charges-action" method="post" action="" class="form-control mb-3">
		<?php wp_nonce_field( 'charges-bulk' ); ?>

		<div class="row justify-content-between mb-3">
			<!-- Bulk Actions -->
			<div class="col-auto">
				<select name="action" size="1">
					<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
				</select>
				<button name="doChargesDel" id="doChargesDel" class="btn btn-secondary"><?php esc_html_e( 'Apply', 'racketmanager' ); ?></button>
			</div>
		</div>

		<div class="container striped">
			<div class="row table-header">
				<div class="col-2 col-lg-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('charges-action'));" /></div>
				<div class="d-none d-lg-1 col-1 column-num">ID</div>
				<div class="col-6"><?php esc_html_e( 'Name', 'racketmanager' ); ?></div>
				<div class="col-3"><?php esc_html_e( 'Status', 'racketmanager' ); ?></div>
			</div>

			<?php
			$charges = $this->getCharges();
			if ( $charges ) {
				foreach ( $charges as $charge ) {
					?>
					<div class="row table-row">
						<div class="col-2 col-lg-1 check-column"><input type="checkbox" value="<?php echo esc_html( $charge->id ); ?>" name="charge[<?php echo esc_html( $charge->id ); ?>]" /></div>
						<div class="d-none d-lg-1 col-1 column-num"><?php echo esc_html( $charge->id ); ?></div>
						<div class="col-6"><a href="admin.php?page=racketmanager-finances&amp;subpage=charges&amp;charges=<?php echo esc_html( $charge->id ); ?>"><?php echo esc_html( $charge->season ) . ' ' . esc_html( ucfirst( $charge->competition->name ) ); ?></a></div>
						<div class="col-3 "><?php echo esc_html( $charge->status ); ?></div>
					</div>
				<?php } ?>
			<?php } ?>
		</form>
	</div>

	<div class="mb-3">
		<!-- Add New Charge -->
		<a href="admin.php?page=racketmanager-finances&amp;subpage=charges" class="btn btn-primary submit"><?php esc_html_e( 'Add Charges', 'racketmanager' ); ?></a>
	</div>
</div>
