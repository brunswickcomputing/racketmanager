<form id="teams-filter" action="" method="post" name="standings">
	<input type="hidden" name="js-active" value="0" class="js-active" />
	<input type="hidden" name="league-tab" value="0" class="jquery_ui_tab_index" />

	<?php wp_nonce_field( 'teams-bulk' ) ?>
	<?php $league_id = intval($_GET['league_id']); ?>
	<?php $sport = (isset($league->sport) ? $league->sport : '' ); ?>

	<div class="tablenav">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
			<option value="delete"><?php _e('Delete')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="doaction" id="doaction" class="button-secondary action" />
	</div>

	<table id="standings" class="widefat" summary="" title="<?php _e( 'Table', 'racketmanager' ) ?>">
		<thead>
			<tr>
				<th scope="col" class="check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('teams-filter'));" /></th>
				<th class="column-num"><?php _e( 'Rank', 'racketmanager' ) ?></th>
				<?php if ( $league->mode != 'championship' ) { ?><th class="column-num">&#160;</th><?php } ?>
				<th><?php _e( 'Club', 'racketmanager' ) ?></th>
				<?php if ( $league->mode != 'championship' ) { ?>
					<?php if ( !empty($league->groups) && $league->mode == 'championship' ) { ?><th class="column-num"><?php _e( 'Group', 'racketmanager' ) ?></th><?php } ?>
					<?php if ( isset($league->standings['pld']) && 1 == $league->standings['pld'] ) { ?><th class="column-num"><?php _e( 'Pld', 'racketmanager' ) ?></th><?php } ?>
					<?php if ( isset($league->standings['won']) && 1 == $league->standings['won'] ) { ?><th class="column-num"><?php _e( 'W','racketmanager' ) ?></th><?php } ?>
					<?php if ( isset($league->standings['tie']) && 1 == $league->standings['tie'] ) { ?><th class="column-num"><?php _e( 'T','racketmanager' ) ?></th><?php } ?>
					<?php if ( isset($league->standings['lost']) && 1 == $league->standings['lost'] ) { ?><th class="column-num"><?php _e( 'L','racketmanager' ) ?></th><?php } ?>
					<?php if ( isset($league->standings['winPercent']) && 1 == $league->standings['winPercent'] ) { ?><th class="column-num"><?php _e( 'PCT','racketmanager' ) ?></th><?php } ?>
					<?php $league->displayStandingsHeader(); ?>
					<th class="column-num"><?php _e( 'Pts', 'racketmanager' ) ?></th>
					<th class="column-num"><?php _e( '+/- Points', 'racketmanager' ) ?></th>
					<th class="column-num"><?php _e( 'ID', 'racketmanager' ) ?></th>
				<?php } ?>
			</tr>
		</thead>
		<tbody id="the-list-standings" class="lm-form-table standings-table <?php if ($league->team_ranking == 'manual') echo 'sortable' ?>">
			<?php $class = ''; ?>
			<?php foreach( $teams AS $i => $team ) {
				$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
				<tr class="<?php echo $class ?>" id="team_<?php echo $team->id ?>">
					<th scope="row" class="check-column"><input type="hidden" name="team_id[<?php echo $team->id ?>]" value="<?php echo $team->id ?>" /><input type="checkbox" value="<?php echo $team->id ?>" name="team[<?php echo $team->id ?>]" /></th>
					<td class="column-num">
						<?php if ($league->team_ranking == 'manual') { ?>
							<input type="text" name="rank[<?php echo $team->id ?>]" size="2" id="rank_<?php echo $team->id ?>" class="rank-input" value="<?php echo $team->rank ?>" /><input type="hidden" name="table_id[<?php echo $team->table_id ?>]" value="<?php echo $team->table_id ?>" />
						<?php } else { ?>
							<?php echo $i+1;//$team->rank ?>
						<?php } ?>
					</td>
					<?php if ( $league->mode != 'championship' ) { ?><td class="column-num"><?php echo $team->status ?></td><?php } ?>
					<td><a href="admin.php?page=racketmanager&amp;subpage=team&amp;league_id=<?php echo $league_id ?>&amp;edit=<?php echo $team->id; ?>"><?php if ($team->home == 1) echo "<strong>".$team->title."</strong>"; else echo $team->title; ?></a></td>
					<?php if ( !empty($league->groups) && $league->mode == 'championship' ) { ?><td class="column-num"><?php echo $team->group ?></td><?php } ?>
					<?php if ( $league->mode != 'championship' ) { ?>
						<?php if ( $league->point_rule != 'manual' ) { ?>
							<?php if ( isset($league->standings['pld']) && 1 == $league->standings['pld'] ) { ?><td class="column-num"><?php echo $team->done_matches ?></td><?php } ?>
							<?php if ( isset($league->standings['won']) && 1 == $league->standings['won'] ) { ?><td class="column-num"><?php echo $team->won_matches ?></td><?php } ?>
							<?php if ( isset($league->standings['tie']) && 1 == $league->standings['tie'] ) { ?><td class="column-num"><?php echo $team->draw_matches ?></td><?php } ?>
							<?php if ( isset($league->standings['lost']) && 1 == $league->standings['lost'] ) { ?><td class="column-num"><?php echo $team->lost_matches ?></td><?php } ?>
							<?php if ( isset($league->standings['winPercent']) && 1 == $league->standings['winPercent'] ) { ?><td class="column-num"><?php echo $team->winPercent ?></td><?php } ?>
						<?php } else { ?>
							<td class="column-num">
								<?php if ( 1 == $league->standings['pld'] ) { ?><input type="text" size="2" name="num_done_matches[<?php echo $team->id ?>]" value="<?php echo $team->done_matches  ?>" />
							<?php } else { ?><input type="hidden" name="num_done_matches[<?php echo $team->id ?>]" value="0" />
						<?php } ?>
					</td>
					<td class="column-num">
						<?php if ( 1 == $league->standings['won'] ) { ?><input type="text" size="2" name="num_won_matches[<?php echo $team->id ?>]" value="<?php echo $team->won_matches  ?>" />
					<?php } else { ?><input type="hidden" name="num_won_matches[<?php echo $team->id ?>]" value="0" />
				<?php } ?>
			</td>
			<td class="column-num">
				<?php if ( 1 == $league->standings['tie'] ) { ?><input type="text" size="2" name="num_draw_matches[<?php echo $team->id ?>]" value="<?php echo $team->draw_matches ?>" />
			<?php } else { ?><input type="hidden" name="num_draw_matches[<?php echo $team->id ?>]" value="0" />
		<?php } ?>
	</td>
	<td class="column-num">
		<?php if ( 1 == $league->standings['lost'] ) { ?><input type="text" size="2" name="num_lost_matches[<?php echo $team->id ?>]" value="<?php echo $team->lost_matches ?>" />
	<?php } else { ?><input type="hidden" name="num_lost_matches[<?php echo $team->id ?>]" value="0" />
<?php } ?>
</td>

<?php } ?>
<?php do_action( 'racketmanager_standings_columns_'.$league->sport, $team, $league->point_rule ) ?>
<?php $league->displayStandingsColumns($team, $league->point_rule); ?>
<td class="column-num">
	<?php if ( $league->point_rule != 'manual' ) { ?><?php printf($league->point_format, $team->points_plus, $team->points_minus) ?>
	<?php } else { ?><input type="text" size="2" name="points_plus[<?php echo $team->id ?>]" value="<?php echo $team->points_plus ?>" /> : <input type="text" size="2" name="points_minus[<?php echo $team->id ?>]" value="<?php echo $team->points_minus ?>" />
	<?php } ?>
</td>
<td class="column-num">
	<input type="text" size="3" style="text-align: center;" id="add_points_<?php echo $team->id ?>" name="add_points[<?php echo $team->id ?>]" value="<?php echo $team->add_points ?>" onblur="Racketmanager.saveAddPoints(this.value, <?php echo $team->id ?>, <?php echo $league_id ?> )" /><span class="loading" id="loading_<?php echo $team->id ?>"></span>
</td>
<td class="column-num"><?php echo $team->id ?></td>
<?php } ?>
</tr>
<?php } ?>
</tbody>
</table>

<?php if ( (isset($league->team_ranking) && ($league->team_ranking == 'manual')) && ($league->mode != 'championship') ) { ?>
	<script type='text/javascript'>
	</script>
<?php } ?>

<?php if ( (isset($league->point_rule) && ($league->point_rule == 'manual')) ) { ?>
	<input type="hidden" name="updateLeague" value="teams_manual" />
	<p class="submit" style="float: right; margin: 0 0 1em 0;"><input type="submit" value="<?php _e( 'Save Standings', 'racketmanager' ) ?>" class="button button-primary" /></p>
<?php } ?>

<?php if ( (isset($league->team_ranking) && ($league->team_ranking == 'manual')) ) { ?>
	<p class="submit"><input type="submit" name="saveRanking" value="<?php _e( 'Save Ranking', 'racketmanager' ) ?>" class="button button-primary" /></p>
	<p class="submit"><input type="submit" name="randomRanking" value="<?php _e( 'Random Ranking', 'racketmanager' ) ?>" class="button button-primary" /></p>
<?php } ?>

<?php if ( (isset($league->team_ranking) && ($league->team_ranking !== 'manual')) ) { ?>
	<p class="submit"><input type="submit" name="updateRanking" value="<?php _e( 'Update Ranking', 'racketmanager' ) ?>" class="button button-primary" /></p>
<?php } ?>
</form>
