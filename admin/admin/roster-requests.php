<!-- Roster Request -->

<form id="roster-request-filter" method="post" action="" class="form-control">
	<?php wp_nonce_field( 'roster-request-bulk' ) ?>

	<div class="tablenav">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
			<option value="approve"><?php _e('Approve')?></option>
			<option value="delete"><?php _e('Delete')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="dorosterrequest" id="dorosterrequest" class="btn btn-secondary action" />
	</div>

	<div class="container">
		<div class="row table-header">
			<div class="col-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('roster-request-filter'));" /></div>
			<div class="col-1 column-num">ID</div>
			<div class="col-2"><?php _e( 'Club', 'racketmanager' ) ?></div>
			<div class="col-1"><?php _e( 'First Name', 'racketmanager' ) ?></div>
			<div class="col-1"><?php _e( 'Surame', 'racketmanager' ) ?></div>
			<div class="col-1"><?php _e( 'Gender', 'racketmanager' ) ?></div>
			<div class="col-1"><?php _e( 'LTA Tennis Number', 'racketmanager' ) ?></div>
			<div class="col-1"><?php _e( 'Requested Date', 'racketmanager' ) ?></div>
			<div class="col-1"><?php _e( 'Requested User', 'racketmanager' ) ?></div>
			<div class="col-1"><?php _e( 'Completed Date', 'racketmanager' ) ?></div>
			<div class="col-1"><?php _e( 'Completed User', 'racketmanager' ) ?></div>
		</div>
		<?php
		$clubs = $racketmanager->getClubs();
		$class = '';
		foreach ($clubs AS $club) {
			$club = get_club($club->id);
			$rosterRequests = $club->getRosterRequests( array('completed' => true) );
			foreach ($rosterRequests AS $rosterRequest) {
				$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
				<div class="row table-row <?php echo $class ?>">
					<div class="col-1 check-column">
						<input type="checkbox" value="<?php echo $rosterRequest->id ?>" name="rosterRequest[<?php echo $rosterRequest->id ?>]" />
					</div>
					<div class="col-1 column-num"><?php echo $rosterRequest->id ?><input type="hidden" id="club_id[<?php echo $rosterRequest->id ?>]" name="club_id[<?php echo $rosterRequest->id ?>]" value="<?php echo $club->id ?>"/></div>
					<div class="col-2"><?php echo $club->name ?></div>
					<div class="col-1"><?php echo $rosterRequest->first_name ?></div>
					<div class="col-1"><?php echo $rosterRequest->surname ?></div>
					<div class="col-1"><?php echo $rosterRequest->gender ?></div>
					<div class="col-1"><?php echo $rosterRequest->btm ?></div>
					<div class="col-1"><?php echo $rosterRequest->requested_date ?></div>
					<div class="col-1"><?php echo $rosterRequest->requestedUser ?></div>
					<div class="col-1"><?php echo $rosterRequest->completed_date ?></div>
					<div class="col-1"><?php echo $rosterRequest->completedUser ?></div>
				</div>
			<?php } ?>
		<?php } ?>
	</div>
</form>
