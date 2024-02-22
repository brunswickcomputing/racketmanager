<?php
/**
 * Season administration panel
 *
 * @package Racketmanager/Admin
 */

namespace Racketmanager;

?>
<!-- View Seasons -->
<div class="mb-3">
<form id="seasons-filter" method="post" action="" class="form-control">
	<?php wp_nonce_field( 'seasons-bulk', 'racketmanager_nonce' ); ?>

	<div class="tablenav">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
			<option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
		</select>
		<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doSeasonDel" id="doSeasonDel" class="btn btn-secondary action" />
	</div>

	<div class="container">
		<div class="row table-header">
			<div class="col-12 col-md-1 check-column"><input type="checkbox" id="check-all-seasons" onclick="Racketmanager.checkAll(document.getElementById('seasons-filter'));" /></div>
			<div class="col-12 col-md-1 column-num">ID</div>
			<div class="col-12 col-md-2"><?php esc_html_e( 'Name', 'racketmanager' ); ?></div>
			<div class="col-12 col-md-3"><?php esc_html_e( 'Action', 'racketmanager' ); ?></div>
		</div>
		<?php
		$seasons = $this->get_seasons();
		if ( $seasons ) {
			$class = '';
			foreach ( $seasons as $season ) {
				?>
				<?php $class = ( 'alternate' === $class ) ? '' : 'alternate'; ?>
				<div class="row table-row <?php echo esc_html( $class ); ?>">
					<div class="col-12 col-md-1 check-column">
						<input type="checkbox" value="<?php echo esc_html( $season->id ); ?>" name="season[<?php echo esc_html( $season->id ); ?>]" />
					</div>
					<div class="col-12 col-md-1 column-num"><?php echo esc_html( $season->id ); ?></div>
					<div class="col-12 col-md-2"><?php echo esc_html( $season->name ); ?></div>
					<div class="col-12 col-md-3"><a class="btn btn-secondary" href="admin.php?page=racketmanager-admin&amp;view=competitions&amp;season=<?php echo esc_html( $season->name ); ?>">Add Competitions</a></div>
				</div>
			<?php } ?>
		<?php } ?>
	</div>
</form>
</div>
<!-- Add New Season -->
<div clas="container">
	<h2><?php esc_html_e( 'Add Season', 'racketmanager' ); ?></h2>
	<form action="" method="post" class="form-control">
		<?php wp_nonce_field( 'racketmanager_add-season' ); ?>
		<div class="form-floating mb-3">
			<input class="form-control" required="required" placeholder="<?php esc_html_e( 'Enter season name', 'racketmanager' ); ?>" type="text" name="seasonName" id="seasonName" value=""  />
			<label for="seasonName"><?php esc_html_e( 'Name', 'racketmanager' ); ?></label>
		</div>
		<input type="hidden" name="addSeason" value="season" />
		<input type="submit" name="addSeason" value="<?php esc_html_e( 'Add Season', 'racketmanager' ); ?>" class="btn btn-primary" />

	</form>
</div>
