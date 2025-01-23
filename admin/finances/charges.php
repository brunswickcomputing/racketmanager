<?php
/**
 * Charges administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

$args            = array();
$args['orderby'] = array(
	'type' => 'ASC',
	'name' => 'ASC',
);
$competitions    = $racketmanager->get_competitions( $args );
$seasons         = $racketmanager->get_seasons( 'DESC' );
$charges         = $finance_charges;
?>
<div class="container">
	<div class="row gx-3 align-items-center mb-3">
		<form id="invoices-filter" method="get" action="" class="form-control">
			<input type="hidden" name="page" value="<?php echo 'racketmanager-finances'; ?>" />
			<input type="hidden" name="tab" value="<?php echo 'racketmanager-charges'; ?>" />
			<div class="row gx-3 align-items-center">
				<div class="col-12 col-md-4 col-lg-auto mb-3 mb-md-0">
					<select class="form-select" name="competition" id="competition">
						<option value="" <?php selected( '', $competition_id ); ?>><?php esc_html_e( 'All competitions', 'racketmanager' ); ?></option>
						<?php
						foreach ( $competitions as $competition ) {
							?>
							<option value="<?php echo esc_attr( $competition->id ); ?>" <?php selected( $competition->id, $competition_id ); ?>><?php echo esc_html( $competition->name ); ?></option>
							<?php
						}
						?>
					</select>
				</div>
				<div class="col-12 col-md-4 col-lg-auto mb-3 mb-md-0">
					<select class="form-select" name="season" id="season">
						<option value="" <?php selected( '', $season ); ?>><?php esc_html_e( 'All seasons', 'racketmanager' ); ?></option>
						<?php
						foreach ( $seasons as $season_dtls ) {
							?>
							<option value="<?php echo esc_html( $season_dtls->name ); ?>" <?php selected( $season_dtls->name, $season ); ?>><?php echo esc_html( $season_dtls->name ); ?></option>
							<?php
						}
						?>
					</select>
				</div>
				<div class="col-auto">
					<button class="btn btn-primary"><?php esc_html_e( 'Filter', 'racketmanager' ); ?></button>
				</div>
			</div>
		</form>
	</div>
	<div class="row gx-3 align-items-center mb-3">
		<form id="charges-action" method="post" action="" class="form-control mb-3">
			<?php wp_nonce_field( 'charges-bulk' ); ?>
			<div class="row gx-3 mb-3 align-items-center">
				<!-- Bulk Actions -->
				<div class="col-auto">
					<select class="form-select" name="action">
						<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
					</select>
				</div>
				<div class="col-auto">
					<button name="doChargesDel" id="doChargesDel" class="btn btn-secondary"><?php esc_html_e( 'Apply', 'racketmanager' ); ?></button>
				</div>
			</div>
			<table class="table table-striped">
				<thead class="table-dark">
					<tr>
						<th class="check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('charges-action'));" /></th>
						<th class=""><?php esc_html_e( 'Name', 'racketmanager' ); ?></th>
						<th class=""><?php esc_html_e( 'Status', 'racketmanager' ); ?></th>
						<th class="text-end"><?php esc_html_e( 'Amount', 'racketmanager' ); ?></th>
					</tr>
				</div>

				<?php
				if ( $charges ) {
					?>
					<tbody>
						<?php
						$total_amount = 0;
						foreach ( $charges as $charge ) {
							$charge_amount = 0;
							if ( 'final' === $charge->status ) {
								$invoices = $charge->get_invoices();
								foreach ( $invoices as $invoice ) {
									$charge_amount += $invoice->amount;
								}
							}
							$charge->total = $charge_amount;
							$total_amount += $charge_amount;
							?>
							<tr>
								<td class="check-column"><input type="checkbox" value="<?php echo esc_html( $charge->id ); ?>" name="charge[<?php echo esc_html( $charge->id ); ?>]" /></td>
								<td class=""><a href="admin.php?page=racketmanager-finances&amp;view=charges&amp;charges=<?php echo esc_html( $charge->id ); ?>"><?php echo esc_html( $charge->season ) . ' ' . esc_html( ucfirst( $charge->competition->name ) ); ?></a></td>
								<td class=""><?php echo esc_html( $charge->status ); ?></td>
								<td class="text-end">
									<?php
									if ( empty( $charge->total ) ) {
										echo '-';
									} else {
										the_currency_amount( $charge->total );
									}
									?>
								</td>
							</tr>
							<?php
						}
						?>
					</tbody>
					<tfoot class="table-footer">
						<tr>
							<td colspan="3"></td>
							<td class="text-end"><?php the_currency_amount( $total_amount ); ?></td>
						</tr>
					</tfoot>
					<?php
				}
				?>
			</table>
		</form>
	</div>
	<div class="row gx-3 align-items-center mb-3">
		<div class="mb-3">
			<!-- Add New Charge -->
			<a href="admin.php?page=racketmanager-finances&amp;view=charges" class="btn btn-primary submit"><?php esc_html_e( 'Add Charges', 'racketmanager' ); ?></a>
		</div>
	</div>
</div>
