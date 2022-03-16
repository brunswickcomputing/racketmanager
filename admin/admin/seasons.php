<!-- View Seasons -->
<form id="seasons-filter" method="post" action="">
	<?php wp_nonce_field( 'seasons-bulk' ) ?>

	<div class="tablenav">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
			<option value="delete"><?php _e('Delete')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="doSeasonDel" id="doSeasonDel" class="btn btn-secondary action" />
	</div>

	<div class="container">
		<div class="row table-header">
			<div class="col-12 col-md-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('seasons-filter'));" /></div>
			<div class="col-12 col-md-1 column-num">ID</div>
			<div class="col-12 col-md-2"><?php _e( 'Name', 'racketmanager' ) ?></div>
			<div class="col-12 col-md-3"><?php _e( 'Action', 'racketmanager' ) ?></div>
		</div>
		<?php if ( $seasons = $racketmanager->getSeasons() ) {
			$class = '';
			foreach ( $seasons AS $season ) { ?>
				<?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
				<div class="row table-row <?php echo $class ?>">
					<div class="col-12 col-md-1 check-column">
						<input type="checkbox" value="<?php echo $season->id ?>" name="season[<?php echo $season->id ?>]" />
					</div>
					<div class="col-12 col-md-1 column-num"><?php echo $season->id ?></div>
					<div class="col-12 col-md-2"><?php echo $season->name ?></div>
					<div class="col-12 col-md-3"><a class="btn btn-secondary" href="admin.php?page=racketmanager-admin&amp;subpage=competitions&amp;season=<?php echo $season->name ?>">Add Competitions</a></div>
				</div>
			<?php } ?>
		<?php } ?>
	</div>
</form>
<!-- Add New Season -->
<div clas="container">
	<h2><?php _e( 'Add Season', 'racketmanager' ) ?></h2>
	<form action="" method="post" class="form-control">
		<?php wp_nonce_field( 'racketmanager_add-season' ) ?>
		<div class="form-group">
			<label for="seasonName"><?php _e( 'Name', 'racketmanager' ) ?></label>
			<div class="input">
				<input required="required" placeholder="<?php _e( 'Enter season name', 'racketmanager') ?>" type="text" name="seasonName" id="seasonName" value=""  />
			</div>
		</div>
		<input type="hidden" name="addSeason" value="season" />
		<input type="submit" name="addSeason" value="<?php _e( 'Add Season','racketmanager' ) ?>" class="btn btn-primary" />

	</form>
</div>
