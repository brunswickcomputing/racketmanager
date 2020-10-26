				<form id="leagues-filter" method="post" action="">
					<?php wp_nonce_field( 'leagues-bulk' ) ?>

					<input type="hidden" name="competition_id" value="<?php echo $competition_id ?>" />
                    <div class="tablenav">
						<!-- Bulk Actions -->
						<select name="action" size="1">
							<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
							<option value="delete"><?php _e('Delete')?></option>
						</select>
						<input type="submit" value="<?php _e('Apply'); ?>" name="doactionleague" id="doactionleague" class="button-secondary action" />
					</div>

					<table class="widefat" summary="" title="LeagueManager">
						<thead>
						<tr>
							<th scope="col" class="check-column"><input type="checkbox" onclick="Leaguemanager.checkAll(document.getElementById('leagues-filter'));" /></th>
							<th scope="col" class="num">ID</th>
							<th scope="col"><?php _e( 'League', 'leaguemanager' ) ?></th>
							<th scope="col" class="num"><?php _e( 'Teams', 'leaguemanager' ) ?></th>
							<th scope="col" class="num"><?php _e( 'Matches', 'leaguemanager' ) ?></th>
							<th scope="col"><?php _e( 'Actions', 'leaguemanager' ) ?></th>
						</tr>
						<tbody id="the-list">
<?php
    if ( $leagues = $competition->getLeagues( array('competition' => $competition_id)) ) {
        $class = '';
        foreach ( $leagues AS $league ) {
            $league = get_league($league);
            $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
							<tr class="<?php echo $class ?>">
								<th scope="row" class="check-column"><input type="checkbox" value="<?php echo $league->id ?>" name="league[<?php echo $league->id ?>]" /></th>
								<td class="num"><?php echo $league->id ?></td>
								<td><a href="admin.php?page=leaguemanager&amp;subpage=show-league&amp;league_id=<?php echo $league->id ?>"><?php echo $league->title ?></a></td>
								<td class="num"><?php echo $league->num_teams_total ?></td>
								<td class="num"><?php echo $league->num_matches_total ?></td>
								<td><a href="admin.php?page=leaguemanager&amp;subpage=show-competition&amp;competition_id=<?php echo $competition->id ?>&amp;editleague=<?php echo $league->id ?>"><?php _e( 'Edit', 'leaguemanager' ) ?></a></td>
							</tr>
	<?php } ?>
<?php } ?>
						</tbody>
					</table>
				</form>

<!-- Add New League -->
<form action="admin.php?page=leaguemanager&amp;subpage=show-competition&competition_id=<?php echo $competition_id ?>" method="post" style="margin-top: 3em;">
	<?php wp_nonce_field( 'leaguemanager_add-league' ) ?>
	<input type="hidden" name="competition_id" value="<?php echo $competition_id ?>" />
	<input type="hidden" name="league_id" value="<?php echo $league_id ?>" />
	<table class="lm-form-table">
		<tr valign="top">
			<th scope="row"><label for="league_title"><?php _e( 'League', 'leaguemanager' ) ?></label></th>
			<td><input type="text" required="required" placeholder="<?php _e( 'Enter new league name', 'leaguemanager') ?>"name="league_title" id="league_title" value="<?php echo $league_title ?>" size="30" /></td>
		</tr>
	</table>
	<p class="submit"><input type="submit" name="addLeague" value="<?php if ( !$league_id ) _e( 'Add League', 'leaguemanager' ); else _e( 'Update League', 'leaguemanager' ); ?>" class="button button-primary" /></p>
</form>
