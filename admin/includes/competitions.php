
<div class="tab-pane <?php if ( $standalone ) echo 'active show ' ?>fade" id="competitions<?php echo $competitionType ?>" role="tabpanel" aria-labelledby="competitions<?php echo $competitionType ?>-tab">
  <form id="competitions-filter" method="post" action="">
    <?php wp_nonce_field( 'competitions-bulk' ) ?>

    <?php if ( !$type ) { ?>
      <div class="tablenav">
        <!-- Bulk Actions -->
        <select name="action" size="1">
          <option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
          <option value="delete"><?php _e('Delete')?></option>
        </select>
        <input type="submit" value="<?php _e('Apply'); ?>" name="docompdel" id="docompdel" class="btn btn-secondary action" />
      </div>
    <?php } ?>

    <div class="container">
      <div class="row table-header">

        <?php if ( !$type ) { ?>
          <div class="col-12 col-md-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('competitions-filter'));" /></div>
        <?php } ?>
        <div class="col-12 col-md-1 column-num">ID</div>
        <div class="col-12 col-md-3"><?php _e( 'Competition', 'racketmanager' ) ?></div>
        <?php if ( !$type ) { ?>
          <div class="col-12 col-md-1 column-num"><?php _e( 'Number of Seasons', 'racketmanager' ) ?></div>
        <?php } ?>
        <div class="col-12 col-md-1 column-num"><?php _e( 'Leagues', 'racketmanager' ) ?></div>
        <div class="col-12 col-md-1 column-num"><?php _e( 'Number of Sets', 'racketmanager' ) ?></div>
        <div class="col-12 col-md-1 column-num"><?php _e( 'Number of Rubbers', 'racketmanager' ) ?></div>
        <div class="col-12 col-md-1 centered"><?php _e( 'Type', 'racketmanager' ) ?></div>
      </div>
      <?php $competitions = $racketmanager->getCompetitions( $competitionQuery );
      $class = '';
      foreach ( $competitions AS $competition ) {
        $competition = get_competition($competition);
        $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
        <div class="row table-row <?php echo $class ?>">
          <?php if ( !$type ) { ?>
            <div class="col-12 col-md-1 check-column"><input type="checkbox" value="<?php echo $competition->id ?>" name="competition[<?php echo $competition->id ?>]" /></div>
          <?php } ?>
          <div class="col-12 col-md-1 column-num"><?php echo $competition->id ?></div>
          <div class="col-12 col-md-3 column-num"><a href="admin.php?page=racketmanager&amp;subpage=show-competition&amp;competition_id=<?php echo $competition->id ?>"><?php echo $competition->name ?></a></div>
          <?php if ( !$type ) { ?>
            <div class="col-12 col-md-1 column-num"><?php echo $competition->num_seasons ?></div>
          <?php } ?>
          <div class="col-12 col-md-1 column-num"><?php echo $competition->num_leagues ?></div>
          <div class="col-12 col-md-1 column-num"><?php echo $competition->num_sets ?></div>
          <div class="col-12 col-md-1 column-num"><?php echo $competition->num_rubbers ?></div>
          <div class="col-12 col-md-2 centered">
            <?php switch ($competition->type) {
              case 'WS': _e( 'Ladies Singles', 'racketmanager' ); break;
              case 'WD': _e( 'Ladies Doubles', 'racketmanager' ); break;
              case 'MS': _e( 'Mens Singles', 'racketmanager' ); break;
              case 'MD': _e( 'Mens Doubles', 'racketmanager' ); break;
              case 'XD': _e( 'Mixed Doubles', 'racketmanager' ); break;
              case 'LD': _e( 'The League', 'racketmanager' ); break;
            } ?>
          </div>
        </div>
      <?php } ?>
    </div>
  </form>
</div>
