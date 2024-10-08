<?php
/**
 * Admin screen for tournament entries.
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

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
				foreach ( $pending_entries as $key => $players ) {
					foreach ( $players as $player ) {
						?>
						<tr>
							<td>
								<?php
								if ( ! empty( $player->email ) ) {
									?>
									<a href="#">
									<?php
								}
								?>
								<?php echo esc_html( $player->display_name ); ?>
								<?php
								if ( ! empty( $player->email ) ) {
									?>
									</a>
									<?php
								}
								?>
							</td>
						</tr>
						<?php
					}
				}
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
				foreach ( $confirmed_entries as $key => $players ) {
					foreach ( $players as $player ) {
						?>
						<tr>
							<td><?php echo esc_html( $player->display_name ); ?></td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>
	</div>
</div>
