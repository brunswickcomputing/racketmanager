<!-- Show tournaments -->

<form id="tournaments-filter" method="post" action="">
	<?php wp_nonce_field( 'tournaments-bulk' ) ?>

	<div class="tablenav">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
			<option value="delete"><?php _e('Delete')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="doTournamentDel" id="doTournamentDel" class="button-secondary action" />
        <!-- Add New Team -->
        <a href="admin.php?page=leaguemanager&amp;subpage=tournament" name="addTournament" class="button button-primary submit"><?php _e( 'Add Tournament','leaguemanager' ) ?></a>
	</div>

	<table class="widefat" summary="" title="LeagueManager Tournaments">
		<thead>
		<tr>
			<th scope="col" class="check-column"><input type="checkbox" onclick="Leaguemanager.checkAll(document.getElementById('tournaments-filter'));" /></th>
			<th scope="col" class="num">ID</th>
            <th scope="col"><?php _e( 'Name', 'leaguemanager' ) ?></th>
			<th scope="col"><?php _e( 'Type', 'leaguemanager' ) ?></th>
            <th scope="col"><?php _e( 'Venue', 'leaguemanager' ) ?></th>
            <th scope="col"><?php _e( 'Date', 'leaguemanager' ) ?></th>
            <th scope="col"><?php _e( 'Closing Date', 'leaguemanager' ) ?></th>
		</tr>
		<tbody id="the-list">
	<?php if ( $tournaments = $leaguemanager->getTournaments( ) ) { $class = ''; ?>
		<?php foreach ( $tournaments AS $tournament ) {
			$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
			<tr class="<?php echo $class ?>">
				<th scope="row" class="check-column">
					<input type="checkbox" value="<?php echo $tournament->id ?>" name="tournament[<?php echo $tournament->id ?>]" />
				</th>
				<td class="num"><?php echo $tournament->id ?></td>
				<td><a href="admin.php?page=leaguemanager&amp;subpage=tournament&amp;tournament_id=<?php echo $tournament->id ?> "><?php echo $tournament->name ?></a></td>
                <td><?php echo $tournament->type ?></td>
                <td><?php echo $tournament->venueName ?></td>
                <td><?php echo $tournament->date ?></td>
                <td><?php echo $tournament->closingdate ?></td>
			</tr>
		<?php } ?>
	<?php } ?>
		</tbody>
	</table>
</form>
