<?php
	$clubs = $racketmanager->getClubs();
?>
<!-- Roster Request -->
<form id="invoices-filter" method="get" action="" class="form-control mb-3">
	<input type="hidden" name="page" value="<?php echo 'racketmanager-players' ?>" />
	<input type="hidden" name="tab" value="<?php echo 'playerrequest' ?>" />
	<div class="col-auto">
		<select class="select" name="club" id="club">
			<option value="all"><?php _e( 'All clubs', 'racketmanager' ) ?></option>
			<?php foreach ( $clubs as $club ) { ?>
				<option value="<?php echo $club->id ?>" <?php echo $club->id == $clubId ?  'selected' :  '' ?>><?php echo $club->name ?></option>
			<?php } ?>
		</select>
		<select class="select" name="status">
			<option value="all" <?php echo $status == 'all' ? 'selected' : '' ?>><?php _e('All', 'racketmanager')?></option>
			<option value="outstanding" <?php echo $status == 'outstanding' ? 'selected' : '' ?>><?php _e('Outstanding', 'racketmanager')?></option>
		</select>
		<button class="btn btn-primary"><?php _e('Filter') ?></button>
	</div>
</form>

<form id="club-player-request-filter" method="post" action="" class="form-control">
	<?php wp_nonce_field( 'club-player-request-bulk' ) ?>

	<div class="mb-3">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
			<option value="approve"><?php _e('Approve', 'racketmanager')?></option>
			<option value="delete"><?php _e('Delete', 'racketmanager')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply', 'racketmanager'); ?>" name="doplayerrequest" id="doplayerrequest" class="btn btn-secondary action" />
	</div>

	<div class="container">
		<div class="row table-header">
			<div class="col-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('club-player-request-filter'));" /></div>
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
		<?php foreach ($playerRequests as $playerRequest) { ?>
			<div class="row table-row <?php echo $playerRequest->class ?>">
				<div class="col-1 check-column">
					<input type="checkbox" value="<?php echo $playerRequest->id ?>" name="playerRequest[<?php echo $playerRequest->id ?>]" />
				</div>
				<div class="col-1 column-num"><?php echo $playerRequest->id ?><input type="hidden" id="club_id[<?php echo $playerRequest->id ?>]" name="club_id[<?php echo $playerRequest->id ?>]" value="<?php echo $club->id ?>"/></div>
				<div class="col-2"><?php echo $playerRequest->clubName ?></div>
				<div class="col-1"><?php echo $playerRequest->first_name ?></div>
				<div class="col-1"><?php echo $playerRequest->surname ?></div>
				<div class="col-1"><?php echo $playerRequest->gender ?></div>
				<div class="col-1"><?php echo $playerRequest->btm ?></div>
				<div class="col-1"><?php echo $playerRequest->requested_date ?></div>
				<div class="col-1"><?php echo $playerRequest->requestedUser ?></div>
				<div class="col-1"><?php echo $playerRequest->completed_date ?></div>
				<div class="col-1"><?php echo $playerRequest->completedUser ?></div>
			</div>
		<?php } ?>
	</div>
</form>
