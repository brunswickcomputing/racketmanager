<!-- Add Player -->
<div class="mb-3">
	<form action="" method="post" class="form-control">
		<?php wp_nonce_field( 'racketmanager_add-player' ) ?>
		<div class="form-floating mb-3">
			<input required="required" placeholder="<?php _e( 'Enter first name', 'racketmanager') ?>" type="text" name="firstname" id="firstname" value="" size="30" class="form-control"/>
			<label for="firstname"><?php _e( 'First Name', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
			<input required="required"  placeholder="<?php _e( 'Enter surname', 'racketmanager') ?>" type="text" name="surname" id="surname" value="" size="30" class="form-control" />
			<label for="surname"><?php _e( 'Surname', 'racketmanager' ) ?></label>
		</div>
		<label><?php _e('Gender', 'racketmanager') ?></label>
		<div class="form-check">
			<input type="radio" required="required" name="gender" id="genderMale" value="M" class="form-check-input" /><label for "genderMale" class="form-check-label"><?php _e('Male', 'racketmanager') ?></label>
		</div>
		<div class="form-check">
			<input type="radio" required="required" name="gender" id="genderFemale" value="F" class="form-check-input" /><label for "genderFemale" class="form-check-label"><?php _e('Female', 'racketmanager') ?></label>
		</div>
		<div class="form-floating mb-3">
			<input type="number"  placeholder="<?php _e( 'Enter LTA Tennis Number', 'racketmanager') ?>" name="btm" id="gender" size="11" class="form-control" />
			<label for="btm"><?php _e('LTA Tennis Number', 'racketmanager') ?></label>
		</div>
		<div class="form-floating mb-3">
			<input type="email" placeholder="<?php _e( 'Enter email address', 'racketmanager') ?>" name="email" id="email" class="form-control" />
			<label for="email"><?php _e('Email address', 'racketmanager') ?></label>
		</div>
		<input type="hidden" name="addPlayer" value="player" />
		<input type="submit" name="addPlayer" value="<?php _e( 'Add Player','racketmanager' ) ?>" class="btn btn-primary" />

	</form>
</div>
<div class="mb-3">
	<form id="player-filter" method="post" action="" class="form-control">
		<?php wp_nonce_field( 'player-bulk' ) ?>

		<div class="row g-3 mb-3 align-items-center">
			<!-- Bulk Actions -->
			<div class="col-auto">
				<div class="form-floating">
					<select class="form-select" name="action" size="1">
						<option value="-1"><?php _e('Select', 'racketmanager') ?></option>
						<option value="delete"><?php _e('Delete player', 'racketmanager')?></option>
					</select>
					<label for="action"><?php _e('Bulk Action', 'racketmanager') ?></label>
				</div>
			</div>
			<div class="col-auto">
				<input type="submit" value="<?php _e('Apply', 'racketmanager'); ?>" name="doPlayerDel" id="dorPlayerDel" class="btn btn-secondary action" />
			</div>
			<div class="col-auto">
				<div class="form-floating">
					<input placeholder="<?php _e( 'Enter search', 'racketmanager') ?>" type="text" name="name" id="name" size="30" class="form-control" />
					<label for="name"><?php _e( 'Search by name', 'racketmanager' ) ?></label>
				</div>
			</div>
			<div class="col-auto">
			<input type="submit" value="<?php _e('Search', 'racketmanager'); ?>" name="doPlayerSearch" id="dorPlayerSearch" class="btn btn-secondary action" />
			</div>
		</div>

		<div class="container">
			<div class="row table-header">
				<div class="col-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('player-filter'));" /></div>
				<div class="col-1 column-num">ID</div>
				<div class="col-3"><?php _e( 'Name', 'racketmanager' ) ?></div>
				<div class="col-1"><?php _e( 'Gender', 'racketmanager' ) ?></div>
				<div class="col-1"><?php _e( 'LTA Tennis Number', 'racketmanager' ) ?></div>
				<div class="col-1"><?php _e( 'Created', 'racketmanager') ?></div>
				<div class="col-1"><?php _e( 'Removed', 'racketmanager') ?></div>
			</div>
			<?php if ( $players ) {
				$class = '';
				foreach ( $players AS $player ) {
					$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
					<div class="row table-row <?php echo $class ?>">
						<div class="col-1 check-column">
							<?php if ( $player->removed_date == '' ) { ?>
								<input type="checkbox" value="<?php echo $player->id ?>" name="player[<?php echo $player->id ?>]" />
							<?php } ?>
						</div>
						<div class="col-1 column-num"><?php echo $player->id ?></div>
						<div class="col-3"><a href="admin.php?page=racketmanager-players&amp;view=player&amp;player_id=<?php echo $player->id ?>"><?php echo $player->fullname ?></a></div>
						<div class="col-1"><?php echo $player->gender ?></div>
						<div class="col-1"><?php echo $player->btm ?></div>
						<div class="col-1"><?php echo substr($player->created_date,0,10) ?></div>
						<div class="col-1"><?php if ( isset($player->removed_date) ) { echo $player->removed_date; } ?></div>
					</div>
				<?php } ?>
			<?php } ?>
		</div>
	</form>
</div>
