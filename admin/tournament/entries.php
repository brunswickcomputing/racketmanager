<?php
/**
 * Admin screen for tournament entries.
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

?>
<?php
if ( ! empty( $entries_withdrawn ) ) {
	?>
	<div class="row">
		<div class="col-12 col-md-6">
			<table class="table table-striped">
				<thead class="table-dark">
					<tr>
						<th><?php esc_html_e( 'Withdrawn Entries', 'racketmanager' ); ?> <?php echo empty( $entries_withdrawn ) ? null : '(' . count( $entries_withdrawn ) . ')'; ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$player_list = $withdrawn_entries;
					$entered     = true;
					require 'player-list.php';
					?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
if ( ! empty( $entries_pay_due ) ) {
	?>
	<div class="row">
		<div class="col-12 col-md-6">
			<table class="table table-striped">
				<thead class="table-dark">
					<tr>
						<th><?php esc_html_e( 'Unpaid Entries', 'racketmanager' ); ?> <?php echo empty( $entries_pay_due ) ? null : '(' . count( $entries_pay_due ) . ')'; ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$player_list = $pay_due_entries;
					$entered     = true;
					require 'player-list.php';
					?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
?>
<div class="row">
	<div class="col-12 col-md-6">
		<table class="table table-striped">
			<thead class="table-dark">
				<tr>
					<th><?php esc_html_e( 'Pending Entries', 'racketmanager' ); ?> <?php echo empty( $entries_pending ) ? null : '(' . count( $entries_pending ) . ')'; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$player_list = $pending_entries;
				$entered     = false;
				require 'player-list.php';
				?>
			</tbody>
		</table>
	</div>
	<div class="col-12 col-md-6">
		<table class="table table-striped">
			<thead class="table-dark">
				<tr>
					<th><?php esc_html_e( 'Confirmed Entries', 'racketmanager' ); ?> <?php echo empty( $entries_confirmed ) ? null : '(' . count( $entries_confirmed ) . ')'; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$player_list = $confirmed_entries;
				$entered     = true;
				require 'player-list.php';
				?>
			</tbody>
		</table>
	</div>
</div>
