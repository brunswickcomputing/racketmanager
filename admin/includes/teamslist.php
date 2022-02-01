<?php
?>
<div class="wrap league-block">
    <p class="racketmanager_breadcrumb"><a href="admin.php?page=racketmanager"><?php _e( 'RacketManager', 'racketmanager' ) ?></a> &raquo; <a href="admin.php?page=racketmanager&amp;subpage=show-league&amp;league_id=<?php echo $league->id ?>"><?php echo $league->title ?></a> &raquo; <?php echo 'Add Teams to League' ?></p>
<h1><?php printf( "%s - %s",  $league->title, 'Add Teams to League' ); ?></h1>
<form action="admin.php?page=racketmanager&amp;subpage=show-league&amp;league_id=<?php echo $league_id ?>&amp;season=<?php echo $season ?>" method="post" enctype="multipart/form-data" name="teams_add">
<?php wp_nonce_field( 'racketmanager_add-teams-bulk' ) ?>
    <input type="hidden" name="competition_id" value="<?php echo $league->competition_id ?>" />
    <input type="hidden" name="league_id" value="<?php echo $league_id ?>" />
    <input type="hidden" name="season" value="<?php echo $season ?>" />

    <legend>Select Teams to Add</legend>

    <div class="tablenav">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="addTeamsToLeague"><?php _e('Add')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="doAddTeamToLeague" id="doAddTeamToLeague" class="button action" />
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

	<?php
        if ( $clubs = $racketmanager->getClubs() ) {
            foreach ( $clubs AS $club ) {
                $club = get_club($club);
                if ( $teams = $club->getTeams($entryType, $leagueType ) ) {
                    $class = '';
                    foreach ( $teams AS $team ) { ?>
                        <?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
                        <tr class="<?php echo $class ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" value="<?php echo $team->id ?>" name="team[<?php echo $team->id ?>]" />
                            </th>
                            <td class="column-num"><?php echo $team->id ?></td>
                            <td><?php echo $team->title ?></td>
                            <td><?php echo $team->affiliatedclubname ?></td>
                            <td><?php echo $team->stadium ?></td>
                        </tr>
                    <?php
                    }
                }
            }
        } ?>
		</tbody>
	</table>
</form>

