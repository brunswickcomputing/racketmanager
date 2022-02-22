<?php
if ( $view == 'constitution' ) {
  $title = __('Add Teams to Constitution', 'racketmanager');
  $link = $league->competitionName;
  $breadcrumb = 'show-competition&amp;competition_id='.$league->competition_id;
} else {
  $title = __('Add Teams to League', 'racketmanager');
  $link = $league->title;
  $breadcrumb = 'show-league&amp;league_id='.$league->id;
}
$mainTitle = $link.' - '.$title;
?>
<div class="wrap league-block">
  <p class="racketmanager_breadcrumb"><a href="admin.php?page=racketmanager"><?php _e( 'RacketManager', 'racketmanager' ) ?></a> &raquo; <a href="admin.php?page=racketmanager&amp;subpage=<?php echo $breadcrumb ?>"><?php echo $link ?></a> &raquo; <?php echo $title ?></p>
  <h1><?php echo $mainTitle; ?></h1>
  <form action="admin.php?page=racketmanager&amp;subpage=<?php echo $breadcrumb ?>&amp;season=<?php echo $season ?>" method="post" enctype="multipart/form-data" name="teams_add">
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
      </thead>
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
</div>
