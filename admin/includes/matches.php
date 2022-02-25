<?php if ( !empty($season['num_match_days']) ) { ?>
	<!-- Bulk Editing of Matches -->
	<form action="admin.php" method="get" style="float: right;">
		<input type="hidden" name="page" value="racketmanager" />
		<input type="hidden" name="subpage" value="match" />
		<input type="hidden" name="league_id" value="<?php echo $league->id ?>" />
		<input type="hidden" name="season" value="<?php echo $season['name'] ?>" />
		<input type="hidden" name="group" value="<?php echo $group ?>" />

		<select size="1" name="match_day">
			<?php for ($i = 1; $i <= $season['num_match_days']; $i++) { ?>
				<option value="<?php echo $i ?>"><?php printf(__( '%d. Match Day', 'racketmanager'), $i) ?></option>
			<?php } ?>
		</select>
		<input type="hidden" name="league-tab" value="0" class="jquery_ui_tab_index" />
		<input type="submit" value="<?php _e('Edit Matches', 'racketmanager'); ?>" class="button-secondary action" />
	</form>
<?php } ?>

<form id="matches-filter" action="admin.php?page=racketmanager&subpage=show-league&league_id=<?php echo $league->id ?>&season=<?php echo $season ?>" method="post">
	<?php wp_nonce_field( 'matches-bulk' ) ?>

	<input type="hidden" name="current_match_day" value="<?php echo $matchDay ?>" />
	<input type="hidden" name="league-tab" value="0" class="jquery_ui_tab_index" />
	<input type="hidden" name="group" value="<?php echo $group ?>" />
	<input type="hidden" name="season" value="<?php echo $season ?>" />

	<div class="tablenav">
		<!-- Bulk Actions -->
		<select name="delMatchOption" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
			<option value="delete"><?php _e('Delete', 'racketmanager')?></option>
		</select>
		<input type='submit' name="delmatches" id="delmatches" class="button-secondary action" value='<?php _e( 'Apply' ) ?>' />

		<?php if ( !empty($league->current_season['num_match_days']) ) { ?>
			<select size='1' name='match_day'>
				<?php $selected = ( isset($_POST['doaction-match_day']) && $_POST['match_day'] == -1 ) ? ' selected="selected"' : ''; ?>
				<option value="-1"<?php echo $selected ?>><?php _e( 'Show all Matches', 'racketmanager' ) ?></option>
				<?php for ($i = 1; $i <= $league->current_season['num_match_days']; $i++) { ?>
					<option value='<?php echo $i ?>'<?php selected($league->match_day, $i)  ?>><?php printf(__( '%d. Match Day', 'racketmanager'), $i) ?></option>
				<?php } ?>
			</select>
			<select size="1" name="team_id">
				<option value=""><?php _e( 'Choose Team', 'racketmanager' ) ?></option>
				<?php foreach ( $teams AS $team ) { ?>
					<?php $selected = (isset($_POST['team_id']) && intval($_POST['team_id']) == $team->id) ? ' selected="selected"' : ''; ?>
					<option value="<?php echo $team->id ?>"<?php echo $selected ?>><?php echo $team->title ?></option>
				<?php } ?>
			</select>
			<input type='submit' name="doaction-match_day" id="doaction-match_day" class="button-secondary action" value='<?php _e( 'Filter' ) ?>' />
		<?php } ?>
	</div>

	<table class="widefat" summary="" title="<?php _e( 'Match Plan','racketmanager' ) ?>" style="margin-bottom: 2em;">
		<thead>
			<tr>
				<th scope="col" class="check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('matches-filter'));" /></th>
				<th scope="col"><?php _e( 'ID', 'racketmanager' ) ?></th>
				<th scope="col"><?php _e( 'Date','racketmanager' ) ?></th>
				<?php if ( !empty($league->groups) && $league->mode == 'championship' ) { ?>
					<th scope="col" class="column-num"><?php _e( 'Group', 'racketmanager' ) ?></th>
				<?php } ?>
				<th scope="col" class="match-title"><?php _e( 'Match','racketmanager' ) ?></th>
				<th scope="col"><?php _e( 'Location','racketmanager' ) ?></th>
				<th scope="col"><?php _e( 'Begin','racketmanager' ) ?></th>
				<?php do_action( 'matchtable_header_'.(isset($league->sport) ? $league->sport : '' )); ?>
				<th scope="col" class="score"><?php _e( 'Score', 'racketmanager' ) ?></th>
			</tr>
		</thead>
		<tbody id="the-list-matches-<?php echo $group ?>" class="lm-form-table">
			<?php if ( $matches ) { $class = ''; ?>
			<?php foreach ( $matches AS $match ) { $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
				<tr class="<?php echo $class ?>">
					<th scope="row" class="check-column">
						<input type="hidden" name="matches[<?php echo $match->id ?>]" value="<?php echo $match->id ?>" />
						<input type="hidden" name="home_team[<?php echo $match->id ?>]" value="<?php echo $match->home_team ?>" />
						<input type="hidden" name="away_team[<?php echo $match->id ?>]" value="<?php echo $match->away_team ?>" />

						<input type="checkbox" value="<?php echo $match->id ?>" name="match[<?php echo $match->id ?>]" />
					</th>
					<td><?php echo $match->id ?></td>
					<td><?php echo ( substr($match->date, 0, 10) == '0000-00-00' ) ? 'N/A' : mysql2date($this->date_format, $match->date) ?></td>
					<?php if ( !empty($league->groups) && $league->mode == 'championship' ) : ?><td class="column-num"><?php echo $match->group ?></td><?php endif; ?>
					<td class="match-title"><a href="admin.php?page=racketmanager&amp;subpage=match&amp;league_id=<?php echo $league->id ?>&amp;edit=<?php echo $match->id ?>&amp;season=<?php echo $season ?><?php if(isset($group)) echo '&amp;group=' . $group; ?>"><?php echo $match->match_title ?></a></td>
					<td><?php echo ( empty($match->location) ) ? 'N/A' : $match->location ?></td>
					<td><?php echo ( '00:00' == $match->hour.":".$match->minutes ) ? 'N/A' : mysql2date($this->time_format, $match->date) ?></td>
					<?php do_action( 'matchtable_columns_'.(isset($league->sport) ? $league->sport : '' ), $match ) ?>
					<td class="score">
						<input class="points" type="text" size="2" style="text-align: center;" id="home_points[<?php echo $match->id ?>]" name="home_points[<?php echo $match->id ?>]" value="<?php echo (isset($match->home_points) ? $match->home_points : '') ?>" /> : <input class="points" type="text" size="2" style="text-align: center;" id="away_points[<?php echo $match->id ?>]" name="away_points[<?php echo $match->id ?>]" value="<?php echo (isset($match->away_points) ? $match->away_points : '') ?>" />
					</td>
				</tr>
			<?php } ?>
		<?php } ?>
	</tbody>
</table>

<?php do_action ( 'racketmanager_match_administration_descriptions' ) ?>

<div class="tablenav">
	<?php if ( isset($league->mode) && $league->mode != "championship" && $league->getPageLinks('matches') ) { ?>
		<div class="tablenav-pages"><?php echo $league->getPageLinks('matches') ?></div>
	<?php } ?>

	<?php if ( $matches ) { ?>
		<input type="hidden" name="league_id" value="<?php echo $league->id ?>" />
		<input_type="hidden" name="num_rubbers" value="<?php echo $league->num_rubbers ?>" />
		<input type="hidden" name="updateLeague" value="results" />
		<p style="float: left; margin: 0; padding: 0;"><input type="submit" name="updateResults" value="<?php _e( 'Update Results','racketmanager' ) ?>" class="button button-primary" /></p>
	<?php } ?>
</div>
</form>
<?php require('match-modal.php'); ?>
