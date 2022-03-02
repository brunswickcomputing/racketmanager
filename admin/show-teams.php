<?php
/**
* Teams main page administration panel
*
*/
namespace ns;
?>
<div class="container">
	<p class="racketmanager_breadcrumb"><a href="admin.php?page=racketmanager-clubs"><?php _e( 'Clubs', 'racketmanager' ) ?></a> &raquo; <?php _e( 'Teams', 'racketmanager' ) ?></p>
	<h1><?php _e( 'Teams', 'racketmanager' ) ?> - <?php echo $club->name ?></h1>

<!-- Add Team -->
<form id="teams-filter" method="post" action="">
	<?php wp_nonce_field( 'teams-bulk' ) ?>

	<div class="tablenav">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
			<option value="delete"><?php _e('Delete')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="doteamdel" id="doteamdel" class="btn btn-secondary action" />
	</div>

	<table class="widefat" summary="" title="RacketManager Teams">
		<thead>
			<tr>
				<th scope="col" class="check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('teams-filter'));" /></th>
				<th scope="col" class="column-num">ID</th>
				<th scope="col"><?php _e( 'Title', 'racketmanager' ) ?></th>
				<th scope="col"><?php _e( 'Affiliated Club', 'racketmanager' ) ?></th>
				<th scope="col"><?php _e( 'Stadium', 'racketmanager' ) ?></th>
			</tr>
			<tbody id="the-list">
				<?php if ( isset($club_id) && $club_id > 0) {
					$affiliatedClub = $club_id;
					$club = get_club($club_id);
					if ( $teams = $club->getTeams() ) {
						$class = '';
						foreach ( $teams AS $team ) {
							$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
							<tr class="<?php echo $class ?>">
								<th scope="row" class="check-column">
									<input type="checkbox" value="<?php echo $team->id ?>" name="team[<?php echo $team->id ?>]" />
								</th>
								<td class="column-num"><?php echo $team->id ?></td>
								<td class="teamname"><a href="admin.php?page=racketmanager&amp;subpage=team&amp;edit=<?php echo $team->id; ?><?php if ( $team->affiliatedclub!= '' ) ?>&amp;club_id=<?php echo $team->affiliatedclub ?> "><?php echo $team->title ?></a></td>
								<td><?php echo $team->affiliatedclubname ?></td>
								<td><?php echo $team->stadium ?></td>
							</tr>
						<?php }
					}
				} else {
					$affiliatedClub = '';
					foreach ( $clubs AS $club ) {
						$club = get_club($club);
						if ( $teams = $club->getTeams() ) {
							$class = '';
							foreach ( $teams AS $team ) {
								$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
								<tr class="<?php echo $class ?>">
									<th scope="row" class="check-column">
										<input type="checkbox" value="<?php echo $team->id ?>" name="team[<?php echo $team->id ?>]" />
									</th>
									<td class="column-num"><?php echo $team->id ?></td>
									<td class="teamname"><a href="admin.php?page=racketmanager&amp;subpage=team&amp;edit=<?php echo $team->id; ?><?php if ( $team->affiliatedclub!= '' ) ?>&amp;club_id=<?php echo $team->affiliatedclub ?> "><?php echo $team->title ?></a></td>
									<td><?php echo $team->affiliatedclubname ?></td>
									<td><?php echo $team->stadium ?></td>
								</tr>
							<?php }
						}
					}
				} ?>
			</tbody>
		</table>
	</form>
	<h3><?php _e( 'Add Team', 'racketmanager' ) ?></h3>
	<!-- Add New Team -->
	<form action="" method="post">
		<?php wp_nonce_field( 'racketmanager_add-team' ) ?>
		<div class="form-group">
			<label for="team_type"><?php _e( 'Type', 'racketmanager' ) ?></label>
			<div class="input">
				<select size='1' required="required" name='team_type' id='team_type'>
					<option><?php _e( 'Select', 'racketmanager') ?></option>
					<option value='WS'><?php _e( 'Ladies Singles', 'racketmanager') ?></option>
					<option value='WD'><?php _e( 'Ladies Doubles', 'racketmanager') ?></option>
					<option value='MD'><?php _e( 'Mens Doubles', 'racketmanager') ?></option>
					<option value='MS'><?php _e( 'Mens Singles', 'racketmanager') ?></option>
					<option value='XD'><?php _e( 'Mixed Doubles', 'racketmanager') ?></option>

				</select>
			</div>
		</div>
		<input type="hidden" name="affiliatedClub" value=<?php echo $club->id ?> />
		<input type="hidden" name="addTeam" value="team" />
		<input type="submit" name="addTeam" value="<?php _e( 'Add Team','racketmanager' ) ?>" class="btn btn-primary" />

	</form>
</div>
