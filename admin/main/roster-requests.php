<!-- Roster Request -->

<form id="roster-request-filter" method="post" action="">
	<?php wp_nonce_field( 'roster-request-bulk' ) ?>

    <div class="tablenav">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
            <option value="approve"><?php _e('Approve')?></option>
			<option value="delete"><?php _e('Delete')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="dorosterrequest" id="dorosterrequest" class="button-secondary action" />
	</div>

	<table class="widefat" summary="" title="LeagueManager Roster Request">
		<thead>
		<tr>
			<th scope="col" class="check-column"><input type="checkbox" onclick="Leaguemanager.checkAll(document.getElementById('roster-request-filter'));" /></th>
			<th scope="col" class="num">ID</th>
            <th scope="col"><?php _e( 'Club', 'leaguemanager' ) ?></th>
			<th scope="col"><?php _e( 'First Name', 'leaguemanager' ) ?></th>
            <th scope="col"><?php _e( 'Surame', 'leaguemanager' ) ?></th>
			<th scope="col"><?php _e( 'Gender', 'leaguemanager' ) ?></th>
			<th scope="col"><?php _e( 'BTM', 'leaguemanager' ) ?></th>
            <th scope="col"><?php _e( 'Requested Date', 'leaguemanager' ) ?></th>
            <th scope="col"><?php _e( 'Requested User', 'leaguemanager' ) ?></th>
            <th scope="col"><?php _e( 'Completed Date', 'leaguemanager' ) ?></th>
            <th scope="col"><?php _e( 'Completed User', 'leaguemanager' ) ?></th>
		</tr>
		<tbody id="the-list">

<?php
    $clubs = $leaguemanager->getClubs();
    $class = '';
    foreach ($clubs AS $club) {
        $club = get_club($club->id);
        $rosterRequests = $club->getRosterRequests( true );
        foreach ($rosterRequests AS $rosterRequest) {
            $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
			<tr class="<?php echo $class ?>">
				<th scope="row" class="check-column">
					<input type="checkbox" value="<?php echo $rosterRequest->id ?>" name="rosterRequest[<?php echo $rosterRequest->id ?>]" />
				</th>
				<td class="num"><?php echo $rosterRequest->id ?><input type="hidden" id="club_id[<?php echo $rosterRequest->id ?>]" name="club_id[<?php echo $rosterRequest->id ?>]" value="<?php echo $club->id ?>"/></td>
                <td><?php echo $club->name ?></td>
                <td><?php echo $rosterRequest->first_name ?></td>
				<td><?php echo $rosterRequest->surname ?></td>
				<td><?php echo $rosterRequest->gender ?></td>
				<td><?php echo $rosterRequest->btm ?></td>
                <td><?php echo $rosterRequest->requested_date ?></td>
                <td><?php echo $rosterRequest->requestedUser ?></td>
                <td><?php echo $rosterRequest->completed_date ?></td>
                <td><?php echo $rosterRequest->completedUser ?></td>
			</tr>
		<?php } ?>
	<?php } ?>
		</tbody>
	</table>
</form>
