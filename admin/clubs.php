<!-- Add Team -->

<form id="teams-filter" method="post" action="">
	<?php wp_nonce_field( 'clubs-bulk' ) ?>

	<div class="tablenav">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
			<option value="delete"><?php _e('Delete')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="doClubDel" id="doClubDel" class="button-secondary action" />
        <!-- Add New Team -->
        <a href="admin.php?page=leaguemanager&amp;subpage=club" name="addTeam" class="button button-primary submit"><?php _e( 'Add Club','leaguemanager' ) ?></a>
	</div>

	<table class="widefat" summary="" title="LeagueManager Clubs">
		<thead>
		<tr>
			<th scope="col" class="check-column"><input type="checkbox" onclick="Leaguemanager.checkAll(document.getElementById('clubs-filter'));" /></th>
			<th scope="col" class="num">ID</th>
			<th scope="col"><?php _e( 'Name', 'leaguemanager' ) ?></th>
            <th scope="col"><?php _e( 'Match Secretary', 'leaguemanager' ) ?></th>
            <th scope="col" class="num"></th>
            <th scope="col" class="num"></th>
		</tr>
		<tbody id="the-list">
<?php if ( isset($club_id) && $club_id > 0) {
    $affiliatedClub = $club_id;
} else {
    $affiliatedClub = '';
} ?>
	<?php if ( $teams = $leaguemanager->getClubs( ) ) { $class = ''; ?>
		<?php foreach ( $clubs AS $club ) {
            $club = get_club($club);
			$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
			<tr class="<?php echo $class ?>">
				<th scope="row" class="check-column">
					<input type="checkbox" value="<?php echo $club->id ?>" name="club[<?php echo $club->id ?>]" />
				</th>
				<td class="num"><?php echo $club->id ?></td>
				<td><a href="admin.php?page=leaguemanager&amp;subpage=club&amp;club_id=<?php echo $club->id ?> "><?php echo $club->name ?></a></td>
                <td><?php echo $club->matchSecretaryName ?></td>
                <td><a href="admin.php?page=leaguemanager&amp;view=roster&amp;club_id=<?php echo $club->id ?> " class="button-secondary"><?php _e( 'Roster', 'leaguemanager' ) ?></a></td>
                <td><a href="admin.php?page=leaguemanager&amp;view=teams&amp;club_id=<?php echo $club->id ?> " class="button-secondary"><?php _e( 'Teams', 'leaguemanager' ) ?></a></td>
			</tr>
		<?php } ?>
	<?php } ?>
		</tbody>
	</table>
</form>
