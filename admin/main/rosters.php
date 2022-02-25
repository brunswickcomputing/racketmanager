<!-- Add Roster -->
<form action="" method="post">
	<?php wp_nonce_field( 'racketmanager_add-roster' ) ?>
	<div class="lm-form-table">
		<?php if ( $players = $racketmanager->getPlayers( array() ) ) { ?>
			<select size="1" name="player_id" id="player_id">
				<option><?php _e( 'Select player', 'racketmanager' ) ?></option>
				<?php foreach ( $players AS $player ) {
					if ( isset($player->removed_date) && $player->removed_date != '') {
						$disabled = 'disabled';
					} else {
						$disabled = '';
					}?>
					<option value="<?php echo $player->id ?>" <?php echo $disabled ?>><?php echo $player->fullname ?> (<?php echo $player->btm ?>)</option>
				<?php } ?>
			</select>
		<?php } ?>
	</div>
	<input type="hidden" name="addRoster" value="player" />
	<input type="hidden" name="club_id" value=<?php echo $club_id ?> />
	<p class="submit"><input type="submit" name="addPlayertoRoster" value="<?php _e( 'Add Roster','racketmanager' ) ?>" class="button button-primary" /></p>

</form>

<!-- View Rosters -->
<form action="admin.php?page=racketmanager" method="get">
	<input type="hidden" name="page" value="racketmanager" />
	<input type="hidden" name="view" value="roster" />
	<div class="lm-form-table">
		<?php if ( $clubs = $racketmanager->getClubs( ) ) { ?>
			<select size="1" name="club_id" id="club_id">
				<option><?php _e( 'Select affiliated club', 'racketmanager' ) ?></option>
				<?php foreach ( $clubs AS $club ) { ?>
					<option value="<?php echo $club->id ?>" <?php echo ($club->id == $club_id ?  'selected' :  '') ?>><?php echo $club->name ?></option>
				<?php } ?>
			</select>
		<?php } ?>
		<input type="submit" value="<?php _e( 'View Roster','racketmanager' ) ?>" class="button button-primary" />
	</div>

</form>


<form id="roster-filter" method="post" action="">
	<?php wp_nonce_field( 'roster-bulk' ) ?>

	<div class="tablenav">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
			<option value="delete"><?php _e('Delete')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="dorosterdel" id="dorosterdel" class="button-secondary action" />
	</div>

	<table class="widefat" summary="" title="RacketManager Roster">
		<thead>
			<tr>
				<th scope="col" class="check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('roster-filter'));" /></th>
				<th scope="col" class="column-num">ID</th>
				<th scope="col"><?php _e( 'Name', 'racketmanager' ) ?></th>
				<th scope="col"><?php _e( 'Gender', 'racketmanager' ) ?></th>
				<th scope="col"><?php _e( 'BTM', 'racketmanager' ) ?></th>
				<th scope="col"><?php _e( 'Removed', 'racketmanager') ?></th>
				<th scope="col"><?php _e( 'Removed By', 'racketmanager') ?></th>
				<th scope="col"><?php _e( 'Created On', 'racketmanager') ?></th>
				<th scope="col"><?php _e( 'Created By', 'racketmanager') ?></th>
			</tr>
			<?php if ( !$club_id == 0 ) { $club = get_club($club_id); ?>
				<tbody id="the-list">

					<?php if ( $rosters = $club->getRoster(array()) ) { $class = ''; ?>
					<?php foreach ( $rosters AS $roster ) { ?>
						<?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
						<tr class="<?php echo $class ?>">
							<th scope="row" class="check-column">
								<?php if ( !isset($roster->removed_date) ) { ?>
									<input type="checkbox" value="<?php echo $roster->roster_id ?>" name="roster[<?php echo $roster->roster_id ?>]" />
								<?php } ?>
							</th>
							<td class="column-num"><?php echo $roster->roster_id ?></td>
							<td><?php echo $roster->fullname ?></td>
							<td><?php echo $roster->gender ?></td>
							<td><?php echo $roster->btm ?></td>
							<td><?php if ( isset($roster->removed_date) ) { echo $roster->removed_date; } ?></td>
							<td><?php echo $roster->removedUserName ?></td>
							<td><?php echo $roster->created_date ?></td>
							<td><?php echo $roster->createdUserName ?></td>
						</tr>
					<?php } ?>
				<?php } ?>
			</tbody>
		<?php } ?>
	</table>
</form>
