<?php
$type = 'all';
if ( isset( $_GET['type'] ) ) {
  $type = $_GET['type'];
}
if ( isset($_GET['tournament']) ) {
  $action = "admin.php?page=racketmanager&subpage=show-competitions&tournament=".$_GET['tournament'];
} else {
  $action = "admin.php?page=racketmanager-admin";
}
?>
<div class="container league-block">
  <?php if ( !isset( $_GET['tournament'] ) ) { ?>
    <div class="row justify-content-end">
  		<div class="col-auto racketmanager_breadcrumb">
  			<a href="admin.php?page=racketmanager"><?php _e( 'RacketManager', 'racketmanager' ) ?></a> &raquo; <?php echo $season->name ?> &raquo; <?php echo 'Add Competitions to Season' ?>
  		</div>
  	</div>
  <?php } ?>
  <h1><?php if ( isset( $_GET['tournament'] ) ) { _e('Add Competitions to Tournament', 'racketmanager'); } else { printf( "%s - %s",  $season->name, 'Add Competitions to Season' ); } ?></h1>
  <div class="container">
    <legend>Select Competitions to Add</legend>
    <form action="<?php echo $action ?>" method="post" enctype="multipart/form-data" name="competitions_add" id="competitions_add">
      <?php wp_nonce_field( 'racketmanager_add-seasons-competitions-bulk' ) ?>
      <input type="hidden" name="season_id" value="<?php echo $season->id ?>" />
      <input type="hidden" name="season" value="<?php echo $season->name ?>" />
      <?php if ( isset($_GET['tournament']) ) { ?>
        <input type="hidden" name="tournament" value="<?php echo $_GET['tournament'] ?>" />
      <?php } ?>
      <?php if ( $type == 'all' ) { ?>
        <div id="matchDays">
          <label for="num_match_days"><?php _e( 'Number of Match Days', 'racketmanager' ) ?></label>
          <input type="number" min="1" step="1" required="required" class="small-text" name="num_match_days" id="num_match_days" size="2" />
        </div>
      <?php } ?>
      <div class="container">
        <?php if ( $type == 'all' ) { ?>
          <!-- Nav tabs -->
          <ul class="nav nav-pills" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="competitionscup-tab" data-bs-toggle="pill" data-bs-target="#competitionscup" type="button" role="tab" aria-controls="competitionscup" aria-selected="true"><?php _e( 'Cups', 'racketmanager' ) ?></button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="competitionsleague-tab" data-bs-toggle="pill" data-bs-target="#competitionsleague" type="button" role="tab" aria-controls="competitionsleague" aria-selected="false"><?php _e( 'Leagues', 'racketmanager' ) ?></button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="competitionstournament-tab" data-bs-toggle="pill" data-bs-target="#competitionstournament" type="button" role="tab" aria-controls="competitionstournament" aria-selected="false"><?php _e( 'Tournaments', 'racketmanager' ) ?></button>
            </li>
          </ul>
        <?php } ?>
        <!-- Tab panes -->
        <div class="tab-content">
          <div class="tablenav">
            <!-- Bulk Actions -->
            <select name="action" size="1">
              <option value="addCompetitionsToSeason"><?php _e('Add')?></option>
            </select>
            <input type="submit" value="<?php _e('Apply'); ?>" name="doaddCompetitionsToSeason" id="doaddCompetitionsToSeason" class="btn btn-primary action" />
          </div>
          <div class="container">
            <div class="row table-header">
              <div class="col-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('competitions_add'));" /></div>
              <div class="col-2 column-num">ID</div>
              <div class="col-4"><?php _e( 'Name', 'racketmanager' ) ?></div>
            </div>
          </div>

          <?php $competitionTypes = $this->getCompetitionTypes();
          $i = 0;
          foreach ( $competitionTypes AS $competitionType ) {
            if ( $type == 'all' || $type == $competitionType ) {
              $i ++; ?>
              <div id="competitions<?php echo $competitionType ?>" class="tab-pane table-pane <?php if ( $i == 1 )  { echo 'active show';} ?> fade" role="tabpanel" aria-labelledby="competitions<?php echo $competitionType ?>-tab">
                <div class="container">
                  <?php $competitionQuery = array( 'type' => $competitionType );
                  if ( isset($_GET['tournamenttype']) ) {
                    $competitionQuery['name'] = $_GET['tournamenttype'];
                  }
                  $competitions = $racketmanager->getCompetitions( $competitionQuery );
                  $class = ''; ?>
                  <?php foreach ( $competitions AS $competition ) {
                    $competition = get_competition($competition);
                    $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
                    <div class="row table-row <?php echo $class ?>">
                      <div class="col-1 check-column">
                        <?php if ( !is_numeric(array_search($season->name,array_column($competition->seasons, 'name') ,true)) ) { ?>
                          <input type="checkbox" value="<?php echo $competition->id ?>" name="competition[<?php echo $competition->id ?>]" />
                        <?php } ?>
                      </div>
                      <div class="col-2 column-num"><?php echo $competition->id ?></div>
                      <div class="col-4"><?php echo $competition->name ?></div>
                    </div>
                  <?php } ?>
                </div>
              </div>
            <?php } ?>
          <?php } ?>
        </div>
      </div>
    </form>
  </div>
</div>
