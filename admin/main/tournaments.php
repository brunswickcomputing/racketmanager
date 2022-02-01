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
        <a href="admin.php?page=racketmanager&amp;subpage=tournament" name="addTournament" class="button button-primary submit"><?php _e( 'Add Tournament','racketmanager' ) ?></a>
	</div>

	<table class="widefat" summary="" title="RacketManager Tournaments">
		<thead>
		<tr>
			<th scope="col" class="check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('tournaments-filter'));" /></th>
			<th scope="col" class="column-num">ID</th>
            <th scope="col"><?php _e( 'Name', 'racketmanager' ) ?></th>
			<th scope="col"><?php _e( 'Type', 'racketmanager' ) ?></th>
            <th scope="col"><?php _e( 'Season', 'racketmanager' ) ?></th>
            <th scope="col"><?php _e( 'Venue', 'racketmanager' ) ?></th>
            <th scope="col"><?php _e( 'Date', 'racketmanager' ) ?></th>
            <th scope="col"><?php _e( 'Closing Date', 'racketmanager' ) ?></th>
		</tr>
		<tbody id="the-list">
	<?php if ( $tournaments = $racketmanager->getTournaments( array() ) ) { $class = ''; ?>
		<?php foreach ( $tournaments AS $tournament ) {
			$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
			<tr class="<?php echo $class ?>">
				<th scope="row" class="check-column">
					<input type="checkbox" value="<?php echo $tournament->id ?>" name="tournament[<?php echo $tournament->id ?>]" />
				</th>
				<td class="column-num"><?php echo $tournament->id ?></td>
				<td><a href="admin.php?page=racketmanager&amp;subpage=tournament&amp;tournament_name=<?php echo $tournament->name ?> "><?php echo $tournament->name ?></a></td>
                <td><?php echo $tournament->type ?></td>
                <td><?php echo $tournament->season ?></td>
                <td><?php echo $tournament->venueName ?></td>
                <td><?php echo $tournament->date ?></td>
                <td><?php echo $tournament->closingdate ?></td>
			</tr>
		<?php } ?>
	<?php } ?>
		</tbody>
	</table>
</form>
