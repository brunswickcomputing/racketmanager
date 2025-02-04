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
					foreach ( $withdrawn_entries as $key => $players ) {
						foreach ( $players as $player ) {
							?>
							<tr>
								<td>
									<?php
									$rating         = $player->rating;
									$match_types    = Racketmanager_Util::get_match_types();
									$rating_display = '';
									foreach ( $match_types as $match_type => $description ) {
										$rating_display .= '[' . $match_type . ' - ' . $rating[ $match_type ] . ']';
									}
									echo esc_html( $player->display_name ) . ' ' . esc_html( $rating_display );
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
					foreach ( $pay_due_entries as $key => $players ) {
						foreach ( $players as $player ) {
							?>
							<tr>
								<td>
									<?php
									$rating         = $player->rating;
									$match_types    = Racketmanager_Util::get_match_types();
									$rating_display = '';
									foreach ( $match_types as $match_type => $description ) {
										$rating_display .= '[' . $match_type . ' - ' . $rating[ $match_type ] . ']';
									}
									echo esc_html( $player->display_name ) . ' ' . esc_html( $rating_display );
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
								if ( isset( $player->display_name ) ) {
									echo esc_html( $player->display_name );
								}
								if ( ! empty( $player->email ) ) {
									?>
									</a>
									<?php
								}
								?>
								<?php
								$rating         = $player->rating;
								$match_types    = Racketmanager_Util::get_match_types();
								$rating_display = '';
								foreach ( $match_types as $match_type => $description ) {
									$rating_display .= '[' . $match_type . ' - ' . $rating[ $match_type ] . ']';
								}
								echo ' ' . esc_html( $rating_display );
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
							<td>
								<?php
								$rating         = $player->rating;
								$match_types    = Racketmanager_Util::get_match_types();
								$rating_display = '';
								foreach ( $match_types as $match_type => $description ) {
									$rating_display .= '[' . $match_type . ' - ' . $rating[ $match_type ] . ']';
								}
								echo esc_html( $player->display_name ) . ' ' . esc_html( $rating_display );
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
</div>
