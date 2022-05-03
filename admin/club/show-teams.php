<?php
/**
* Teams main page administration panel
*
*/
namespace ns;
?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-clubs"><?php _e( 'Clubs', 'racketmanager' ) ?></a> &raquo; <?php _e( 'Teams', 'racketmanager' ) ?>
		</div>
	</div>
	<h1><?php _e( 'Teams', 'racketmanager' ) ?> - <?php echo $club->name ?></h1>

	<!-- View Teams -->
	<div class="mb-3">
		<form id="teams-filter" method="post" action="" class="form-control">
			<?php wp_nonce_field( 'teams-bulk' ) ?>

			<div class="tablenav">
				<!-- Bulk Actions -->
				<select name="action" size="1">
					<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
					<option value="delete"><?php _e('Delete')?></option>
				</select>
				<input type="submit" value="<?php _e('Apply'); ?>" name="doteamdel" id="doteamdel" class="btn btn-secondary action" />
			</div>

			<div class="container">
				<div class="row table-header">
					<div class="col-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('teams-filter'));" /></div>
					<div class="col-1 column-num">ID</div>
					<div class="col-3"><?php _e( 'Title', 'racketmanager' ) ?></div>
					<div class="col-3"><?php _e( 'Stadium', 'racketmanager' ) ?></div>
				</div>
				<?php $affiliatedClub = $club_id;
				$club = get_club($club_id);
				$teams = $club->getTeams();
				$class = '';
				foreach ( $teams AS $team ) {
					$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
					<div class="row table-row <?php echo $class ?>">
						<div class="col-1 check-column">
							<input type="checkbox" value="<?php echo $team->id ?>" name="team[<?php echo $team->id ?>]" />
						</div>
						<div class="col-1 column-num"><?php echo $team->id ?></div>
						<div class="col-3 teamname"><a href="admin.php?page=racketmanager&amp;subpage=team&amp;edit=<?php echo $team->id; ?><?php if ( $team->affiliatedclub!= '' ) ?>&amp;club_id=<?php echo $team->affiliatedclub ?> "><?php echo $team->title ?></a></div>
						<div class="col-3"><?php echo $team->stadium ?></div>
					</div>
				<?php } ?>
			</div>
		</form>
	</div>
	<!-- Add New Team -->
	<div class="mb-3">
		<h3><?php _e( 'Add Team', 'racketmanager' ) ?></h3>
		<form action="" method="post" class="form-control">
			<?php wp_nonce_field( 'racketmanager_add-team' ) ?>
			<div class="form-floating mb-3">
				<select class="form-select" size='1' required="required" name='team_type' id='team_type'>
					<option><?php _e( 'Select', 'racketmanager') ?></option>
					<option value='WS'><?php _e( 'Ladies Singles', 'racketmanager') ?></option>
					<option value='WD'><?php _e( 'Ladies Doubles', 'racketmanager') ?></option>
					<option value='MD'><?php _e( 'Mens Doubles', 'racketmanager') ?></option>
					<option value='MS'><?php _e( 'Mens Singles', 'racketmanager') ?></option>
					<option value='XD'><?php _e( 'Mixed Doubles', 'racketmanager') ?></option>
				</select>
				<label for="team_type"><?php _e( 'Type', 'racketmanager' ) ?></label>
			</div>
			<input type="hidden" name="affiliatedClub" value=<?php echo $club->id ?> />
			<input type="hidden" name="addTeam" value="team" />
			<input type="submit" name="addTeam" value="<?php _e( 'Add Team','racketmanager' ) ?>" class="btn btn-primary" />

		</form>
	</div>
</div>
