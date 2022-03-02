<?php
/**
* Clubs main page administration panel
*
*/
namespace ns;
?>
<div class="container">
	<h1><?php _e( 'Clubs', 'racketmanager' ) ?></h1>

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

		<table class="widefat" summary="" title="RacketManager Clubs">
			<thead>
				<tr>
					<th scope="col" class="check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('clubs-filter'));" /></th>
					<th scope="col" class="column-num">ID</th>
					<th scope="col"><?php _e( 'Name', 'racketmanager' ) ?></th>
					<th scope="col"><?php _e( 'Match Secretary', 'racketmanager' ) ?></th>
					<th scope="col" class="column-num"></th>
					<th scope="col" class="column-num"></th>
				</tr>
				<tbody id="the-list">
					<?php $clubs = $racketmanager->getClubs( );
					$class = '';
					foreach ( $clubs AS $club ) {
						$club = get_club($club);
						$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
						<tr class="<?php echo $class ?>">
							<th scope="row" class="check-column">
								<input type="checkbox" value="<?php echo $club->id ?>" name="club[<?php echo $club->id ?>]" />
							</th>
							<td class="column-num"><?php echo $club->id ?></td>
							<td class="clubname"><a href="admin.php?page=racketmanager&amp;subpage=club&amp;club_id=<?php echo $club->id ?> "><?php echo $club->name ?></a></td>
							<td><?php echo $club->matchSecretaryName ?></td>
							<td><a href="admin.php?page=racketmanager&amp;view=roster&amp;club_id=<?php echo $club->id ?> " class="btn btn-secondary"><?php _e( 'Roster', 'racketmanager' ) ?></a></td>
							<td><a href="admin.php?page=racketmanager-clubs&amp;view=teams&amp;club_id=<?php echo $club->id ?> " class="btn btn-secondary"><?php _e( 'Teams', 'racketmanager' ) ?></a></td>
						</tr>
					<?php } ?>
			</tbody>
		</table>
	</form>
	<!-- Add New Club -->
	<a href="admin.php?page=racketmanager&amp;subpage=club" name="addTeam" class="btn btn-primary submit"><?php _e( 'Add Club','racketmanager' ) ?></a>
</div>
