<?php
if ( empty($this->seasons) ) { ?>
	<p><?php _e('No seasons defined', 'racketmanager') ?>
<?php } else {
	$latestSeason = reset($this->seasons)->name;
	if ( empty($competition->seasons)  ) { ?>
		<p><?php _e('No pending seasons for competition', 'racketmanager') ?>
	<?php } else {
		$latestCompetitionSeason = end($competition->seasons)['name'];
		$teams = $competition->getConstitution( array('season' => $latestSeason, 'oldseason' => $latestCompetitionSeason));
		$constitutionAction = "update";
		if ( !$teams ) {
			$teams = $competition->buildConstitution( array('season' => $latestCompetitionSeason));
			$constitutionAction = "insert";
		}
		$leagues = $competition->getLeagues( array('competition' => $competition_id))
		?>
		<h2 class="header"><?php _e( 'Constitution', 'racketmanager' ) ?> - <?php echo $latestSeason ?></h2>
		<form id="teams-filter" method="post" action="">
			<div>
				<input type="submit" value="<?php _e('Save'); ?>" name="saveconstitution" id="saveconstitution" class="button-primary action" />
				<a class="button-secondary" href="admin.php?page=racketmanager&amp;subpage=teams&amp;league_id=<?php echo end($leagues)->id ?>&amp;season=<?php echo $latestSeason ?>&amp;view=constitution">Add Teams</a>
				<a class="button-secondary" onclick="Racketmanager.notifyEntryOpen(<?php echo $competition_id ?>);" ><?php _e( 'Notify entries open', 'racketmanager' ) ?></a>
				<span id="notifyMessage"></span>
			</div>
			<?php wp_nonce_field( 'constitution-bulk' ) ?>

			<input type="hidden" name="js-active" value="0" class="js-active" />
			<input type="hidden" name="constitutionAction" value="<?php echo $constitutionAction ?>" />
			<input type="hidden" name="competition_id" value="<?php echo $competition_id ?>" />
			<input type="hidden" name="latestSeason" id="latestSeason" value="<?php echo $latestSeason ?>" />
			<input type="hidden" name="latestCompetitionSeason" value="<?php echo $latestCompetitionSeason ?>" />
			<div class="tablenav">
				<!-- Bulk Actions -->
				<select name="action" size="1">
					<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
					<option value="delete"><?php _e('Delete')?></option>
				</select>
				<input type="submit" value="<?php _e('Apply'); ?>" name="doactionconstitution" id="doactionconstitution" class="button-secondary action" />
			</div>

			<table class="widefat" summary="" title="RacketManager">
				<thead>
					<tr>
						<th scope="col" class="check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('leagues-filter'));" /></th>
						<th scope="col"><?php _e( 'Previous League', 'racketmanager' ) ?></th>
						<th scope="col"><?php _e( 'New League', 'racketmanager' ) ?></th>
						<th scope="col"><?php _e( 'Team', 'racketmanager' ) ?></th>
						<th scope="col"><?php _e( 'Status', 'racketmanager' ) ?></th>
						<th scope="col" class="column-num"><?php _e( 'Previous Rank', 'racketmanager' ) ?></th>
						<th scope="col" class="column-num"><?php _e( 'Rank', 'racketmanager' ) ?></th>
						<th scope="col" class="column-num"><?php _e( 'Points', 'racketmanager' ) ?></th>
						<th scope="col" class="column-num"><?php _e( '+/- Points', 'racketmanager' ) ?></th>
						<th scope="col"><?php _e( 'Entered', 'racketmanager' ) ?></th>
					</tr>
				</thead>
				<tbody id="the-list" class="standings-table sortable">
					<?php
					if ( $teams ) {
						$class = '';
						foreach ( $teams AS $team ) {
							$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
							<tr class="<?php echo $class ?>">
								<th scope="row" class="check-column">
									<input type="checkbox" value="<?php echo $team->tableId ?>" name="table[<?php echo $team->tableId ?>]" />
									<input type="hidden" name="tableId[<?php echo $team->tableId ?>]" value="<?php echo $team->tableId ?>" />
								</th>
								<td>
									<?php echo $team->oldLeagueTitle ?>
									<input type="hidden" name="originalLeagueId[<?php echo $team->tableId ?>]" value=<?php echo $team->oldLeagueId ?> />
								</td>
								<td>
									<select size=1 name="leagueId[<?php echo $team->tableId ?>]">
										<?php foreach ($leagues as $i => $league) { ?>
											<option value="<?php echo $league->id ?>" <?php selected($league->id, $team->leagueId) ?>><?php echo $league->title ?></option>
										<?php } ?>
									</select>
								</td>
								<td>
									<?php echo $team->title ?>
									<input type="hidden" name="teamId[<?php echo $team->tableId ?>]" id="teamId[<?php echo $team->tableId ?>]" value=<?php echo $team->teamId ?> />
								</td>
								<td>
									<select size=1 name="status[<?php echo $team->tableId ?>]">
										<option value="" <?php selected('', $team->status) ?>></option>
										<option value="C" <?php selected('C', $team->status) ?>><?php _e( "Champions", "racketmanager") ?></option>
										<option value="P1" <?php selected('P1', $team->status) ?>><?php _e( "Promoted in first place", "racketmanager") ?></option>
										<option value="P2" <?php selected('P2', $team->status) ?>><?php _e( "Promoted in second place", "racketmanager") ?></option>
										<option value="P3" <?php selected('P3', $team->status) ?>><?php _e( "Promoted in third place", "racketmanager") ?></option>
										<option value="RB" <?php selected('RB', $team->status) ?>><?php _e( "Relegated in bottom place", "racketmanager") ?></option>
										<option value="RT" <?php selected('RT', $team->status) ?>><?php _e( "Relegated as team in division above", "racketmanager") ?></option>
										<option value="BT" <?php selected('BT', $team->status) ?>><?php _e( "Not relegated bottom team", "racketmanager") ?></option>
										<option value="NT" <?php selected('NT', $team->status) ?>><?php _e( "New team", "racketmanager") ?></option>
									</select>
								</td>
								<td class="column-num">
									<?php echo $team->oldRank ?>
									<input type="hidden" name="oldrank[<?php echo $team->tableId ?>]" id="oldrank[<?php echo $team->tableId ?>]" value=<?php echo $team->oldRank ?> />
								</td>
								<td class="column-num">
									<input type="text" size="2" class="rank-input" name="rank[<?php echo $team->tableId ?>]" id="rank[<?php echo $team->tableId ?>]" value=<?php echo $team->rank ?> />
								</td>
								<td class="column-num" name="points[<?php echo $team->tableId ?>]">
									<?php echo $team->points_plus + $team->add_points ?>
									<input type="hidden" name="points_plus[<?php echo $team->tableId ?>]" value=<?php echo $team->points_plus ?> />
								</td>
								<td class="column-num">
									<?php echo $team->add_points ?>
									<input type="hidden" name="add_points[<?php echo $team->tableId ?>]" value=<?php echo $team->add_points ?> />
								</td>
								<td>
									<select size=1 name="profile[<?php echo $team->tableId ?>]">
										<option value="0" <?php selected('0', $team->profile) ?>><?php _e( "Pending", "racketmanager") ?></option>
										<option value="1" <?php selected('1', $team->profile) ?>><?php _e( "Confirmed", "racketmanager") ?></option>
										<option value="2" <?php selected('2', $team->profile) ?>><?php _e( "New team", "racketmanager") ?></option>
										<option value="3" <?php selected('3', $team->profile) ?>><?php _e( "Withdrawn", "racketmanager") ?></option>
									</select>
								</td>
							</tr>
						<?php } ?>
					<?php } ?>
				</tbody>
			</table>
		</form>
	<?php
	if ( $latestCompetitionSeason >= $latestSeason ) { ?>
	<script>
	jQuery("#constitution").find("*").prop('disabled', true);
	jQuery("#constitution").addClass("disabledButton");
	</script>
<?php }
 }
}
?>
