<?php
/**
 * Event leagues administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

$leagues = $event->get_leagues();
?>
<div class>
	<div class="row gx-3 align-items-center mb-3">
		<form id="leagues-filter" method="post" action="" class="form-control">
			<?php wp_nonce_field( 'leagues-bulk', 'racketmanager_nonce' ); ?>
			<div class="row gx-3 mb-3 align-items-center">
				<!-- Bulk Actions -->
				<div class="col-auto">
					<select class="form-select" name="action">
						<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
					</select>
				</div>
				<div class="col-auto">
					<button name="doactionleague" id="doactionleague" class="btn btn-secondary"><?php esc_html_e( 'Apply', 'racketmanager' ); ?></button>
				</div>
			</div>
			<table class="table table-striped">
				<thead class="table-dark">
					<tr>
						<th class="check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('leagues-filter'));" /></th>
						<th class="">
							<?php
							if ( $event->is_championship ) {
								esc_html_e( 'Draw', 'racketmanager' );
							} else {
								esc_html_e( 'League', 'racketmanager' );
							}
							?>
						</th>
						<th class="">
							<?php
							if ( $event->is_championship ) {
								esc_html_e( 'Entries', 'racketmanager' );
							} else {
								esc_html_e( 'Teams', 'racketmanager' );
							}
							?>
						</th>
						<th class="">
							<?php
							if ( $event->is_championship ) {
								esc_html_e( 'Draw Size', 'racketmanager' );
							} else {
								esc_html_e( 'Matches', 'racketmanager' );
							}
							?>
						</th>
						<th></th>
					</tr>
				</thead>
				<?php
				if ( $leagues ) {
					?>
					<tbody>
						<?php
						foreach ( $leagues as $league ) {
							?>
							<tr>
								<td class="check-column">
									<input type="checkbox" value="<?php echo esc_html( $league->id ); ?>" name="league[<?php echo esc_html( $league->id ); ?>]" />
								</td>
								<td class="">
									<a href="admin.php?page=racketmanager-<?php echo esc_attr( $event->competition->type ); ?>s&amp;view=league&amp;league_id=<?php echo esc_html( $league->id ); ?>&amp;season=<?php echo esc_html( $season ); ?>"><?php echo esc_html( $league->title ); ?></a>
								</td>
								<td class="">
									<?php echo esc_html( $league->num_teams_total ); ?>
								</td>
								<td class="">
									<?php
									if ( $league->is_championship ) {
										echo esc_html( $league->championship->num_teams_first_round );
									} else {
										$league->set_num_matches( true );
										echo esc_html( $league->num_matches_total );
									}
									?>
								</td>
								<td class="">
									<a href="admin.php?page=racketmanager-<?php echo esc_attr( $league->event->competition->type ); ?>s&amp;view=event&amp;event_id=<?php echo esc_html( $event->id ); ?>&amp;editleague=<?php echo esc_html( $league->id ); ?>"><?php esc_html_e( 'Edit', 'racketmanager' ); ?></a>
								</td>
							</tr>
							<?php
						}
						?>
					</tbody>
					<?php
				}
				?>
			</table>
		</form>
	</div>
	<?php
	if ( empty( $tournament ) ) {
		?>
		<!-- Add New League -->
			<?php
			if ( ! $league_id ) {
				$form_action = __( 'Add League', 'racketmanager' );
			} else {
				$form_action = __( 'Update League', 'racketmanager' );
			}
			?>
		<h3><?php echo esc_html( $form_action ); ?></h3>
		<form action="" method="post" class="form-control">
			<?php wp_nonce_field( 'racketmanager_add-league', 'racketmanager_nonce' ); ?>
			<input type="hidden" name="event_id" value="<?php echo esc_html( $event_id ); ?>" />
			<input type="hidden" name="league_id" value="<?php echo esc_html( $league_id ); ?>" />
			<?php
			if ( $event->competition->is_league ) {
				if ( $league_id ) {
					?>
					<div class="form-floating mb-3">
						<input type="text" class="form-control" required="required" placeholder="<?php esc_html_e( 'Enter new league name', 'racketmanager' ); ?>"name="sequence" id="sequence" value="<?php echo esc_html( $league_edit->sequence ); ?>" size="30" />
						<label for="sequence"><?php esc_html_e( 'League sequence', 'racketmanager' ); ?></label>
					</div>
					<?php
				} else {
					?>
					<?php
				}
				?>
				<?php
			} else {
				?>
				<div class="form-floating mb-3">
					<input type="text" class="form-control" required="required" placeholder="<?php esc_html_e( 'Enter new league name', 'racketmanager' ); ?>"name="league_title" id="league_title" value="<?php echo esc_html( $league_title ); ?>" size="30" />
					<label for="league_title"><?php esc_html_e( 'League name', 'racketmanager' ); ?></label>
				</div>
				<?php
			}
			?>
			<div class="form-group mb-3">
				<input type="submit" name="addLeague" value="<?php echo esc_html( $form_action ); ?>" class="btn btn-primary" />
			</div>
		</form>
		<?php
	}
	?>
</div>
