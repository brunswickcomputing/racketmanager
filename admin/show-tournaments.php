<?php
/**
 * Tournaments main page administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
<div class="container">
	<h1><?php esc_html_e( 'Tournaments', 'racketmanager' ); ?></h1>
	<div class="alert_rm" id="alert-tournaments" style="display:none;">
		<div class="alert__body">
			<div class="alert__body-inner" id="alert-tournaments-response">
			</div>
		</div>
	</div>
	<div class="form-control mb-3">
		<form id="tournaments-filter" method="post" action="">
			<?php wp_nonce_field( 'tournaments-bulk' ); ?>
			<div class="tablenav">
				<!-- Bulk Actions -->
				<select name="action" size="1">
					<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
				</select>
				<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doTournamentDel" id="doTournamentDel" class="btn btn-secondary action" />
			</div>
			<div class="container">
				<div class="row table-header">
					<div class="col-12 col-md-auto check-column"><input type="checkbox" id="checkAll" onclick="Racketmanager.checkAll(document.getElementById('tournaments-filter'));" /></div>
					<div class="col-12 col-md-2"><?php esc_html_e( 'Name', 'racketmanager' ); ?></div>
					<div class="col-12 col-md-1"><?php esc_html_e( 'Season', 'racketmanager' ); ?></div>
					<div class="col-12 col-md-2"><?php esc_html_e( 'Venue', 'racketmanager' ); ?></div>
					<div class="col-12 col-md-1"><?php esc_html_e( 'Date', 'racketmanager' ); ?></div>
				</div>
				<?php
				$tournaments = $this->get_tournaments(
					array(
						'orderby' => array(
							'date' => 'desc',
							'name' => 'asc',
						),
					)
				);
				if ( $tournaments ) {
					$class = '';
					?>
					<?php
					foreach ( $tournaments as $tournament ) {
						$class = ( 'alternate' === $class ) ? '' : 'alternate';
						?>
						<div class="row table-row <?php echo esc_html( $class ); ?>">
							<div class="col-12 col-md-auto check-column">
								<input type="checkbox" value="<?php echo esc_html( $tournament->id ); ?>" name="tournament[<?php echo esc_html( $tournament->id ); ?>]" />
							</div>
							<div class="col-12 col-md-2"><a href="admin.php?page=racketmanager-tournaments&amp;view=tournament&amp;tournament_id=<?php echo esc_html( $tournament->id ); ?> "><?php echo esc_html( $tournament->name ); ?></a></div>
							<div class="col-12 col-md-1"><?php echo esc_html( $tournament->season ); ?></div>
							<div class="col-12 col-md-2"><?php echo esc_html( $tournament->venue_name ); ?></div>
							<div class="col-12 col-md-1"><?php echo esc_html( $tournament->date ); ?></div>
							<div class="col-12 col-md-auto"><a href="admin.php?page=racketmanager&amp;subpage=show-competition&amp;season=<?php echo esc_html( $tournament->season ); ?>&amp;competition_id=<?php echo esc_html( $tournament->competition_id ); ?>&amp;tournament=<?php echo esc_html( $tournament->id ); ?>" class="btn btn-secondary"><?php esc_html_e( 'Events', 'racketmanager' ); ?></a></div>
							<?php if ( $tournament->open ) { ?>
								<div class="col-12 col-md-auto"><button class="btn btn-secondary" onclick="Racketmanager.notifyTournamentEntryOpen(event, '<?php echo esc_html( $tournament->id ); ?>');"><?php esc_html_e( 'Notify open', 'racketmanager' ); ?></button></div>
								<div class="col-12 col-md-auto"><span id="notifyMessage-<?php echo esc_html( $tournament->id ); ?>"></span></div>
							<?php } elseif ( $tournament->active ) { ?>
								<div class="col-12 col-md-auto">
									<a href="admin.php?page=racketmanager-tournaments&amp;view=tournament-plan&amp;tournament=<?php echo esc_html( $tournament->id ); ?>" class="btn btn-secondary"><?php esc_html_e( 'Plan Finals', 'racketmanager' ); ?></a>
								</div>
							<?php } ?>
						</div>
					<?php } ?>
				<?php } ?>
			</form>
		</div>
	</div>
	<div class="mb-3">
		<!-- Add New Tournament -->
		<a href="admin.php?page=racketmanager_tournaments&amp;view=tournament" class="btn btn-primary submit"><?php esc_html_e( 'Add Tournament', 'racketmanager' ); ?></a>
	</div>
</div>
