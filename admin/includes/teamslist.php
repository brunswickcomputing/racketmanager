<?php
if ( $view == 'constitution' ) {
  $title = __('Add Teams to Constitution', 'racketmanager');
  $link = $league->competition->name;
  $breadcrumb = 'show-competition&amp;competition_id='.$league->competition_id;
} else {
  $title = __('Add Teams to League', 'racketmanager');
  $link = $league->title;
  $breadcrumb = 'show-league&amp;league_id='.$league->id;
}
$mainTitle = $link.' - '.$title;
?>
<div class="container">
  <div class="row justify-content-end">
    <div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager"><?php _e( 'RacketManager', 'racketmanager' ) ?></a> &raquo; <a href="admin.php?page=racketmanager&amp;subpage=<?php echo $breadcrumb ?>"><?php echo $link ?></a> &raquo; <?php echo $title ?>
    </div>
  </div>
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

    <div class="container">
      <div class="row table-header">
        <div class="col-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('teams-filter'));" /></div>
        <div class="col-1 column-num">ID</div>
        <div class="col-3"><?php _e( 'Title', 'racketmanager' ) ?></div>
        <div class="col-3"><?php _e( 'Affiliated Club', 'racketmanager' ) ?></div>
        <div class="col-3"><?php _e( 'Stadium', 'racketmanager' ) ?></div>
      </div>
      <?php
      $class = '';
      if ( $clubs = $racketmanager->getClubs() ) {
        foreach ( $clubs AS $club ) {
          $club = get_club($club);
          if ( $teams = $club->getTeams($entryType, $leagueType ) ) {
            foreach ( $teams AS $team ) { ?>
              <?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
              <div class="row table-row <?php echo $class ?>">
                <div class="col-1 check-column">
                  <input type="checkbox" value="<?php echo $team->id ?>" name="team[<?php echo $team->id ?>]" />
                </div>
                <div class="col-1 column-num"><?php echo $team->id ?></div>
                <div class="col-3"><?php echo $team->title ?></div>
                <div class="col-3"><?php echo $team->affiliatedclubname ?></div>
                <div class="col-3"><?php echo $team->stadium ?></div>
              </div>
              <?php
            }
          }
        }
      } ?>
    </form>
  </div>
</div>
