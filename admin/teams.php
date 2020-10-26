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

    <div class="tablenav">
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
    $club = get_club($club_id);
    if ( $teams = $club->getTeams() ) {
        $class = '';
        foreach ( $teams AS $team ) {
            $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
            <tr class="<?php echo $class ?>">
                <th scope="row" class="check-column">
                    <input type="checkbox" value="<?php echo $team->id ?>" name="team[<?php echo $team->id ?>]" />
                </th>
                <td class="num"><?php echo $team->id ?></td>
                <td><a href="admin.php?page=leaguemanager&amp;subpage=team&amp;edit=<?php echo $team->id; ?><?php if ( $team->affiliatedclub!= '' ) ?>&amp;club_id=<?php echo $team->affiliatedclub ?> "><?php echo $team->title ?></a></td>
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
                        <td class="num"><?php echo $team->id ?></td>
                        <td><a href="admin.php?page=leaguemanager&amp;subpage=team&amp;edit=<?php echo $team->id; ?><?php if ( $team->affiliatedclub!= '' ) ?>&amp;club_id=<?php echo $team->affiliatedclub ?> "><?php echo $team->title ?></a></td>
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
<h2><?php _e( 'Add Team', 'leaguemanager' ) ?></h2>
<!-- Add New Team -->
<form action="" method="post">
	<?php wp_nonce_field( 'leaguemanager_add-team' ) ?>
    <div class="form-group">
        <label for="affiliatedClub"><?php _e( 'Affiliated Club', 'leaguemanager' ) ?></label>
        <div class="input">
            <select size="1" name="affiliatedClub" id="affiliatedClub" >
                <option><?php _e( 'Select club' , 'leaguemanager') ?></option>
                <?php foreach ( $clubs AS $club ) { ?>
                <option value="<?php echo $club->id ?>"<?php if(isset($affiliatedClub)) selected($club->id, $affiliatedClub ) ?>><?php echo $club->name ?></option>
                <?php } ?>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label for="team_type"><?php _e( 'Type', 'leaguemanager' ) ?></label>
        <div class="input">
            <select size='1' required="required" name='team_type' id='team_type'>
                <option><?php _e( 'Select', 'leaguemanager') ?></option>
                <option value='WS'><?php _e( 'Ladies Singles', 'leaguemanager') ?></option>
                <option value='WD'><?php _e( 'Ladies Doubles', 'leaguemanager') ?></option>
                <option value='MD'><?php _e( 'Mens Doubles', 'leaguemanager') ?></option>
                <option value='MS'><?php _e( 'Mens Singles', 'leaguemanager') ?></option>
                <option value='XD'><?php _e( 'Mixed Doubles', 'leaguemanager') ?></option>

            </select>
        </div>
    </div>
	<input type="hidden" name="addTeam" value="team" />
	<p class="submit"><input type="submit" name="addTeam" value="<?php _e( 'Add Team','leaguemanager' ) ?>" class="button button-primary" /></p>

</form>
