<?php
/**
* Tournaments main page administration panel
*
*/
namespace ns;
?>
<div class="container">
	<h1><?php _e( 'Tournaments', 'racketmanager' ) ?></h1>

	<form id="tournaments-filter" method="post" action="">
		<?php wp_nonce_field( 'tournaments-bulk' ) ?>
		<div class="tablenav">
			<!-- Bulk Actions -->
			<select name="action" size="1">
				<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
				<option value="delete"><?php _e('Delete')?></option>
			</select>
			<input type="submit" value="<?php _e('Apply'); ?>" name="doTournamentDel" id="doTournamentDel" class="btn btn-secondary action" />
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
					<th scope="col"><?php _e( 'Competitions', 'racketmanager' ) ?></th>
				</tr>
				<tbody id="the-list">
					<?php if ( $tournaments = $racketmanager->getTournaments( array( 'orderby' => array('date' => 'desc', 'name' => 'asc')) ) ) { $class = ''; ?>
					<?php foreach ( $tournaments AS $tournament ) {
						$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
						<tr class="<?php echo $class ?>">
							<th scope="row" class="check-column">
								<input type="checkbox" value="<?php echo $tournament->id ?>" name="tournament[<?php echo $tournament->id ?>]" />
							</th>
							<td class="column-num"><?php echo $tournament->id ?></td>
							<td class="tournamentname"><a href="admin.php?page=racketmanager&amp;subpage=tournament&amp;tournament_name=<?php echo $tournament->name ?> "><?php echo $tournament->name ?></a></td>
							<td><?php echo $tournament->type ?></td>
							<td><?php echo $tournament->season ?></td>
							<td><?php echo $tournament->venueName ?></td>
							<td><?php echo $tournament->dateDisplay ?></td>
							<td><?php echo $tournament->closingDateDisplay ?></td>
							<td><a href="admin.php?page=racketmanager&amp;subpage=show-competitions&amp;season=<?php echo $tournament->season ?>&amp;type=<?php echo $tournament->type ?>&amp;competitiontype=tournament" class="btn btn-secondary"><?php _e( 'Competitions', 'racketmanager' ) ?></a></td>
						</tr>
					<?php } ?>
				<?php } ?>
			</tbody>
		</table>
	</form>
	<!-- Add New Tournament -->
	<a href="admin.php?page=racketmanager&amp;subpage=tournament" name="addTournament" class="btn btn-primary submit"><?php _e( 'Add Tournament','racketmanager' ) ?></a>
</div>
