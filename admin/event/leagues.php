<?php
/**
 * Event leagues administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
<div class>

	<form id='leagues-filter' method='post' action='' class='form-control mb-3'>
		<?php wp_nonce_field( 'leagues-bulk', 'racketmanager_nonce' ); ?>

		<input type="hidden" name="event_id" value="<?php echo esc_html( $event_id ); ?>" />
		<?php
		if ( empty( $tournament ) ) {
			?>
			<div class="tablenav">
				<!-- Bulk Actions -->
				<select name="action" size="1">
					<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
				</select>
				<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doactionleague" id="doactionleague" class="btn btn-secondary action" />
			</div>
			<?php
		}
		?>

		<div class="container">
			<div class="row table-header">
				<div class="col-2 col-lg-1 check-column"><input type="checkbox" id="check-all-leagues" onclick="Racketmanager.checkAll(document.getElementById('leagues-filter'));" /></div>
				<div class="d-none d-lg-block col-1 column-num">ID</div>
				<div class="col-4">
					<?php
					if ( $event->is_championship ) {
						esc_html_e( 'Draw', 'racketmanager' );
					} else {
						esc_html_e( 'League', 'racketmanager' );
					}
					?>
				</div>
				<div class="col-3 col-lg-1 column-num">
					<?php
					if ( $event->is_championship ) {
						esc_html_e( 'Entries', 'racketmanager' );
					} else {
						esc_html_e( 'Teams', 'racketmanager' );
					}
					?>
				</div>
				<div class="col-3 col-lg-1 column-num">
					<?php
					if ( $event->is_championship ) {
						esc_html_e( 'Draw Size', 'racketmanager' );
					} else {
						esc_html_e( 'Matches', 'racketmanager' );
					}
					?>
				</div>
			</div>

			<?php
			$leagues = $event->get_leagues();
			if ( $leagues ) {
				$class = '';
				foreach ( $leagues as $league ) {
					$league = get_league( $league );
					$class  = ( 'alternate' === $class ) ? '' : 'alternate';
					?>
					<div class="row table-row <?php echo esc_html( $class ); ?>">
						<div class="col-2 col-lg-1 check-column"><input type="checkbox" value="<?php echo esc_html( $league->id ); ?>" name="league[<?php echo esc_html( $league->id ); ?>]" /></div>
						<div class="d-none d-lg-block col-1 column-num"><?php echo esc_html( $league->id ); ?></div>
						<div class="col-4"><a href="admin.php?page=racketmanager&amp;subpage=show-league&amp;league_id=<?php echo esc_html( $league->id ); ?>&amp;season=<?php echo esc_html( $season ); ?>"><?php echo esc_html( $league->title ); ?></a></div>
						<div class="col-3 col-lg-1 column-num">
							<?php echo esc_html( $league->num_teams_total ); ?>
						</div>
						<div class="col-3 col-lg-1 column-num">
							<?php
							if ( $league->is_championship ) {
								echo esc_html( $league->championship->num_teams_first_round );
							} else {
								$league->set_num_matches( true );
								echo esc_html( $league->num_matches_total );
							}
							?>
						</div>
						<div class="d-none d-lg-block col-auto"><a href="admin.php?page=racketmanager&amp;subpage=show-event&amp;event_id=<?php echo esc_html( $event->id ); ?>&amp;editleague=<?php echo esc_html( $league->id ); ?>"><?php esc_html_e( 'Edit', 'racketmanager' ); ?></a></div>
					</div>
				<?php } ?>
			<?php } ?>
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
		<form action="admin.php?page=racketmanager&subpage=show-event&event_id=<?php echo esc_html( $event_id ); ?>" method="post" class="form-control">
			<?php wp_nonce_field( 'racketmanager_add-league', 'racketmanager_nonce' ); ?>
			<input type="hidden" name="event_id" value="<?php echo esc_html( $event_id ); ?>" />
			<input type="hidden" name="league_id" value="<?php echo esc_html( $league_id ); ?>" />
			<div class="form-floating mb-3">
				<input type="text" class="form-control" required="required" placeholder="<?php esc_html_e( 'Enter new league name', 'racketmanager' ); ?>"name="league_title" id="league_title" value="<?php echo esc_html( $league_title ); ?>" size="30" />
				<label for="league_title"><?php esc_html_e( 'League name', 'racketmanager' ); ?></label>
			</div>
			<div class="form-group mb-3">
				<input type="submit" name="addLeague" value="<?php echo esc_html( $form_action ); ?>" class="btn btn-primary" />
			</div>
		</form>
		<?php
	}
	?>
</div>
