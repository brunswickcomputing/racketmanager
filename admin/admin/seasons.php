<!-- Add Season -->
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

	<table class="widefat" summary="" title="RacketManager Seasons">
		<thead>
			<tr>
				<th scope="col" class="check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('seasons-filter'));" /></th>
				<th scope="col" class="column-num">ID</th>
				<th scope="col"><?php _e( 'Name', 'racketmanager' ) ?></th>
				<th scope="col"><?php _e( 'Action', 'racketmanager' ) ?></th>
			</tr>
			<tbody id="the-list">
				<?php if ( $seasons = $racketmanager->getSeasons() ) { $class = ''; ?>
				<?php foreach ( $seasons AS $season ) { ?>
					<?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
					<tr class="<?php echo $class ?>">
						<th scope="row" class="check-column">
							<input type="checkbox" value="<?php echo $season->id ?>" name="season[<?php echo $season->id ?>]" />
						</th>
						<td class="column-num"><?php echo $season->id ?></td>
						<td><?php echo $season->name ?></td>
						<td><a class="btn btn-secondary" href="admin.php?page=racketmanager-admin&amp;subpage=competitions&amp;season=<?php echo $season->name ?>">Add Competitions</a></td>
					</tr>
				<?php } ?>
			<?php } ?>
		</tbody>
	</table>
</form>
<h2><?php _e( 'Add Season', 'racketmanager' ) ?></h2>
<!-- Add New Season -->
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
