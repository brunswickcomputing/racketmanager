<?php
?>
<div class="wrap league-block">
    <p class="leaguemanager_breadcrumb"><a href="admin.php?page=leaguemanager"><?php _e( 'LeagueManager', 'leaguemanager' ) ?></a> &raquo; <a href="admin.php?page=leaguemanager&amp;subpage=show-league&amp;league_id=<?php echo $league->id ?>"><?php echo $league->title ?></a> &raquo; <?php echo 'Add Teams to League' ?></p>
<h1><?php printf( "%s &mdash; %s",  $league->title, 'Add Teams to League' ); ?></h1>
<form action="admin.php?page=leaguemanager&amp;subpage=show-league&amp;league_id=<?php echo $league_id ?>&amp;season=<?php echo $season ?>" method="post" enctype="multipart/form-data" name="teams_add">
<?php wp_nonce_field( 'leaguemanager_add-teams-bulk' ) ?>
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

	<?php
        if ( $clubs = $leaguemanager->getClubs() ) {
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
                            <td class="num"><?php echo $team->id ?></td>
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

