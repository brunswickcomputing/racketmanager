<?php
/**
 * Tournaments main page administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

$seasons      = $this->get_seasons( 'DESC' );
$competitions = $this->get_competitions( array( 'type' => 'tournament' ) );
?>
<div class="container">
	<h1><?php esc_html_e( 'Tournaments', 'racketmanager' ); ?></h1>
	<div class="container">
		<div class="row justify-content-between mb-3">
			<form id="tournaments-list-filter" method="get" action="" class="form-control">
				<input type="hidden" name="page" value="<?php echo esc_html( 'racketmanager-tournaments' ); ?>" />
				<div class="col-auto">
					<select class="form-select-1" size="1" name="season" id="season">
						<option value=""><?php esc_html_e( 'All seasons', 'racketmanager' ); ?></option>
						<?php
						foreach ( $seasons as $season ) {
							?>
							<option value="<?php echo esc_html( $season->name ); ?>" <?php echo esc_html( $season->name === $season_select ? 'selected' : '' ); ?>><?php echo esc_html( $season->name ); ?></option>
							<?php
						}
						?>
					</select>
					<select class="form-select-1" size="1" name="competition" id="competition">
						<option value=""><?php esc_html_e( 'All competitions', 'racketmanager' ); ?></option>
						<?php
						foreach ( $competitions as $competition ) {
							?>
							<option value="<?php echo esc_html( $competition->id ); ?>" <?php selected( $competition->id, $competition_select ); ?>><?php echo esc_html( $competition->name ); ?></option>
							<?php
						}
						?>
					</select>
					<button class="btn btn-primary"><?php esc_html_e( 'Filter', 'racketmanager' ); ?></button>
				</div>
			</form>
		</div>
	</div>
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
					<div class="col-1 col-md-auto check-column"><input type="checkbox" id="checkAll" onclick="Racketmanager.checkAll(document.getElementById('tournaments-filter'));" /></div>
					<div class="col-6 col-md-2"><?php esc_html_e( 'Name', 'racketmanager' ); ?></div>
					<div class="d-none d-md-block col-md-1"><?php esc_html_e( 'Season', 'racketmanager' ); ?></div>
					<div class="col-5 col-md-2"><?php esc_html_e( 'Venue', 'racketmanager' ); ?></div>
					<div class="d-none d-md-block col-md-1"><?php esc_html_e( 'Date', 'racketmanager' ); ?></div>
				</div>
				<?php
				if ( $tournaments ) {
					$class = '';
					?>
					<?php
					foreach ( $tournaments as $tournament ) {
						$class = ( 'alternate' === $class ) ? '' : 'alternate';
						?>
						<div class="row table-row <?php echo esc_html( $class ); ?>">
							<div class="col-1 col-md-auto check-column">
								<input type="checkbox" value="<?php echo esc_html( $tournament->id ); ?>" name="tournament[<?php echo esc_html( $tournament->id ); ?>]" />
							</div>
							<div class="col-6 col-md-2"><a href="admin.php?page=racketmanager-tournaments&amp;view=tournament&amp;tournament=<?php echo esc_html( $tournament->id ); ?>&amp;season=<?php echo esc_attr( $tournament->season ); ?> "><?php echo esc_html( $tournament->name ); ?></a></div>
							<div class="d-none d-md-block col-md-1"><?php echo esc_html( $tournament->season ); ?></div>
							<div class="col-5 col-md-2"><?php echo esc_html( $tournament->venue_name ); ?></div>
							<div class="d-none d-md-block col-md-1"><?php echo esc_html( $tournament->date ); ?></div>
						</div>
						<?php
					}
				}
				?>
			</form>
		</div>
	</div>
	<div class="mb-3">
		<!-- Add New Tournament -->
		<a href="admin.php?page=racketmanager-tournaments&amp;view=modify" class="btn btn-primary submit"><?php esc_html_e( 'Add Tournament', 'racketmanager' ); ?></a>
	</div>
</div>
