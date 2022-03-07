<form id="seasons-filter" action="" method="post">
	<?php wp_nonce_field( 'seasons-bulk' ) ?>

	<input type="hidden" name="competition_id" value="<?php echo $competition_id ?>" />
	<div class="tablenav">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
			<option value="delete"><?php _e('Delete')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="doactionseason" id="doactionseason" class="btn btn-secondary action" />
	</div>
	<table class="widefat">
		<thead>
			<tr>
				<th scope="col" class="check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('seaons-filter'));" /></th>
				<th scope="col"><?php _e( 'Season', 'racketmanager' ) ?></th>
				<th scope="col"><?php _e( 'Match Days', 'racketmanager' ) ?></th>
				<th scope="col"><?php _e( 'Actions', 'racketmanager' ) ?></th>
			</tr>
		</thead>
		<tbody id="the-list">
			<?php if ( !empty($competition->seasons) ) { $class = ''; ?>
			<?php foreach( (array)$competition->seasons AS $key => $season ) { ?>
				<?php $class = ( 'alternate' == $class ) ? '' : 'alternate' ?>
				<tr class="<?php echo $class ?>">
					<th scope="row" class="check-column"><input type="checkbox" value="<?php echo $key ?>" name="del_season[<?php echo $key ?>]" /></th>
					<td><?php echo $season['name'] ?></td>
					<td><?php echo $season['num_match_days'] ?></td>
					<td><a href="admin.php?page=racketmanager&amp;subpage=show-competition&amp;competition_id=<?php echo $competition->id ?>&amp;editseason=<?php echo $key ?>"><?php _e( 'Edit', 'racketmanager' ) ?></a></td>
				</tr>
			<?php } ?>
		<?php } ?>
	</tbody>
</table>
</form>

<h3><?php _e( 'Add Season', 'racketmanager' ) ?></h3>
<form action="" method="post"  class="form-control">
	<?php wp_nonce_field( 'racketmanager_add-season' ) ?>
	<input type="hidden" name="competition_id" value="<?php echo $competition_id ?>" />
	<table class="lm-form-table">
		<div class="form-group">
			<div class="form-label">
				<label for="season"><?php _e( 'Season', 'racketmanager' ) ?></label>
			</div>
			<div class="form-input">
				<?php if ( $season_id ) { ?>
					<input type="number" name="season" id="season" value="<?php echo $season_data['name'] ?>" size="4" />
				<?php } else { ?>
					<select size="1" name="season" id="season" >
						<option><?php _e( 'Select season' , 'racketmanager') ?></option>
						<?php $seasons = $racketmanager->getSeasons( "DESC" );
						foreach ( $seasons AS $season ) { ?>
							<option value="<?php echo $season->name ?>"><?php echo $season->name ?></option>
						<?php } ?>
					</select>
				<?php } ?>
			</div>
		</div>
		<div class="form-group">
			<div class="form-label">
				<label for="num_match_days"><?php if ($competition->is_championship) { _e( 'Number of teams', 'racketmanager' ); } else { _e( 'Number of Match Days', 'racketmanager' ); } ?></label>
			</div>
			<div class="form-input">
				<input type="number" min="1" step="1" class="small-text" name="num_match_days" id="num_match_days" value="<?php echo $season_data['num_match_days'] ?>" size="2" />
			</div>
		</div>
	</table>

	<input type="hidden" name="season_id" value="<?php echo $season_id ?>" />
	<input type="submit" name="saveSeason" class="btn btn-primary" value="<?php if ( !$season_id ) _e( 'Add Season', 'racketmanager' ); else _e( 'Update Season', 'racketmanager' ); ?>" />
</form>
