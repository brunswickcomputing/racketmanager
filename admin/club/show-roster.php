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
		<form id="roster-filter" method="post" action="" class="form-control">
			<?php wp_nonce_field( 'roster-bulk' ) ?>

			<div class="tablenav">
				<!-- Bulk Actions -->
				<select name="action" size="1">
					<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
					<option value="delete"><?php _e('Remove')?></option>
				</select>
				<input type="submit" value="<?php _e('Apply'); ?>" name="dorosterdel" id="dorosterdel" class="btn btn-secondary action" />
			</div>

			<div class="container">
				<div class="row table-header">
					<div class="col-1 col-md-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('roster-filter'));" /></div>
					<div class="col-6 col-md-2"><?php _e( 'Name', 'racketmanager' ) ?></div>
					<div class="col-2 col-md-1"><?php _e( 'Gender', 'racketmanager' ) ?></div>
					<div class="col-2 col-md-1"><?php _e( 'LTA Tennis Number', 'racketmanager' ) ?></div>
					<div class="col-4 col-md-1"><?php _e( 'Removed', 'racketmanager') ?></div>
					<div class="col-4 col-md-1"><?php _e( 'Created On', 'racketmanager') ?></div>
					<div class="col-4 col-md-1"><?php _e( 'Locked On', 'racketmanager') ?></div>
				</div>
				<?php if ( !$club_id == 0 ) { $club = get_club($club_id); ?>

					<?php if ( $rosters = $club->getPlayers(array()) ) {
						$class = '';
						foreach ( $rosters as $roster ) { ?>
							<?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
							<div class="row table-row <?php echo $class ?>">
								<div class="col-1 col-md-1 check-column">
									<?php if ( !isset($roster->removed_date) ) { ?>
										<input type="checkbox" value="<?php echo $roster->roster_id ?>" name="roster[<?php echo $roster->roster_id ?>]" />
									<?php } ?>
								</div>
								<div class="col-6 col-md-2"><a href="admin.php?page=racketmanager-clubs&amp;view=player&amp;club_id=<?php echo $club->id ?>&amp;player_id=<?php echo $roster->player_id ?>"><?php echo $roster->fullname ?></a></div>
								<div class="col-1 col-md-1"><?php echo $roster->gender ?></div>
								<div class="col-4 col-md-1"><?php echo $roster->btm ?></div>
								<div class="col-4 col-md-1" title="<?php if (!empty($roster->removedUserName)) { echo __('Removed by',' racketmanager').' '.$roster->removedUserName; } ?>"><?php if ( isset($roster->removed_date) ) { echo $roster->removed_date; } ?></div>
								<div class="col-4 col-md-1" title="<?php if (!empty($roster->createdUserName)) { echo __('Created by',' racketmanager').' '.$roster->createdUserName; } ?>"><?php echo $roster->created_date ?></div>
								<div class="col-4 col-md-1" title="<?php if (!empty($roster->lockedUserName)) { echo __('Locked by',' racketmanager').' '.$roster->lockedUserName; } ?>"><?php echo $roster->locked_date ?></div>
							</div>
						<?php } ?>
					<?php } ?>
				<?php } ?>
			</div>
		</form>
	</div>
</div>
