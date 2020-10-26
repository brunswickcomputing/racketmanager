<!-- Add Team -->
<!-- View Teams -->
<form action="admin.php?page=leaguemanager" method="get">
	<input type="hidden" name="page" value="leaguemanager" />
	<input type="hidden" name="view" value="teams" />
	<div class="lm-form-table">
<?php if ( $clubs = $leaguemanager->getClubs( ) ) { ?>
		<select size="1" name="club_id" id="club_id">
			<option><?php _e( 'Select affiliated club', 'leaguemanager' ) ?></option>
<?php foreach ( $clubs AS $club ) { ?>
			<option value="<?php echo $club->id ?>" <?php echo ($club->id == $club_id ?  'selected' :  '') ?>><?php echo $club->name ?></option>
	<?php } ?>
		</select>
<?php } ?>
		<input type="submit" value="<?php _e( 'View Teams','leaguemanager' ) ?>" class="button button-primary" />
	</div>

</form>

<form id="teams-filter" method="post" action="">
	<?php wp_nonce_field( 'teams-bulk' ) ?>

	<div class="tablenav" style="margin-bottom: 0.1em;">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
			<option value="delete"><?php _e('Delete')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="doteamdel" id="doteamdel" class="button-secondary action" />
	</div>

	<table class="widefat" summary="" title="LeagueManager Teams">
		<thead>
		<tr>
			<th scope="col" class="check-column"><input type="checkbox" onclick="Leaguemanager.checkAll(document.getElementById('teams-filter'));" /></th>
			<th scope="col" class="num">ID</th>
			<th scope="col"><?php _e( 'Title', 'leaguemanager' ) ?></th>
			<th scope="col"><?php _e( 'Affiliated Club', 'leaguemanager' ) ?></th>
			<th scope="col"><?php _e( 'Stadium', 'leaguemanager' ) ?></th>
		</tr>
		<tbody id="the-list">
<?php if ( isset($club_id) && $club_id > 0) {
    $affiliatedClub = $club_id;
} else {
    $affiliatedClub = '';
} ?>
	<?php if ( $teams = $leaguemanager->getTeamsList($affiliatedClub) ) { $class = ''; ?>
		<?php foreach ( $teams AS $team ) { ?>
			<?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
			<tr class="<?php echo $class ?>">
				<th scope="row" class="check-column">
					<input type="checkbox" value="<?php echo $team->id ?>" name="team[<?php echo $team->id ?>]" />
				</th>
				<td class="num"><?php echo $team->id ?></td>
				<td><a href="admin.php?page=leaguemanager&amp;subpage=team&amp;edit=<?php echo $team->id; ?><?php if ( $affiliatedClub!= '' ) ?>&amp;club_id=<?php echo $affiliatedClub ?> "><?php echo $team->title ?></a></td>
                <td><?php echo $team->affiliatedclubname ?></td>
				<td><?php echo $team->stadium ?></td>
			</tr>
		<?php } ?>
	<?php } ?>
		</tbody>
	</table>
</form>
<h2><?php _e( 'Add Team', 'leaguemanager' ) ?></h2>
<!-- Add New Team -->
<form action="" method="post">
	<?php wp_nonce_field( 'leaguemanager_add-team' ) ?>
	<table class="lm-form-table">
		<tr valign="top">
			<th scope="row"><label for="teamName"><?php _e( 'Name', 'leaguemanager' ) ?></label></th>
			<td><input required="required" placeholder="<?php _e( 'Enter name for new Team', 'leaguemanager') ?>" type="text" name="teamName" id="teamName" value="" size="30" style="margin-bottom: 1em;" /></td>
		</tr>
            <tr valign="top">
            <th scope="row"><label for="affiliatedClub"><?php _e( 'Affiliated Club', 'leaguemanager' ) ?></label></th>
            <td>
                <select size="1" name="affiliatedClub" id="affiliatedClub" >
                    <option><?php _e( 'Select club' , 'leaguemanager') ?></option>
<?php foreach ( $clubs AS $club ) { ?>
                    <option value="<?php echo $club->id ?>"<?php if(isset($affiliatedClub)) selected($club->id, $affiliatedClub ) ?>><?php echo $club->name ?></option>
                    <?php } ?>
                </select>
            </td>
        </tr>
		<tr valign='top'>
			<th scope='row'><label for='stadium'><?php _e('Stadium', 'leaguemanager') ?></label></th>
			<td><input required="required" placeholder="<?php _e( 'Stadium', 'leaguemanager') ?>" type='text' name='stadium' id='stadium' value='' size='50' /></td>
		</tr>

	</table>
	<input type="hidden" name="addTeam" value="team" />
	<p class="submit"><input type="submit" name="addTeam" value="<?php _e( 'Add Team','leaguemanager' ) ?>" class="button button-primary" /></p>

</form>
