<!-- Add Player -->
<form action="" method="post">
	<?php wp_nonce_field( 'racketmanager_add-player' ) ?>
	<div class="form-group">
		<label for="firstname"><?php _e( 'First Name', 'racketmanager' ) ?></label>
		<div class="input">
			<input required="required" placeholder="<?php _e( 'Enter first name', 'racketmanager') ?>" type="text" name="firstname" id="firstname" value="" size="30" />
		</div>
	</div>
	<div class="form-group">
		<label for="surname"><?php _e( 'Surname', 'racketmanager' ) ?></label>
		<div class="input">
			<input required="required"  placeholder="<?php _e( 'Enter surname', 'racketmanager') ?>" type="text" name="surname" id="surname" value="" size="30" />
		</div>
	</div>
	<div class="form-group">
		<label><?php _e('Gender', 'racketmanager') ?></label>
		<div class="form-check">
			<input type="radio" required="required" name="gender" id="genderMale" value="M" /><label for "genderMale"><?php _e('Male', 'racketmanager') ?></label>
		</div>
		<div class="form-check">
			<input type="radio" required="required" name="gender" id="genderFemale" value="F" /><label for "genderFemale"><?php _e('Female', 'racketmanager') ?></label>
		</div>
	</div>
	<div class="form-group">
		<label for="btm"><?php _e('BTM', 'racketmanager') ?></label>
		<div class="input">
			<input type="number"  placeholder="<?php _e( 'Enter BTM number', 'racketmanager') ?>" name="btm" id="gender" size="11" />
		</div>
	</div>
	<input type="hidden" name="addPlayer" value="player" />
	<p class="submit"><input type="submit" name="addPlayer" value="<?php _e( 'Add Player','racketmanager' ) ?>" class="button button-primary" /></p>

</form>

<form id="player-filter" method="post" action="">
	<?php wp_nonce_field( 'player-bulk' ) ?>

	<div class="tablenav">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
			<option value="delete"><?php _e('Delete')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="doPlayerDel" id="dorPlayerDel" class="button-secondary action" />
	</div>

	<table class="widefat" summary="" title="RacketManager Players">
		<thead>
			<tr>
				<th scope="col" class="check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('player-filter'));" /></th>
				<th scope="col" class="column-num">ID</th>
				<th scope="col"><?php _e( 'Name', 'racketmanager' ) ?></th>
				<th scope="col"><?php _e( 'Gender', 'racketmanager' ) ?></th>
				<th scope="col"><?php _e( 'BTM', 'racketmanager' ) ?></th>
				<th scope="col"><?php _e( 'Created', 'racketmanager') ?></th>
				<th scope="col"><?php _e( 'Removed', 'racketmanager') ?></th>
			</tr>
			<tbody id="the-list">
				<?php if ( $players = $racketmanager->getPlayers( array() ) ) { $class = ''; ?>
				<?php foreach ( $players AS $player ) { ?>
					<?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
					<tr class="<?php echo $class ?>">
						<th scope="row" class="check-column">
							<?php if ( $player->removed_date == '' ) { ?>
								<input type="checkbox" value="<?php echo $player->id ?>" name="player[<?php echo $player->id ?>]" />
							<?php } ?>
						</th>
						<td class="column-num"><?php echo $player->id ?></td>
						<td><?php echo $player->fullname ?></td>
						<td><?php echo $player->gender ?></td>
						<td><?php echo $player->btm ?></td>
						<td><?php echo substr($player->created_date,0,10) ?></td>
						<td><?php if ( isset($player->removed_date) ) { echo $player->removed_date; } ?></td>
					</tr>
				<?php } ?>
			<?php } ?>
		</tbody>
	</table>
</form>
