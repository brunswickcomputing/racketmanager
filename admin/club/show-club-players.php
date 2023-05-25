<?php
/**
* Club Players main page administration panel
*
*/
namespace ns;
?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-clubs"><?php _e( 'Clubs', 'racketmanager' ) ?></a> &raquo; <?php _e( 'Players', 'racketmanager' ) ?>
		</div>
	</div>
	<h1><?php _e( 'Players', 'racketmanager' ) ?> - <?php echo $club->name ?></h1>

	<!-- Add player -->
	<div class="mb-3">
		<!-- Add Player -->
		<h2><?php _e( 'Add Player', 'racketmanager' ) ?></h2>
		<?php include_once( RACKETMANAGER_PATH . '/admin/includes/player.php' ); ?>
	</div>

	<div class="mb-3">
		<h2><?php _e( 'View Players', 'racketmanager' ) ?></h2>
		<form id="players-filter" method="get" action="" class="form-control mb-3">
			<input type="hidden" name="page" value="<?php echo 'racketmanager-clubs' ?>" />
			<input type="hidden" name="view" value="<?php echo 'players' ?>" />
			<input type="hidden" name="club_id" value="<?php echo $club->id ?>" />
			<select class="" name="active" id="active">
				<option value="" <?php echo $active == '' ?  'selected' :  '' ?>><?php _e( 'All players', 'racketmanager' ) ?></option>
				<option value="true" <?php echo $active == 'true' ?  'selected' :  '' ?>><?php _e( 'Active', 'racketmanager' ) ?></option>
			</select>
			<select class="" name="gender" id="gender">
				<option value="" <?php echo $gender == '' ?  'selected' :  '' ?>><?php _e( 'All genders', 'racketmanager' ) ?></option>
				<option value="F" <?php echo $gender == 'F' ?  'selected' :  '' ?>><?php _e( 'Female', 'racketmanager' ) ?></option>
				<option value="M" <?php echo $gender == 'M' ?  'selected' :  '' ?>><?php _e( 'Male', 'racketmanager' ) ?></option>
			</select>
			<button class="btn btn-primary"><?php _e('Filter') ?></button>
		</form>
		<form id="players-action" method="post" action="" class="form-control">
			<?php wp_nonce_field( 'club-players-bulk' ) ?>

			<div class="tablenav">
				<!-- Bulk Actions -->
				<select name="action" size="1">
					<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
					<option value="delete"><?php _e('Remove')?></option>
				</select>
				<input type="submit" value="<?php _e('Apply'); ?>" name="doClubPlayerdel" id="doClubPlayerdel" class="btn btn-secondary action" />
			</div>

			<div class="container">
				<div class="row table-header">
					<div class="col-1 col-md-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('players-action'));" /></div>
					<div class="col-6 col-md-2"><?php _e( 'Name', 'racketmanager' ) ?></div>
					<div class="col-2 col-md-1"><?php _e( 'Gender', 'racketmanager' ) ?></div>
					<div class="col-2 col-md-1"><?php _e( 'LTA Tennis Number', 'racketmanager' ) ?></div>
					<div class="col-4 col-md-1"><?php _e( 'Removed', 'racketmanager') ?></div>
					<div class="col-4 col-md-1"><?php _e( 'Created On', 'racketmanager') ?></div>
					<div class="col-4 col-md-1"><?php _e( 'Locked On', 'racketmanager') ?></div>
				</div>
				<?php if ( !$club_id == 0 ) { $club = get_club($club_id); ?>

					<?php if ( $players ) {
						$class = '';
						foreach ( $players as $player ) { ?>
							<?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
							<div class="row table-row <?php echo $class ?>">
								<div class="col-1 col-md-1 check-column">
									<?php if ( !isset($player->removed_date) ) { ?>
										<input type="checkbox" value="<?php echo $player->roster_id ?>" name="clubPlayer[<?php echo $player->roster_id ?>]" />
									<?php } ?>
								</div>
								<div class="col-6 col-md-2"><?php if ( !isset($player->removed_date) ) { echo '<a href="admin.php?page=racketmanager-clubs&amp;view=player&amp;club_id='.$club->id.'&amp;player_id='.$player->player_id.'">'; } ?><?php echo $player->fullname ?><?php if ( !isset($player->removed_date) ) { echo '</a>'; } ?></div>
								<div class="col-1 col-md-1"><?php echo $player->gender ?></div>
								<div class="col-4 col-md-1"><?php echo $player->btm ?></div>
								<div class="col-4 col-md-1" title="<?php if (!empty($player->removedUserName)) { echo __('Removed by','racketmanager').' '.$player->removedUserName; } ?>"><?php if ( isset($player->removed_date) ) { echo $player->removed_date; } ?></div>
								<div class="col-4 col-md-1" title="<?php if (!empty($player->createdUserName)) { echo __('Created by','racketmanager').' '.$player->createdUserName; } ?>"><?php echo substr($player->created_date, 0, 10) ?></div>
								<div class="col-4 col-md-1" title="<?php if (!empty($player->lockedUserName)) { echo __('Locked by','racketmanager').' '.$player->lockedUserName; } ?>"><?php echo $player->locked_date ?></div>
							</div>
						<?php } ?>
					<?php } ?>
				<?php } ?>
			</div>
		</form>
	</div>
</div>
