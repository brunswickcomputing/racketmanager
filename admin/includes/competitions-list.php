<?php
?>
<div class="container league-block">
  <p class="racketmanager_breadcrumb"><a href="admin.php?page=racketmanager"><?php _e( 'RacketManager', 'racketmanager' ) ?></a> &raquo; <?php echo $season->name ?> &raquo; <?php echo 'Add Competitions to Season' ?></p>
  <h1><?php printf( "%s - %s",  $season->name, 'Add Competitions to Season' ); ?></h1>
  <script type='text/javascript'>
  jQuery(function() {
    jQuery("#tabs-competitions").tabs({
    });
  });
  </script>
  <div class="container">
    <legend>Select Competitions to Add</legend>
    <form action="admin.php?page=racketmanager-admin" method="post" enctype="multipart/form-data" name="competitions_add">
      <?php wp_nonce_field( 'racketmanager_add-seasons-competitions-bulk' ) ?>
      <input type="hidden" name="season_id" value="<?php echo $season->id ?>" />
      <input type="hidden" name="season" value="<?php echo $season->name ?>" />
      <div id="matchDays">
        <label for="num_match_days"><?php _e( 'Number of Match Days', 'racketmanager' ) ?></label>
        <input type="number" min="1" step="1" required="required" class="small-text" name="num_match_days" id="num_match_days" size="2" />
      </div>
      <div class="container">
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
              <div class="col-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('competitions-filter'));" /></div>
              <div class="col-2 column-num">ID</div>
              <div class="col-4"><?php _e( 'Name', 'racketmanager' ) ?></div>
            </div>
          </div>

          <?php $competitionTypes = $this->getCompetitionTypes();
          $i = 0;
          foreach ( $competitionTypes AS $competitionType ) { $i ++; ?>
            <div id="competitions<?php echo $competitionType ?>" class="tab-pane table-pane <?php if ( $i == 1 )  { echo 'active show';} ?> fade" role="tabpanel" aria-labelledby="competitions<?php echo $competitionType ?>-tab">
              <div class="container">
                <?php $competitionQuery = array( 'type' => $competitionType );
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
        </div>
      </div>
    </form>
  </div>
</div>
