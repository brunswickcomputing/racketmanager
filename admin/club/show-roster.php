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
	<div class="container">
		<!-- Add Roster -->
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

	<div class="container">
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

			<div class="container">
				<div class="row table-header">
					<div class="col-12 col-md-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('roster-filter'));" /></div>
					<div class="col-12 col-md-2"><?php _e( 'Name', 'racketmanager' ) ?></div>
					<div class="col-12 col-md-1"><?php _e( 'Gender', 'racketmanager' ) ?></div>
					<div class="col-12 col-md-1"><?php _e( 'BTM', 'racketmanager' ) ?></div>
					<div class="col-12 col-md-1"><?php _e( 'Removed', 'racketmanager') ?></div>
					<div class="col-12 col-md-2"><?php _e( 'Removed By', 'racketmanager') ?></div>
					<div class="col-12 col-md-1"><?php _e( 'Created On', 'racketmanager') ?></div>
					<div class="col-12 col-md-2"><?php _e( 'Created By', 'racketmanager') ?></div>
				</div>
				<?php if ( !$club_id == 0 ) { $club = get_club($club_id); ?>

					<?php if ( $rosters = $club->getRoster(array()) ) {
						$class = '';
						foreach ( $rosters AS $roster ) { ?>
							<?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
							<div class="row table-row <?php echo $class ?>">
								<div class="col-12 col-md-1 check-column">
									<?php if ( !isset($roster->removed_date) ) { ?>
										<input type="checkbox" value="<?php echo $roster->roster_id ?>" name="roster[<?php echo $roster->roster_id ?>]" />
									<?php } ?>
								</div>
								<div class="col-12 col-md-2"><?php echo $roster->fullname ?></div>
								<div class="col-12 col-md-1"><?php echo $roster->gender ?></div>
								<div class="col-12 col-md-1"><?php echo $roster->btm ?></div>
								<div class="col-12 col-md-1"><?php if ( isset($roster->removed_date) ) { echo $roster->removed_date; } ?></div>
								<div class="col-12 col-md-2"><?php echo $roster->removedUserName ?></div>
								<div class="col-12 col-md-1"><?php echo $roster->created_date ?></div>
								<div class="col-12 col-md-2"><?php echo $roster->createdUserName ?></div>
							</div>
						<?php } ?>
					<?php } ?>
				<?php } ?>
			</div>
		</form>
	</div>
</div>
