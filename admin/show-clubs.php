<?php
/**
* Clubs main page administration panel
*
*/
namespace ns;
?>
<div class="container">
	<h1><?php _e( 'Clubs', 'racketmanager' ) ?></h1>

	<div class="container">
		<form id="teams-filter" method="post" action="">
			<?php wp_nonce_field( 'clubs-bulk' ) ?>

			<div class="tablenav">
				<!-- Bulk Actions -->
				<select name="action" size="1">
					<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
					<option value="delete"><?php _e('Delete')?></option>
				</select>
				<input type="submit" value="<?php _e('Apply'); ?>" name="doClubDel" id="doClubDel" class="btn btn-secondary action" />
			</div>

			<div class="container">
				<div class="row table-header">
					<div class="col-12 col-md-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('clubs-filter'));" /></div>
					<div class="col-12 col-md-1 column-num">ID</div>
					<div class="col-12 col-md-3"><?php _e( 'Name', 'racketmanager' ) ?></div>
					<div class="col-12 col-md-3"><?php _e( 'Match Secretary', 'racketmanager' ) ?></div>
				</div>
				<?php $clubs = $racketmanager->getClubs( );
				$class = '';
				foreach ( $clubs AS $club ) {
					$club = get_club($club);
					$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
					<div class="row table-row <?php echo $class ?>">
						<div class="col-12 col-md-1 check-column">
							<input type="checkbox" value="<?php echo $club->id ?>" name="club[<?php echo $club->id ?>]" />
						</div>
						<div class="col-12 col-md-1 column-num"><?php echo $club->id ?></div>
						<div class="col-12 col-md-3 clubname"><a href="admin.php?page=racketmanager&amp;subpage=club&amp;club_id=<?php echo $club->id ?> "><?php echo $club->name ?></a></div>
						<div class="col-12 col-md-3"><?php echo $club->matchSecretaryName ?></div>
						<div class="col-2"><a href="admin.php?page=racketmanager-clubs&amp;view=roster&amp;club_id=<?php echo $club->id ?> " class="btn btn-secondary"><?php _e( 'Players', 'racketmanager' ) ?></a></div>
						<div class="col-2"><a href="admin.php?page=racketmanager-clubs&amp;view=teams&amp;club_id=<?php echo $club->id ?> " class="btn btn-secondary"><?php _e( 'Teams', 'racketmanager' ) ?></a></div>
					</div>
				<?php } ?>
			</form>
		</div>
	</div>
	<div class="container">
		<!-- Add New Club -->
		<a href="admin.php?page=racketmanager&amp;subpage=club" name="addTeam" class="btn btn-primary submit"><?php _e( 'Add Club','racketmanager' ) ?></a>
	</div>
</div>
