<form id="seasons-filter" action="" method="post">
	<?php wp_nonce_field( 'seasons-bulk' ) ?>
	
	<input type="hidden" name="competition_id" value="<?php echo $competition_id ?>" />
	<div class="tablenav" style="margin-bottom: 0.1em;">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
			<option value="delete"><?php _e('Delete')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="doactionseason" id="doactionseason" class="button-secondary action" />
	</div>
	<table class="widefat">
		<thead>
			<tr>
				<th scope="col" class="check-column"><input type="checkbox" onclick="Leaguemanager.checkAll(document.getElementById('seaons-filter'));" /></th>
				<th scope="col"><?php _e( 'Season', 'leaguemanager' ) ?></th>
				<th scope="col"><?php _e( 'Match Days', 'leaguemanager' ) ?></th>
				<th scope="col"><?php _e( 'Actions', 'leaguemanager' ) ?></th>
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
				<td><a href="admin.php?page=leaguemanager&amp;subpage=show-competition&amp;competition_id=<?php echo $competition->id ?>&amp;editseason=<?php echo $key ?>"><?php _e( 'Edit', 'leaguemanager' ) ?></a></td>
			</tr>
	<?php } ?>
<?php } ?>
		</tbody>
	</table>
</form>

<form action="admin.php?page=leaguemanager&amp;subpage=show-competition&competition_id=<?php echo $competition_id ?>" method="post"  style="margin-top: 3em;">
	<?php wp_nonce_field( 'leaguemanager_add-season' ) ?>
	<input type="hidden" name="competition_id" value="<?php echo $competition_id ?>" />
	<table class="lm-form-table">
		<tr valign="top">
			<th scope="row"><label for="season"><?php _e( 'Season', 'leaguemanager' ) ?></label></th>
			<td>
				<input type="number" name="season" id="season" value="<?php echo $season_data['name'] ?>" size="4" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="num_match_days"><?php _e( 'Number of Match Days', 'leaguemanager' ) ?></label></th>
			<td>
				<input type="number" min="1" step="1" class="small-text" name="num_match_days" id="num_match_days" value="<?php echo $season_data['num_match_days'] ?>" size="2" />
			</td>
		</tr>

	</table>

	<input type="hidden" name="season_id" value="<?php echo $season_id ?>" />
	<p class="submit"><input type="submit" name="saveSeason" class="button button-primary" value="<?php if ( !$season_id ) _e( 'Add Season', 'leaguemanager' ); else _e( 'Update Season', 'leaguemanager' ); ?>" /></p>
</form>
