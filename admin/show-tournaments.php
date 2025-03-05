<?php
/**
 * Tournaments main page administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

$seasons      = $this->get_seasons( 'DESC' );
$competitions = $this->get_competitions( array( 'type' => 'tournament' ) );
$age_groups   = Racketmanager_Util::get_age_groups();
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
					<select class="form-select-1" name="competition" id="competition">
						<option value=""><?php esc_html_e( 'All competitions', 'racketmanager' ); ?></option>
						<?php
						foreach ( $competitions as $competition ) {
							?>
							<option value="<?php echo esc_html( $competition->id ); ?>" <?php selected( $competition->id, $competition_select ); ?>><?php echo esc_html( $competition->name ); ?></option>
							<?php
						}
						?>
					</select>
					<select class="form-select-1" name="age_group" id="age_group">
						<option value=""><?php esc_html_e( 'All age groups', 'racketmanager' ); ?></option>
						<?php
						foreach ( $age_groups as $age_group => $age_group_desc ) {
							?>
							<option value="<?php echo esc_attr( $age_group ); ?>" <?php selected( $age_group, $age_group_select ); ?>><?php echo esc_html( $age_group_desc ); ?></option>
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
			<div class="mb-2">
				<!-- Bulk Actions -->
				<select name="action" size="1">
					<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
				</select>
				<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doTournamentDel" id="doTournamentDel" class="btn btn-secondary action" />
			</div>
			<table class="table table-striped">
				<thead class="table-dark">
					<tr>
						<th class="check-column"><input type="checkbox" id="checkAll" onclick="Racketmanager.checkAll(document.getElementById('tournaments-filter'));" /></th>
						<th><?php esc_html_e( 'Name', 'racketmanager' ); ?></th>
						<th class="d-none d-md-table-cell"><?php esc_html_e( 'Season', 'racketmanager' ); ?></th>
						<th><?php esc_html_e( 'Venue', 'racketmanager' ); ?></th>
						<th class="d-none d-md-table-cell"><?php esc_html_e( 'Date', 'racketmanager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $tournaments as $tournament ) {
						?>
						<tr>
							<td class="check-column"><input type="checkbox" value="<?php echo esc_html( $tournament->id ); ?>" name="tournament[<?php echo esc_html( $tournament->id ); ?>]" /></td>
							<td><a href="admin.php?page=racketmanager-tournaments&amp;view=tournament&amp;tournament=<?php echo esc_html( $tournament->id ); ?>&amp;season=<?php echo esc_attr( $tournament->season ); ?> "><?php echo esc_html( $tournament->name ); ?></a></td>
							<td class="d-none d-md-table-cell"><?php echo esc_html( $tournament->season ); ?></td>
							<td><?php echo esc_html( $tournament->venue_name ); ?></td>
							<td class="d-none d-md-table-cell"><?php echo esc_html( $tournament->date ); ?></td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</form>
	</div>
	<div class="mb-3">
		<!-- Add New Tournament -->
		<a href="admin.php?page=racketmanager-tournaments&amp;view=modify" class="btn btn-primary submit"><?php esc_html_e( 'Add Tournament', 'racketmanager' ); ?></a>
	</div>
</div>
