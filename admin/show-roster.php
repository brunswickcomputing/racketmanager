<?php
/**
* Roster main page administration panel
*
*/
namespace ns;
?>
<div class="container">
	<p class="racketmanager_breadcrumb"><a href="admin.php?page=racketmanager-clubs"><?php _e( 'Clubs', 'racketmanager' ) ?></a> &raquo; <?php _e( 'Players', 'racketmanager' ) ?></p>
	<h1><?php _e( 'Players', 'racketmanager' ) ?> - <?php echo $club->name ?></h1>

<!-- View Rosters -->
<div class="row">
	<!-- Add Roster -->
	<div>
		<form action="" method="post" class="form-control">
			<?php wp_nonce_field( 'racketmanager_add-roster' ) ?>
			<div class="lm-form-table">
				<?php if ( $players = $racketmanager->getPlayers( array() ) ) { ?>
					<div class="form-group">
						<label for="player_id"><?php _e("Player","racketmanager") ?></label>
						<div class="input">
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
						</div>
					</div>
				<?php } ?>
				<input type="submit" name="addPlayertoRoster" value="<?php _e( 'Add Player','racketmanager' ) ?>" class="btn btn-primary" />
			</div>
			<input type="hidden" name="addRoster" value="player" />
			<input type="hidden" name="club_id" value=<?php echo $club_id ?> />
		</form>
	</div>
</div>

<form id="roster-filter" method="post" action="">
	<?php wp_nonce_field( 'roster-bulk' ) ?>

	<div class="tablenav">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
			<option value="delete"><?php _e('Delete')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="dorosterdel" id="dorosterdel" class="btn btn-secondary action" />
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
</div>
