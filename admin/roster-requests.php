<!-- Roster Request -->

<form id="roster-request-filter" method="post" action="">
	<?php wp_nonce_field( 'roster-request-bulk' ) ?>

	<div class="tablenav" style="margin-bottom: 0.1em;">
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

	<?php if ( $rosterRequests = $leaguemanager->getRosterRequests(array() ) ) { $class = ''; ?>
		<?php foreach ( $rosterRequests AS $rosterRequest ) { ?>
			<?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
			<tr class="<?php echo $class ?>">
				<th scope="row" class="check-column">
					<input type="checkbox" value="<?php echo $rosterRequest->id ?>" name="rosterRequest[<?php echo $rosterRequest->id ?>]" />
				</th>
				<td class="num"><?php echo $rosterRequest->id ?></td>
                <td><?php echo $rosterRequest->affiliatedClubName ?></td>
                <td><?php echo $rosterRequest->firstName ?></td>
				<td><?php echo $rosterRequest->surname ?></td>
				<td><?php echo $rosterRequest->gender ?></td>
				<td><?php echo $rosterRequest->btm ?></td>
                <td><?php echo $rosterRequest->requestedDate ?></td>
                <td><?php echo $rosterRequest->requestedUser ?></td>
                <td><?php echo $rosterRequest->completedDate ?></td>
                <td><?php echo $rosterRequest->completedUser ?></td>
			</tr>
		<?php } ?>
	<?php } ?>
		</tbody>
	</table>
</form>

